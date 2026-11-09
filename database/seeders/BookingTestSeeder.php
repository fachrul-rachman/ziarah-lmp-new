<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Lot;
use App\Models\TimeSlot;
use App\Models\Zone;
use App\Support\Normalization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingTestSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (['08:00:00', '10:00:00', '13:00:00', '15:00:00', '19:00:00'] as $startTime) {
                TimeSlot::firstOrCreate(['start_time' => $startTime]);
            }

            foreach (['Karawang', 'Tangerang'] as $name) {
                $location = Location::whereRaw('lower(name) = lower(?)', [$name])->first()
                    ?? Location::create(['name' => $name]);

                foreach (['A', 'B'] as $zoneName) {
                    $zone = Zone::where('location_id', $location->id)->whereRaw('lower(name) = lower(?)', [$zoneName])->first()
                        ?? Zone::create(['location_id' => $location->id, 'name' => $zoneName]);

                    foreach ([123 => 'Single', 124 => 'Double', 125 => 'Family'] as $suffix => $size) {
                        $number = (string) $suffix;
                        $existing = Lot::where('location_id', $location->id)->where('zone_id', $zone->id)
                            ->where('grave_type', 'makam')->where('normalized_lot_number', $number)->first();
                        if (! $existing) {
                            $prefixed = Lot::where('location_id', $location->id)->where('zone_id', $zone->id)
                                ->where('grave_type', 'makam')->where('normalized_lot_number', Normalization::normalizeLotNumber($zoneName.'-'.$suffix))->first();
                            $prefixed?->update(['lot_number' => $number, 'normalized_lot_number' => $number]);
                        }
                        Lot::firstOrCreate([
                            'location_id' => $location->id,
                            'zone_id' => $zone->id,
                            'grave_type' => 'makam',
                            'normalized_lot_number' => Normalization::normalizeLotNumber($number),
                        ], [
                            'lot_number' => $number,
                            'size' => $size,
                            'normalized_size' => Normalization::normalizeSizeKey($size),
                        ]);
                    }
                }
            }
        });
    }
}
