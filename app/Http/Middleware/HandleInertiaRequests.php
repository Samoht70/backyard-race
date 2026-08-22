<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Http\Resources\BoardResource;
use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    private const SHARED_TRANSLATION_GROUPS = ['ui', 'race', 'event', 'registration', 'document', 'auth', 'error'];

    protected $rootView = 'app';

    private ?Event $event = null;

    private bool $isEventResolved = false;

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $access = $this->access($request->user());

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'permissions' => $this->permissions($request->user()),
            ],
            'access' => $access,
            'board' => $this->board($access['event']),
            'translations' => $this->translations(),
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function permissions(?User $user): array
    {
        $granted = [];

        foreach (Permission::cases() as $permission) {
            $granted[$permission->value] = (bool) $user?->can($permission->value);
        }

        return $granted;
    }

    /**
     * @return array<string, bool>
     */
    private function access(?User $user): array
    {
        $event = $this->event();
        $gate = Gate::forUser($user);

        return [
            'event' => $event !== null && $gate->allows('view', $event),
            'documents' => $event !== null && $gate->allows('viewAny', [Document::class, $event]),
            'registration' => $user?->participant()->exists() === true,
            'register' => $user === null && $event !== null && $event->acceptsRegistrations(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function board(bool $isEventVisible): ?array
    {
        $event = $this->event();

        return $event === null || ! $isEventVisible ? null : new BoardResource($event)->resolve();
    }

    private function event(): ?Event
    {
        if (! $this->isEventResolved) {
            $this->event = Event::currentOrNull();
            $this->isEventResolved = true;
        }

        return $this->event;
    }

    /**
     * @return array<string, string>
     */
    private function translations(): array
    {
        $locale = app()->getLocale();

        $groups = [];

        foreach (self::SHARED_TRANSLATION_GROUPS as $group) {
            if (File::exists(lang_path("{$locale}/{$group}.php"))) {
                $groups[$group] = Lang::get($group);
            }
        }

        return Arr::dot($groups);
    }
}
