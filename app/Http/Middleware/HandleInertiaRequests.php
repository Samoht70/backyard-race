<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
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
    /**
     * Translation groups delivered to the Inertia front-end, flattened to
     * dotted keys. Groups rendered only by PHP (validation, mail) stay out:
     * shipping them would put every framework message in every response.
     */
    private const SHARED_TRANSLATION_GROUPS = ['ui', 'race', 'event', 'registration', 'document', 'auth'];

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'permissions' => $this->permissions($request->user()),
            ],
            'access' => $this->access($request->user()),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'translations' => $this->translations(),
        ];
    }

    /**
     * Always complete: a guest gets every ability at false, so no screen has to
     * branch on a missing key. Each value is the result of the same can() the
     * server authorises with, so display and decision cannot drift apart.
     *
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
        if ($user === null) {
            return ['event' => false, 'documents' => false, 'registration' => false];
        }

        $event = Event::query()->first();
        $gate = Gate::forUser($user);

        return [
            'event' => $event !== null && $gate->allows('view', $event),
            'documents' => $event !== null && $gate->allows('viewAny', [Document::class, $event]),
            'registration' => $user->participant()->exists(),
        ];
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
