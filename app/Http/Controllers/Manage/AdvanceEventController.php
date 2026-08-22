<?php

namespace App\Http\Controllers\Manage;

use App\Actions\AdvanceEventStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\EventAdvanceRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

class AdvanceEventController extends Controller
{
    public function __invoke(EventAdvanceRequest $request, AdvanceEventStatus $advance): RedirectResponse
    {
        $event = $advance(
            Event::current(),
            EventStatus::from($request->string('to')->value()),
        );

        $this->flashSuccess(__('event.manage.advanced', ['status' => $event->status->label()]));

        return to_route('manage.event.edit');
    }
}
