<?php

namespace App\Http\Controllers\Manage;

use App\Actions\WithdrawRunner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\RunnerWithdrawalRequest;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;

class RunnerWithdrawalController extends Controller
{
    public function __invoke(
        RunnerWithdrawalRequest $request,
        Participant $participant,
        WithdrawRunner $withdraw,
    ): RedirectResponse {
        $withdraw($participant);

        $this->flashSuccess(__('race.withdrawal.recorded', ['name' => $participant->user->name]));

        return to_route('manage.index');
    }
}
