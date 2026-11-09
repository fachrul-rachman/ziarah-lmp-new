<?php

use App\Http\Controllers\Booking\VisitationPlanController;
use App\Http\Middleware\AuthenticateBurialApi;
use Illuminate\Support\Facades\Route;

Route::post('/visitation-plans', [VisitationPlanController::class, 'create'])
    ->middleware([AuthenticateBurialApi::class, 'throttle:30,1']);
