<?php

use App\Enums\Permission;
use App\Http\Controllers\BriefingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignSystemController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\Manage;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', EventController::class)
    ->name('home');

Route::get('design-system', DesignSystemController::class)
    ->name('design-system');

Route::resource('documents', DocumentController::class)
    ->only(['index']);

Route::middleware('auth')
    ->group(function () {
        Route::get('dashboard', DashboardController::class)
            ->name('dashboard');

        Route::singleton('briefing', BriefingController::class)
            ->only(['show']);

        Route::singleton('registration', RegistrationController::class)
            ->only(['show', 'edit', 'update']);

        Route::prefix('manage')
            ->name('manage.')
            ->group(function () {
                Route::middleware('can:'.Permission::ManageEvent->value)
                    ->group(function () {
                        Route::get('/', Manage\IndexController::class)->name('index');

                        Route::singleton('event', Manage\EventController::class)
                            ->only(['edit', 'update']);

                        Route::post('event/advance', Manage\AdvanceEventController::class)->name('event.advance');
                        Route::post('event/revert', Manage\RevertEventController::class)->name('event.revert');
                    });

                Route::middleware('can:'.Permission::ManageDocuments->value)
                    ->group(function () {
                        Route::singleton('briefing', Manage\BriefingController::class)
                            ->only(['edit', 'update']);

                        Route::resource('documents', Manage\DocumentController::class)
                            ->only(['index', 'store', 'destroy']);
                    });

                Route::middleware('can:'.Permission::ManageParticipants->value)
                    ->group(function () {
                        Route::resource('registrations', Manage\RegistrationController::class)
                            ->only(['index', 'edit', 'update'])
                            ->parameters(['registrations' => 'participant']);

                        Route::post('registrations/{participant}/transition', Manage\RegistrationTransitionController::class)
                            ->name('registrations.transition');
                    });
            });
    });

require __DIR__.'/account.php';
require __DIR__.'/profile.php';
