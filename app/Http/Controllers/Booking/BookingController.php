<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\TimeSlot;
use App\Services\EthicsConsentService;
use App\Services\LotSizeRuleService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        private readonly LotSizeRuleService $sizeRules,
        private readonly EthicsConsentService $ethicsConsent,
    ) {}

    public function index(): Response
    {
        $locations = Location::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Location $l) => ['id' => $l->id, 'name' => $l->name])
            ->values();

        $timeSlots = TimeSlot::query()
            ->orderBy('start_time')
            ->get()
            ->map(function (TimeSlot $slot) {
                $start = CarbonImmutable::parse($slot->start_time)->format('H:i');

                return [
                    'id' => $slot->id,
                    'start_time' => $start,
                    'end_time' => CarbonImmutable::parse($slot->start_time)->addMinutes(59)->format('H:i'),
                ];
            })
            ->values();

        return Inertia::render('booking/index', [
            'locations' => $locations,
            'timeSlots' => $timeSlots,
            'ethics_image_url' => $this->ethicsConsent->imageUrl(),
        ]);
    }

    public function lotSizeRules(): JsonResponse
    {
        return response()->json([
            'default_rule' => $this->sizeRules->defaultRule('global'),
            'rules' => $this->sizeRules->allRules(),
        ]);
    }

    public function zones(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'grave_type' => ['required', 'string', 'in:makam,kotak_abu'],
        ]);

        $zoneIds = DB::table('lots')
            ->whereNull('deleted_at')
            ->where('location_id', $validated['location_id'])
            ->where('grave_type', $validated['grave_type'])
            ->select('zone_id')
            ->distinct()
            ->pluck('zone_id')
            ->all();

        $zones = DB::table('zones')
            ->whereIn('id', $zoneIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($z) => ['id' => $z->id, 'name' => $z->name])
            ->values();

        return response()->json(['zones' => $zones]);
    }
}
