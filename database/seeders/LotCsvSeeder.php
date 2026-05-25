<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LotCsvSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $path = database_path('seeders/data/data_full.csv');

        if (! file_exists($path)) {
            throw new \Exception("File CSV tidak ditemukan: {$path}");
        }

        $file = fopen($path, 'r');

        $header = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($header, $row);

            $locationName = trim($data['lokasi']);
            $zoneName = trim($data['zona']);
            $graveType = trim($data['jenis_makam']);
            $lotNumber = trim($data['no_lot']);
            $size = trim($data['ukuran']);

            $locationId = DB::table('locations')->whereRaw('lower(name) = lower(?)', [$locationName])->value('id');

            if (! $locationId) {
                $locationId = DB::table('locations')->insertGetId([
                    'name' => $locationName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $zoneId = DB::table('zones')
                ->where('location_id', $locationId)
                ->whereRaw('lower(name) = lower(?)', [$zoneName])
                ->value('id');

            if (! $zoneId) {
                $zoneId = DB::table('zones')->insertGetId([
                    'location_id' => $locationId,
                    'name' => $zoneName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('lots')->updateOrInsert(
                [
                    'location_id' => $locationId,
                    'zone_id' => $zoneId,
                    'grave_type' => $graveType,
                    'normalized_lot_number' => Str::of($lotNumber)->lower()->toString(),
                    'deleted_at' => null,
                ],
                [
                    'location_id' => $locationId,
                    'zone_id' => $zoneId,
                    'grave_type' => $graveType,
                    'lot_number' => $lotNumber,
                    'normalized_lot_number' => Str::of($lotNumber)->lower()->toString(),
                    'size' => $size,
                    'normalized_size' => Str::of($size)->lower()->toString(),
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        fclose($file);
    }
}