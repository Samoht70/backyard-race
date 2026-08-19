<?php

use App\Http\Controllers\Auth\AccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest', 'throttle:registration'])
    ->group(function () {
        Route::singleton('account', AccountController::class)
            ->creatable()
            ->only(['create', 'store', 'edit', 'update', 'show']);
    });
