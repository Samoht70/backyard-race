<?php

namespace App\Enums;

enum ScheduleChange: string
{
    case Onwards = 'onwards';
    case SingleRound = 'single_round';

    public function confirmation(int $number, int $minutes): string
    {
        $replace = ['number' => $number, 'minutes' => $minutes];

        return match ($this) {
            self::Onwards => __('race.duration.onwards_saved', $replace),
            self::SingleRound => __('race.duration.single_round_saved', $replace),
        };
    }
}
