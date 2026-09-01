<?php

namespace App\Http\Resources;

use App\Services\RaceBoard\RunnerTally;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RunnerTally
 */
class RunnerTallyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'running' => $this->running,
            'out' => $this->out,
        ];
    }
}
