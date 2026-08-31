<?php

namespace App\Http\Controllers\Manage;

use App\Actions\ReinstateRunner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\LapReinstatementRequest;
use App\Models\Lap;
use Illuminate\Http\RedirectResponse;

class LapReinstatementController extends Controller
{
    public function __invoke(
        LapReinstatementRequest $request,
        Lap $lap,
        ReinstateRunner $reinstate,
    ): RedirectResponse {
        $performance = $reinstate($lap, $request->finishedAt());

        $this->flashSuccess(__('race.correction.reinstated', [
            'name' => $lap->participant->user->name,
            'number' => $lap->round->number,
            'time' => $performance->validatedAt->format('H:i'),
        ]));

        return to_route('manage.corrections');
    }
}
