<?php

namespace App\Services\RaceBoard;

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

        return $round === null ? null : new RoundBoard($round, $this->runnersOf($round));
    }

    /**
     * @return Collection<int, Participant>
     */
    private function runnersOf(Round $round): Collection
    {
        return Participant::query()
            ->whereHas('laps', fn (Builder $laps): Builder => $laps->where('round_id', $round->id))
            ->with([
                'user',
                'laps' => fn (Relation $laps): Relation => $laps->where('round_id', $round->id),
            ])
            ->withRaceStatus()
            ->withValidatedLapsCount()
            ->orderBy('bib_number')
            ->get();
    }
}
