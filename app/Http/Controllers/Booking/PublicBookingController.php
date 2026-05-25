<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Lot;
use App\Models\Location;
use App\Models\TimeSlot;
use App\Services\CancelBookingService;
use App\Services\RescheduleBookingService;
use App\Support\Normalization;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingController extends Controller
{
    public function __construct(
        private readonly CancelBookingService $cancelService,
        private readonly RescheduleBookingService $rescheduleService,
    ) {
    }

    public function show(string $publicToken): Response
    {
        $booking = $this->loadBookingByToken($publicToken);
        $expired = $this->isExpired($booking);

        return Inertia::render('booking/show', [
            'booking' => $this->toBookingPayload($booking),
            'expired' => $expired,
        ]);
    }

    public function cancelForm(string $publicToken): Response
    {
        $booking = $this->loadBookingByToken($publicToken);
        $expired = $this->isExpired($booking);

        return Inertia::render('booking/cancel', [
            'booking' => $this->toBookingPayload($booking),
            'expired' => $expired,
        ]);
    }

    public function cancel(Request $request, string $publicToken): RedirectResponse
    {
        $booking = $this->loadBookingByToken($publicToken);

        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:500'],
        ], [
            'cancel_reason.required' => 'Alasan cancel wajib diisi.',
        ]);

        try {
            $this->cancelService->cancelByCustomer($booking, (string) $validated['cancel_reason']);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors(['cancel' => $e->getMessage()]);
        }

        return redirect()->to("/booking/{$booking->public_token}/cancel/success");
    }

    public function cancelSuccess(string $publicToken): Response
    {
        $booking = $this->loadBookingByToken($publicToken);

        return Inertia::render('booking/cancel-success', [
            'booking' => $this->toBookingPayload($booking),
        ]);
    }

    public function rescheduleForm(string $publicToken): Response
    {
        $booking = $this->loadBookingByToken($publicToken);
        $expired = $this->isExpired($booking);

        $locations = Location::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Location $l) => ['id' => $l->id, 'name' => $l->name])
            ->values();

        $timeSlots = TimeSlot::query()
            ->orderBy('start_time')
            ->get()
            ->map(function (TimeSlot $slot) {
                $start = CarbonImmutable::parse($slot->start_time)->format('H:i');
                return [
                    'id' => $slot->id,
                    'start_time' => $start,
                    'end_time' => CarbonImmutable::parse($slot->start_time)->addMinutes(59)->format('H:i'),
                ];
            })
            ->values();

        return Inertia::render('booking/reschedule', [
            'booking' => $this->toBookingPayload($booking),
            'expired' => $expired,
            'locations' => $locations,
            'timeSlots' => $timeSlots,
        ]);
    }

    public function reschedule(Request $request, string $publicToken): RedirectResponse
    {
        $booking = $this->loadBookingByToken($publicToken);

        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'grave_type' => ['required', 'string', 'in:makam,kotak_abu'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'visit_date' => ['required', 'date'],
            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
            'lot_id' => ['required', 'integer', 'exists:lots,id'],
            'chairs_count' => ['required', 'integer', 'min:0', 'max:200'],
            'burn_barrels_count' => ['required', 'integer', 'min:0', 'max:50'],
            'has_tent' => ['required', 'boolean'],
            'has_prayer_table' => ['required', 'boolean'],
            'has_lamp' => ['required', 'boolean'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
        ], [
            'customer_name.required' => 'Nama wajib diisi.',
            'customer_email.required' => 'Email wajib diisi.',
            'customer_email.email' => 'Format email tidak valid.',
        ]);

        if ($booking->activity_type !== 'ziarah' && $validated['grave_type'] !== 'makam') {
            return redirect()->back()->withErrors([
                'grave_type' => 'Untuk kegiatan, jenis makam hanya bisa Makam.',
            ]);
        }

        if (! empty($validated['customer_phone'])) {
            try {
                $validated['customer_phone'] = Normalization::normalizePhoneId((string) $validated['customer_phone']);
            } catch (\RuntimeException $e) {
                return redirect()->back()->withErrors([
                    'customer_phone' => $e->getMessage(),
                ]);
            }
        }

        $lotOk = Lot::query()
            ->whereKey($validated['lot_id'])
            ->where('location_id', $validated['location_id'])
            ->where('zone_id', $validated['zone_id'])
            ->where('grave_type', $validated['grave_type'])
            ->whereNull('deleted_at')
            ->exists();
        if (! $lotOk) {
            return redirect()->back()->withErrors([
                'lot_id' => 'Lot tidak valid untuk pilihan ini.',
            ]);
        }

        $minDate = now()->timezone('Asia/Jakarta')->startOfDay()->addDays(2);
        $visitDate = CarbonImmutable::parse($validated['visit_date'], 'Asia/Jakarta')->startOfDay();
        if ($visitDate->lessThan($minDate)) {
            return redirect()->back()->withErrors([
                'visit_date' => 'Tanggal kunjungan minimal H+2.',
            ]);
        }

        try {
            $this->rescheduleService->reschedule($booking, [
                'location_id' => (int) $validated['location_id'],
                'zone_id' => (int) $validated['zone_id'],
                'lot_id' => (int) $validated['lot_id'],
                'grave_type' => (string) $validated['grave_type'],
                'visit_date' => (string) $validated['visit_date'],
                'time_slot_id' => (int) $validated['time_slot_id'],
                'facilities' => [
                    'chairs_count' => (int) $validated['chairs_count'],
                    'burn_barrels_count' => (int) $validated['burn_barrels_count'],
                    'has_tent' => (bool) $validated['has_tent'],
                    'has_prayer_table' => (bool) $validated['has_prayer_table'],
                    'has_lamp' => (bool) $validated['has_lamp'],
                ],
                'customer_name' => (string) $validated['customer_name'],
                'customer_email' => (string) $validated['customer_email'],
                'customer_phone' => ! empty($validated['customer_phone']) ? (string) $validated['customer_phone'] : null,
            ]);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors([
                'availability' => $e->getMessage(),
            ]);
        }

        return redirect()->to("/booking/{$booking->public_token}");
    }

    private function loadBookingByToken(string $publicToken): Booking
    {
        return Booking::query()
            ->with(['location:id,name', 'zone:id,name', 'lot:id,lot_number,size,grave_type', 'timeSlot:id,start_time', 'facilities'])
            ->where('public_token', $publicToken)
            ->firstOrFail();
    }

    private function isExpired(Booking $booking): bool
    {
        $today = now()->timezone('Asia/Jakarta')->startOfDay();
        $visit = CarbonImmutable::parse($booking->visit_date, 'Asia/Jakarta')->startOfDay();
        return $visit->lessThan($today);
    }

    /**
     * @return array<string,mixed>
     */
    private function toBookingPayload(Booking $booking): array
    {
        $start = CarbonImmutable::parse($booking->timeSlot->start_time)->format('H:i');
        $end = CarbonImmutable::parse($booking->timeSlot->start_time)->addMinutes(59)->format('H:i');

        return [
            'id' => $booking->id,
            'public_token' => $booking->public_token,
            'booking_code' => $booking->booking_code,
            'activity_type' => $booking->activity_type,
            'customer_name' => $booking->customer_name,
            'customer_email' => $booking->customer_email,
            'customer_phone' => $booking->customer_phone,
            'grave_type' => $booking->grave_type,
            'visit_date' => optional($booking->visit_date)->format('Y-m-d'),
            'location' => ['id' => $booking->location->id, 'name' => $booking->location->name],
            'zone' => ['id' => $booking->zone->id, 'name' => $booking->zone->name],
            'lot' => [
                'id' => $booking->lot->id,
                'lot_number' => $booking->lot->lot_number,
                'size' => $booking->lot->size,
            ],
            'time_slot' => [
                'id' => $booking->timeSlot->id,
                'start_time' => $start,
                'end_time' => $end,
            ],
            'facilities' => [
                'chairs_count' => (int) ($booking->facilities->chairs_count ?? 0),
                'burn_barrels_count' => (int) ($booking->facilities->burn_barrels_count ?? 0),
                'has_tent' => (bool) ($booking->facilities->has_tent ?? false),
                'has_prayer_table' => (bool) ($booking->facilities->has_prayer_table ?? false),
                'has_lamp' => (bool) ($booking->facilities->has_lamp ?? false),
            ],
            'status' => $booking->status,
            'cancel_reason' => $booking->cancel_reason,
        ];
    }
}
