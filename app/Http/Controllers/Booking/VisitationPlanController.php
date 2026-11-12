<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Lot;
use App\Models\TimeSlot;
use App\Models\VisitationPlan;
use App\Models\Zone;
use App\Services\BookingAvailabilityService;
use App\Services\BookingLeadTimeService;
use App\Services\BookingService;
use App\Services\EthicsConsentService;
use App\Services\EventRuleService;
use App\Services\LotSizeRuleService;
use App\Support\Normalization;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class VisitationPlanController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'request_reference' => ['required', 'string', 'max:255'],
            'applicant.name' => ['required', 'string', 'max:255'],
            'applicant.whatsapp' => ['required', 'string', 'max:32'],
            'applicant.relationship_to_deceased' => ['required', 'string', 'max:100'],
            'deceased.name' => ['required', 'string', 'max:255'],
            'deceased.date_of_death' => ['required', 'date_format:Y-m-d'],
            'burial.site' => ['required', 'string', 'max:100'],
            'burial.zone' => ['required', 'string', 'max:255'],
            'burial.lot_number' => ['required', 'string', 'max:255'],
            'burial.scheduled_at' => ['required', 'date', 'regex:/T.*(?:Z|[+-]\d{2}:\d{2})$/'],
        ]);
        try {
            $phone = Normalization::normalizePhoneId($data['applicant']['whatsapp']);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['applicant.whatsapp' => $e->getMessage()]);
        }
        $burialAt = CarbonImmutable::parse($data['burial']['scheduled_at'])->timezone('Asia/Jakarta');
        if ($burialAt->toDateString() < $data['deceased']['date_of_death']) {
            throw ValidationException::withMessages(['burial.scheduled_at' => 'Tanggal pemakaman tidak boleh sebelum tanggal meninggal.']);
        }
        $site = Str::slug($data['burial']['site']);
        $locations = Location::query()->get(['id', 'name'])
            ->filter(fn (Location $location) => Str::slug($location->name) === $site);
        if ($site === '' || $locations->count() !== 1) {
            throw ValidationException::withMessages(['burial.site' => 'Nama lokasi tidak ditemukan secara unik. Pastikan nama lokasi sesuai data Ziarah.']);
        }
        $locationId = $locations->first()->id;
        $zoneName = Str::slug($data['burial']['zone']);
        $zones = Zone::where('location_id', $locationId)->get(['id', 'name'])
            ->filter(fn (Zone $zone) => Str::slug($zone->name) === $zoneName);
        if ($zoneName === '' || $zones->count() !== 1) {
            throw ValidationException::withMessages(['burial.zone' => 'Zona tidak ditemukan secara unik pada lokasi ini.']);
        }
        $lotNumber = Normalization::normalizeLotNumber($data['burial']['lot_number']);
        $lots = Lot::where('location_id', $locationId)->where('zone_id', $zones->first()->id)
            ->where('normalized_lot_number', $lotNumber)->limit(2)->get();
        if ($lots->count() !== 1) {
            throw ValidationException::withMessages(['burial.lot_number' => 'Lokasi atau lot tidak ditemukan secara unik. Hubungi admin untuk mencocokkan data.']);
        }
        $values = [
            'applicant_name' => $data['applicant']['name'],
            'applicant_phone' => $phone,
            'relationship_to_deceased' => $data['applicant']['relationship_to_deceased'],
            'deceased_name' => $data['deceased']['name'],
            'date_of_death' => $data['deceased']['date_of_death'],
            'burial_at' => $burialAt,
            'lot_id' => $lots->first()->id,
        ];
        $plan = VisitationPlan::firstOrCreate(['request_reference' => $data['request_reference']], $values + [
            'public_token' => Str::random(48),
            'expires_at' => now()->addDays(5),
        ]);
        $plan->fill($values);
        abort_if($plan->isDirty(), 409, 'Referensi pemakaman sudah digunakan dengan data berbeda.');

        return response()->json([
            'url' => url('/plan/'.$plan->public_token),
            'expires_at' => $plan->expires_at->toIso8601String(),
        ], $plan->wasRecentlyCreated ? 201 : 200)->header('Cache-Control', 'no-store');
    }

    public function show(string $token, LotSizeRuleService $rules, EthicsConsentService $ethics, BookingLeadTimeService $leadTime): Response
    {
        $plan = VisitationPlan::where('public_token', $token)->with(['lot.location', 'lot.zone', 'bookings.timeSlot'])->firstOrFail();

        $response = Inertia::render('booking/plan', [
            'plan' => [
                'token' => $plan->public_token,
                'applicant_name' => $plan->applicant_name,
                'applicant_phone' => $plan->applicant_phone,
                'customer_email' => $plan->bookings->sortByDesc('id')->first()?->customer_email ?? '',
                'deceased_name' => $plan->deceased_name,
                'date_of_death' => $plan->date_of_death->toDateString(),
                'burial_at' => $plan->burial_at->timezone('Asia/Jakarta')->format('d-m-Y H:i').' WIB',
                'location' => $plan->lot->location->name,
                'zone' => $plan->lot->zone->name,
                'lot' => $plan->lot->lot_number,
                'moments' => $plan->moments(),
                'maximum_date' => $plan->maximumDate()->toDateString(),
                'year_maximum_date' => $plan->maximumDate('year')->toDateString(),
                'minimum_date' => max($plan->burial_at->timezone('Asia/Jakarta')->toDateString(), $leadTime->earliestVisitAt()->toDateString()),
                'expires_at' => $plan->expires_at->timezone('Asia/Jakarta')->format('d-m-Y H:i').' WIB',
                'expired' => $plan->expires_at->isPast() || $plan->lot->trashed(),
            ],
            'bookings' => $plan->bookings->map(fn ($b) => [
                'token' => $b->public_token, 'code' => $b->booking_code, 'moment' => $b->planned_moment,
                'label' => $b->activityLabel(), 'date' => $b->visit_date->toDateString(),
                'time' => substr($b->timeSlot->start_time, 0, 5), 'status' => $b->status,
            ]),
            'size_rule' => $rules->ruleForSize($plan->lot->normalized_size ?: $plan->lot->size),
            'booking_rules' => $leadTime->payload(),
            'ethics_image_url' => $ethics->imageUrl(),
            'ethics_pdf_url' => $ethics->pdfUrl(),
        ])->toResponse(request());
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }

    public function options(Request $request, string $token, BookingAvailabilityService $availability, BookingLeadTimeService $leadTime, EventRuleService $events): JsonResponse
    {
        $plan = VisitationPlan::where('public_token', $token)->firstOrFail();
        abort_if($plan->expires_at->isPast(), 410, 'Tautan pemesanan sudah kedaluwarsa.');
        $maximumDate = $plan->maximumDate($request->input('moment') === 'year' ? 'year' : null);
        $data = $request->validate([
            'moment' => ['sometimes', 'required', 'in:7,40,100,year,custom'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$maximumDate->toDateString()],
        ]);
        $slots = TimeSlot::orderBy('start_time')->get()->map(function ($slot) use ($plan, $data, $availability, $leadTime) {
            $visitAt = CarbonImmutable::parse($data['date'].' '.$slot->start_time, 'Asia/Jakarta');

            return [
                'id' => $slot->id,
                'start_time' => substr($slot->start_time, 0, 5),
                'available' => $visitAt->greaterThanOrEqualTo($plan->burial_at)
                    && $leadTime->allows($data['date'], $slot->start_time)
                    && ! $availability->conflicts($plan->lot_id, $data['date'], $slot->start_time),
            ];
        });

        return response()->json([
            'slots' => $slots,
            'hidden_facilities' => $events->hiddenFacilitiesForDateAndLocation($data['date'], $plan->lot->location_id),
        ])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request, string $token, BookingService $bookings): RedirectResponse
    {
        $data = $request->validate([
            'ethics_confirmed' => ['required', 'accepted'],
            'whatsapp_consent' => ['required', 'accepted'],
            'customer_email' => ['required', 'email', 'max:255'],
            'schedules' => ['required', 'array', 'min:1', 'max:20'],
            'schedules.*.moment' => ['required', 'in:7,40,100,year,custom'],
            'schedules.*.date' => ['required', 'date_format:Y-m-d'],
            'schedules.*.time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
            'schedules.*.chairs_count' => ['required', 'integer', 'min:0', 'max:200'],
            'schedules.*.burn_barrels_count' => ['required', 'integer', 'min:0', 'max:50'],
            'schedules.*.has_tent' => ['required', 'boolean'],
            'schedules.*.has_prayer_table' => ['required', 'boolean'],
            'schedules.*.has_lamp' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $token, $bookings) {
            $plan = VisitationPlan::where('public_token', $token)->lockForUpdate()->firstOrFail();
            if ($plan->expires_at->isPast()) {
                throw ValidationException::withMessages(['availability' => 'Tautan pemesanan sudah kedaluwarsa. Hubungi tim Lestari Memorial Park.']);
            }
            foreach ($data['schedules'] as $index => $schedule) {
                $moment = $schedule['moment'];
                $active = $plan->bookings()->whereIn('status', ['confirmed', 'rescheduled', 'completed']);
                if ($moment !== 'custom') {
                    if ($active->where('planned_moment', $moment)->exists()) {
                        throw ValidationException::withMessages(["schedules.{$index}.date" => 'Momen ini sudah dipesan.']);
                    }
                } elseif ($active->whereDate('visit_date', $schedule['date'])->exists()) {
                    throw ValidationException::withMessages(["schedules.{$index}.date" => 'Sudah ada booking pada tanggal ini.']);
                }
                try {
                    $bookings->create([
                        'activity_type' => 'ziarah', 'location_id' => $plan->lot->location_id,
                        'zone_id' => $plan->lot->zone_id, 'lot_id' => $plan->lot_id, 'grave_type' => $plan->lot->grave_type,
                        'visit_date' => $schedule['date'], 'time_slot_id' => $schedule['time_slot_id'],
                        'customer_name' => $plan->applicant_name, 'customer_phone' => $plan->applicant_phone,
                        'customer_email' => $data['customer_email'],
                        'visitation_plan_id' => $plan->id, 'planned_moment' => $moment,
                        'ethics_consented_at' => now(), 'whatsapp_consented_at' => now(),
                        'facilities' => collect($schedule)->only(['chairs_count', 'burn_barrels_count', 'has_tent', 'has_prayer_table', 'has_lamp'])->all(),
                    ]);
                } catch (\RuntimeException $e) {
                    throw ValidationException::withMessages(["schedules.{$index}.time_slot_id" => $e->getMessage()]);
                }
            }
        });

        return redirect('/plan/'.$token)->with('success', 'Booking berhasil dibuat. Konfirmasi WhatsApp akan dikirim ke nomor pemohon.');
    }
}
