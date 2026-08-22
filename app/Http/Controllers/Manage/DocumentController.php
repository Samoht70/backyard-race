<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\DocumentStoreRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\Event;
use App\Rules\DocumentFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(): Response
    {
        $event = Event::currentOrNew();

        return Inertia::render('manage/documents/Index', [
            'documents' => DocumentResource::collection($event->documents()->with('media')->get())->resolve(),
            'isEditable' => Gate::allows('create', [Document::class, $event]),
            'maxFileMegabytes' => DocumentFile::maxMegabytes(),
        ]);
    }

    public function store(DocumentStoreRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $document = $request->event()->documents()->create([
                'title' => $request->title(),
                'description' => $request->description(),
            ]);

            $document->addMedia($request->document())->toMediaCollection(Document::FILE_COLLECTION);
        });

        $this->flashSuccess(__('document.manage.saved'));

        return to_route('manage.documents.index');
    }

    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $document->delete();

        $this->flashSuccess(__('document.manage.deleted'));

        return to_route('manage.documents.index');
    }
}
