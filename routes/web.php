<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Admin\TimeSlotController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\Admin\LotController;
use App\Http\Controllers\Admin\LotImportController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\LotSizeRuleController;
use App\Http\Controllers\Booking\BookingAvailabilityController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Booking\PublicBookingController;
use App\Http\Controllers\Booking\BookingSubmitController;
use App\Http\Controllers\Booking\EventRulesController;
use App\Models\Booking;

Route::get('/', [BookingController::class, 'index'])->name('home');
Route::get('/booking/zones', [BookingController::class, 'zones'])->name('booking.zones');
Route::get('/booking/lot-size-rules', [BookingController::class, 'lotSizeRules'])->name('booking.lot-size-rules');
Route::get('/booking/lots', [BookingAvailabilityController::class, 'lots'])->name('booking.lots');
Route::get('/booking/hidden-facilities', [EventRulesController::class, 'hiddenFacilities'])->name('booking.hidden-facilities');
Route::post('/booking', [BookingSubmitController::class, 'store'])->name('booking.store');
Route::get('/booking/success/{publicToken}', [BookingSubmitController::class, 'success'])->name('booking.success');
Route::get('/booking/pdf/{publicToken}', [BookingSubmitController::class, 'pdf'])->name('booking.pdf');
Route::get('/booking/{publicToken}', [PublicBookingController::class, 'show'])->name('booking.show');
Route::get('/booking/{publicToken}/cancel', [PublicBookingController::class, 'cancelForm'])->name('booking.cancel.form');
Route::post('/booking/{publicToken}/cancel', [PublicBookingController::class, 'cancel'])->name('booking.cancel');
Route::get('/booking/{publicToken}/cancel/success', [PublicBookingController::class, 'cancelSuccess'])->name('booking.cancel.success');
Route::get('/booking/{publicToken}/reschedule', [PublicBookingController::class, 'rescheduleForm'])->name('booking.reschedule.form');
Route::post('/booking/{publicToken}/reschedule', [PublicBookingController::class, 'reschedule'])->name('booking.reschedule');

Route::get('/login', fn () => redirect('/admin/login'))->name('login');

Route::redirect('/admin', '/admin/dashboard');

Route::prefix('admin')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'create'])->name('admin.login');
        Route::post('/login', [AdminAuthController::class, 'store'])->name('admin.login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout');

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/bookings/{booking}', [AdminDashboardController::class, 'showBooking'])->name('admin.bookings.show');
        Route::post('/bookings/{booking}/cancel', [AdminDashboardController::class, 'cancel'])->name('admin.bookings.cancel');
        Route::post('/exports', [AdminDashboardController::class, 'export'])->name('admin.exports.store');
        Route::get('/exports/{exportJob}', [AdminDashboardController::class, 'showExportJob'])->name('admin.exports.show');
        Route::get('/exports/{exportJob}/download', [AdminDashboardController::class, 'downloadExport'])->name('admin.exports.download');
        Route::get('/locations', [LocationController::class, 'index'])->name('admin.locations');
        Route::post('/locations', [LocationController::class, 'store'])->name('admin.locations.store');
        Route::put('/locations/{location}', [LocationController::class, 'update'])->name('admin.locations.update');
        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('admin.locations.destroy');

        Route::post('/locations/{location}/zones', [ZoneController::class, 'store'])->name('admin.zones.store');
        Route::put('/locations/{location}/zones/{zone}', [ZoneController::class, 'update'])->name('admin.zones.update');
        Route::delete('/locations/{location}/zones/{zone}', [ZoneController::class, 'destroy'])->name('admin.zones.destroy');

        Route::post('/lots', [LotController::class, 'store'])->name('admin.lots.store');
        Route::put('/lots/{lot}', [LotController::class, 'update'])->name('admin.lots.update');
        Route::delete('/lots/{lot}', [LotController::class, 'destroy'])->name('admin.lots.destroy');
        Route::post('/lots/bulk-delete', [LotController::class, 'bulkDelete'])->name('admin.lots.bulk-delete');
        Route::get('/zones/{zone}/lots', [LotController::class, 'lotsForZone'])->name('admin.zones.lots');
        Route::post('/lots/import', [LotImportController::class, 'store'])->name('admin.lots.import');
        Route::get('/import-jobs/{importJob}', [LotImportController::class, 'show'])->name('admin.import-jobs.show');

        Route::get('/time-slots', [TimeSlotController::class, 'index'])->name('admin.time-slots');
        Route::post('/time-slots', [TimeSlotController::class, 'store'])->name('admin.time-slots.store');
        Route::post('/time-slots/bulk', [TimeSlotController::class, 'bulkStore'])->name('admin.time-slots.bulk');
        Route::delete('/time-slots/{timeSlot}', [TimeSlotController::class, 'destroy'])->name('admin.time-slots.destroy');

        Route::get('/events', [EventController::class, 'index'])->name('admin.events');
        Route::post('/events', [EventController::class, 'store'])->name('admin.events.store');
        Route::put('/events/{event}', [EventController::class, 'update'])->name('admin.events.update');
        Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('admin.events.destroy');

        Route::get('/settings', [AdminPageController::class, 'settings'])->name('admin.settings');
        Route::post('/settings', [AdminPageController::class, 'updateSettings'])->name('admin.settings.update');
        Route::get('/lot-size-rules', [LotSizeRuleController::class, 'index'])->name('admin.lot-size-rules.index');
        Route::post('/lot-size-rules', [LotSizeRuleController::class, 'store'])->name('admin.lot-size-rules.store');
    });
});

// Local-only preview routes (to iterate on email templates without creating new bookings)
Route::get('/_preview/mail/booking-confirmed/{booking}', function (Booking $booking) {
    abort_unless(app()->environment('local'), 404);

    $booking->loadMissing(['timeSlot', 'location', 'zone', 'lot', 'facilities']);

    return view('mail.booking-confirmed', [
        'booking' => $booking,
    ]);
})
    ->middleware('auth')
    ->name('preview.mail.booking-confirmed');
