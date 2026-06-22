<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Services\BookingAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingAvailabilityController extends Controller
{
    public function __construct(private readonly BookingAvailabilityService $availability) {}

    public function lots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'min:1'],
            'zone_id' => ['required', 'integer', 'min:1'],
            'grave_type' => ['required', 'string', 'in:makam,kotak_abu'],
            'visit_date' => ['required', 'date'],
            'time_slot_id' => ['required', 'integer', 'min:1'],
            'exclude_booking_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $today = now()->timezone('Asia/Jakarta')->startOfDay();
        $minDate = $today->addDays((int) config('booking.lots_min_days_ahead', 2));
        $maxDate = $today->addDays((int) config('booking.lots_max_days_ahead', 100));
        $visitDate = CarbonImmutable::parse($validated['visit_date'], 'Asia/Jakarta')->startOfDay();

        if ($visitDate->lessThan($minDate)) {
            return response()->json([
                'message' => 'Tanggal kunjungan minimal H+2.',
            ], 422);
        }

        if ($visitDate->greaterThan($maxDate)) {
            return response()->json([
                'message' => 'Tanggal kunjungan maksimal 100 hari ke depan.',
            ], 422);
        }

        $lots = $this->availability->availableLots(
            (int) $validated['location_id'],
            (int) $validated['zone_id'],
            $validated['grave_type'],
            $visitDate->toDateString(),
            (int) $validated['time_slot_id'],
            isset($validated['exclude_booking_id']) ? (int) $validated['exclude_booking_id'] : null,
        );

        return response()->json(['lots' => $lots]);
    }
}
