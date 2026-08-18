<?php

namespace App\Http\Controllers;

use App\Enums\RunnerStatus;
use Inertia\Inertia;
use Inertia\Response;

class DesignSystemController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('DesignSystem', [
            'statuses' => RunnerStatus::options(),
        ]);
    }
}
