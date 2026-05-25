<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Services\EventRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventRulesController extends Controller
{
    public function __construct(private readonly EventRuleService $rules)
    {
    }

    public function hiddenFacilities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visit_date' => ['required', 'date'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
        ]);

        $keys = $this->rules
            ->hiddenFacilitiesForDateAndLocation($validated['visit_date'], $validated['location_id'])
            ->values();

        $reasons = $this->rules
            ->hiddenFacilityReasonsForDateAndLocation($validated['visit_date'], $validated['location_id'])
            ->values();

        return response()->json([
            'hidden_facilities' => $keys,
            'hidden_facility_reasons' => $reasons,
        ]);
    }
}
