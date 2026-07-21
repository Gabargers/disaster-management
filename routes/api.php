<?php

use App\Http\Controllers\Api\PersonAffectedController;
use Illuminate\Support\Facades\Route;

Route::post('/person-affecteds', PersonAffectedController::class)
    ->middleware('system.api.token')
    ->name('api.person-affecteds.store');
