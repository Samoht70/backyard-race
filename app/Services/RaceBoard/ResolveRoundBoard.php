<?php

namespace App\Services\RaceBoard;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Round;
use App\Services\RaceSchedule\CurrentRound;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

final class ResolveRoundBoard
{
    public function __invoke(Event $event, ?CurrentRound $current): ?RoundBoard
    {
        if ($current === null || ! $event->exists) {
            return null;
        }

        $round = $event->rounds()->where('number', $current->number)->first();

        return $round === null
            ? null
            : new RoundBoard($round, $this->runnersOf($round), $this->tallyOf($event));
    }

    /**
     * @return Collection<int, Participant>
     */
    private function runnersOf(Round $round): Collection
    {
        return Participant::query()
            ->running()
            ->whereHas('laps', fn (Builder $laps): Builder => $laps->where('round_id', $round->id))
            ->with([
                'user',
                'laps' => fn (Relation $laps): Relation => $laps->where('round_id', $round->id),
            ])
            ->withValidatedLapsCount()
            ->orderBy('bib_number')
            ->get();
    }

    private function tallyOf(Event $event): RunnerTally
    {
        $counts = (array) $event->participants()
            ->where('status', RegistrationStatus::Confirmed)
            ->toBase()
            ->selectRaw('sum(case when exited_at is null then 1 else 0 end) as still_running')
            ->selectRaw('sum(case when exited_at is null then 0 else 1 end) as gone')
            ->first();

        return new RunnerTally(
            (int) ($counts['still_running'] ?? 0),
            (int) ($counts['gone'] ?? 0),
        );
    }
}
