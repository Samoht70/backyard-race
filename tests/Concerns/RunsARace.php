<?php

namespace Tests\Concerns;

use App\Models\Event;
use App\Models\Participant;
use App\Models\Round;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

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

    protected function roundOf(Event $event, int $number = 1): Round
    {
        $duration = $event->lap_duration_minutes ?? 60;
        $firstStartAt = $event->first_start_at ?? $this->at('2026-09-05 13:00');

        return Round::factory()
            ->for($event)
            ->numbered($number)
            ->startingAt($firstStartAt->addMinutes($duration * ($number - 1)), $duration)
            ->create();
    }

    protected function runner(Event $event): Participant
    {
        return Participant::factory()->confirmed()->create(['event_id' => $event->getKey()]);
    }

    /**
     * @return Collection<int, Participant>
     */
    protected function runners(Event $event, int $count): Collection
    {
        return Participant::factory()->count($count)->confirmed()->create(['event_id' => $event->getKey()]);
    }
}
