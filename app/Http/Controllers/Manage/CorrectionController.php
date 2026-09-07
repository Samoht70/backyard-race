<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Resources\Manage\CorrectableLapResource;
use App\Models\Event;
use App\Models\Lap;
use App\Services\RaceCorrection\ResolveCorrectionDesk;
use Illuminate\Database\Eloquent\Collection;
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
            'reinstatable' => $this->lapsOf($desk->reinstatable, $event),
            'revertable' => $this->lapsOf($desk->revertable, $event),
        ]);
    }

    /**
     * @param  Collection<int, Lap>  $laps
     * @return array<int, array<array-key, mixed>>
     */
    private function lapsOf(Collection $laps, Event $event): array
    {
        return $laps
            ->map(fn (Lap $lap): array => new CorrectableLapResource($lap, $event)->resolve())
            ->values()
            ->all();
    }
}
