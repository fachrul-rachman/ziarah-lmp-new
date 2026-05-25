<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Services\BookingAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingAvailabilityController extends Controller
{
    public function __construct(private readonly BookingAvailabilityService $availability)
    {
    }

    public function lots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'grave_type' => ['required', 'string', 'in:makam,kotak_abu'],
            'visit_date' => ['required', 'date'],
            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
            'exclude_booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
        ]);

        $lots = $this->availability->availableLots(
            $validated['location_id'],
            $validated['zone_id'],
            $validated['grave_type'],
            $validated['visit_date'],
            $validated['time_slot_id'],
            isset($validated['exclude_booking_id']) ? (int) $validated['exclude_booking_id'] : null,
        );

        return response()->json(['lots' => $lots]);
    }
}
