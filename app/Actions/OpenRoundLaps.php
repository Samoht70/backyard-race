<?php

namespace App\Actions;

use App\Enums\LapStatus;
use App\Models\Lap;
use App\Models\Participant;
use App\Models\Round;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class OpenRoundLaps
{
    public function __invoke(Round $round): int
    {
        $runners = Participant::query()
            ->running()
            ->where('event_id', $round->event_id)
            ->whereDoesntHave('laps', fn (Builder $laps): Builder => $laps->where('round_id', $round->getKey()))
            ->pluck('id');

        if ($runners->isEmpty()) {
            return 0;
        }

        $openedAt = CarbonImmutable::now();

        Lap::query()->insert($runners->map(fn (int $runnerId): array => [
            'participant_id' => $runnerId,
            'round_id' => $round->getKey(),
            'status' => LapStatus::Pending->value,
            'created_at' => $openedAt,
            'updated_at' => $openedAt,
        ])->all());

        return $runners->count();
    }
}
