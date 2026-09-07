<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunnerSearchRequest;
use App\Http\Resources\RunnerSearchResultResource;
use App\Http\Resources\RunnerTallyResource;
use App\Models\Event;
use App\Models\Participant;
use App\Services\RaceBoard\ResolveRunnerSearch;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RunnersController extends Controller
{
    public function __invoke(RunnerSearchRequest $request, ResolveRunnerSearch $search): Response
    {
        $event = Event::current();

        Gate::authorize('view', $event);

        $term = $request->term();
        $matches = $search($event, $term);

        return Inertia::render('Runners', [
            'query' => $term,
            'tally' => new RunnerTallyResource($matches->tally)->resolve(),
            'runners' => $matches->runners
                ->map(fn (Participant $runner): array => new RunnerSearchResultResource(
                    $runner,
                    $event->lap_distance_meters,
                )->resolve())
                ->values()
                ->all(),
        ]);
    }
}
