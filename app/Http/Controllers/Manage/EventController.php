<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\EventUpdateRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventLifecycle\EventLifecycleFactory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * firstOrNew so the screen works on a database that has never been seeded.
     */
    public function edit(): Response
    {
        $event = Event::query()->firstOrNew();
        $lifecycle = $event->lifecycle();
        $next = $lifecycle->nextStatus();

        return Inertia::render('manage/Event', [
            'event' => new EventResource($event)->resolve(),
            'transition' => [
                'current' => $lifecycle->status()->value,
                'next' => $next?->value,
                'nextIsReversible' => $next !== null
                    && app(EventLifecycleFactory::class)->fromStatus($next)->previousStatus() !== null,
                'previous' => $lifecycle->previousStatus()?->value,
                'refusals' => $lifecycle->refusals($event),
                'revertRefusals' => $lifecycle->revertRefusals($event),
            ],
            'frozenFields' => $lifecycle->frozenAttributes(),
            'isEditable' => $lifecycle->isEditable(),
        ]);
    }

    public function update(EventUpdateRequest $request): RedirectResponse
    {
        Event::query()->firstOrNew()->fill($request->validated())->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('event.manage.saved')]);

        return to_route('manage.event.edit');
    }
}
