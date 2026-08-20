<?php

namespace App\Actions;

use App\Models\Participant;
use Illuminate\Support\Facades\DB;

final class UpdateRegistration
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(Participant $participant, array $attributes): Participant
    {
        return DB::transaction(function () use ($participant, $attributes): Participant {
            $participant->user->fill($attributes)->save();
            $participant->fill($attributes)->save();

            return $participant->refresh();
        });
    }
}
