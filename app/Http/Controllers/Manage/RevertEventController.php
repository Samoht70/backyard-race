<?php

namespace App\Http\Controllers\Manage;

use App\Actions\RevertEventStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\EventRevertRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

class RevertEventController extends Controller
{
    public function __invoke(EventRevertRequest $request, RevertEventStatus $revert): RedirectResponse
    {
        $event = $revert(
            Event::current(),
            EventStatus::from($request->string('to')->value()),
        );

        $this->flashSuccess(__('event.manage.reverted', ['status' => $event->status->label()]));

        return to_route('manage.event.edit');
    }
}
