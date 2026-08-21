<?php

namespace App\Http\Controllers\Manage;

use App\Actions\TransitionRegistration;
use App\Enums\RegistrationTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\RegistrationTransitionRequest;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class RegistrationTransitionController extends Controller
{
    public function __invoke(
        RegistrationTransitionRequest $request,
        Participant $participant,
        TransitionRegistration $transition,
    ): RedirectResponse {
        $participant = $transition(
            $participant,
            RegistrationTransition::from($request->string('transition')->value()),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('registration.manage.transitioned', ['status' => $participant->status->label()]),
        ]);

        return to_route('manage.registrations.index');
    }
}
