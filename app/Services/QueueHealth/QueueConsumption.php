<?php

namespace App\Services\QueueHealth;

use App\Enums\QueueState;
use Illuminate\Support\Arr;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\WaitTimeCalculator;

final class QueueConsumption
{
    private const PAUSED = 'paused';

    private const DEFAULT_WAIT_SECONDS = 60;

    public function __construct(
        private readonly MasterSupervisorRepository $masters,
        private readonly WaitTimeCalculator $waits,
    ) {}

    public function state(): QueueState
    {
        $masters = $this->masters->all();

        if ($masters === []) {
            return QueueState::WorkerAbsent;
        }

        if ($this->anyPaused($masters)) {
            return QueueState::WorkerPaused;
        }

        return $this->waitsTooLong() ? QueueState::Backlogged : QueueState::Consuming;
    }

    /**
     * @param  array<mixed>  $masters
     */
    private function anyPaused(array $masters): bool
    {
        foreach ($masters as $master) {
            if (Arr::get((array) $master, 'status') === self::PAUSED) {
                return true;
            }
        }

        return false;
    }

    private function waitsTooLong(): bool
    {
        foreach ($this->waits->calculate() as $queue => $wait) {
            $threshold = $this->thresholdFor($queue);

            if ($threshold > 0 && $wait > $threshold) {
                return true;
            }
        }

        return false;
    }

    private function thresholdFor(int|string $queue): int
    {
        $configured = config("horizon.waits.{$queue}", self::DEFAULT_WAIT_SECONDS);

        return is_numeric($configured) ? (int) $configured : self::DEFAULT_WAIT_SECONDS;
    }
}
