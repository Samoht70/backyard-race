<?php

namespace App\Http\Controllers\Manage;

use App\Actions\AdvanceEventStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\EventAdvanceRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AdvanceEventController extends Controller
{
    public function __invoke(EventAdvanceRequest $request, AdvanceEventStatus $advance): RedirectResponse
    {
        $event = $advance(
            Event::query()->firstOrFail(),
            EventStatus::from($request->string('to')->value()),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('event.manage.advanced', ['status' => $event->status->label()]),
        ]);

        return to_route('manage.event.edit');
    }
}
