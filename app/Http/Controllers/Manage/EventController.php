<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\EventUpdateRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventLifecycle\EventLifecycleFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * firstOrNew so the screen works on a database that has never been seeded.
     */
    public function edit(): Response
    {
        $event = Event::currentOrNew();
        $lifecycle = $event->lifecycle();
        $next = $lifecycle->nextStatus();

        return Inertia::render('manage/Event', [
            'event' => new EventResource($event)->resolve(),
            'transition' => [
                'current' => $lifecycle->status()->value,
                'next' => $next?->value,
                'nextIsReversible' => app(EventLifecycleFactory::class)->isReversible($next),
                'previous' => $lifecycle->previousStatus()?->value,
                'refusals' => $lifecycle->refusals($event),
                'revertRefusals' => $lifecycle->revertRefusals($event),
            ],
            'frozenFields' => $lifecycle->frozenAttributes(),
            'isEditable' => Gate::allows('update', $event),
        ]);
    }

    public function update(EventUpdateRequest $request): RedirectResponse
    {
        Event::currentOrNew()->fill($request->validated())->save();

        $this->flashSuccess(__('event.manage.saved'));

        return to_route('manage.event.edit');
    }
}
