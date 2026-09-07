<?php

namespace App\Actions;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Standing;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

final class FreezeStandings
{
    public function __invoke(Event $event): int
    {
        $rows = $this->rows($event, $this->rankedRunners($event));

        if ($rows === []) {
            return 0;
        }

        Standing::query()->insert($rows);

        return count($rows);
    }

    /**
     * @return Collection<int, Participant>
     */
    private function rankedRunners(Event $event): Collection
    {
        return Participant::query()
            ->where('event_id', $event->getKey())
            ->where('status', RegistrationStatus::Confirmed)
            ->withAValidatedLap()
            ->withValidatedLapsCount()
            ->bestFirst()
            ->with('user')
            ->get();
    }

    /**
     * @param  Collection<int, Participant>  $runners
     * @return list<array<string, mixed>>
     */
    private function rows(Event $event, Collection $runners): array
    {
        $now = CarbonImmutable::now();
        $rows = [];
        $rank = 0;
        $position = 0;
        $lapsOfTheRankAbove = null;

        foreach ($runners as $runner) {
            $position++;
            $laps = $runner->validatedLapsCount();

            if ($laps !== $lapsOfTheRankAbove) {
                $rank = $position;
                $lapsOfTheRankAbove = $laps;
            }

            $rows[] = [
                'event_id' => $event->getKey(),
                'participant_id' => $runner->getKey(),
                'rank' => $rank,
                'bib_number' => $runner->bib_number,
                'first_name' => $runner->user->first_name,
                'last_name' => $runner->user->last_name,
                'validated_laps' => $laps,
                'exit_reason' => $runner->exit_reason?->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }
}
