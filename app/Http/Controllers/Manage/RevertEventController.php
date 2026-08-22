<?php

namespace App\Http\Controllers\Manage;

use App\Actions\RevertEventStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\EventRevertRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class RevertEventController extends Controller
{
    public function __invoke(EventRevertRequest $request, RevertEventStatus $revert): RedirectResponse
    {
        $event = $revert(
            Event::query()->firstOrFail(),
            EventStatus::from($request->string('to')->value()),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('event.manage.reverted', ['status' => $event->status->label()]),
        ]);

        return to_route('manage.event.edit');
    }
}
