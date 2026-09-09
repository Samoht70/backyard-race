<?php

namespace App\Actions;

use App\Enums\ScheduleChange;
use App\Exceptions\RoundDurationRefusedException;
use App\Models\Event;
use App\Models\ScheduleSegment;
use App\Services\RaceSchedule\ResolveNextRound;
use App\Services\RaceSchedule\RoundSchedule;
use Illuminate\Support\Facades\DB;

final class ChangeRoundDuration
{
    public function __construct(
        private readonly OpenDueRounds $openDueRounds,
        private readonly ResolveNextRound $resolveNextRound,
    ) {}

    /**
     * @throws RoundDurationRefusedException
     */
    public function __invoke(Event $event, int $fromRoundNumber, int $lapDurationMinutes, ScheduleChange $change): void
    {
        ($this->openDueRounds)($event);

        $schedule = RoundSchedule::fromEvent($event);
        $next = ($this->resolveNextRound)($event);

        if ($schedule === null || $next === null) {
            throw RoundDurationRefusedException::noSchedule();
        }

        if ($fromRoundNumber < $next->number) {
            throw RoundDurationRefusedException::roundStarted();
        }

        $resumed = $schedule->durationOf($fromRoundNumber + 1);

        DB::transaction(fn () => $this->rewrite($event, $fromRoundNumber, $lapDurationMinutes, $change, $resumed));

        $event->unsetRelation('scheduleSegments');
    }

    private function rewrite(Event $event, int $fromRoundNumber, int $lapDurationMinutes, ScheduleChange $change, int $resumed): void
    {
        $this->write($event, $fromRoundNumber, $lapDurationMinutes);

        if ($change === ScheduleChange::SingleRound) {
            $this->write($event, $fromRoundNumber + 1, $resumed);
        }
    }

    private function write(Event $event, int $fromRoundNumber, int $lapDurationMinutes): void
    {
        ScheduleSegment::query()->updateOrCreate(
            ['event_id' => $event->getKey(), 'from_round_number' => $fromRoundNumber],
            ['lap_duration_minutes' => $lapDurationMinutes],
        );
    }
}
