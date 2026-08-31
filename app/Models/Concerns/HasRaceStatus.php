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
            && ! $this->laps()->where('status', LapStatus::Eliminated)->exists();
    }

    public function runnerStatus(): RunnerStatus
    {
        return $this->isRunning() ? RunnerStatus::Running : RunnerStatus::Eliminated;
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
}
