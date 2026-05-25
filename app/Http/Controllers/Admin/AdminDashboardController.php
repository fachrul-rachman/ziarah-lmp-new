<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ExportJob;
use App\Models\Location;
use App\Models\Zone;
use App\Jobs\ProcessBookingExportJob;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'activity_type' => ['nullable', 'string', 'in:ziarah,naik_batu,start_work,wang_san'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'zone_id' => ['nullable', 'integer', 'exists:zones,id'],
            'status' => ['nullable', 'string', 'in:confirmed,rescheduled,cancelled,completed'],
        ]);

        $q = Booking::query()
            ->with(['location:id,name', 'zone:id,name', 'lot:id,lot_number,size', 'timeSlot:id,start_time', 'facilities'])
            ->orderByDesc('visit_date')
            ->orderByDesc('id');

        if (! empty($filters['date'])) {
            $q->whereDate('visit_date', $filters['date']);
        }
        if (! empty($filters['activity_type'])) {
            $q->where('activity_type', $filters['activity_type']);
        }
        if (! empty($filters['location_id'])) {
            $q->where('location_id', (int) $filters['location_id']);
        }
        if (! empty($filters['zone_id'])) {
            $q->where('zone_id', (int) $filters['zone_id']);
        }
        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        $paginator = $q->paginate(25)->withQueryString();

        $bookings = collect($paginator->items())->map(function (Booking $b) {
            $start = CarbonImmutable::parse($b->timeSlot->start_time)->format('H:i');
            $end = CarbonImmutable::parse($b->timeSlot->start_time)->addMinutes(59)->format('H:i');

            $f = $b->facilities;
            $facText = implode(' · ', [
                'Kursi '.(int) ($f->chairs_count ?? 0),
                'Tong '.(int) ($f->burn_barrels_count ?? 0),
                'Tenda '.(($f->has_tent ?? false) ? 'Ya' : 'Tidak'),
                'Meja '.(($f->has_prayer_table ?? false) ? 'Ya' : 'Tidak'),
                'Lampu '.(($f->has_lamp ?? false) ? 'Ya' : 'Tidak'),
            ]);

            return [
                'id' => $b->id,
                'customer_name' => $b->customer_name,
                'customer_phone' => $b->customer_phone,
                'activity_type' => $b->activity_type,
                'location' => $b->location->name ?? '-',
                'zone' => $b->zone->name ?? '-',
                'lot' => $b->lot ? "{$b->lot->lot_number} ({$b->lot->size})" : '-',
                'visit_date' => optional($b->visit_date)->format('Y-m-d'),
                'time' => "{$start} - {$end}",
                'facilities' => $facText,
                'status' => $b->status,
            ];
        })->values();

        $locations = Location::query()->orderBy('name')->get(['id', 'name']);
        $zones = Zone::query()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/dashboard', [
            'filters' => $filters,
            'bookings' => $bookings,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'links' => $paginator->linkCollection(),
            ],
            'locations' => $locations,
            'zones' => $zones,
            'latestExportJobId' => $request->session()->get('export_job_id'),
        ]);
    }

    public function showBooking(Booking $booking): Response
    {
        $booking->load(['location:id,name', 'zone:id,name', 'lot:id,lot_number,size', 'timeSlot:id,start_time', 'facilities']);

        $start = CarbonImmutable::parse($booking->timeSlot->start_time)->format('H:i');
        $end = CarbonImmutable::parse($booking->timeSlot->start_time)->addMinutes(59)->format('H:i');

        $reschedules = DB::table('booking_reschedule_histories')
            ->where('booking_id', $booking->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($r) => [
                'from_date' => (string) $r->old_visit_date,
                'to_date' => (string) $r->new_visit_date,
                'from_time_slot_id' => (int) $r->old_time_slot_id,
                'to_time_slot_id' => (int) $r->new_time_slot_id,
                'created_at' => (string) $r->created_at,
            ])
            ->values();

        return Inertia::render('admin/booking-detail', [
            'booking' => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'customer_name' => $booking->customer_name,
                'customer_email' => $booking->customer_email,
                'customer_phone' => $booking->customer_phone,
                'activity_type' => $booking->activity_type,
                'grave_type' => $booking->grave_type,
                'location' => $booking->location->name ?? '-',
                'zone' => $booking->zone->name ?? '-',
                'lot' => $booking->lot ? "{$booking->lot->lot_number} ({$booking->lot->size})" : '-',
                'visit_date' => optional($booking->visit_date)->format('Y-m-d'),
                'time' => "{$start} - {$end}",
                'status' => $booking->status,
                'cancel_reason' => $booking->cancel_reason,
                'facilities' => [
                    'chairs_count' => (int) ($booking->facilities->chairs_count ?? 0),
                    'burn_barrels_count' => (int) ($booking->facilities->burn_barrels_count ?? 0),
                    'has_tent' => (bool) ($booking->facilities->has_tent ?? false),
                    'has_prayer_table' => (bool) ($booking->facilities->has_prayer_table ?? false),
                    'has_lamp' => (bool) ($booking->facilities->has_lamp ?? false),
                ],
                'reschedules' => $reschedules,
            ],
        ]);
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        if ($booking->status === 'cancelled') {
            return redirect()->back()->with('success', 'Booking sudah dibatalkan.');
        }

        $booking->update([
            'status' => 'cancelled',
            'cancel_reason' => null,
        ]);

        return redirect()->back()->with('success', 'Booking berhasil dibatalkan.');
    }

    public function export(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'format' => ['required', 'string', 'in:excel,pdf'],
            'date' => ['nullable', 'date'],
            'activity_type' => ['nullable', 'string', 'in:ziarah,naik_batu,start_work,wang_san'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'zone_id' => ['nullable', 'integer', 'exists:zones,id'],
            'status' => ['nullable', 'string', 'in:confirmed,rescheduled,cancelled,completed'],
        ]);

        $exportJob = ExportJob::query()->create([
            'status' => 'queued',
            'format' => $validated['format'],
            'filters_json' => collect($validated)->except('format')->toArray(),
            'file_path' => null,
            'error_message' => null,
            'started_at' => null,
            'finished_at' => null,
        ]);

        ProcessBookingExportJob::dispatch($exportJob->id);

        return redirect()->back()
            ->with('success', 'Export dijadwalkan. Silakan tunggu hingga selesai.')
            ->with('export_job_id', $exportJob->id);
    }

    public function showExportJob(ExportJob $exportJob): JsonResponse
    {
        return response()->json([
            'id' => $exportJob->id,
            'status' => $exportJob->status,
            'format' => $exportJob->format,
            'error_message' => $exportJob->error_message,
            'download_url' => $exportJob->status === 'completed' ? route('admin.exports.download', $exportJob) : null,
        ]);
    }

    public function downloadExport(ExportJob $exportJob)
    {
        if ($exportJob->status !== 'completed' || ! $exportJob->file_path) {
            abort(404);
        }

        $path = $exportJob->file_path;
        if (! Storage::exists($path)) {
            abort(404);
        }

        return Storage::download($path);
    }
}
