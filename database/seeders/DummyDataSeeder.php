<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $locations = [
            'Pemakaman Barat',
            'Pemakaman Timur',
            'Pemakaman Utara',
        ];

        $locationIds = [];
        foreach ($locations as $name) {
            $existingLocationId = DB::table('locations')
                ->whereRaw('lower(name) = lower(?)', [$name])
                ->value('id');

            if ($existingLocationId) {
                DB::table('locations')->where('id', $existingLocationId)->update([
                    'name' => $name,
                    'updated_at' => $now,
                ]);
                $locationIds[] = $existingLocationId;
                continue;
            }

            $locationIds[] = DB::table('locations')->insertGetId([
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $zoneNames = ['Zona A', 'Zona B'];
        $lotSizes = ['Single', 'Double', 'Family'];

        foreach ($locationIds as $locationId) {
            $zoneIds = [];
            foreach ($zoneNames as $zoneName) {
                $existingZoneId = DB::table('zones')
                    ->where('location_id', $locationId)
                    ->whereRaw('lower(name) = lower(?)', [$zoneName])
                    ->value('id');

                if ($existingZoneId) {
                    DB::table('zones')->where('id', $existingZoneId)->update([
                        'name' => $zoneName,
                        'updated_at' => $now,
                    ]);
                    $zoneIds[] = $existingZoneId;
                    continue;
                }

                $zoneId = DB::table('zones')->insertGetId([
                    'location_id' => $locationId,
                    'name' => $zoneName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $zoneIds[] = $zoneId;
            }

            foreach ($zoneIds as $zoneIndex => $zoneId) {
                for ($i = 1; $i <= 10; $i++) {
                    $graveType = ($i % 2 === 0) ? 'kotak_abu' : 'makam';
                    $lotNumber = sprintf('%s-%03d', $zoneIndex === 0 ? 'A' : 'B', $i);

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
                        'size' => $lotSizes[($i - 1) % count($lotSizes)],
                        'created_at' => $now,
                        'updated_at' => $now,
                        ],
                    );
                }
            }
        }

        foreach (['08:00', '10:00', '13:00', '15:00', '19:00'] as $startTime) {
            DB::table('time_slots')->updateOrInsert(
                ['start_time' => $startTime],
                ['updated_at' => $now, 'created_at' => $now],
            );
        }

        DB::table('settings')->upsert(
            [
                ['key' => 'discord_webhook_url', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'discord_notification_time', 'value' => '08:00', 'created_at' => $now, 'updated_at' => $now],
            ],
            ['key'],
            ['value', 'updated_at'],
        );
    }
}
