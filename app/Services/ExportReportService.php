<?php

namespace App\Services;

use App\Models\Booking;
use App\Services\Exports\AdminBookingExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportReportService
{
    /**
     * @param array<string,mixed> $filters
     */
    public function exportBookings(string $format, array $filters): string
    {
        [$rows, $minDate, $maxDate] = $this->queryRows($filters);

        $dir = 'exports';
        Storage::makeDirectory($dir);

        $stamp = now()->format('Ymd_His');
        $base = "booking_export_{$stamp}";

        if ($format === 'excel') {
            $path = "{$dir}/{$base}.xlsx";
            Excel::store(new AdminBookingExport($rows, $minDate, $maxDate), $path);
            return $path;
        }

        $path = "{$dir}/{$base}.pdf";
        $pdf = Pdf::loadView('pdf.admin-bookings-export', [
            'rows' => $rows,
            'minDate' => $minDate,
            'maxDate' => $maxDate,
        ])->setPaper('a4', 'landscape');
        Storage::put($path, $pdf->output());
        return $path;
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{0: array<int,array<string,mixed>>, 1: ?string, 2: ?string}
     */
    private function queryRows(array $filters): array
    {
        $q = Booking::query()
            ->select([
                'id',
                'customer_name',
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

        $bookings = $q->get();
        $minDate = $bookings->min(fn (Booking $b) => optional($b->visit_date)->format('Y-m-d'));
        $maxDate = $bookings->max(fn (Booking $b) => optional($b->visit_date)->format('Y-m-d'));

        $rows = $bookings
            ->map(function (Booking $b): array {
                $startTime = $b->timeSlot?->start_time;
                $start = $startTime ? \Carbon\CarbonImmutable::parse($startTime, 'Asia/Jakarta')->format('H:i') : '';
                $end = $startTime ? \Carbon\CarbonImmutable::parse($startTime, 'Asia/Jakarta')->addMinutes(59)->format('H:i') : '';

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
                    'time_range' => $start && $end ? "{$start} - {$end}" : '',
                    'location' => $b->location?->name ?? '',
                    'customer_name' => (string) ($b->customer_name ?? ''),
                    'grave_label' => $graveLabel,
                    'zone' => $b->zone?->name ?? '',
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
            return [
                $a['activity_type'] ?? '',
                $a['zone'] ?? '',
                $a['visit_date'] ?? '',
                $a['time_range'] ?? '',
                $a['lot'] ?? '',
            ] <=> [
                $b['activity_type'] ?? '',
                $b['zone'] ?? '',
                $b['visit_date'] ?? '',
                $b['time_range'] ?? '',
                $b['lot'] ?? '',
            ];
        });

        return [$rows, $minDate, $maxDate];
    }
}
