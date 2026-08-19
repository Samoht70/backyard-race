<?php

use App\Enums\Permission;
use App\Http\Controllers\DesignSystemController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\Manage;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')
    ->name('home');

Route::get('design-system', DesignSystemController::class)
    ->name('design-system');

Route::middleware('auth')
    ->group(function () {
        Route::inertia('dashboard', 'Dashboard')
            ->name('dashboard');

        Route::singleton('event', EventController::class)
            ->only(['show']);

        Route::singleton('registration', RegistrationController::class)
            ->only(['show', 'edit', 'update']);

        Route::middleware('can:'.Permission::ManageEvent->value)
            ->prefix('manage')
            ->name('manage.')
            ->group(function () {
                Route::get('/', Manage\IndexController::class)->name('index');

                Route::singleton('event', Manage\EventController::class)
                    ->only(['edit', 'update']);

                Route::post('event/advance', Manage\AdvanceEventController::class)->name('event.advance');
            });
    });

require __DIR__.'/account.php';
require __DIR__.'/profile.php';
