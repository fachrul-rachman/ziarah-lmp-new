<?php

use App\Models\User;
use App\Models\WalkIn;
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
            ->has('ethics_image_url'));
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
        'ethics_confirmed' => true,
    ]);

    $walkIn = WalkIn::query()->firstOrFail();

    $response->assertRedirect(route('walk-in.success', $walkIn->public_token));
    expect($walkIn->customer_name)->toBe('Budi Santoso')
        ->and($walkIn->customer_phone)->toBe('6281234567890')
        ->and($walkIn->lot_number)->toBe('A-12')
        ->and($walkIn->ethics_consented_at)->not->toBeNull();

    $this->get(route('walk-in.success', $walkIn->public_token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('walk-in/success')
            ->where('walkIn.customer_name', 'Budi Santoso'));
});

test('walk-in lot number is optional and limited to ten characters', function () {
    $this->post('/walk-in', [
        'customer_name' => 'Budi Santoso',
        'customer_phone' => '081234567890',
        'lot_number' => '12345678901',
        'ethics_confirmed' => true,
    ])->assertSessionHasErrors('lot_number');
});

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
        'ethics_consented_at' => now(),
    ]);

    $this->actingAs($admin)->get('/admin/dashboard?record_type=walk_in')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('recordType', 'walk_in')
            ->where('walkIns.0.customer_name', 'Budi Santoso')
            ->has('bookings', 0));
});
