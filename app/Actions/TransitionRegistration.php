<?php

namespace App\Actions;

use App\Enums\RegistrationTransition;
use App\Exceptions\RegistrationTransitionRefusedException;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Support\Facades\DB;

final class TransitionRegistration
{
    /**
     * Dropping the `where` on the status being left lets a concurrent request's
     * transition be overwritten, and a double click move the same runner twice.
     * Releasing the event lock lets two confirmations share the last seat.
     *
     * @throws RegistrationTransitionRefusedException
     */
    public function __invoke(Participant $participant, RegistrationTransition $transition): Participant
    {
        return DB::transaction(function () use ($participant, $transition): Participant {
            $event = Event::query()
                ->whereKey($participant->event_id)
                ->lockForUpdate()
                ->firstOrFail();

            $leaving = $participant->status;
            $next = $transition->apply($participant->lifecycle());

            if ($next->consumesASeat() && $event->isFull()) {
                throw RegistrationTransitionRefusedException::full();
            }

            $moved = Participant::query()
                ->whereKey($participant->getKey())
                ->where('status', $leaving->value)
                ->update(['status' => $next->status()->value]);

            if ($moved === 0) {
                throw RegistrationTransitionRefusedException::stale();
            }

            return $participant->refresh();
        });
    }
}
