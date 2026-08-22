<?php

namespace App\Actions;

use App\Enums\RegistrationOutcome;
use App\Enums\RegistrationTransition;
use App\Exceptions\RegistrationTransitionRefusedException;
use App\Models\Event;
use App\Models\Participant;
use App\Notifications\RegistrationProcessed;
use Illuminate\Support\Facades\DB;

final class TransitionRegistration
{
    public function __construct(private NextBibNumber $nextBibNumber) {}

    /**
     * @throws RegistrationTransitionRefusedException
     */
    public function __invoke(Participant $participant, RegistrationTransition $transition): Participant
    {
        $leaving = $participant->lifecycle();

        $moved = DB::transaction(function () use ($participant, $transition, $leaving): Participant {
            $event = Event::query()
                ->whereKey($participant->event_id)
                ->lockForUpdate()
                ->firstOrFail();

            $next = $transition->apply($leaving);

            if ($next->consumesASeat() && $event->isFull()) {
                throw RegistrationTransitionRefusedException::full();
            }

            $changes = ['status' => $next->status()->value];

            if ($next->assignsBibNumber() && $participant->bib_number === null) {
                $changes['bib_number'] = ($this->nextBibNumber)($event);
            }

            $written = Participant::query()
                ->whereKey($participant->getKey())
                ->where('status', $leaving->status()->value)
                ->update($changes);

            if ($written === 0) {
                throw RegistrationTransitionRefusedException::stale();
            }

            return $participant->refresh();
        });

        $moved->user->notify(new RegistrationProcessed(RegistrationOutcome::of($leaving, $transition)));

        return $moved;
    }
}
