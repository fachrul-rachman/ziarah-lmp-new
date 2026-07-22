<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Lot;
use App\Services\BookingService;
use App\Support\Normalization;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BookingSubmitController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'activity_type' => ['required', 'string', 'in:ziarah,naik_batu,start_work,wang_san'],
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
            'customer_phone' => ['required', 'string', 'max:32'],
            'additional_note' => ['nullable', 'string', 'max:1000'],
            'ethics_confirmed' => ['required', 'accepted'],
        ], [
            'customer_name.required' => 'Nama wajib diisi.',
            'customer_email.required' => 'Email wajib diisi.',
            'customer_email.email' => 'Format email tidak valid.',
            'customer_phone.required' => 'Nomor telepon wajib diisi.',
            'ethics_confirmed.accepted' => 'Persetujuan etika berziarah wajib dicentang.',
        ]);

        if ($validated['activity_type'] !== 'ziarah' && $validated['grave_type'] !== 'makam') {
            return redirect()->back()->withErrors([
                'grave_type' => 'Untuk kegiatan, jenis makam hanya bisa Makam.',
            ]);
        }

        try {
            $validated['customer_phone'] = Normalization::normalizePhoneId((string) $validated['customer_phone']);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors([
                'customer_phone' => $e->getMessage(),
            ]);
        }

        // Defensive: ensure chosen lot belongs to filters.
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

        // Enforce H+2 rule server-side (Asia/Jakarta).
        $minDate = now()->timezone('Asia/Jakarta')->startOfDay()->addDays(2);
        $visitDate = CarbonImmutable::parse($validated['visit_date'], 'Asia/Jakarta')->startOfDay();
        if ($visitDate->lessThan($minDate)) {
            return redirect()->back()->withErrors([
                'visit_date' => 'Tanggal kunjungan minimal H+2.',
            ]);
        }

        try {
            $booking = $this->bookingService->create([
                'activity_type' => $validated['activity_type'],
                'location_id' => (int) $validated['location_id'],
                'zone_id' => (int) $validated['zone_id'],
                'lot_id' => (int) $validated['lot_id'],
                'grave_type' => $validated['grave_type'],
                'visit_date' => $validated['visit_date'],
                'time_slot_id' => (int) $validated['time_slot_id'],
                'facilities' => [
                    'chairs_count' => (int) $validated['chairs_count'],
                    'burn_barrels_count' => (int) $validated['burn_barrels_count'],
                    'has_tent' => (bool) $validated['has_tent'],
                    'has_prayer_table' => (bool) $validated['has_prayer_table'],
                    'has_lamp' => (bool) $validated['has_lamp'],
                ],
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'additional_note' => $validated['additional_note'] ?? null,
                'ethics_consented_at' => now(),
            ]);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors([
                'availability' => $e->getMessage(),
            ]);
        }

        return redirect()->to("/booking/success/{$booking->public_token}");
    }

    public function success(string $publicToken): Response
    {
        $booking = Booking::query()
            ->with(['location:id,name', 'zone:id,name', 'lot:id,lot_number,size', 'timeSlot:id,start_time'])
            ->where('public_token', $publicToken)
            ->firstOrFail();

        $facility = DB::table('booking_facilities')
            ->where('booking_id', $booking->id)
            ->first();

        return Inertia::render('booking/success', [
            'booking' => [
                'public_token' => $booking->public_token,
                'booking_code' => $booking->booking_code,
                'activity_type' => $booking->activity_type,
                'customer_name' => $booking->customer_name,
                'customer_email' => $booking->customer_email,
                'customer_phone' => $booking->customer_phone,
                'additional_note' => $booking->additional_note,
                'grave_type' => $booking->grave_type,
                'visit_date' => $booking->visit_date?->format('Y-m-d'),
                'location' => ['id' => $booking->location->id, 'name' => $booking->location->name],
                'zone' => ['id' => $booking->zone->id, 'name' => $booking->zone->name],
                'lot' => [
                    'id' => $booking->lot->id,
                    'lot_number' => $booking->lot->lot_number,
                    'size' => $booking->lot->size,
                ],
                'time_slot' => [
                    'id' => $booking->timeSlot->id,
                    'start_time' => CarbonImmutable::parse($booking->timeSlot->start_time)->format('H:i'),
                    'end_time' => CarbonImmutable::parse($booking->timeSlot->start_time)->addMinutes(59)->format('H:i'),
                ],
                'facilities' => [
                    'chairs_count' => (int) ($facility->chairs_count ?? 0),
                    'burn_barrels_count' => (int) ($facility->burn_barrels_count ?? 0),
                    'has_tent' => (bool) ($facility->has_tent ?? false),
                    'has_prayer_table' => (bool) ($facility->has_prayer_table ?? false),
                    'has_lamp' => (bool) ($facility->has_lamp ?? false),
                ],
            ],
        ]);
    }

    public function pdf(string $publicToken): HttpResponse
    {
        $booking = Booking::query()
            ->with(['location:id,name', 'zone:id,name', 'lot:id,lot_number,size', 'timeSlot:id,start_time', 'facilities'])
            ->where('public_token', $publicToken)
            ->firstOrFail();

        $pdf = Pdf::loadView('pdf.booking', [
            'booking' => $booking,
        ])->setPaper('a4');

        $filename = "Bukti-Booking-{$booking->booking_code}.pdf";

        return $pdf->download($filename);
    }
}
