<?php

namespace App\Http\Controllers\Manage;

use App\Actions\RevertLapValidation;
use App\Enums\LapStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\LapReversionRequest;
use App\Models\Lap;
use Illuminate\Http\RedirectResponse;

class LapReversionController extends Controller
{
    public function __invoke(
        LapReversionRequest $request,
        Lap $lap,
        RevertLapValidation $revert,
    ): RedirectResponse {
        $status = $revert($lap);

        $this->flashSuccess(__($this->messageOf($status), [
            'name' => $lap->participant->user->name,
            'number' => $lap->round->number,
        ]));

        return to_route('manage.corrections');
    }

    private function messageOf(LapStatus $status): string
    {
        return $status === LapStatus::Eliminated
            ? 'race.correction.reverted_out'
            : 'race.correction.reverted_pending';
    }
}
