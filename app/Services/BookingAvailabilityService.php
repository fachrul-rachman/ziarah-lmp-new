<?php

namespace App\Services;

use App\Models\Lot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingAvailabilityService
{
    /**
     * @return Collection<int,array{id:int,lot_number:string,size:string,normalized_size:string}>
     */
    public function availableLots(
        int $locationId,
        int $zoneId,
        string $graveType,
        string $visitDate,
        int $timeSlotId,
        ?int $excludeBookingId = null,
    ): Collection {
        $cacheStore = config('booking.lots_cache_store');
        $cacheKey = implode(':', [
            'booking',
            'lots',
            $locationId,
            $zoneId,
            $graveType,
            $visitDate,
            $timeSlotId,
            $excludeBookingId ?? 'none',
        ]);
        $ttlSeconds = max(1, (int) config('booking.lots_cache_seconds', 30));

        $loadLots = function () use (
            $locationId, $zoneId, $graveType, $visitDate, $timeSlotId, $excludeBookingId,
        ) {
            $startTime = DB::table('time_slots')->where('id', $timeSlotId)->value('start_time');
            if (! $startTime) {
                return [];
            }

            return Lot::query()
                ->where('location_id', $locationId)
                ->where('zone_id', $zoneId)
                ->where('grave_type', $graveType)
                ->whereNotExists(function (Builder $query) use ($visitDate, $startTime, $excludeBookingId) {
                    $this->conflictQuery($visitDate, $startTime, $excludeBookingId, $query)
                        ->whereColumn('bookings.lot_id', 'lots.id');
                })
                ->orderBy('normalized_lot_number')
                ->get(['id', 'lot_number', 'size', 'normalized_size'])
                ->map(fn (Lot $lot) => [
                    'id' => $lot->id,
                    'lot_number' => $lot->lot_number,
                    'size' => $lot->size,
                    'normalized_size' => (string) $lot->normalized_size,
                ])
                ->values()
                ->all();
        };

        try {
            $rows = Cache::store($cacheStore ?: null)->remember($cacheKey, now()->addSeconds($ttlSeconds), $loadLots);
        } catch (\RedisException|InvalidArgumentException) {
            $rows = $loadLots();
        }

        return collect($rows);
    }

    public function conflicts(int $lotId, string $date, string $startTime, ?int $excludeBookingId = null): bool
    {
        return $this->conflictQuery($date, $startTime, $excludeBookingId)
            ->where('bookings.lot_id', $lotId)->exists();
    }

    private function conflictQuery(string $date, string $startTime, ?int $excludeBookingId, ?Builder $query = null): Builder
    {
        $start = CarbonImmutable::parse($startTime);
        $day = CarbonImmutable::parse($date);

        return ($query ?? DB::query())->from('bookings')
            ->join('time_slots', 'time_slots.id', '=', 'bookings.time_slot_id')
            ->whereIn('bookings.status', ['confirmed', 'rescheduled'])
            ->when($excludeBookingId !== null, fn ($q) => $q->where('bookings.id', '!=', $excludeBookingId))
            ->where(function (Builder $dates) use ($date, $day, $start) {
                $dates->where(function (Builder $sameDay) use ($date, $start) {
                    $sameDay->whereDate('bookings.visit_date', $date);
                    if ($start->hour >= 1) {
                        $sameDay->where('time_slots.start_time', '>', $start->subHour()->format('H:i:s'));
                    }
                    if ($start->hour < 23) {
                        $sameDay->where('time_slots.start_time', '<', $start->addHour()->format('H:i:s'));
                    }
                });
                if ($start->hour === 0) {
                    $dates->orWhere(fn (Builder $previous) => $previous
                        ->whereDate('bookings.visit_date', $day->subDay()->toDateString())
                        ->where('time_slots.start_time', '>', $start->subHour()->format('H:i:s')));
                }
                if ($start->hour === 23) {
                    $dates->orWhere(fn (Builder $next) => $next
                        ->whereDate('bookings.visit_date', $day->addDay()->toDateString())
                        ->where('time_slots.start_time', '<', $start->addHour()->format('H:i:s')));
                }
            });
    }
}
