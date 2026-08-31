<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrentRoundResource;
use App\Http\Resources\NextRoundResource;
use App\Http\Resources\RoundRunnerResource;
use App\Models\Event;
use App\Models\Participant;
use App\Services\RaceBoard\ResolveRoundBoard;
use App\Services\RaceBoard\RoundBoard;
use App\Services\RaceSchedule\ResolveCurrentRound;
use App\Services\RaceSchedule\ResolveNextRound;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(
        ResolveCurrentRound $resolveCurrentRound,
        ResolveNextRound $resolveNextRound,
        ResolveRoundBoard $resolveRoundBoard,
    ): Response {
        $event = Event::currentOrNew();
        $round = $resolveCurrentRound($event);
        $next = $resolveNextRound($event);
        $board = $resolveRoundBoard($event, $round);

        return Inertia::render('manage/Index', [
            'currentRound' => $round === null ? null : new CurrentRoundResource($round)->resolve(),
            'nextRound' => $next === null ? null : new NextRoundResource($next)->resolve(),
            'roundRunners' => $board === null ? [] : $this->runnersOf($board, $event->lap_distance_meters),
        ]);
    }

    /**
     * @return array<int, array<array-key, mixed>>
     */
    private function runnersOf(RoundBoard $board, ?int $lapDistanceMeters): array
    {
        return $board->runners
            ->map(fn (Participant $runner): array => new RoundRunnerResource(
                $runner,
                $board->round,
                $lapDistanceMeters,
            )->resolve())
            ->values()
            ->all();
    }
}
