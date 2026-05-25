<?php

namespace App\Imports;

use App\Support\Normalization;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithReadFilter;
use Maatwebsite\Excel\Events\AfterChunk;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\ImportFailed;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class LotExcelImport implements OnEachRow, WithHeadingRow, WithChunkReading, WithReadFilter, ShouldQueue, WithEvents
{
    public function __construct(private readonly int $importJobId)
    {
    }

    /** @var array<string,int> */
    private array $locationCache = [];

    /** @var array<string,int> */
    private array $zoneCache = [];

    public function onRow(Row $row): void
    {
        $rowIndex = $row->getIndex();
        $data = $row->toArray();
        DB::table('import_jobs')->where('id', $this->importJobId)->increment('processed_rows');

        try {
            $mapped = $this->mapRow($data);
            $errors = $this->validateMapped($mapped);
            if (count($errors) > 0) {
                $this->storeRowError($rowIndex, $data, implode(' ', $errors));
                DB::table('import_jobs')->where('id', $this->importJobId)->increment('failed_rows');
                return;
            }

            $locationId = $this->getOrCreateLocationId($mapped['lokasi']);
            $zoneId = $this->getOrCreateZoneId($locationId, $mapped['zona']);

            $now = now();
            DB::statement(
                "INSERT INTO lots (location_id, zone_id, grave_type, lot_number, normalized_lot_number, size, normalized_size, created_at, updated_at, deleted_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)
                 ON CONFLICT (grave_type, location_id, zone_id, normalized_lot_number) WHERE deleted_at IS NULL
                 DO UPDATE SET lot_number = EXCLUDED.lot_number, size = EXCLUDED.size, normalized_size = EXCLUDED.normalized_size, updated_at = EXCLUDED.updated_at",
                [
                    $locationId,
                    $zoneId,
                    $mapped['grave_type'],
                    $mapped['no_lot_raw'],
                    $mapped['no_lot_normalized'],
                    Normalization::normalizeSizeDisplay($mapped['ukuran']),
                    Normalization::normalizeSizeKey($mapped['ukuran']),
                    $now,
                    $now,
                ],
            );

            DB::table('import_jobs')->where('id', $this->importJobId)->increment('success_rows');
        } catch (\Throwable $e) {
            $this->storeRowError($rowIndex, $data, $e->getMessage());
            DB::table('import_jobs')->where('id', $this->importJobId)->increment('failed_rows');
        }
    }

    /** @param array<string,mixed> $row */
    private function mapRow(array $row): array
    {
        // Support minor label variations by normalizing keys.
        $keys = array_change_key_case($row, CASE_LOWER);

        $jenisMakam = (string) ($keys['jenis_makam'] ?? $keys['jenis makam'] ?? '');
        $lokasi = (string) ($keys['lokasi'] ?? '');
        $zona = (string) ($keys['zona'] ?? '');
        $noLot = (string) ($keys['no_lot'] ?? $keys['no lot'] ?? $keys['no'] ?? '');
        $ukuran = (string) ($keys['ukuran'] ?? $keys['size'] ?? '');

        $jenisMakamNorm = Normalization::normalizeText($jenisMakam);
        $graveType = match (mb_strtolower($jenisMakamNorm)) {
            'makam' => 'makam',
            'kotak abu', 'kotak_abu', 'kotak-abu' => 'kotak_abu',
            default => '',
        };

        $lokasiNorm = Normalization::normalizeText($lokasi);
        $zonaNorm = Normalization::normalizeText($zona);
        $noLotRaw = Normalization::normalizeText($noLot);
        $noLotNormalized = Normalization::normalizeLotNumber($noLotRaw);
        $ukuranNorm = Normalization::normalizeText($ukuran);

        return [
            'grave_type' => $graveType,
            'lokasi' => $lokasiNorm,
            'zona' => $zonaNorm,
            'no_lot_raw' => $noLotRaw,
            'no_lot_normalized' => $noLotNormalized,
            'ukuran' => $ukuranNorm,
        ];
    }

    /** @param array<string,string> $mapped */
    private function validateMapped(array $mapped): array
    {
        $errors = [];
        if ($mapped['grave_type'] === '') {
            $errors[] = 'jenis_makam wajib Makam atau Kotak Abu.';
        }
        if ($mapped['lokasi'] === '') {
            $errors[] = 'lokasi wajib diisi.';
        }
        if ($mapped['zona'] === '') {
            $errors[] = 'zona wajib diisi.';
        }
        if ($mapped['no_lot_raw'] === '') {
            $errors[] = 'no_lot wajib diisi.';
        }
        if ($mapped['ukuran'] === '') {
            $errors[] = 'ukuran wajib diisi.';
        }
        return $errors;
    }

    private function getOrCreateLocationId(string $locationName): int
    {
        $key = mb_strtolower($locationName);
        if (isset($this->locationCache[$key])) {
            return $this->locationCache[$key];
        }

        $existingId = DB::table('locations')
            ->whereRaw('lower(name) = lower(?)', [$locationName])
            ->value('id');

        if ($existingId) {
            return $this->locationCache[$key] = (int) $existingId;
        }

        $id = DB::table('locations')->insertGetId([
            'name' => $locationName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->locationCache[$key] = (int) $id;
    }

    private function getOrCreateZoneId(int $locationId, string $zoneName): int
    {
        $key = $locationId.'|'.mb_strtolower($zoneName);
        if (isset($this->zoneCache[$key])) {
            return $this->zoneCache[$key];
        }

        $existingId = DB::table('zones')
            ->where('location_id', $locationId)
            ->whereRaw('lower(name) = lower(?)', [$zoneName])
            ->value('id');

        if ($existingId) {
            return $this->zoneCache[$key] = (int) $existingId;
        }

        $id = DB::table('zones')->insertGetId([
            'location_id' => $locationId,
            'name' => $zoneName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->zoneCache[$key] = (int) $id;
    }

    /** @param array<string,mixed> $raw */
    private function storeRowError(int $rowNumber, array $raw, string $message): void
    {
        DB::table('import_job_errors')->insert([
            'import_job_id' => $this->importJobId,
            'row_number' => $rowNumber,
            'raw_data_json' => json_encode($raw, JSON_THROW_ON_ERROR),
            'error_message' => $message,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function chunkSize(): int
    {
        // Smaller chunks reduce peak memory during PhpSpreadsheet parsing.
        return 50;
    }

    public function readFilter(): IReadFilter
    {
        // Hard limit read columns (A-E) to reduce memory usage on wide sheets.
        // Expected headers: jenis_makam, lokasi, zona, no_lot, ukuran.
        return new \App\Imports\ReadFilters\LotImportReadFilter(maxColumnIndex: 5, headerRow: 1);
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function () {
                @ini_set('memory_limit', '512M');
                gc_enable();
            },
            AfterChunk::class => function () {
                // Best-effort: encourage cycle collection between chunk jobs.
                gc_collect_cycles();
            },
            AfterImport::class => function () {
                DB::table('import_jobs')
                    ->where('id', $this->importJobId)
                    ->update([
                        'status' => 'completed',
                        'finished_at' => now(),
                        'updated_at' => now(),
                    ]);
            },
            ImportFailed::class => function () {
                DB::table('import_jobs')
                    ->where('id', $this->importJobId)
                    ->update([
                        'status' => 'failed',
                        'finished_at' => now(),
                        'updated_at' => now(),
                    ]);
            },
        ];
    }
}
