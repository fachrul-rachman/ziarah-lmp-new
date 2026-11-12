<?php

use App\Models\Booking;
use App\Models\VisitationPlan;
use App\Services\BookingAvailabilityService;
use App\Services\BookingCodeService;
use App\Services\BookingService;
use App\Services\BookingWhatsAppService;
use App\Services\CancelBookingService;
use App\Services\RescheduleBookingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/../Support/BookingDatabase.php';

beforeEach(function () {
    $this->withoutVite();
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-09 09:00:00', 'Asia/Jakarta'));
    config(['whatsapp.token' => null, 'whatsapp.phone_number_id' => null, 'whatsapp.api_version' => null, 'booking.burial_api_key' => 'test-secret',
        'booking.lots_cache_store' => 'array', 'booking.lots_rate_limit_store' => 'array']);
    Http::preventStrayRequests();
    createBookingDatabase();
    DB::table('lots')->where('id', 1)->update(['lot_number' => '123', 'normalized_lot_number' => '123']);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function burialPayload(): array
{
    return ['request_reference' => 'LMP-123',
        'applicant' => ['name' => 'Budi', 'whatsapp' => '+6281234567890', 'relationship_to_deceased' => 'Anak'],
        'deceased' => ['name' => 'Hendra', 'date_of_death' => '2026-11-01'],
        'burial' => ['site' => 'karawang', 'zone' => 'A', 'lot_number' => '123', 'scheduled_at' => '2026-11-03T12:00:00+07:00']];
}

function createTestPlan(): VisitationPlan
{
    test()->withToken('test-secret')->postJson('/api/visitation-plans', burialPayload())->assertCreated();

    return VisitationPlan::firstOrFail();
}

function planSchedule(string $moment = '40', string $date = '2026-12-11', int $slot = 1): array
{
    return ['moment' => $moment, 'date' => $date, 'time_slot_id' => $slot,
        'chairs_count' => 5, 'burn_barrels_count' => 0, 'has_tent' => false, 'has_prayer_table' => false, 'has_lamp' => false];
}

function bookingInput(string $date = '2026-12-11', int $slot = 1): array
{
    $schedule = planSchedule(date: $date, slot: $slot);

    return ['activity_type' => 'ziarah', 'location_id' => 1, 'zone_id' => 1, 'lot_id' => 1,
        'grave_type' => 'makam', 'visit_date' => $date, 'time_slot_id' => $slot,
        'customer_name' => 'Budi', 'customer_phone' => '6281234567890', 'customer_email' => 'budi@example.test',
        'ethics_consented_at' => now(), 'whatsapp_consented_at' => now(),
        'facilities' => collect($schedule)->except(['moment', 'date', 'time_slot_id'])->all()];
}

test('burial API authenticates, validates mapping and returns the same link without extending expiry', function () {
    $this->postJson('/api/visitation-plans', burialPayload())->assertUnauthorized();
    $plan = createTestPlan();
    $expiry = $plan->expires_at->toIso8601String();
    CarbonImmutable::setTestNow(CarbonImmutable::now()->addDay());
    $this->withToken('test-secret')->postJson('/api/visitation-plans', burialPayload())
        ->assertOk()->assertJson(['url' => url('/plan/'.$plan->public_token), 'expires_at' => $expiry]);
    $changed = burialPayload();
    $changed['deceased']['name'] = 'Different';
    $this->postJson('/api/visitation-plans', $changed)->assertConflict();
    $changed['request_reference'] = 'LMP-456';
    $changed['burial']['site'] = 'unknown';
    $this->postJson('/api/visitation-plans', $changed)->assertUnprocessable();
    expect(VisitationPlan::count())->toBe(1);
});

test('plan dates use death plus days and leap anniversary and plan page contains the existing bookings', function () {
    $plan = createTestPlan();
    expect($plan->moments()['7'])->toBe('2026-11-08')->and($plan->moments()['40'])->toBe('2026-12-11');
    $plan->date_of_death = '2024-02-29';
    expect($plan->moments()['year'])->toBe('2025-02-28');
    $this->get('/plan/'.VisitationPlan::first()->public_token)->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('booking/plan')->where('plan.deceased_name', 'Hendra')->where('plan.expired', false));
});

test('multiple moments save atomically through booking service and duplicate submissions cannot duplicate bookings', function () {
    $plan = createTestPlan();
    $payload = ['ethics_confirmed' => true, 'customer_email' => 'budi@example.test', 'whatsapp_consent' => true,
        'schedules' => [planSchedule(), planSchedule('year', '2027-11-01')]];
    $this->post('/plan/'.$plan->public_token, $payload)->assertSessionHasNoErrors()->assertRedirect('/plan/'.$plan->public_token);
    expect(Booking::count())->toBe(2)->and(Booking::pluck('booking_code')->unique()->count())->toBe(2)
        ->and(DB::table('booking_whatsapp_messages')->count())->toBe(2);
    $this->post('/plan/'.$plan->public_token, $payload)->assertSessionHasErrors();
    expect(Booking::count())->toBe(2);
    $this->get('/booking/'.Booking::first()->public_token)->assertOk();
});

test('a conflicting later schedule rolls back the entire selection and its outbox', function () {
    $plan = createTestPlan();
    app(BookingService::class)->create(bookingInput('2026-12-12'));
    $this->post('/plan/'.$plan->public_token, ['ethics_confirmed' => true, 'customer_email' => 'budi@example.test', 'whatsapp_consent' => true,
        'schedules' => [planSchedule(), planSchedule('custom', '2026-12-12')]])->assertSessionHasErrors('schedules.1.time_slot_id');
    expect($plan->bookings()->count())->toBe(0)->and(DB::table('booking_whatsapp_messages')->count())->toBe(1);
});

test('expired plan preserves access to details but cannot add bookings and GET never consumes the link', function () {
    $plan = createTestPlan();
    $this->get('/plan/'.$plan->public_token)->assertOk();
    expect($plan->fresh()->expires_at->equalTo($plan->expires_at))->toBeTrue();
    CarbonImmutable::setTestNow(CarbonImmutable::now()->addDays(5)->addSecond());
    $this->get('/plan/'.$plan->public_token)->assertOk()->assertInertia(fn (Assert $page) => $page->where('plan.expired', true));
    $this->post('/plan/'.$plan->public_token, ['ethics_confirmed' => true, 'customer_email' => 'budi@example.test', 'whatsapp_consent' => true, 'schedules' => [planSchedule()]])->assertSessionHasErrors('availability');
    expect(Booking::count())->toBe(0);
});

test('consent, burial cutoff and one year limit are enforced on the server', function () {
    $plan = createTestPlan();
    $payload = ['ethics_confirmed' => true, 'customer_email' => 'budi@example.test', 'schedules' => [planSchedule()]];
    $this->post('/plan/'.$plan->public_token, $payload)->assertSessionHasErrors('whatsapp_consent');
    $payload['whatsapp_consent'] = true;
    foreach ([planSchedule('40', '2027-11-02'), planSchedule('custom', '2027-11-02'), planSchedule('7', '2026-11-02')] as $schedule) {
        $payload['schedules'] = [$schedule];
        $this->post('/plan/'.$plan->public_token, $payload)->assertSessionHasErrors();
    }
    expect(Booking::count())->toBe(0);
});

test('choosing a different visit date preserves the original moment and prevents a second booking for it', function () {
    $plan = createTestPlan();
    $url = '/plan/'.$plan->public_token;
    $payload = ['ethics_confirmed' => true, 'customer_email' => 'budi@example.test', 'whatsapp_consent' => true,
        'schedules' => [planSchedule('7', '2026-11-20'), planSchedule('40', '2027-01-10')]];
    $this->post($url, $payload)->assertSessionHasNoErrors();
    expect($plan->bookings()->where('planned_moment', '40')->firstOrFail()->visit_date->toDateString())->toBe('2027-01-10');
    $this->get($url)->assertInertia(fn (Assert $page) => $page->where('plan.moments.40', '2026-12-11'));

    $payload['schedules'] = [planSchedule('40', '2027-02-01')];
    $this->post($url, $payload)->assertSessionHasErrors('schedules.0.date');
    expect($plan->bookings()->count())->toBe(2);
});

test('only the anniversary moment can book and reschedule up to one month after the anniversary', function () {
    $plan = createTestPlan();
    $url = '/plan/'.$plan->public_token;
    $this->getJson($url.'/options?moment=year&date=2027-12-01')->assertOk()->assertJsonPath('slots.0.available', true);
    $this->getJson($url.'/options?moment=year&date=2027-12-02')->assertUnprocessable();
    $this->getJson($url.'/options?moment=40&date=2027-12-01')->assertUnprocessable();

    $payload = ['ethics_confirmed' => true, 'customer_email' => 'budi@example.test', 'whatsapp_consent' => true,
        'schedules' => [planSchedule('year', '2027-12-02')]];
    $this->post($url, $payload)->assertSessionHasErrors('schedules.0.time_slot_id');
    $payload['schedules'] = [planSchedule('year', '2027-12-01')];
    $this->post($url, $payload)->assertSessionHasNoErrors();
    $booking = $plan->bookings()->firstOrFail();
    $this->get('/booking/'.$booking->public_token.'/reschedule')->assertInertia(fn (Assert $page) => $page->where('maximum_date', '2027-12-01'));
    $this->getJson('/booking/lots?'.http_build_query(['location_id' => 1, 'zone_id' => 1, 'grave_type' => 'makam',
        'visit_date' => '2027-12-01', 'time_slot_id' => 1, 'booking_token' => $booking->public_token]))->assertOk()->assertJsonCount(1, 'lots');
    $input = bookingInput('2027-11-30');
    $reschedule = collect($input)->except(['facilities', 'ethics_consented_at', 'whatsapp_consented_at'])->all()
        + $input['facilities'] + ['whatsapp_consent' => true];
    $this->post('/booking/'.$booking->public_token.'/reschedule', $reschedule)->assertSessionHasNoErrors();
    $reschedule['visit_date'] = '2027-12-02';
    $this->post('/booking/'.$booking->public_token.'/reschedule', $reschedule)->assertSessionHasErrors('availability');
    expect($booking->fresh()->planned_moment)->toBe('year')
        ->and($booking->fresh()->visit_date->toDateString())->toBe('2027-11-30');
});

test('shared availability and write checks reject overlapping slots including across midnight', function () {
    app(BookingService::class)->create(bookingInput());
    $availability = app(BookingAvailabilityService::class);
    expect($availability->conflicts(1, '2026-12-11', '09:30:00'))->toBeTrue()
        ->and($availability->conflicts(1, '2026-12-11', '10:00:00'))->toBeFalse();
    expect(fn () => app(BookingService::class)->create(bookingInput(slot: 2)))->toThrow(RuntimeException::class);
    app(BookingService::class)->create(bookingInput('2026-12-12', 4));
    expect($availability->conflicts(1, '2026-12-13', '00:00:00'))->toBeTrue();
    expect($availability->availableLots(1, 1, 'makam', '2026-12-13', 5))->toBeEmpty();
});

test('cancelled and completed bookings cannot be resurrected through a stale reschedule object', function () {
    $booking = app(BookingService::class)->create(bookingInput());
    $stale = Booking::find($booking->id);
    app(CancelBookingService::class)->cancel($booking, 'Berhalangan');
    expect(fn () => app(RescheduleBookingService::class)->reschedule($stale, bookingInput('2026-12-12')))->toThrow(RuntimeException::class);
    expect($booking->fresh()->status)->toBe('cancelled')->and(DB::table('booking_reschedule_histories')->count())->toBe(0);
    $booking->update(['status' => 'completed']);
    expect(fn () => app(RescheduleBookingService::class)->reschedule($stale, bookingInput('2026-12-12')))->toThrow(RuntimeException::class);
});

test('reschedule uses shared rules, retains plan and invalidates old notification jobs', function () {
    $plan = createTestPlan();
    $booking = app(BookingService::class)->create(bookingInput() + ['visitation_plan_id' => $plan->id, 'planned_moment' => '40']);
    $updated = app(RescheduleBookingService::class)->reschedule($booking, bookingInput('2026-12-12'));
    expect($updated->status)->toBe('rescheduled')->and($updated->visitation_plan_id)->toBe($plan->id)
        ->and(DB::table('booking_reschedule_histories')->count())->toBe(1);
    config(['whatsapp.token' => 'test', 'whatsapp.phone_number_id' => '123', 'whatsapp.api_version' => 'v25.0']);
    Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.test']]])]);
    $sender = app(BookingWhatsAppService::class);
    foreach (DB::table('booking_whatsapp_messages')->pluck('id') as $id) {
        $sender->send($id);
        $sender->send($id);
    }
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request['template']['name'] === 'ziarah_booking_rescheduled');
});

test('H-1 reminders only target active planned bookings once and respect the configured Jakarta time', function () {
    $plan = createTestPlan();
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-12-10 08:59:00', 'Asia/Jakarta'));
    DB::table('settings')->insert(['key' => 'booking_minimum_value', 'value' => '0']);
    $booking = app(BookingService::class)->create(bookingInput() + ['visitation_plan_id' => $plan->id, 'planned_moment' => '40']);
    app(BookingService::class)->create(bookingInput(slot: 3));
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    expect(DB::table('booking_whatsapp_messages')->where('kind', 'reminder')->count())->toBe(0);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-12-10 09:00:00', 'Asia/Jakarta'));
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    expect(DB::table('booking_whatsapp_messages')->where('kind', 'reminder')->count())->toBe(1);
    app(CancelBookingService::class)->cancel($booking, 'Batal');
    config(['whatsapp.token' => 'test', 'whatsapp.phone_number_id' => '123', 'whatsapp.api_version' => 'v25.0']);
    $id = DB::table('booking_whatsapp_messages')->where('kind', 'reminder')->value('id');
    app(BookingWhatsAppService::class)->send($id);
    Http::assertNothingSent();
    expect(DB::table('booking_whatsapp_messages')->find($id)->status)->toBe('skipped');
});

test('WhatsApp configuration and failed or ambiguous sends cannot undo bookings or trigger automatic duplicate messages', function () {
    $booking = app(BookingService::class)->create(bookingInput());
    $sender = app(BookingWhatsAppService::class);
    $id = DB::table('booking_whatsapp_messages')->value('id');
    $sender->send($id);
    expect(DB::table('booking_whatsapp_messages')->find($id)->status)->toBe('pending');
    config(['whatsapp.token' => 'test', 'whatsapp.phone_number_id' => '123', 'whatsapp.api_version' => 'v25.0']);
    Http::fake(['*' => Http::failedConnection()]);
    $sender->send($id);
    $sender->send($id);
    expect(DB::table('booking_whatsapp_messages')->find($id)->status)->toBe('unknown')
        ->and($booking->fresh()->status)->toBe('confirmed');
    $this->artisan('whatsapp:retry', ['message' => $id])->assertFailed();
    $this->artisan('whatsapp:retry', ['message' => $id, '--verified-not-sent' => true])->assertSuccessful();
    expect(DB::table('booking_whatsapp_messages')->find($id)->status)->toBe('pending');
});

test('regular booking keeps email for administration and reschedule and cancel still work through public routes', function () {
    $input = bookingInput();
    $payload = collect($input)->except(['facilities', 'ethics_consented_at', 'whatsapp_consented_at'])->all()
        + $input['facilities'] + ['ethics_confirmed' => true, 'customer_email' => 'budi@example.test', 'whatsapp_consent' => true];
    $this->post('/booking', $payload)->assertSessionHasNoErrors()->assertRedirect();
    $booking = Booking::firstOrFail();
    expect($booking->customer_email)->toBe('budi@example.test')->and($booking->whatsapp_consented_at)->not->toBeNull();
    $payload['visit_date'] = '2026-12-12';
    $this->post('/booking/'.$booking->public_token.'/reschedule', $payload)->assertSessionHasNoErrors()->assertRedirect();
    expect($booking->fresh()->visit_date->toDateString())->toBe('2026-12-12');
    $this->post('/booking/'.$booking->public_token.'/cancel', ['cancel_reason' => 'Berhalangan'])->assertSessionHasNoErrors()->assertRedirect();
    expect($booking->fresh()->status)->toBe('cancelled');
});

test('planned reschedule beyond 100 days keeps the fixed lot and respects the death anniversary', function () {
    $plan = createTestPlan();
    $booking = app(BookingService::class)->create(bookingInput() + ['visitation_plan_id' => $plan->id, 'planned_moment' => '40']);
    $url = '/booking/'.$booking->public_token;
    $this->get($url.'/reschedule')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('maximum_date', '2027-11-01')->where('booking.visitation_plan_id', $plan->id));
    $this->getJson('/booking/lots?'.http_build_query(['location_id' => 1, 'zone_id' => 1, 'grave_type' => 'makam',
        'visit_date' => '2027-10-31', 'time_slot_id' => 1, 'booking_token' => $booking->public_token]))->assertOk()->assertJsonCount(1, 'lots');
    $input = bookingInput('2027-10-31');
    $payload = collect($input)->except(['facilities', 'ethics_consented_at', 'whatsapp_consented_at'])->all()
        + $input['facilities'] + ['whatsapp_consent' => true];
    $this->post($url.'/reschedule', $payload)->assertSessionHasNoErrors();
    $payload['visit_date'] = '2027-11-02';
    $this->post($url.'/reschedule', $payload)->assertSessionHasErrors('availability');
    expect($booking->fresh()->visit_date->toDateString())->toBe('2027-10-31');
});

test('event facility restrictions are enforced by both booking and reschedule services', function () {
    DB::table('events')->insert(['id' => 1, 'name' => 'Event', 'start_date' => '2026-12-11', 'end_date' => '2026-12-12']);
    DB::table('event_locations')->insert(['event_id' => 1, 'location_id' => 1]);
    DB::table('event_hidden_facilities')->insert([['event_id' => 1, 'facility_key' => 'chairs'], ['event_id' => 1, 'facility_key' => 'tent']]);
    $input = bookingInput();
    $input['facilities']['has_tent'] = true;
    $booking = app(BookingService::class)->create($input);
    expect($booking->facilities->chairs_count)->toBe(0)->and($booking->facilities->has_tent)->toBeFalse();
    $input['visit_date'] = '2026-12-12';
    $booking = app(RescheduleBookingService::class)->reschedule($booking, $input);
    expect($booking->facilities->chairs_count)->toBe(0)->and($booking->facilities->has_tent)->toBeFalse();
});

test('burial site matches all simplified location names without location ID configuration', function () {
    DB::table('locations')->insert(['id' => 2, 'name' => 'Tangerang Selatan']);
    DB::table('zones')->insert(['id' => 2, 'location_id' => 2, 'name' => 'A']);
    DB::table('lots')->insert(['id' => 2, 'location_id' => 2, 'zone_id' => 2, 'grave_type' => 'makam',
        'lot_number' => '123', 'normalized_lot_number' => '123', 'size' => '2x3', 'normalized_size' => '2x3']);
    $payload = burialPayload();
    $payload['burial']['site'] = '  KARAWANG  ';
    $this->withToken('test-secret')->postJson('/api/visitation-plans', $payload)->assertCreated();
    $payload['request_reference'] = 'LMP-TANGERANG';
    $payload['burial']['site'] = 'tangerang-selatan';
    $this->postJson('/api/visitation-plans', $payload)->assertCreated();
    expect(VisitationPlan::where('request_reference', 'LMP-TANGERANG')->first()->lot_id)->toBe(2);
    DB::table('locations')->insert(['id' => 3, 'name' => 'Tangerang-Selatan']);
    $payload['request_reference'] = 'LMP-AMBIGUOUS';
    $this->postJson('/api/visitation-plans', $payload)->assertUnprocessable()->assertJsonValidationErrors('burial.site');
});

test('burial explicit zone resolves identical lot numbers without guessing from a prefix', function () {
    DB::table('lots')->where('id', 1)->update(['lot_number' => '123', 'normalized_lot_number' => '123']);
    DB::table('zones')->insert(['id' => 2, 'location_id' => 1, 'name' => 'B']);
    DB::table('lots')->insert(['id' => 2, 'location_id' => 1, 'zone_id' => 2, 'grave_type' => 'makam', 'lot_number' => '123', 'normalized_lot_number' => '123', 'size' => 'Single', 'normalized_size' => 'single']);
    $plan = createTestPlan();
    expect($plan->lot_id)->toBe(1);
    $payload = burialPayload();
    $payload['request_reference'] = 'LMP-ZONE-B';
    $payload['burial']['zone'] = 'B';
    $this->withToken('test-secret')->postJson('/api/visitation-plans', $payload)->assertCreated();
    expect(VisitationPlan::where('request_reference', 'LMP-ZONE-B')->first()->lot_id)->toBe(2);
    $payload['request_reference'] = 'LMP-NO-ZONE';
    unset($payload['burial']['zone']);
    $this->postJson('/api/visitation-plans', $payload)->assertUnprocessable()->assertJsonValidationErrors('burial.zone');
});

test('availability still returns zone lots when Redis is unavailable', function () {
    config(['booking.lots_cache_store' => 'redis']);
    Cache::shouldReceive('store')->with('redis')->andThrow(new RedisException('Redis unavailable'));
    $lots = app(BookingAvailabilityService::class)->availableLots(1, 1, 'makam', '2026-12-11', 1);
    expect($lots->pluck('id')->all())->toBe([1]);
});

test('compact booking codes keep the original format for every activity', function () {
    $codes = app(BookingCodeService::class);
    $date = CarbonImmutable::parse('2026-11-07');
    expect($codes->make('ziarah', $date, 'SB878'))->toBe('Z-20261107-sb878')
        ->and($codes->make('naik_batu', $date, 'SB878'))->toBe('NB-20261107-sb878')
        ->and($codes->make('start_work', $date, 'SB878'))->toBe('SW-20261107-sb878')
        ->and($codes->make('wang_san', $date, 'SB878'))->toBe('WS-20261107-sb878');
});

test('changing only the time twice queues distinct messages even when compact booking codes stay the same', function () {
    $booking = app(BookingService::class)->create(bookingInput());
    $code = $booking->booking_code;
    $service = app(RescheduleBookingService::class);
    $booking = $service->reschedule($booking, bookingInput(slot: 2));
    $booking = $service->reschedule($booking, bookingInput(slot: 3));
    expect($booking->booking_code)->toBe($code)
        ->and(DB::table('booking_whatsapp_messages')->count())->toBe(3);
    config(['whatsapp.token' => 'test', 'whatsapp.phone_number_id' => '123', 'whatsapp.api_version' => 'v25.0']);
    Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.latest']]])]);
    foreach (DB::table('booking_whatsapp_messages')->pluck('id') as $id) {
        app(BookingWhatsAppService::class)->send($id);
        app(BookingWhatsAppService::class)->send($id);
    }
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request['template']['name'] === 'ziarah_booking_rescheduled'
        && str_contains($request['template']['components'][0]['parameters'][3]['text'], '10:00')
        && $request['template']['components'][0]['parameters'][5]['text'] === 'Zona A, nomor 123');
    expect(DB::table('booking_whatsapp_messages')->where('status', 'skipped')->count())->toBe(2);
});

test('customer email is required for internal records and saved to each planned booking', function () {
    $plan = createTestPlan();
    $payload = ['ethics_confirmed' => true, 'whatsapp_consent' => true, 'schedules' => [planSchedule()]];
    $this->post('/plan/'.$plan->public_token, $payload)->assertSessionHasErrors('customer_email');
    expect(Booking::count())->toBe(0);
    $payload['customer_email'] = 'pemohon@example.test';
    $payload['schedules'][] = planSchedule('year', '2027-11-01');
    $this->post('/plan/'.$plan->public_token, $payload)->assertSessionHasNoErrors();
    expect(Booking::pluck('customer_email')->all())->toBe(['pemohon@example.test', 'pemohon@example.test']);
    $this->get('/plan/'.$plan->public_token)->assertInertia(fn (Assert $page) => $page->where('plan.customer_email', 'pemohon@example.test'));
});

test('WhatsApp buttons use the shared root template and select the correct booking or plan path', function (bool $planned, string $kind) {
    $plan = $planned ? createTestPlan() : null;
    if ($kind === 'reminder') {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-12-10 09:00:00', 'Asia/Jakarta'));
        DB::table('settings')->insert(['key' => 'booking_minimum_value', 'value' => '0']);
    }
    $input = bookingInput();
    if ($plan) {
        $input += ['visitation_plan_id' => $plan->id, 'planned_moment' => '40'];
    }
    $booking = app(BookingService::class)->create($input);
    if ($kind === 'rescheduled') {
        $booking = app(RescheduleBookingService::class)->reschedule($booking, bookingInput('2026-12-12'));
    }
    if ($kind === 'reminder') {
        app(BookingWhatsAppService::class)->queue($booking, 'reminder');
    }
    config(['whatsapp.token' => 'test', 'whatsapp.phone_number_id' => '123', 'whatsapp.api_version' => 'v25.0']);
    Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.target']]])]);
    $messageId = DB::table('booking_whatsapp_messages')->where('kind', $kind)->value('id');
    app(BookingWhatsAppService::class)->send($messageId);
    $expected = $plan ? 'plan/'.$plan->public_token : 'booking/'.$booking->public_token;
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request['template']['components'][1]['parameters'][0]['text'] === $expected);
})->with([
    'regular confirmation' => [false, 'confirmation'],
    'regular reschedule' => [false, 'rescheduled'],
    'planned confirmation' => [true, 'confirmation'],
    'planned reschedule' => [true, 'rescheduled'],
    'planned reminder' => [true, 'reminder'],
]);

test('repeated schedulers keep one queued WhatsApp job per message while workers are stopped', function () {
    (require database_path('migrations/0001_01_01_000002_create_jobs_table.php'))->up();
    config(['queue.default' => 'database', 'whatsapp.token' => 'test', 'whatsapp.phone_number_id' => '123', 'whatsapp.api_version' => 'v25.0']);
    app(BookingService::class)->create(bookingInput());
    app(BookingService::class)->create(bookingInput(slot: 3));

    $this->artisan('whatsapp:bookings')->assertSuccessful();
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    CarbonImmutable::setTestNow(CarbonImmutable::now()->addDays(3));
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    expect(DB::table('jobs')->count())->toBe(2);
    Http::assertNothingSent();

    Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.queue']]])]);
    $this->artisan('queue:work', ['--once' => true])->assertSuccessful();
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    expect(DB::table('jobs')->count())->toBe(1);
    $this->artisan('queue:work', ['--stop-when-empty' => true])->assertSuccessful();
    Http::assertSentCount(2);
    expect(DB::table('jobs')->count())->toBe(0)
        ->and(DB::table('booking_whatsapp_messages')->where('status', 'accepted')->count())->toBe(2);
});

test('a reserved WhatsApp job stays unique before the worker claims the outbox message', function () {
    (require database_path('migrations/0001_01_01_000002_create_jobs_table.php'))->up();
    config(['queue.default' => 'database', 'whatsapp.token' => 'test', 'whatsapp.phone_number_id' => '123', 'whatsapp.api_version' => 'v25.0']);
    app(BookingService::class)->create(bookingInput());
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    $reserved = app('queue')->connection()->pop();
    expect($reserved)->not->toBeNull();
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    expect(DB::table('jobs')->count())->toBe(1);
    Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.reserved']]])]);
    $reserved->fire();
    Http::assertSentCount(1);
    expect(DB::table('jobs')->count())->toBe(0);
});

test('a queue dispatch failure releases the WhatsApp job lock so the next scheduler can recover', function () {
    (require database_path('migrations/0001_01_01_000002_create_jobs_table.php'))->up();
    config(['queue.default' => 'missing-test-connection', 'whatsapp.token' => 'test', 'whatsapp.phone_number_id' => '123', 'whatsapp.api_version' => 'v25.0']);
    app(BookingService::class)->create(bookingInput());
    expect(fn () => Artisan::call('whatsapp:bookings'))->toThrow(InvalidArgumentException::class);
    expect(DB::table('booking_whatsapp_messages')->value('status'))->toBe('pending');

    config(['queue.default' => 'database']);
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    expect(DB::table('jobs')->count())->toBe(1);
    Http::assertNothingSent();
});

test('worker failure before sending releases the unique lock and leaves the message recoverable', function () {
    (require database_path('migrations/0001_01_01_000002_create_jobs_table.php'))->up();
    config(['queue.default' => 'database', 'queue.failed.database' => 'sqlite', 'whatsapp.token' => 'test', 'whatsapp.phone_number_id' => '123', 'whatsapp.api_version' => 'v25.0']);
    app(BookingService::class)->create(bookingInput());
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    $brokenSender = Mockery::mock(BookingWhatsAppService::class)->makePartial();
    $brokenSender->shouldReceive('send')->once()->andThrow(new RuntimeException('Temporary failure before contacting Meta'));
    app()->instance(BookingWhatsAppService::class, $brokenSender);
    $this->artisan('queue:work', ['--once' => true])->assertSuccessful();
    expect(DB::table('failed_jobs')->count())->toBe(1)
        ->and(DB::table('booking_whatsapp_messages')->value('status'))->toBe('pending');

    app()->forgetInstance(BookingWhatsAppService::class);
    Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.recovered']]])]);
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    expect(DB::table('jobs')->count())->toBe(1);
    $this->artisan('queue:work', ['--once' => true])->assertSuccessful();
    Http::assertSentCount(1);
});

test('a rejected WhatsApp message can be manually retried after its unique job finishes', function () {
    (require database_path('migrations/0001_01_01_000002_create_jobs_table.php'))->up();
    config(['queue.default' => 'database', 'whatsapp.token' => 'test', 'whatsapp.phone_number_id' => '123', 'whatsapp.api_version' => 'v25.0']);
    app(BookingService::class)->create(bookingInput());
    Http::fakeSequence()
        ->push(['error' => ['code' => 132000]], 400)
        ->push(['messages' => [['id' => 'wamid.manual-retry']]]);
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    $this->artisan('queue:work', ['--once' => true])->assertSuccessful();
    $message = DB::table('booking_whatsapp_messages')->first();
    expect($message->status)->toBe('failed');

    $this->artisan('whatsapp:retry', ['message' => $message->id])->assertSuccessful();
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    $this->artisan('whatsapp:bookings')->assertSuccessful();
    expect(DB::table('jobs')->count())->toBe(1);
    $this->artisan('queue:work', ['--once' => true])->assertSuccessful();
    expect(DB::table('booking_whatsapp_messages')->value('status'))->toBe('accepted')
        ->and(DB::table('jobs')->count())->toBe(0);
});

test('lamp follows the existing night hours in both booking and reschedule services', function (string $startTime, bool $allowed) {
    $slotId = DB::table('time_slots')->insertGetId(['start_time' => $startTime]);
    $input = bookingInput(slot: $slotId);
    $input['facilities']['has_lamp'] = true;
    $booking = app(BookingService::class)->create($input);
    expect($booking->facilities->has_lamp)->toBe($allowed);

    $input['visit_date'] = '2026-12-12';
    $booking = app(RescheduleBookingService::class)->reschedule($booking, $input);
    expect($booking->facilities->has_lamp)->toBe($allowed);
})->with([
    'five in the morning' => ['05:00:00', true],
    'end of night hours' => ['05:59:00', true],
    'start of daytime hours' => ['06:00:00', false],
    'end of daytime hours' => ['17:59:00', false],
    'start of night hours' => ['18:00:00', true],
]);
