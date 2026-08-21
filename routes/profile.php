<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->group(function () {
        Route::singleton('profile', ProfileController::class)
            ->destroyable()
            ->only(['edit', 'update', 'destroy']);
    });
