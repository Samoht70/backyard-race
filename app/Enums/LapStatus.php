<?php

namespace App\Enums;

enum LapStatus: string
{
    case Pending = 'pending';
    case Validated = 'validated';
    case Eliminated = 'eliminated';
}
