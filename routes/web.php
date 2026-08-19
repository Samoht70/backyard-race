<?php

use App\Enums\Permission;
use App\Http\Controllers\DesignSystemController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\Manage;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('design-system', DesignSystemController::class)->name('design-system');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('event', [EventController::class, 'show'])->name('event.show');

    Route::middleware('can:'.Permission::ManageEvent->value)
        ->prefix('manage')
        ->name('manage.')
        ->group(function () {
            Route::inertia('/', 'manage/Index')->name('index');

            Route::get('event', [Manage\EventController::class, 'edit'])->name('event.edit');
            Route::put('event', [Manage\EventController::class, 'update'])->name('event.update');
            Route::post('event/advance', Manage\AdvanceEventController::class)->name('event.advance');
        });
});

require __DIR__.'/settings.php';
