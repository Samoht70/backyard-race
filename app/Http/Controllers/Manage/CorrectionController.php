<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Resources\Manage\CorrectableLapResource;
use App\Models\Event;
use App\Services\RaceCorrection\ResolveCorrectionDesk;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CorrectionController extends Controller
{
    public function __invoke(ResolveCorrectionDesk $resolveCorrectionDesk): Response
    {
        $event = Event::currentOrNew();

        Gate::authorize('correctLaps', $event);

        $desk = $resolveCorrectionDesk($event);

        return Inertia::render('manage/Corrections', [
            'reinstatable' => CorrectableLapResource::collection($desk->reinstatable)->resolve(),
            'revertable' => CorrectableLapResource::collection($desk->revertable)->resolve(),
        ]);
    }
}
