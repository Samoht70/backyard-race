<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\RegistrationStatus;
use App\Http\Requests\RunnerSearchRequest;
use App\Http\Resources\NextRoundResource;
use App\Http\Resources\RunnerSearchResultResource;
use App\Http\Resources\RunnerTallyResource;
use App\Models\Event;
use App\Models\Participant;
use App\Services\RaceBoard\ResolveRunnerSearch;
use App\Services\RaceSchedule\ResolveNextRound;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        RunnerSearchRequest $searchRequest,
        ResolveRunnerSearch $search,
        ResolveNextRound $resolveNextRound,
    ): Response {
        $event = Event::currentOrNull();

        if ($event === null) {
            return Inertia::render('Dashboard', ['mode' => 'no_event', 'event' => null]);
        }

        $user = $request->user();
        $isRacing = $event->lifecycle()->isRacing();

        if ($user->can(Permission::ManageEvent->value)) {
            return $isRacing
                ? $this->renderSearch($event, $searchRequest, $search, $resolveNextRound)
                : $this->renderMode('manager_idle', $event);
        }

        $participant = $user->participant;

        if ($participant === null) {
            return $this->renderMode('no_registration', $event);
        }

        if ($isRacing && $participant->status === RegistrationStatus::Confirmed) {
            return $this->renderRunnerStatus($event, $participant);
        }

        return $this->renderMode('runner_waiting', $event);
    }

    private function renderSearch(
        Event $event,
        RunnerSearchRequest $request,
        ResolveRunnerSearch $search,
        ResolveNextRound $resolveNextRound,
    ): Response {
        $term = $request->term();
        $matches = $search($event, $term);
        $next = $resolveNextRound($event);

        return Inertia::render('Dashboard', [
            'mode' => 'manager_search',
            'event' => $this->eventSummary($event),
            'query' => $term,
            'tally' => new RunnerTallyResource($matches->tally)->resolve(),
            'runners' => $matches->runners
                ->map(fn (Participant $runner): array => new RunnerSearchResultResource(
                    $runner,
                    $event->lap_distance_meters,
                )->resolve())
                ->values()
                ->all(),
            'nextRound' => $next === null ? null : new NextRoundResource($next)->resolve(),
        ]);
    }

    private function renderRunnerStatus(Event $event, Participant $participant): Response
    {
        $participant->loadMissing('laps.round');

        return Inertia::render('Dashboard', [
            'mode' => 'runner_active',
            'event' => $this->eventSummary($event),
            'runner' => new RunnerSearchResultResource($participant, $event->lap_distance_meters)->resolve(),
        ]);
    }

    private function renderMode(string $mode, Event $event): Response
    {
        return Inertia::render('Dashboard', [
            'mode' => $mode,
            'event' => $this->eventSummary($event),
        ]);
    }

    /**
     * @return array{name: string|null, status: string}
     */
    private function eventSummary(Event $event): array
    {
        return ['name' => $event->name, 'status' => $event->status->value];
    }
}
