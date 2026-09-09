<?php

namespace App\Http\Controllers;

use App\Http\Resources\StandingResource;
use App\Models\Event;
use App\Models\Standing;
use Inertia\Inertia;
use Inertia\Response;

class StandingController extends Controller
{
    public function index(): Response
    {
        $event = Event::currentOrNull();

        abort_if($event === null || ! $event->lifecycle()->isOver(), 404);

        return Inertia::render('Standings', [
            'event' => [
                'name' => $event->name,
                'finished_at' => $event->finished_at?->format('d/m/Y H:i'),
            ],
            'standings' => $event->standings
                ->map(fn (Standing $standing): array => new StandingResource($standing, $event)->resolve())
                ->values()
                ->all(),
        ]);
    }
}
