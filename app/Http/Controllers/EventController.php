<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function show(Request $request): Response
    {
        $event = Event::query()->firstOrFail();

        Gate::authorize('view', $event);

        return Inertia::render('Event', [
            'event' => new EventResource($event)->resolve(),
            'canRegister' => $event->lifecycle()->allowsRegistration(),
            'isRegistered' => $request->user()?->participant()->exists() === true,
        ]);
    }
}
