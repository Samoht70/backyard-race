<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\Briefing;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BriefingController extends Controller
{
    public function show(): Response
    {
        $event = Event::current();

        Gate::authorize('view', $event);

        return Inertia::render('Briefing', [
            'html' => Briefing::toHtml(Briefing::orDefault($event->briefing)),
        ]);
    }
}
