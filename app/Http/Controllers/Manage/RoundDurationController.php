<?php

namespace App\Http\Controllers\Manage;

use App\Actions\ChangeRoundDuration;
use App\Enums\ScheduleChange;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\RoundDurationRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

class RoundDurationController extends Controller
{
    public function __invoke(RoundDurationRequest $request, ChangeRoundDuration $change): RedirectResponse
    {
        $from = $request->integer('from');
        $minutes = $request->integer('lap_duration_minutes');
        $scope = ScheduleChange::from($request->string('change')->value());

        $change(Event::current(), $from, $minutes, $scope);

        $this->flashSuccess($scope->confirmation($from, $minutes));

        return to_route('manage.index');
    }
}
