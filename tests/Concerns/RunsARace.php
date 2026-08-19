<?php

namespace Tests\Concerns;

use App\Models\Event;
use Carbon\CarbonImmutable;

trait RunsARace
{
    protected function at(string $instant): CarbonImmutable
    {
        return CarbonImmutable::parse($instant, 'Europe/Paris');
    }

    protected function runningEvent(string $firstStartAt = '2026-09-05 13:00', int $lapDurationMinutes = 60): Event
    {
        return Event::factory()->running()->create([
            'first_start_at' => $this->at($firstStartAt),
            'lap_duration_minutes' => $lapDurationMinutes,
        ]);
    }
}
