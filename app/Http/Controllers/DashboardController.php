<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\RegistrationStatus;
use App\Http\Requests\RunnerSearchRequest;
use App\Http\Resources\CurrentRoundResource;
use App\Http\Resources\NextRoundResource;
use App\Http\Resources\RunnerSearchResultResource;
use App\Http\Resources\RunnerTallyResource;
use App\Models\Event;
use App\Models\Participant;
use App\Services\RaceBoard\ResolveRunnerSearch;
use App\Services\RaceBoard\ResolveRunnerTally;
use App\Services\RaceSchedule\ResolveCurrentRound;
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
        ResolveCurrentRound $resolveCurrentRound,
        ResolveRunnerTally $resolveRunnerTally,
        ResolveNextRound $resolveNextRound,
    ): Response {
        $event = Event::currentOrNull();

        if ($event === null) {
            return Inertia::render('Dashboard', ['mode' => 'no_event', 'event' => null]);
        }

        $user = $request->user();

        if ($user->can(Permission::ManageEvent->value)) {
            return $event->lifecycle()->isRacing()
                ? $this->renderSearch($event, $searchRequest, $search, $resolveCurrentRound)
                : $this->renderMode('manager_idle', $event);
        }

        $participant = $user->participant;

        if ($participant === null) {
            return $this->renderMode('no_registration', $event);
        }

        if ($participant->status !== RegistrationStatus::Confirmed) {
            return $this->renderMode('runner_waiting', $event);
        }

        return $this->renderRunnerStatus(
            $event,
            $participant,
            $resolveCurrentRound,
            $resolveRunnerTally,
            $resolveNextRound,
        );
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
                ->map(fn (Participant $runner): array => new RunnerSearchResultResource($runner, $event)->resolve())
                ->values()
                ->all(),
        ]);
    }

    private function renderRunnerStatus(
        Event $event,
        Participant $participant,
        ResolveCurrentRound $resolveCurrentRound,
        ResolveRunnerTally $resolveRunnerTally,
        ResolveNextRound $resolveNextRound,
    ): Response {
        $participant->loadMissing('laps.round');

        return Inertia::render('Dashboard', [
            'mode' => 'runner_active',
            'event' => $this->eventSummary($event),
            'currentRound' => $this->currentRound($event, $resolveCurrentRound),
            'nextRound' => $this->nextRound($event, $participant, $resolveNextRound),
            'tally' => new RunnerTallyResource($resolveRunnerTally($event))->resolve(),
            'runner' => new RunnerSearchResultResource($participant, $event)->resolve(),
        ]);
    }

    /**
     * @return array<array-key, mixed>|null
     */
    private function nextRound(
        Event $event,
        Participant $participant,
        ResolveNextRound $resolveNextRound,
    ): ?array {
        if (! $participant->isRunning() || ! $event->lifecycle()->announcesNextRound()) {
            return null;
        }

        $round = $resolveNextRound($event);

        return $round === null ? null : new NextRoundResource($round)->resolve();
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
