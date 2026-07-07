<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Cms\BarangayController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])
        ->name('authenticate');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::view('/dashboard', 'dashboard.index', [
        'page_title' => 'Disaster Operations Dashboard',
        'page_description' => 'City social welfare assistance monitoring and response workflow.',
    ])->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('disaster')->name('disaster.')->group(function () {
        Route::view('/tciss-masterlist', 'disaster.tciss', [
            'page_title' => 'TCISS / Masterlist Verification',
            'page_description' => 'Verify affected families before DAFAC intake.',
        ])->name('tciss.index');

        Route::view('/dafac-intake', 'disaster.dafac', [
            'page_title' => 'DAFAC Intake',
            'page_description' => 'Encode household details and family composition.',
        ])->name('dafac.index');

        Route::view('/duplicate-checking', 'disaster.duplicates', [
            'page_title' => 'Duplicate Checking',
            'page_description' => 'Review, merge, or approve possible duplicate household records.',
        ])->name('duplicates.index');

        Route::view('/validation', 'disaster.validation', [
            'page_title' => 'Validation',
            'page_description' => 'Validate housing condition, ownership, and supporting documents.',
        ])->name('validation.index');

        Route::view('/payroll-preparation', 'disaster.payroll', [
            'page_title' => 'Payroll Preparation',
            'page_description' => 'Prepare the final cleaned list of validated families.',
        ])->name('payroll.index');

        Route::view('/payouts', 'disaster.payouts', [
            'page_title' => 'Payout Scheduling',
            'page_description' => 'Schedule assistance distribution and track releases.',
        ])->name('payouts.index');

        Route::view('/post-payout-requirements', 'disaster.requirements', [
            'page_title' => 'Post-Payout Requirements',
            'page_description' => 'Track BFP certificates, barangay certifications, and uploaded files.',
        ])->name('requirements.index');

        Route::view('/reports', 'disaster.reports', [
            'page_title' => 'Reports',
            'page_description' => 'Generate DAFAC, payroll, payout, and requirements reports.',
        ])->name('reports.index');
    });

    Route::prefix('superadmin')->name('superadmin.')->group(function () {
        Route::view('/dashboard', 'dashboard.index', [
            'page_title' => 'Disaster Operations Dashboard',
            'page_description' => 'City social welfare assistance monitoring and response workflow.',
        ])->name('dashboard');

        Route::prefix('cms')->group(function () {
            Route::get('/barangays/data', [BarangayController::class, 'data'])->name('barangay.data');
            Route::resource('barangay', BarangayController::class)->only([
                'index',
                'store',
                'update',
                'destroy',
            ]);
        });
    });
});
