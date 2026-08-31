<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrentRoundResource;
use App\Http\Resources\NextRoundResource;
use App\Models\Event;
use App\Services\RaceSchedule\ResolveCurrentRound;
use App\Services\RaceSchedule\ResolveNextRound;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(ResolveCurrentRound $resolveCurrentRound, ResolveNextRound $resolveNextRound): Response {
        $event = Event::currentOrNew();
        $round = $resolveCurrentRound($event);
        $next = $resolveNextRound($event);

        return Inertia::render('manage/Index', [
            'currentRound' => $round === null ? null : new CurrentRoundResource($round)->resolve(),
            'nextRound' => $next === null ? null : new NextRoundResource($next)->resolve(),
        ]);
    }
}
