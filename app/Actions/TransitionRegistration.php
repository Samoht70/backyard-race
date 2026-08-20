<?php

namespace App\Actions;

use App\Enums\RegistrationTransition;
use App\Exceptions\RegistrationTransitionRefusedException;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Support\Facades\DB;

final class TransitionRegistration
{
    public function __construct(private NextBibNumber $nextBibNumber) {}

    /**
     * Dropping the `where` on the status being left lets a concurrent request's
     * transition be overwritten, and a double click assign a second bib number.
     * Releasing the event lock lets two confirmations share the last seat, or
     * the same bib number.
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

            $changes = ['status' => $next->status()->value];

            if ($next->assignsBibNumber() && $participant->bib_number === null) {
                $changes['bib_number'] = ($this->nextBibNumber)($event);
            }

            $moved = Participant::query()
                ->whereKey($participant->getKey())
                ->where('status', $leaving->value)
                ->update($changes);

            if ($moved === 0) {
                throw RegistrationTransitionRefusedException::stale();
            }

            return $participant->refresh();
        });
    }
}
