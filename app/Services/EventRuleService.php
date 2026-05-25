<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EventRuleService
{
    /**
     * @return Collection<int,string>
     */
    public function hiddenFacilitiesForDateAndLocation(string $visitDate, int $locationId): Collection
    {
        return DB::table('events')
            ->join('event_locations', 'event_locations.event_id', '=', 'events.id')
            ->join('event_hidden_facilities', 'event_hidden_facilities.event_id', '=', 'events.id')
            ->where('event_locations.location_id', $locationId)
            ->whereDate('events.start_date', '<=', $visitDate)
            ->whereDate('events.end_date', '>=', $visitDate)
            ->distinct()
            ->orderBy('event_hidden_facilities.facility_key')
            ->pluck('event_hidden_facilities.facility_key');
    }

    /**
     * @return Collection<int,array{facility_key:string,event_names:array<int,string>}>
     */
    public function hiddenFacilityReasonsForDateAndLocation(string $visitDate, int $locationId): Collection
    {
        $rows = DB::table('events')
            ->join('event_locations', 'event_locations.event_id', '=', 'events.id')
            ->join('event_hidden_facilities', 'event_hidden_facilities.event_id', '=', 'events.id')
            ->where('event_locations.location_id', $locationId)
            ->whereDate('events.start_date', '<=', $visitDate)
            ->whereDate('events.end_date', '>=', $visitDate)
            ->groupBy('event_hidden_facilities.facility_key')
            ->orderBy('event_hidden_facilities.facility_key')
            ->select([
                'event_hidden_facilities.facility_key as facility_key',
                DB::raw("array_agg(DISTINCT events.name) as event_names"),
            ])
            ->get();

        return $rows->map(function ($r) {
            $names = $r->event_names;
            if (is_string($names)) {
                // Fallback: if driver returns a PG array string, best-effort parse.
                $names = trim($names, '{}');
                $names = $names === '' ? [] : array_map(fn ($v) => trim($v, '"'), explode(',', $names));
            }
            if (! is_array($names)) {
                $names = [];
            }

            return [
                'facility_key' => (string) $r->facility_key,
                'event_names' => array_values(array_filter(array_map('strval', $names))),
            ];
        })->values();
    }
}
