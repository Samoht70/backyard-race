<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrationUpdateRequest;
use App\Http\Resources\ParticipantResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $participant = $request->user()?->participant;

        if ($participant === null) {
            return to_route('dashboard');
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
            return to_route('dashboard');
        }

        Gate::authorize('update', $participant);

        return Inertia::render('registration/Edit', [
            'registration' => new ParticipantResource($participant)->resolve(),
        ]);
    }

    public function update(RegistrationUpdateRequest $request): RedirectResponse
    {
        $request->participant()->fill($request->validated())->save();

        $this->flashSuccess(__('registration.updated'));

        return to_route('registration.show');
    }
}
