<?php

use App\Http\Controllers\Api\PersonAffectedController;
use Illuminate\Support\Facades\Route;

Route::post('/person-affecteds', PersonAffectedController::class)
    ->middleware([
        'api.audit',
        'system.api.token',
        'throttle:person-affected-api',
        'api.bounded-json',
    ])
    ->name('api.person-affecteds.store');
