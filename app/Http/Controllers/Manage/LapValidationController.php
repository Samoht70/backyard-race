<?php

namespace App\Http\Controllers\Manage;

use App\Actions\ValidateLap;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\LapValidationRequest;
use App\Models\Lap;
use Illuminate\Http\RedirectResponse;

class LapValidationController extends Controller
{
    public function __invoke(LapValidationRequest $request, Lap $lap, ValidateLap $validate): RedirectResponse
    {
        $validate($lap);

        return to_route('manage.index');
    }
}
