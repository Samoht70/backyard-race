<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrentRoundResource;
use App\Models\Event;
use App\Services\RaceSchedule\ResolveCurrentRound;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(ResolveCurrentRound $resolveCurrentRound): Response
    {
        $round = $resolveCurrentRound(Event::currentOrNew());

        return Inertia::render('manage/Index', [
            'currentRound' => $round === null ? null : new CurrentRoundResource($round)->resolve(),
        ]);
    }
}
