<?php

use App\Models\User;
use App\Models\WalkIn;
use App\Services\BookingLeadTimeService;
use App\Services\ReportScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Schema::dropIfExists('walk_ins');
    Schema::dropIfExists('settings');
    Schema::dropIfExists('users');

    Schema::create('walk_ins', function (Blueprint $table) {
        $table->id();
        $table->string('public_token')->unique();
        $table->string('customer_name');
        $table->string('customer_phone', 15);
        $table->string('lot_number', 10)->nullable();
        $table->string('booking_h2_reason', 50)->nullable();
        $table->timestamp('ethics_consented_at');
        $table->timestamps();
    });

    Schema::create('settings', function (Blueprint $table) {
        $table->string('key')->primary();
        $table->text('value');
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
});

test('walk-in page is publicly accessible without a navigation link', function () {
    $this->get('/walk-in')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('walk-in/index')
            ->has('ethics_image_url')
            ->has('ethics_pdf_url'));
});

test('walk-in requires ethics consent before data is stored', function () {
    $this->post('/walk-in', [
        'customer_name' => 'Budi Santoso',
        'customer_phone' => '081234567890',
        'lot_number' => 'A-12',
        'ethics_confirmed' => false,
    ])->assertSessionHasErrors('ethics_confirmed');

    $this->assertDatabaseCount('walk_ins', 0);
});

test('walk-in stores normalized data and redirects to a simple success page', function () {
    $response = $this->post('/walk-in', [
        'customer_name' => '  Budi Santoso  ',
        'customer_phone' => '0812-3456-7890',
        'lot_number' => ' A-12 ',
        'booking_h2_reason' => 'Keperluan mendadak',
        'ethics_confirmed' => true,
    ]);

    $walkIn = WalkIn::query()->firstOrFail();

    $response->assertRedirect(route('walk-in.success', $walkIn->public_token));
    expect($walkIn->customer_name)->toBe('Budi Santoso')
        ->and($walkIn->customer_phone)->toBe('6281234567890')
        ->and($walkIn->lot_number)->toBe('A-12')
        ->and($walkIn->booking_h2_reason)->toBe('Keperluan mendadak')
        ->and($walkIn->ethics_consented_at)->not->toBeNull();

    $this->get(route('walk-in.success', $walkIn->public_token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('walk-in/success')
            ->where('walkIn.customer_name', 'Budi Santoso'));
});

test('walk-in rejects phone numbers with invalid length or prefix', function (string $phone) {
    $this->post('/walk-in', [
        'customer_name' => 'Budi Santoso',
        'customer_phone' => $phone,
        'booking_h2_reason' => 'Tidak tahu',
        'ethics_confirmed' => true,
    ])->assertSessionHasErrors('customer_phone');

    $this->assertDatabaseCount('walk_ins', 0);
})->with([
    'fewer than 10 digits' => '081234567',
    'more than 13 digits' => '08123456789012',
    'starts with 02' => '0212345678',
    'starts with a single 2' => '2123456789',
    'contains letters' => '08abc12345678',
]);

test('walk-in accepts phone numbers at the minimum and maximum length', function (string $phone) {
    $this->post('/walk-in', [
        'customer_name' => 'Budi Santoso',
        'customer_phone' => $phone,
        'booking_h2_reason' => 'Tidak tahu',
        'ethics_confirmed' => true,
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseCount('walk_ins', 1);
})->with([
    '10 digits' => '0812345678',
    '13 digits' => '6281234567890',
]);

test('walk-in lot number is optional and limited to ten characters', function () {
    $this->post('/walk-in', [
        'customer_name' => 'Budi Santoso',
        'customer_phone' => '081234567890',
        'lot_number' => '12345678901',
        'ethics_confirmed' => true,
    ])->assertSessionHasErrors('lot_number');
});

test('walk-in requires a valid reason for not using regular booking', function (?string $reason) {
    $this->post('/walk-in', [
        'customer_name' => 'Budi Santoso',
        'customer_phone' => '081234567890',
        'booking_h2_reason' => $reason,
        'ethics_confirmed' => true,
    ])->assertSessionHasErrors('booking_h2_reason');

    $this->assertDatabaseCount('walk_ins', 0);
})->with([
    'empty' => '',
    'unknown option' => 'Alasan lain',
]);

test('only an authenticated admin can replace the ethics image', function () {
    Storage::fake('public');

    $this->post('/admin/settings', [
        'discord_webhook_url' => '',
        'discord_notification_time' => '08:00',
        'ethics_image' => UploadedFile::fake()->image('etika.jpg', 1200, 800),
    ])->assertRedirect('/login');

    $admin = User::query()->create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($admin)->post('/admin/settings', [
        'discord_webhook_url' => '',
        'discord_notification_time' => '08:00',
        'ethics_image' => UploadedFile::fake()->image('etika.jpg', 1200, 800),
    ])->assertSessionHasNoErrors();

    $path = DB::table('settings')->where('key', 'ethics_image_path')->value('value');
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

test('ethics image upload rejects files larger than two megabytes', function () {
    Storage::fake('public');

    $admin = User::query()->create([
        'name' => 'Admin',
        'email' => 'admin-upload-limit@example.com',
        'password' => bcrypt('password'),
    ]);

    $oversizedImage = UploadedFile::fake()
        ->image('etika-besar.jpg', 1200, 800)
        ->size(2500);

    $this->actingAs($admin)->post('/admin/settings', [
        'discord_webhook_url' => '',
        'discord_notification_time' => '08:00',
        'ethics_image' => $oversizedImage,
    ])->assertSessionHasErrors('ethics_image');

    expect(DB::table('settings')->where('key', 'ethics_image_path')->exists())->toBeFalse();
});

test('admin can upload an ethics pdf up to four megabytes', function () {
    Storage::fake('public');

    $admin = User::query()->create([
        'name' => 'Admin PDF',
        'email' => 'admin-pdf@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($admin)->post('/admin/settings', [
        'discord_webhook_url' => '',
        'discord_notification_time' => '08:00',
        'ethics_pdf' => UploadedFile::fake()->create('etika.pdf', 4000, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $path = DB::table('settings')->where('key', 'ethics_pdf_path')->value('value');
    expect($path)->toEndWith('.pdf');
    Storage::disk('public')->assertExists($path);
});

test('admin can save the booking notice and its image', function () {
    Storage::fake('public');

    $admin = User::query()->create([
        'name' => 'Admin Notice',
        'email' => 'admin-notice@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($admin)->post('/admin/settings', [
        'discord_webhook_url' => '',
        'discord_notification_time' => '08:00',
        'booking_notice_enabled' => true,
        'booking_notice_title' => 'Perubahan Jalur Tangerang',
        'booking_notice_body' => 'Gunakan pintu masuk sementara.',
        'booking_notice_start_date' => '2026-07-29',
        'booking_notice_end_date' => '2026-08-05',
        'booking_notice_image' => UploadedFile::fake()->image('jalur.png', 1200, 800)->size(3000),
    ])->assertSessionHasNoErrors();

    expect(DB::table('settings')->where('key', 'booking_notice_enabled')->value('value'))->toBe('1')
        ->and(DB::table('settings')->where('key', 'booking_notice_title')->value('value'))->toBe('Perubahan Jalur Tangerang')
        ->and(DB::table('settings')->where('key', 'booking_notice_image_path')->value('value'))->toEndWith('.png');
});

test('admin can configure booking lead time and multiple report times', function () {
    $admin = User::query()->create([
        'name' => 'Admin Schedule',
        'email' => 'admin-schedule@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($admin)->post('/admin/settings', [
        'discord_webhook_url' => '',
        'discord_notification_times' => ['08:00', '14:00'],
        'booking_minimum_value' => 18,
        'booking_minimum_unit' => 'hours',
    ])->assertSessionHasNoErrors();

    expect(app(ReportScheduleService::class)->times())->toBe(['08:00', '14:00'])
        ->and(app(BookingLeadTimeService::class)->rule())->toBe(['value' => 18, 'unit' => 'hours']);
});

test('booking lead time supports exact hours and calendar days', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-14 16:00:00', 'Asia/Jakarta'));
    DB::table('settings')->insert([
        ['key' => 'booking_minimum_value', 'value' => '18'],
        ['key' => 'booking_minimum_unit', 'value' => 'hours'],
    ]);

    $leadTime = app(BookingLeadTimeService::class);
    expect($leadTime->allows('2026-08-15', '09:59'))->toBeFalse()
        ->and($leadTime->allows('2026-08-15', '10:00'))->toBeTrue();

    DB::table('settings')->where('key', 'booking_minimum_value')->update(['value' => '2']);
    DB::table('settings')->where('key', 'booking_minimum_unit')->update(['value' => 'days']);

    expect($leadTime->allows('2026-08-15', '19:00'))->toBeFalse()
        ->and($leadTime->allows('2026-08-16', '08:00'))->toBeTrue();

    CarbonImmutable::setTestNow();
});

test('booking notice is shown during its date range and hidden after it expires', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-29 12:00:00', 'Asia/Jakarta'));
    Storage::fake('public');
    Storage::disk('public')->put('booking-notices/jalur.jpg', 'image');

    Schema::create('locations', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
    Schema::create('time_slots', function (Blueprint $table) {
        $table->id();
        $table->time('start_time');
        $table->timestamps();
    });

    DB::table('settings')->insert([
        ['key' => 'booking_notice_enabled', 'value' => '1'],
        ['key' => 'booking_notice_title', 'value' => 'Perubahan Jalur'],
        ['key' => 'booking_notice_body', 'value' => 'Gunakan pintu sementara.'],
        ['key' => 'booking_notice_start_date', 'value' => '2026-07-01'],
        ['key' => 'booking_notice_end_date', 'value' => '2026-08-05'],
        ['key' => 'booking_notice_image_path', 'value' => 'booking-notices/jalur.jpg'],
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('booking/index')
            ->where('booking_notice.title', 'Perubahan Jalur')
            ->where('booking_notice.download_url', Storage::disk('public')->url('booking-notices/jalur.jpg')));

    DB::table('settings')->where('key', 'booking_notice_end_date')->update(['value' => '2026-07-20']);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('booking_notice', null));

    CarbonImmutable::setTestNow();
});

test('regular booking also requires ethics consent', function () {
    $this->post('/booking', [])->assertSessionHasErrors('ethics_confirmed');
});

test('admin can view walk-in data separately from bookings', function () {
    $admin = User::query()->create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
    ]);
    WalkIn::query()->create([
        'public_token' => (string) Str::ulid(),
        'customer_name' => 'Budi Santoso',
        'customer_phone' => '6281234567890',
        'lot_number' => 'A-12',
        'booking_h2_reason' => 'Tahu tapi lupa',
        'ethics_consented_at' => now(),
    ]);

    $this->actingAs($admin)->get('/admin/dashboard?record_type=walk_in')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('recordType', 'walk_in')
            ->where('walkIns.0.customer_name', 'Budi Santoso')
            ->where('walkIns.0.booking_h2_reason', 'Tahu tapi lupa')
            ->has('bookings', 0));
});
