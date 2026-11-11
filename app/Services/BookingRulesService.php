<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Lot;
use App\Models\TimeSlot;
use App\Models\VisitationPlan;
use Carbon\CarbonImmutable;

class BookingRulesService
{
    public function __construct(
        private readonly BookingLeadTimeService $leadTime,
        private readonly LotSizeRuleService $sizeRules,
        private readonly BookingAvailabilityService $availability,
        private readonly EventRuleService $events,
    ) {}

    /** Validate every write, independent of cached availability shown in the browser. */
    public function validate(array $data, ?Booking $booking = null): array
    {
        $lot = Lot::query()->whereKey($data['lot_id'])->lockForUpdate()->first();
        if (! $lot || $lot->location_id != $data['location_id'] || $lot->zone_id != $data['zone_id'] || $lot->grave_type !== $data['grave_type']) {
            throw new \RuntimeException('Lot tidak valid untuk pilihan ini.');
        }

        $slot = TimeSlot::find($data['time_slot_id']);
        if (! $slot || ! $this->leadTime->allows($data['visit_date'], $slot->start_time)) {
            throw new \RuntimeException($this->leadTime->message());
        }

        $date = CarbonImmutable::parse($data['visit_date'], 'Asia/Jakarta')->startOfDay();
        $planId = $booking?->visitation_plan_id ?? ($data['visitation_plan_id'] ?? null);
        if ($planId) {
            $plan = VisitationPlan::findOrFail($planId);
            $visitAt = CarbonImmutable::parse($data['visit_date'].' '.$slot->start_time, 'Asia/Jakarta');
            if ($lot->id !== $plan->lot_id || $date->greaterThan($plan->maximumDate()) || $visitAt->lessThan($plan->burial_at)) {
                throw new \RuntimeException('Jadwal harus setelah pemakaman, maksimal satu tahun sejak meninggal, dan memakai lot pemakaman.');
            }
        } elseif ($date->greaterThan(CarbonImmutable::now('Asia/Jakarta')->startOfDay()->addDays((int) config('booking.lots_max_days_ahead', 100)))) {
            throw new \RuntimeException('Tanggal kunjungan melebihi batas pemesanan.');
        }

        if ($this->availability->conflicts($lot->id, $data['visit_date'], $slot->start_time, $booking?->id)) {
            throw new \RuntimeException('Lot sudah tidak tersedia untuk tanggal dan jam tersebut.');
        }

        $facilities = $data['facilities'];
        $rule = $this->sizeRules->ruleForSize($lot->normalized_size ?: $lot->size);
        $hidden = $this->events->hiddenFacilitiesForDateAndLocation($data['visit_date'], $lot->location_id);
        foreach (['chairs' => 'chairs_count', 'burn_barrels' => 'burn_barrels_count'] as $key => $field) {
            if ($hidden->contains($key)) {
                $facilities[$field] = 0;
            } elseif ($facilities[$field] < $rule[$key.'_min'] || $facilities[$field] > $rule[$key.'_max']) {
                throw new \RuntimeException("Jumlah fasilitas {$key} tidak sesuai aturan ukuran lot.");
            }
        }
        foreach (['tent', 'prayer_table', 'lamp'] as $key) {
            if (! $rule[$key.'_allowed'] || $hidden->contains($key)) {
                $facilities['has_'.$key] = false;
            }
        }

        $hour = (int) substr($slot->start_time, 0, 2);
        if ($hour >= 5 && $hour < 18) {
            $facilities['has_lamp'] = false;
        }

        return $facilities;
    }
}
