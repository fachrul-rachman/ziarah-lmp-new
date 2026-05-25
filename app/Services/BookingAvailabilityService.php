<?php

namespace App\Services;

use App\Models\Lot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        $bookedLotIds = DB::table('bookings')
            ->where('visit_date', $visitDate)
            ->where('time_slot_id', $timeSlotId)
            ->whereIn('status', ['confirmed', 'rescheduled'])
            ->when($excludeBookingId !== null, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->pluck('lot_id')
            ->all();

        return Lot::query()
            ->where('location_id', $locationId)
            ->where('zone_id', $zoneId)
            ->where('grave_type', $graveType)
            ->whereNull('deleted_at')
            ->when(count($bookedLotIds) > 0, fn ($q) => $q->whereNotIn('id', $bookedLotIds))
            ->orderBy('normalized_lot_number')
            ->get(['id', 'lot_number', 'size', 'normalized_size'])
            ->map(fn (Lot $lot) => [
                'id' => $lot->id,
                'lot_number' => $lot->lot_number,
                'size' => $lot->size,
                'normalized_size' => (string) $lot->normalized_size,
            ]);
    }
}
