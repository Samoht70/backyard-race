<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\BibNumber;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $event = Event::query()->first();
        $participant = $request->user()?->participant;

        return Inertia::render('Dashboard', [
            'event' => $event === null ? null : [
                'name' => $event->name,
                'status' => $event->status->value,
            ],
            'registration' => $participant === null ? null : [
                'status' => $participant->status->value,
                'status_label' => $participant->status->label(),
                'bib_label' => BibNumber::label($participant->bib_number),
            ],
        ]);
    }
}
