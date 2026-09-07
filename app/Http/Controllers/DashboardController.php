<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\RegistrationStatus;
use App\Http\Requests\RunnerSearchRequest;
use App\Http\Resources\CurrentRoundResource;
use App\Http\Resources\RunnerSearchResultResource;
use App\Http\Resources\RunnerTallyResource;
use App\Models\Event;
use App\Models\Participant;
use App\Services\RaceBoard\ResolveRunnerSearch;
use App\Services\RaceBoard\ResolveRunnerTally;
use App\Services\RaceSchedule\ResolveCurrentRound;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        RunnerSearchRequest $searchRequest,
        ResolveRunnerSearch $search,
        ResolveCurrentRound $resolveCurrentRound,
        ResolveRunnerTally $resolveRunnerTally,
    ): Response {
        $event = Event::currentOrNull();

        if ($event === null) {
            return Inertia::render('Dashboard', ['mode' => 'no_event', 'event' => null]);
        }

        $user = $request->user();
        $isRacing = $event->lifecycle()->isRacing();

        if ($user->can(Permission::ManageEvent->value)) {
            return $isRacing
                ? $this->renderSearch($event, $searchRequest, $search, $resolveCurrentRound)
                : $this->renderMode('manager_idle', $event);
        }

        $participant = $user->participant;

        if ($participant === null) {
            return $this->renderMode('no_registration', $event);
        }

        if ($isRacing && $participant->status === RegistrationStatus::Confirmed) {
            return $this->renderRunnerStatus(
                $event,
                $participant,
                $resolveCurrentRound,
                $resolveRunnerTally,
            );
        }

        return $this->renderMode('runner_waiting', $event);
    }

    private function renderSearch(
        Event $event,
        RunnerSearchRequest $request,
        ResolveRunnerSearch $search,
        ResolveCurrentRound $resolveCurrentRound,
    ): Response {
        $term = $request->term();
        $matches = $search($event, $term);

        return Inertia::render('Dashboard', [
            'mode' => 'manager_search',
            'event' => $this->eventSummary($event),
            'query' => $term,
            'currentRound' => $this->currentRound($event, $resolveCurrentRound),
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

    private function renderRunnerStatus(
        Event $event,
        Participant $participant,
        ResolveCurrentRound $resolveCurrentRound,
        ResolveRunnerTally $resolveRunnerTally,
    ): Response {
        $participant->loadMissing('laps.round');

        return Inertia::render('Dashboard', [
            'mode' => 'runner_active',
            'event' => $this->eventSummary($event),
            'currentRound' => $this->currentRound($event, $resolveCurrentRound),
            'tally' => new RunnerTallyResource($resolveRunnerTally($event))->resolve(),
            'runner' => new RunnerSearchResultResource($participant, $event->lap_distance_meters)->resolve(),
        ]);
    }

    /**
     * @return array<array-key, mixed>|null
     */
    private function currentRound(Event $event, ResolveCurrentRound $resolveCurrentRound): ?array
    {
        $round = $resolveCurrentRound($event);

        return $round === null ? null : new CurrentRoundResource($round)->resolve();
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
