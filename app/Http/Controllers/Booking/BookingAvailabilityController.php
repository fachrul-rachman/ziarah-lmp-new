<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TimeSlot;
use App\Services\BookingAvailabilityService;
use App\Services\BookingLeadTimeService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingAvailabilityController extends Controller
{
    public function __construct(
        private readonly BookingAvailabilityService $availability,
        private readonly BookingLeadTimeService $leadTime,
    ) {}

    public function lots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'min:1'],
            'zone_id' => ['required', 'integer', 'min:1'],
            'grave_type' => ['required', 'string', 'in:makam,kotak_abu'],
            'visit_date' => ['required', 'date'],
            'time_slot_id' => ['required', 'integer', 'min:1'],
            'exclude_booking_id' => ['nullable', 'integer', 'min:1'],
            'booking_token' => ['nullable', 'string', 'max:64'],
        ]);

        $today = now()->timezone('Asia/Jakarta')->startOfDay();
        $maxDate = $today->addDays((int) config('booking.lots_max_days_ahead', 100));
        $excludedId = null;
        if (! empty($validated['booking_token'])) {
            $booking = Booking::where('public_token', $validated['booking_token'])->firstOrFail();
            $excludedId = $booking->id;
            if ($booking->visitation_plan_id) {
                $maxDate = $booking->visitationPlan->maximumDate($booking->planned_moment);
            }
        }
        $visitDate = CarbonImmutable::parse($validated['visit_date'], 'Asia/Jakarta')->startOfDay();
        $startTime = TimeSlot::query()->whereKey((int) $validated['time_slot_id'])->value('start_time');

        if (! $startTime || ! $this->leadTime->allows($visitDate->toDateString(), (string) $startTime)) {
            return response()->json([
                'message' => $this->leadTime->message(),
            ], 422);
        }

        if ($visitDate->greaterThan($maxDate)) {
            return response()->json([
                'message' => 'Tanggal kunjungan maksimal '.$maxDate->format('d-m-Y').'.',
            ], 422);
        }

        $lots = $this->availability->availableLots(
            (int) $validated['location_id'],
            (int) $validated['zone_id'],
            $validated['grave_type'],
            $visitDate->toDateString(),
            (int) $validated['time_slot_id'],
            $excludedId,
        );

        return response()->json(['lots' => $lots]);
    }
}
