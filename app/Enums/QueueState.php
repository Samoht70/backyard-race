<?php

namespace App\Enums;

enum QueueState: string
{
    case Consuming = 'consuming';
    case WorkerAbsent = 'worker-absent';
    case WorkerPaused = 'worker-paused';
    case Backlogged = 'backlogged';

    public function isStalled(): bool
    {
        return $this !== self::Consuming;
    }
}
