<?php

namespace Tests\Concerns;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\WaitTimeCalculator;

trait FakesQueueConsumption
{
    protected function workerReports(?string $status = null): void
    {
        $masters = $status === null ? [] : [(object) ['status' => $status]];

        $this->mock(
            MasterSupervisorRepository::class,
            fn ($repository) => $repository->shouldReceive('all')->andReturn($masters),
        );
    }

    protected function waitsFor(int $seconds): void
    {
        $this->mock(
            WaitTimeCalculator::class,
            fn ($calculator) => $calculator->shouldReceive('calculate')->andReturn(['redis:default' => $seconds]),
        );
    }
}
