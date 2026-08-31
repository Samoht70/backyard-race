<?php

namespace App\Services\RaceCorrection;

use App\Enums\LapStatus;
use App\Models\Event;
use App\Models\Lap;
use App\Models\Participant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ResolveCorrectionDesk
{
    private const REVERTABLE_ROUNDS = 2;

    public function __invoke(Event $event, ?CarbonImmutable $at = null): CorrectionDesk
    {
        if (! $event->exists) {
            return new CorrectionDesk(new Collection, new Collection);
        }

        return new CorrectionDesk(
            $this->reinstatable($event, $at ?? CarbonImmutable::now()),
            $this->revertable($event),
        );
    }

    /**
     * @return Collection<int, Lap>
     */
    private function reinstatable(Event $event, CarbonImmutable $at): Collection
    {
        return $this->withRunners(
            $this->lapsOf($event)
                ->where(fn (Builder $lap): Builder => $lap
                    ->where('laps.status', LapStatus::Eliminated)
                    ->orWhere(fn (Builder $overdue): Builder => $overdue
                        ->where('laps.status', LapStatus::Pending)
                        ->where('rounds.deadline_at', '<', $at->utc())))
                ->get(),
        );
    }

    /**
     * @return Collection<int, Lap>
     */
    private function revertable(Event $event): Collection
    {
        $lastNumber = (int) $event->rounds()->max('number');

        return $this->withRunners(
            $this->lapsOf($event)
                ->where('laps.status', LapStatus::Validated)
                ->where('rounds.number', '>', $lastNumber - self::REVERTABLE_ROUNDS)
                ->get(),
        );
    }

    /**
     * @return Builder<Lap>
     */
    private function lapsOf(Event $event): Builder
    {
        return Lap::query()
            ->join('rounds', 'rounds.id', '=', 'laps.round_id')
            ->join('participants', 'participants.id', '=', 'laps.participant_id')
            ->where('rounds.event_id', $event->getKey())
            ->with('round')
            ->orderByDesc('rounds.number')
            ->orderBy('participants.bib_number')
            ->select('laps.*');
    }

    /**
     * @param  Collection<int, Lap>  $laps
     * @return Collection<int, Lap>
     */
    private function withRunners(Collection $laps): Collection
    {
        $runners = Participant::query()
            ->whereIn('id', $laps->pluck('participant_id')->all())
            ->with('user')
            ->withValidatedLapsCount()
            ->get()
            ->keyBy('id');

        return $laps->each(
            fn (Lap $lap): Lap => $lap->setRelation('participant', $runners->get($lap->participant_id)),
        );
    }
}
