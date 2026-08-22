<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

abstract class Controller
{
    protected function flashSuccess(string $message): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);
    }
}
