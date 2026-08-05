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
use Illuminate\Support\Facades\Storage;
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

        $noticeRows = DB::table('settings')->whereIn('key', [
            'booking_notice_enabled',
            'booking_notice_title',
            'booking_notice_body',
            'booking_notice_start_date',
            'booking_notice_end_date',
            'booking_notice_image_path',
        ])->pluck('value', 'key');
        $today = CarbonImmutable::now('Asia/Jakarta')->toDateString();
        $noticeActive = $noticeRows->get('booking_notice_enabled') === '1'
            && $noticeRows->get('booking_notice_start_date', '') <= $today
            && $noticeRows->get('booking_notice_end_date', '') >= $today;
        $notice = null;

        if ($noticeActive) {
            $imagePath = (string) $noticeRows->get('booking_notice_image_path', '');
            $imageUrl = $imagePath !== '' && Storage::disk('public')->exists($imagePath)
                ? Storage::disk('public')->url($imagePath)
                : null;
            $notice = [
                'title' => (string) $noticeRows->get('booking_notice_title', ''),
                'body' => (string) $noticeRows->get('booking_notice_body', ''),
                'image_url' => $imageUrl,
                'download_url' => $imageUrl,
            ];
        }

        return Inertia::render('booking/index', [
            'locations' => $locations,
            'timeSlots' => $timeSlots,
            'ethics_image_url' => $this->ethicsConsent->imageUrl(),
            'ethics_pdf_url' => $this->ethicsConsent->pdfUrl(),
            'booking_notice' => $notice,
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
