<?php

namespace App\Enums;

/**
 * Display status of a runner, derived — never persisted. BR-08 to BR-11 store
 * two states plus an exit reason: an abandon and a timeout both land on
 * `eliminated` and differ only by reason. Adding a transition method here
 * would pre-empt the lifecycle those stories own.
 */
enum RunnerStatus: string
{
    case Running = 'running';
    case Eliminated = 'eliminated';
    case Withdrawn = 'withdrawn';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Running => __('race.status.running'),
            self::Eliminated => __('race.status.eliminated'),
            self::Withdrawn => __('race.status.withdrawn'),
            self::Finished => __('race.status.finished'),
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases(),
        );
    }
}
