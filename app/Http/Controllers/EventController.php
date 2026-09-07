<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Http\Resources\EventTotalsResource;
use App\Http\Resources\RoundOutcomeResource;
use App\Http\Resources\StandingResource;
use App\Models\Event;
use App\Models\Standing;
use App\Services\RaceResult\ResolveEventTotals;
use App\Services\RaceResult\ResolveRoundOutcomes;
use App\Services\RaceResult\RoundOutcome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __invoke(
        Request $request,
        ResolveEventTotals $resolveTotals,
        ResolveRoundOutcomes $resolveOutcomes,
    ): Response {
        $event = Event::currentOrNull();
        $visibleEvent = $event !== null && Gate::allows('view', $event) ? $event : null;

        if ($visibleEvent !== null && $visibleEvent->lifecycle()->isOver()) {
            return $this->results($visibleEvent, $resolveTotals, $resolveOutcomes);
        }

        return $this->announcement($request, $visibleEvent);
    }

    private function results(
        Event $event,
        ResolveEventTotals $resolveTotals,
        ResolveRoundOutcomes $resolveOutcomes,
    ): Response {
        return Inertia::render('Results', [
            'event' => [
                'name' => $event->name,
                'finished_at' => $event->finished_at?->format('d/m/Y H:i'),
            ],
            'winners' => $event->standings()
                ->where('rank', 1)
                ->get()
                ->map(fn (Standing $standing): array => new StandingResource($standing, $event)->resolve())
                ->values()
                ->all(),
            'totals' => new EventTotalsResource($resolveTotals($event))->resolve(),
            'rounds' => array_map(
                fn (RoundOutcome $outcome): array => new RoundOutcomeResource($outcome)->resolve(),
                $resolveOutcomes($event),
            ),
        ]);
    }

    private function announcement(Request $request, ?Event $event): Response
    {
        return Inertia::render('Event', [
            'event' => $event === null ? null : new EventResource($event)->resolve(),
            'canRegister' => $event !== null && $event->lifecycle()->allowsRegistration(),
            'isRegistered' => $request->user()?->participant()->exists() === true,
        ]);
    }
}
