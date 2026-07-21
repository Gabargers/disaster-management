<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\AccountManagementController;
use App\Http\Controllers\Auth\ActivityLogController;
use App\Http\Controllers\Cms\BarangayController;
use App\Http\Controllers\Disaster\TcissMasterlistController;
use App\Http\Controllers\Disaster\EvacuationCenterController;
use App\Http\Controllers\Disaster\DafacIntakeController;
use App\Http\Controllers\Disaster\DisasterWorkflowController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])
        ->name('authenticate');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DisasterWorkflowController::class, 'dashboard'])->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::controller(AccountManagementController::class)
        ->prefix('accounts')
        ->name('accounts.')
        ->middleware('role:admin|superadmin')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/data', 'data')->name('data');
            Route::post('/', 'store')->name('store');
            Route::put('/{account}', 'update')->name('update');
            Route::delete('/{account}', 'destroy')->name('destroy');
        });

    Route::controller(ActivityLogController::class)
        ->prefix('activity-logs')
        ->name('activity-logs.')
        ->middleware('role:superadmin')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/data', 'data')->name('data');
        });

    Route::prefix('disaster')->name('disaster.')->group(function () {
        Route::get('/tciss-masterlist', [TcissMasterlistController::class, 'index'])->middleware('permission:manage tciss masterlist')->name('tciss.index');
        Route::get('/tciss-masterlist/{record}/full-details', [TcissMasterlistController::class, 'fullDetails'])->middleware('permission:manage tciss masterlist')->name('tciss.full-details');
        Route::patch('/tciss-masterlist/{record}/verify', [TcissMasterlistController::class, 'verify'])
            ->middleware('permission:manage tciss masterlist')->name('tciss.verify');
        Route::match(['post','put'], '/tciss-masterlist/{record}/evacuation-center-assignment', [TcissMasterlistController::class, 'assignEvacuationCenter'])
            ->middleware(['permission:manage tciss masterlist', 'permission:evacuation_center.assign_family'])->name('tciss.assign-evacuation-center');
        Route::get('/tciss-masterlist/documents/{document}', [TcissMasterlistController::class, 'document'])
            ->middleware(['signed', 'permission:manage tciss masterlist'])->name('tciss.documents.show');

        Route::controller(DafacIntakeController::class)->middleware('permission:manage dafac intake')->group(function () {
            Route::get('/dafac-intake', 'index')->name('dafac.index');
            Route::post('/dafac-intake', 'store')->name('dafac.store');
            Route::get('/dafac-intake/{dafacRecord}', 'show')->name('dafac.show');
        });

        Route::get('/affected-families/{family}', [DisasterWorkflowController::class, 'show'])->name('families.show');
        Route::get('/barangays/{barangay}/evacuation-centers', [EvacuationCenterController::class, 'centersForBarangay'])->name('barangays.evacuation-centers');
        Route::get('/duplicate-checking', [DisasterWorkflowController::class, 'duplicates'])->middleware('permission:resolve duplicate checks')->name('duplicates.index');
        Route::post('/duplicate-checking/{family}/resolve', [DisasterWorkflowController::class, 'resolveDuplicate'])->middleware('permission:resolve duplicate checks')->name('duplicates.resolve');
        Route::get('/validation', [DisasterWorkflowController::class, 'validations'])->middleware('permission:manage validation records')->name('validation.index');
        Route::post('/validation/{family}', [DisasterWorkflowController::class, 'validateFamily'])->middleware('permission:manage validation records')->name('validation.store');
        Route::get('/payroll-preparation', [DisasterWorkflowController::class, 'payroll'])->middleware('permission:prepare payroll list')->name('payroll.index');
        Route::get('/payroll-preparation/releases/{release}/photo', [DisasterWorkflowController::class, 'payrollPhoto'])->middleware('permission:prepare payroll list')->name('payroll.photo');
        Route::post('/payroll-preparation/families/{family}/requirements', [DisasterWorkflowController::class, 'payrollRequirements'])->middleware('permission:prepare payroll list')->name('payroll.requirements');
        Route::post('/payroll-preparation', [DisasterWorkflowController::class, 'payrollAction'])->middleware('permission:prepare payroll list')->name('payroll.action');

        Route::controller(EvacuationCenterController::class)->middleware('permission:manage payout schedules')->group(function () {
            Route::get('/payouts', 'index')->name('payouts.index');
            Route::post('/payouts/evacuation-centers', 'store')->name('payouts.centers.store');
            Route::put('/payouts/evacuation-centers/{center}', 'update')->name('payouts.centers.update');
            Route::get('/payouts/evacuation-centers/{center}', 'show')->name('payouts.centers.show');
            Route::get('/payouts/evacuation-centers/{center}/families', 'families')->name('payouts.centers.families');
            Route::get('/payouts/evacuation-centers/{center}/families/{family}/payout-details', 'payoutDetails')->name('payouts.centers.families.payout-details');
            Route::patch('/payouts/evacuation-centers/{center}/families/{family}/housing-condition', 'updateHousingCondition')->name('payouts.centers.families.housing-condition');
            Route::get('/payouts/releases/{release}/photo', 'photo')->name('payouts.releases.photo');
            Route::get('/payouts/evacuation-centers/{center}/available-families', 'availableFamilies')->name('payouts.centers.available-families');
            Route::post('/payouts/evacuation-centers/{center}/assign-families', 'assign')->name('payouts.centers.assign');
            Route::post('/payouts/releases/{release}/mark-released', 'release')->name('payouts.releases.release');
        });
        Route::match(['post', 'patch'], '/payouts/evacuation-centers/{center}/payout-availability', [EvacuationCenterController::class, 'availability'])
            ->middleware('permission:manage payout availability')->name('payouts.centers.availability');

        Route::get('/post-payout-requirements', [DisasterWorkflowController::class, 'requirements'])->middleware('permission:manage post payout requirements')->name('requirements.index');
        Route::post('/post-payout-requirements/{requirement}', [DisasterWorkflowController::class, 'verifyRequirements'])->middleware('permission:manage post payout requirements')->name('requirements.verify');
        Route::get('/reports', [DisasterWorkflowController::class, 'reports'])->middleware('permission:view disaster reports')->name('reports.index');
    });

    Route::prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('/dashboard', [DisasterWorkflowController::class, 'dashboard'])->name('dashboard');

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
