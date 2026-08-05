<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\WalkIn;
use App\Services\Exports\AdminBookingExport;
use App\Services\Exports\AdminWalkInExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportReportService
{
    /**
     * @param  array<string,mixed>  $filters
     */
    public function exportWalkIns(string $format, array $filters, ?string $disk = null): string
    {
        $disk = $disk ?: (config('exports.disk') ?? config('filesystems.default'));
        $storage = Storage::disk($disk);
        [$rows, $minDate, $maxDate] = $this->queryWalkInRows($filters);

        $exportMinDate = ! empty($filters['date_from']) ? (string) $filters['date_from'] : $minDate;
        $exportMaxDate = ! empty($filters['date_to']) ? (string) $filters['date_to'] : $maxDate;
        $useDedicatedExportsDisk = $disk === 'exports';
        if (! $useDedicatedExportsDisk) {
            $storage->makeDirectory('exports');
        }

        $base = 'walk_in_export_'.now()->format('Ymd_His');
        if ($format === 'excel') {
            $path = $useDedicatedExportsDisk ? "{$base}.xlsx" : "exports/{$base}.xlsx";
            if (! Excel::store(new AdminWalkInExport($rows, $exportMinDate, $exportMaxDate), $path, $disk)) {
                throw new \RuntimeException('Gagal menyimpan file export Excel.');
            }

            return $path;
        }

        $path = $useDedicatedExportsDisk ? "{$base}.pdf" : "exports/{$base}.pdf";
        $pdf = Pdf::loadView('pdf.admin-walk-ins-export', [
            'rows' => $rows,
            'minDate' => $exportMinDate,
            'maxDate' => $exportMaxDate,
        ])->setPaper('a4', 'landscape');
        if (! $storage->put($path, $pdf->output())) {
            throw new \RuntimeException('Gagal menyimpan file export PDF.');
        }

        return $path;
    }

    /**
     * @param  array<string,mixed>  $filters
     */
    public function exportBookings(string $format, array $filters, ?string $disk = null): string
    {
        $disk = $disk ?: (config('exports.disk') ?? config('filesystems.default'));
        $storage = Storage::disk($disk);

        [$rows, $minDate, $maxDate] = $this->queryRows($filters);

        // Prefer explicitly requested range for export labels / headers.
        $labelFrom = ! empty($filters['date_from']) ? (string) $filters['date_from'] : null;
        $labelTo = ! empty($filters['date_to']) ? (string) $filters['date_to'] : null;
        $exportMinDate = $labelFrom ?? $minDate;
        $exportMaxDate = $labelTo ?? $maxDate;

        $dir = 'exports';
        $useDedicatedExportsDisk = $disk === 'exports';
        if (! $useDedicatedExportsDisk) {
            $storage->makeDirectory($dir);
        }

        $stamp = now()->format('Ymd_His');
        $base = "booking_export_{$stamp}";

        if ($format === 'excel') {
            $path = $useDedicatedExportsDisk ? "{$base}.xlsx" : "{$dir}/{$base}.xlsx";
            $ok = Excel::store(new AdminBookingExport($rows, $exportMinDate, $exportMaxDate), $path, $disk);
            if (! $ok) {
                throw new \RuntimeException('Gagal menyimpan file export Excel.');
            }

            return $path;
        }

        $path = $useDedicatedExportsDisk ? "{$base}.pdf" : "{$dir}/{$base}.pdf";
        $pdf = Pdf::loadView('pdf.admin-bookings-export', [
            'rows' => $rows,
            'minDate' => $exportMinDate,
            'maxDate' => $exportMaxDate,
        ])->setPaper('a4', 'landscape');
        $ok = $storage->put($path, $pdf->output());
        if (! $ok) {
            throw new \RuntimeException('Gagal menyimpan file export PDF.');
        }

        return $path;
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array{0: array<int,array<string,mixed>>, 1: ?string, 2: ?string}
     */
    private function queryRows(array $filters): array
    {
        $q = Booking::query()
            ->select([
                'id',
                'customer_name',
                'additional_note',
                'activity_type',
                'visit_date',
                'status',
                'location_id',
                'zone_id',
                'lot_id',
                'time_slot_id',
                'grave_type',
            ])
            ->with([
                'location:id,name',
                'zone:id,name',
                'lot:id,lot_number,grave_type',
                'timeSlot:id,start_time',
                'facilities:booking_id,chairs_count,burn_barrels_count,has_tent,has_prayer_table,has_lamp',
            ]);

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

        $bookings = $q->get();
        $minDate = $bookings->min(fn (Booking $b) => optional($b->visit_date)->format('Y-m-d'));
        $maxDate = $bookings->max(fn (Booking $b) => optional($b->visit_date)->format('Y-m-d'));

        $rows = $bookings
            ->map(function (Booking $b): array {
                $startTime = $b->timeSlot?->start_time;
                $start = $startTime ? CarbonImmutable::parse($startTime, 'Asia/Jakarta')->format('H:i') : '';
                $end = $startTime ? CarbonImmutable::parse($startTime, 'Asia/Jakarta')->addMinutes(59)->format('H:i') : '';
                $visitDateLabel = optional($b->visit_date)->timezone('Asia/Jakarta')->format('d M Y');
                $visitDateSort = optional($b->visit_date)->format('Y-m-d') ?? '';

                $activityLabel = match ($b->activity_type) {
                    'ziarah' => 'Ziarah',
                    'naik_batu' => 'Naik Batu',
                    'start_work' => 'Start Work',
                    'wang_san' => 'Wang San',
                    default => (string) ($b->activity_type ?? ''),
                };

                $graveType = $b->grave_type ?? $b->lot?->grave_type;
                $graveLabel = $graveType === 'kotak_abu' ? 'Kotak Abu' : 'Makam';

                $f = $b->facilities;
                $chairs = (int) ($f?->chairs_count ?? 0);
                $burn = (int) ($f?->burn_barrels_count ?? 0);
                $tent = (bool) ($f?->has_tent ?? false);
                $prayer = (bool) ($f?->has_prayer_table ?? false);
                $lamp = (bool) ($f?->has_lamp ?? false);

                return [
                    'booking_id' => $b->id,
                    'activity_type' => (string) ($b->activity_type ?? ''),
                    'activity_label' => $activityLabel,
                    'visit_schedule' => trim(($visitDateLabel ?: '').($visitDateLabel && $start ? ', ' : '').($start ?: '')),
                    'visit_date_sort' => $visitDateSort,
                    'time_sort' => $start,
                    'location' => $b->location?->name ?? '',
                    'location_sort' => mb_strtolower((string) ($b->location?->name ?? '')),
                    'customer_name' => (string) ($b->customer_name ?? ''),
                    'additional_note' => trim((string) ($b->additional_note ?? '')),
                    'grave_label' => $graveLabel,
                    'zone' => $b->zone?->name ?? '',
                    'zone_sort' => mb_strtolower((string) ($b->zone?->name ?? '')),
                    'lot' => (string) ($b->lot?->lot_number ?? ''),
                    'has_tent' => $tent ? 1 : 0,
                    'chairs_count' => $chairs,
                    'burn_barrels_count' => $burn,
                    'has_prayer_table' => $prayer,
                    'has_lamp' => $lamp,
                    'visit_date' => optional($b->visit_date)->format('Y-m-d'),
                    'status' => (string) ($b->status ?? ''),
                ];
            })
            ->all();

        usort($rows, function (array $a, array $b): int {
            $comparisons = [
                [$a['location_sort'] ?? '', $b['location_sort'] ?? ''],
                [$a['visit_date_sort'] ?? '', $b['visit_date_sort'] ?? ''],
                [$a['time_sort'] ?? '', $b['time_sort'] ?? ''],
                [$a['zone_sort'] ?? '', $b['zone_sort'] ?? ''],
                [$a['lot'] ?? '', $b['lot'] ?? ''],
                [$a['activity_type'] ?? '', $b['activity_type'] ?? ''],
            ];

            foreach ($comparisons as [$left, $right]) {
                $result = strcasecmp((string) $left, (string) $right);
                if ($result !== 0) {
                    return $result;
                }
            }

            return 0;
        });

        return [$rows, $minDate, $maxDate];
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array{0: array<int,array<string,mixed>>, 1: ?string, 2: ?string}
     */
    private function queryWalkInRows(array $filters): array
    {
        $query = WalkIn::query()->orderBy('created_at')->orderBy('id');

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $walkIns = $query->get();
        $minDate = $walkIns->min(fn (WalkIn $walkIn) => $walkIn->created_at?->format('Y-m-d'));
        $maxDate = $walkIns->max(fn (WalkIn $walkIn) => $walkIn->created_at?->format('Y-m-d'));

        $rows = $walkIns->map(fn (WalkIn $walkIn): array => [
            'customer_name' => $walkIn->customer_name,
            'customer_phone' => $walkIn->customer_phone,
            'lot_number' => $walkIn->lot_number,
            'booking_h2_reason' => $walkIn->booking_h2_reason,
            'visited_at' => $walkIn->created_at?->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i'),
            'ethics_consented_at' => $walkIn->ethics_consented_at?->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i'),
        ])->all();

        return [$rows, $minDate, $maxDate];
    }
}
