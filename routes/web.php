<?php

use App\Enums\Permission;
use App\Http\Controllers\DesignSystemController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('design-system', DesignSystemController::class)->name('design-system');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::middleware('can:'.Permission::ManageEvent->value)
        ->prefix('manage')
        ->name('manage.')
        ->group(function () {
            Route::inertia('/', 'manage/Index')->name('index');
        });
});

require __DIR__.'/settings.php';
