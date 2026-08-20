<?php

namespace App\Http\Controllers;

use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\Event;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(): Response
    {
        $event = Event::query()->firstOrFail();

        Gate::authorize('viewAny', [Document::class, $event]);

        return Inertia::render('Documents', [
            'documents' => DocumentResource::collection($event->documents()->with('media')->get())->resolve(),
        ]);
    }
}
