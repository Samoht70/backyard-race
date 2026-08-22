<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\BriefingUpdateRequest;
use App\Models\Event;
use App\Support\Briefing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BriefingController extends Controller
{
    public function edit(): Response
    {
        $event = Event::current();
        $briefing = Briefing::orDefault($event->briefing);

        return Inertia::render('manage/Briefing', [
            'markdown' => $briefing,
            'html' => Briefing::toHtml($briefing),
            'isEditable' => Gate::allows('updateBriefing', $event),
        ]);
    }

    public function update(BriefingUpdateRequest $request): RedirectResponse
    {
        $event = $request->event();
        $event->briefing = $request->briefing();
        $event->save();

        $this->flashSuccess(__('event.briefing.saved'));

        return to_route('manage.briefing.edit');
    }
}
