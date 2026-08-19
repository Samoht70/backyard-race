<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrationStoreRequest;
use App\Http\Requests\RegistrationUpdateRequest;
use App\Http\Resources\EventResource;
use App\Http\Resources\ParticipantResource;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->participant !== null) {
            return to_route('registration.show');
        }

        $event = Event::query()->firstOrFail();

        Gate::authorize('create', [Participant::class, $event]);

        return Inertia::render('registration/Create', [
            'event' => new EventResource($event)->resolve(),
        ]);
    }

    public function store(RegistrationStoreRequest $request): RedirectResponse
    {
        $participant = new Participant($request->validated());
        $participant->event()->associate(Event::query()->firstOrFail());
        $participant->user()->associate($request->user());
        $participant->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('registration.stored')]);

        return to_route('registration.show');
    }

    public function show(Request $request): Response|RedirectResponse
    {
        $participant = $request->user()?->participant;

        if ($participant === null) {
            return to_route('registration.create');
        }

        Gate::authorize('view', $participant);

        return Inertia::render('registration/Show', [
            'registration' => new ParticipantResource($participant)->resolve(),
            'canEdit' => Gate::allows('update', $participant),
        ]);
    }

    public function edit(Request $request): Response|RedirectResponse
    {
        $participant = $request->user()?->participant;

        if ($participant === null) {
            return to_route('registration.create');
        }

        Gate::authorize('update', $participant);

        return Inertia::render('registration/Edit', [
            'registration' => new ParticipantResource($participant)->resolve(),
        ]);
    }

    public function update(RegistrationUpdateRequest $request): RedirectResponse
    {
        $request->participant()->fill($request->validated())->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('registration.updated')]);

        return to_route('registration.show');
    }
}
