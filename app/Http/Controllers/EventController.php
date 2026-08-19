<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function show(): Response
    {
        $event = Event::query()->firstOrFail();

        Gate::authorize('view', $event);

        return Inertia::render('Event', [
            'event' => (new EventResource($event))->resolve(),
            'canRegister' => $event->lifecycle()->allowsRegistration(),
        ]);
    }
}
