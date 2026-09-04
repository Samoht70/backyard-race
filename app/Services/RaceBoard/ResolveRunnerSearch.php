<?php

namespace App\Services\RaceBoard;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ResolveRunnerSearch
{
    public function __construct(private ResolveRunnerTally $tally) {}

    public function __invoke(Event $event, ?string $term): RunnerSearch
    {
        return new RunnerSearch($this->matches($event, $term), ($this->tally)($event));
    }

    /**
     * @return Collection<int, Participant>
     */
    private function matches(Event $event, ?string $term): Collection
    {
        if (! $event->exists || $term === null) {
            return new Collection;
        }

        return $event->participants()
            ->where('status', RegistrationStatus::Confirmed)
            ->when(
                ctype_digit($term),
                fn (Builder $runners): Builder => $runners->where('bib_number', (int) $term),
                fn (Builder $runners): Builder => $runners->whereHas(
                    'user',
                    fn (Builder $user): Builder => $user
                        ->whereLike('first_name', "%{$term}%")
                        ->orWhereLike('last_name', "%{$term}%"),
                ),
            )
            ->with('user')
            ->withValidatedLapsCount()
            ->withLastValidatedRound()
            ->orderBy('bib_number')
            ->get();
    }
}
