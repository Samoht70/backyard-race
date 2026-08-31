<?php

namespace App\Models\Concerns;

use App\Enums\LapStatus;
use App\Enums\RegistrationStatus;
use App\Enums\RunnerStatus;
use App\Models\Lap;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasRaceStatus
{
    private const ELIMINATED_LAP_EXISTS = 'eliminated_lap_exists';

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
            && ! $this->hasEliminatedLap();
    }

    public function runnerStatus(): RunnerStatus
    {
        return $this->isRunning() ? RunnerStatus::Running : RunnerStatus::Eliminated;
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
            ->whereDoesntHave('laps', fn (Builder $laps): Builder => $laps->where('status', LapStatus::Eliminated));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function withRaceStatus(Builder $query): Builder
    {
        return $query->withExists([
            'laps as '.self::ELIMINATED_LAP_EXISTS => fn (Builder $laps): Builder => $laps->where('status', LapStatus::Eliminated),
        ]);
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

    private function hasEliminatedLap(): bool
    {
        $loaded = $this->getAttribute(self::ELIMINATED_LAP_EXISTS);

        if ($loaded !== null) {
            return (bool) $loaded;
        }

        return $this->laps()->where('status', LapStatus::Eliminated)->exists();
    }
}
