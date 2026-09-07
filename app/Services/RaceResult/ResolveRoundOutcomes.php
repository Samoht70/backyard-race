<?php

namespace App\Services\RaceResult;

use App\Enums\ExitReason;
use App\Enums\LapStatus;
use App\Models\Event;
use Illuminate\Support\Collection;
use stdClass;

final class ResolveRoundOutcomes
{
    /**
     * @return list<RoundOutcome>
     */
    public function __invoke(Event $event): array
    {
        $outcomes = $this->rows($event)
            ->map(fn (stdClass $row): RoundOutcome => $this->outcome((array) $row));

        return array_values($outcomes->all());
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function rows(Event $event): Collection
    {
        return $event->rounds()
            ->toBase()
            ->select('rounds.number')
            ->selectRaw('count(laps.id) as runners')
            ->selectRaw(
                'sum(case when laps.status = ? then 1 else 0 end) as completed_laps',
                [LapStatus::Validated->value],
            )
            ->selectRaw(
                'sum(case when laps.status = ? and participants.exit_reason = ? then 1 else 0 end) as withdrawals',
                [LapStatus::Eliminated->value, ExitReason::Withdrawal->value],
            )
            ->selectRaw(
                'sum(case when laps.status = ? and participants.exit_reason = ? then 1 else 0 end) as timeouts',
                [LapStatus::Eliminated->value, ExitReason::Timeout->value],
            )
            ->leftJoin('laps', 'laps.round_id', '=', 'rounds.id')
            ->leftJoin('participants', 'participants.id', '=', 'laps.participant_id')
            ->groupBy('rounds.id', 'rounds.number')
            ->orderBy('rounds.number')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function outcome(array $row): RoundOutcome
    {
        return new RoundOutcome(
            (int) ($row['number'] ?? 0),
            (int) ($row['runners'] ?? 0),
            (int) ($row['completed_laps'] ?? 0),
            (int) ($row['withdrawals'] ?? 0),
            (int) ($row['timeouts'] ?? 0),
        );
    }
}
