<?php

namespace App\Services\RaceCorrection;

use App\Models\Lap;
use Illuminate\Database\Eloquent\Collection;

final class CorrectionDesk
{
    /**
     * @param  Collection<int, Lap>  $reinstatable
     * @param  Collection<int, Lap>  $revertable
     */
    public function __construct(
        public Collection $reinstatable,
        public Collection $revertable,
    ) {}
}
