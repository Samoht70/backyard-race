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
    public function __invoke(Request $request): Response
    {
        $event = Event::query()->first();
        $visibleEvent = $event !== null && Gate::allows('view', $event) ? $event : null;

        return Inertia::render('Event', [
            'event' => $visibleEvent === null ? null : new EventResource($visibleEvent)->resolve(),
            'canRegister' => $visibleEvent !== null && $visibleEvent->lifecycle()->allowsRegistration(),
            'isRegistered' => $request->user()?->participant()->exists() === true,
        ]);
    }
}
