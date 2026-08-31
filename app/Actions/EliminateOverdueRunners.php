<?php

namespace App\Actions;

use App\Enums\ExitReason;
use App\Enums\LapStatus;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Round;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class EliminateOverdueRunners
{
    public function __invoke(Event $event, ?CarbonImmutable $at = null): int
    {
        if (! $event->lifecycle()->isRacing()) {
            return 0;
        }

        return $this->expiredRounds($event, $at ?? CarbonImmutable::now())
            ->sum(fn (Round $round): int => $this->eliminate($round));
    }

    /**
     * @return Collection<int, Round>
     */
    private function expiredRounds(Event $event, CarbonImmutable $at): Collection
    {
        return $event->rounds()
            ->where('deadline_at', '<=', $at->utc())
            ->orderBy('number')
            ->get();
    }

    private function eliminate(Round $round): int
    {
        return $round->laps()
            ->where('status', LapStatus::Pending)
            ->pluck('participant_id')
            ->filter(fn (int $runnerId): bool => DB::transaction(
                fn (): bool => $this->claim($runnerId, $round->deadline_at),
            ))
            ->count();
    }

    private function claim(int $runnerId, CarbonImmutable $deadlineAt): bool
    {
        $runner = Participant::query()->whereKey($runnerId)->lockForUpdate()->sole();

        if (! $runner->isRunning()) {
            return false;
        }

        $runner->leaveRace(ExitReason::Timeout, $deadlineAt);

        return true;
    }
}
