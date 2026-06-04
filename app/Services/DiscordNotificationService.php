<?php

namespace App\Services;

use App\Models\Booking;
use App\Services\Exports\AdminBookingExport;
use Carbon\CarbonImmutable;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class DiscordNotificationService
{
    /**
     * @return array{status:string,message:?string,attachments:array<int,array<string,string>>}
     */
    public function sendForTargetDate(string $targetDate, int $notificationLogId): array
    {
        $webhook = trim((string) DB::table('settings')->where('key', 'discord_webhook_url')->value('value'));
        if ($webhook === '') {
            return ['status' => 'skipped', 'message' => 'Webhook URL kosong.', 'attachments' => []];
        }

        $bookings = Booking::query()
            ->with(['location:id,name', 'zone:id,name', 'lot:id,lot_number,size,grave_type', 'timeSlot:id,start_time', 'facilities'])
            ->whereDate('visit_date', $targetDate)
            ->whereIn('status', ['confirmed', 'rescheduled'])
            ->orderBy('zone_id')
            ->get();

        if ($bookings->count() === 0) {
            return ['status' => 'skipped', 'message' => null, 'attachments' => []];
        }

        $rows = $bookings->map(fn (Booking $b) => $this->toRow($b))->all();

        $ziarahRows = array_values(array_filter($rows, fn (array $r) => ($r['activity_type'] ?? '') === 'ziarah'));
        $kegiatanRows = array_values(array_filter($rows, fn (array $r) => ($r['activity_type'] ?? '') !== 'ziarah'));

        Storage::makeDirectory('discord');

        $attachments = [];
        $summaryParts = [];

        $dateText = $this->formatIdDate($targetDate);
        $dateFileText = $this->formatIdDateForFilename($targetDate);

        if (count($ziarahRows) > 0) {
            $attachments = array_merge($attachments, $this->generateExcelPerLocation('ziarah', $ziarahRows, $targetDate, $dateFileText));
            $summaryParts[] = $this->summaryBlock('A. Laporan Booking Ziarah', $dateText, $ziarahRows);
        }

        if (count($kegiatanRows) > 0) {
            $attachments = array_merge($attachments, $this->generateExcelPerLocation('kegiatan', $kegiatanRows, $targetDate, $dateFileText));
            $summaryParts[] = $this->summaryBlock('B. Laporan Booking Kegiatan(TOMB)', $dateText, $kegiatanRows);
        }

        $content = implode("\n\n---\n\n", array_filter($summaryParts));

        $this->postMultipart($webhook, $content, $attachments);

        return [
            'status' => 'sent',
            'message' => $content,
            'attachments' => $attachments,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,string>>
     */
    private function generateExcelPerLocation(string $category, array $rows, string $targetDate, string $dateFileText): array
    {
        $byLocation = [];
        foreach ($rows as $r) {
            $loc = (string) ($r['location'] ?? '');
            $byLocation[$loc][] = $r;
        }

        $attachments = [];
        foreach ($byLocation as $locationName => $locRows) {
            // Sort by zone alphabetic (then time/lot for stability).
            usort($locRows, function (array $a, array $b): int {
                return [
                    (string) ($a['zone'] ?? ''),
                    (string) ($a['time_range'] ?? ''),
                    (string) ($a['lot'] ?? ''),
                ] <=> [
                    (string) ($b['zone'] ?? ''),
                    (string) ($b['time_range'] ?? ''),
                    (string) ($b['lot'] ?? ''),
                ];
            });

            $safeLocation = $this->sanitizeFilenamePart($locationName === '' ? 'Lokasi' : $locationName);
            $fileName = "{$category}-{$safeLocation}-{$dateFileText}.xlsx";
            $excelPath = 'discord/'.$fileName;

            Excel::store(new AdminBookingExport($locRows, $targetDate, $targetDate, $category === 'ziarah'), $excelPath);
            $attachments[] = ['type' => 'excel', 'path' => $excelPath];
        }

        return $attachments;
    }

    /**
     * @param array<int,array<string,string>> $attachments
     */
    private function postMultipart(string $webhookUrl, string $content, array $attachments): void
    {
        $client = new Client(['timeout' => 30]);

        $multipart = [
            [
                'name' => 'payload_json',
                'contents' => json_encode([
                    'content' => $content,
                ], JSON_UNESCAPED_UNICODE),
            ],
        ];

        foreach ($attachments as $i => $att) {
            $path = (string) ($att['path'] ?? '');
            $abs = Storage::path($path);
            $multipart[] = [
                'name' => "files[{$i}]",
                'contents' => fopen($abs, 'r'),
                'filename' => basename($abs),
            ];
        }

        $client->post($webhookUrl, ['multipart' => $multipart]);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private function summaryBlock(string $title, string $dateText, array $rows): string
    {
        $totBooking = count($rows);
        $kursi = array_sum(array_map(fn ($r) => (int) ($r['chairs_count'] ?? 0), $rows));
        $tenda = array_sum(array_map(fn ($r) => (int) ($r['has_tent'] ?? 0), $rows));
        $meja = array_sum(array_map(fn ($r) => ((bool) ($r['has_prayer_table'] ?? false)) ? 1 : 0, $rows));
        $tong = array_sum(array_map(fn ($r) => (int) ($r['burn_barrels_count'] ?? 0), $rows));
        $lamp = array_sum(array_map(fn ($r) => ((bool) ($r['has_lamp'] ?? false)) ? 1 : 0, $rows));

        return implode("\n", [
            "**{$title}**",
            "📅 Tanggal: {$dateText}",
            "",
            "📊 Ringkasan:",
            "Total Booking: {$totBooking}",
            "Total Tenda: {$tenda}",
            "Total Kursi: {$kursi}",
            "Total Tong Bakar: {$tong}",
            "Meja Sembayang: {$meja}",
            "Lampu: {$lamp}",
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function toRow(Booking $b): array
    {
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
            // Keep key consistent with AdminBookingExport / ExportReportService.
            'visit_schedule' => trim(($visitDateLabel ?: '').($visitDateLabel && $start ? ', ' : '').($start ?: '')),
            // Useful for stable sorting (even if not used by the export today).
            'visit_date_sort' => $visitDateSort,
            'time_sort' => $start,
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
    }

    private function formatIdDate(string $ymd): string
    {
        $dt = CarbonImmutable::parse($ymd, 'Asia/Jakarta');
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $dt->format('d').' '.$months[(int) $dt->format('n')].' '.$dt->format('Y');
    }

    private function formatIdDateForFilename(string $ymd): string
    {
        $dt = CarbonImmutable::parse($ymd, 'Asia/Jakarta');
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $m = $months[(int) $dt->format('n')] ?? $dt->format('M');
        return $dt->format('d').'-'.$m.'-'.$dt->format('Y');
    }

    private function sanitizeFilenamePart(string $value): string
    {
        $v = trim($value);
        if ($v === '') {
            return 'Lokasi';
        }
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;
        $v = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $v);
        $v = str_replace(' ', '-', $v);
        return $v;
    }
}
