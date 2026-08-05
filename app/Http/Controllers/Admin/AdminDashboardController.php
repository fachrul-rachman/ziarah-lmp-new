<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBookingExportJob;
use App\Models\Booking;
use App\Models\ExportJob;
use App\Models\Location;
use App\Models\WalkIn;
use App\Models\Zone;
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
            'date_from' => ['nullable', 'date', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'activity_type' => ['nullable', 'string', 'in:ziarah,naik_batu,start_work,wang_san'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'zone_id' => ['nullable', 'integer', 'exists:zones,id'],
            'status' => ['nullable', 'string', 'in:confirmed,rescheduled,cancelled,completed'],
            'record_type' => ['nullable', 'string', 'in:booking,walk_in'],
        ]);

        $recordType = $filters['record_type'] ?? 'booking';
        if ($recordType === 'walk_in') {
            return $this->walkInIndex($request, $filters);
        }

        $q = Booking::query()
            ->with(['location:id,name', 'zone:id,name', 'lot:id,lot_number,size', 'timeSlot:id,start_time', 'facilities'])
            ->orderByDesc('visit_date')
            ->orderByDesc('id');

        if (! empty($filters['date_from'])) {
            $q->whereDate('visit_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $q->whereDate('visit_date', '<=', $filters['date_to']);
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
            'walkIns' => [],
            'recordType' => 'booking',
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
                'additional_note' => $booking->additional_note,
                'ethics_consented_at' => $booking->ethics_consented_at?->timezone('Asia/Jakarta')->format('d M Y, H:i'),
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
            'date_from' => ['nullable', 'date', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'activity_type' => ['nullable', 'string', 'in:ziarah,naik_batu,start_work,wang_san'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'zone_id' => ['nullable', 'integer', 'exists:zones,id'],
            'status' => ['nullable', 'string', 'in:confirmed,rescheduled,cancelled,completed'],
            'record_type' => ['nullable', 'string', 'in:booking,walk_in'],
        ]);

        $disk = (string) (config('exports.disk') ?? config('filesystems.default'));
        $connection = (string) (config('exports.queue_connection') ?? (app()->environment('local') ? 'sync' : config('queue.default')));
        $queue = (string) (config('exports.queue') ?? 'exports');

        $exportJob = ExportJob::query()->create([
            'status' => 'queued',
            'format' => $validated['format'],
            'disk' => $disk,
            'filters_json' => collect($validated)->except('format')->toArray(),
            'file_path' => null,
            'error_message' => null,
            'started_at' => null,
            'finished_at' => null,
        ]);

        if ($connection === 'sync') {
            ProcessBookingExportJob::dispatchSync($exportJob->id);
        } else {
            ProcessBookingExportJob::dispatch($exportJob->id)
                ->onConnection($connection)
                ->onQueue($queue);
        }

        return redirect()->back()
            ->with('success', 'Export dijadwalkan. Silakan tunggu hingga selesai.')
            ->with('export_job_id', $exportJob->id);
    }

    public function showExportJob(ExportJob $exportJob): JsonResponse
    {
        $disk = $exportJob->disk ?: (config('exports.disk') ?? config('filesystems.default'));
        $ready = $exportJob->status === 'completed'
            && (bool) $exportJob->file_path
            && Storage::disk($disk)->exists((string) $exportJob->file_path);

        return response()->json([
            'id' => $exportJob->id,
            'status' => $exportJob->status,
            'format' => $exportJob->format,
            'error_message' => $exportJob->error_message,
            'download_url' => $ready ? route('admin.exports.download', $exportJob) : null,
        ]);
    }

    public function downloadExport(ExportJob $exportJob)
    {
        if ($exportJob->status !== 'completed' || ! $exportJob->file_path) {
            abort(404);
        }

        $disk = $exportJob->disk ?: (config('exports.disk') ?? config('filesystems.default'));

        $path = $exportJob->file_path;
        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        return Storage::disk($disk)->download($path);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function walkInIndex(Request $request, array $filters): Response
    {
        $query = WalkIn::query()->orderByDesc('created_at')->orderByDesc('id');

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $paginator = $query->paginate(25)->withQueryString();
        $walkIns = collect($paginator->items())->map(fn (WalkIn $walkIn): array => [
            'id' => $walkIn->id,
            'customer_name' => $walkIn->customer_name,
            'customer_phone' => $walkIn->customer_phone,
            'lot_number' => $walkIn->lot_number,
            'booking_h2_reason' => $walkIn->booking_h2_reason,
            'visited_at' => $walkIn->created_at?->timezone('Asia/Jakarta')->format('d M Y, H:i'),
            'ethics_consented_at' => $walkIn->ethics_consented_at?->timezone('Asia/Jakarta')->format('d M Y, H:i'),
        ])->values();

        return Inertia::render('admin/dashboard', [
            'filters' => $filters,
            'bookings' => [],
            'walkIns' => $walkIns,
            'recordType' => 'walk_in',
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'links' => $paginator->linkCollection(),
            ],
            'locations' => [],
            'zones' => [],
            'latestExportJobId' => $request->session()->get('export_job_id'),
        ]);
    }
}
