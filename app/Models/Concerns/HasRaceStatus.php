<?php

namespace App\Models\Concerns;

use App\Enums\ExitReason;
use App\Enums\LapStatus;
use App\Enums\RegistrationStatus;
use App\Enums\RunnerStatus;
use App\Models\Lap;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasRaceStatus
{
    private const VALIDATED_LAPS_COUNT = 'validated_laps_count';

    /**
     * @return HasMany<Lap, $this>
     */
    public function laps(): HasMany
    {
        return $this->hasMany(Lap::class);
    }

    public function isRunning(): bool
    {
        return $this->status === RegistrationStatus::Confirmed
            && $this->exited_at === null;
    }

    public function runnerStatus(): RunnerStatus
    {
        if ($this->isRunning()) {
            return RunnerStatus::Running;
        }

        return $this->exit_reason?->runnerStatus() ?? RunnerStatus::Eliminated;
    }

    public function leaveRace(ExitReason $reason, CarbonImmutable $at): void
    {
        $this->forceFill(['exit_reason' => $reason, 'exited_at' => $at])->save();

        $this->laps()->where('status', LapStatus::Pending)->update(['status' => LapStatus::Eliminated]);
    }

    public function validatedLapsCount(): int
    {
        $loaded = $this->getAttribute(self::VALIDATED_LAPS_COUNT);

        if ($loaded !== null) {
            return (int) $loaded;
        }

        return $this->laps()->where('status', LapStatus::Validated)->count();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function running(Builder $query): Builder
    {
        return $query
            ->where('status', RegistrationStatus::Confirmed)
            ->whereNull('exited_at');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function outOfTheRace(Builder $query): Builder
    {
        return $query->whereNotNull('exited_at');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function withValidatedLapsCount(Builder $query): Builder
    {
        return $query->withCount([
            'laps as '.self::VALIDATED_LAPS_COUNT => fn (Builder $laps): Builder => $laps->where('status', LapStatus::Validated),
        ]);
    }
}
