<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterRunner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AccountStoreRequest;
use App\Http\Requests\Auth\AccountUpdateRequest;
use App\Models\Event;
use App\Models\User;
use App\Notifications\RegistrationLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    private const LIFETIME_HOURS = 48;

    private const CONFIRMED_EMAIL = 'account.confirmed_email';

    private const ACCESS_CODE = 'account.access_code';

    public function create(Request $request): Response
    {
        $event = $this->event();

        return Inertia::render('auth/register/Start', [
            'status' => $request->session()->get('status'),
            'open' => $event?->acceptsRegistrations() ?? false,
            'seats' => $this->seats($event),
        ]);
    }

    public function store(AccountStoreRequest $request): RedirectResponse
    {
        $email = (string) $request->validated('email');

        Notification::route('mail', $email)
            ->notify(new RegistrationLink($this->signedLink($email), self::LIFETIME_HOURS));

        return to_route('account.create')
            ->with('status', __('auth.register.link_sent', ['hours' => self::LIFETIME_HOURS]));
    }

    public function edit(Request $request): Response|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return to_route('account.create')->with('status', __('auth.register.link_invalid'));
        }

        $email = $request->string('email')->value();

        if ($this->alreadyRegistered($email)) {
            return to_route('login')->with('status', __('auth.register.already_registered'));
        }

        $event = $this->event();

        if ($event?->acceptsRegistrations() !== true) {
            return to_route('account.create');
        }

        $request->session()->put(self::CONFIRMED_EMAIL, $email);

        return Inertia::render('auth/register/Complete', [
            'email' => $email,
            'seats' => $this->seats($event),
        ]);
    }

    public function update(AccountUpdateRequest $request, RegisterRunner $registerRunner): RedirectResponse
    {
        $email = $request->session()->pull(self::CONFIRMED_EMAIL);

        if (! is_string($email)) {
            return to_route('account.create')->with('status', __('auth.register.link_invalid'));
        }

        if ($this->alreadyRegistered($email)) {
            return to_route('login')->with('status', __('auth.register.already_registered'));
        }

        $code = $registerRunner(Event::query()->firstOrFail(), $email, $request->validated());

        return to_route('account.show')->with(self::ACCESS_CODE, $code);
    }

    public function show(Request $request): Response|RedirectResponse
    {
        $code = $request->session()->get(self::ACCESS_CODE);

        if (! is_string($code)) {
            return to_route('login');
        }

        return Inertia::render('auth/register/Code', [
            'code' => $code,
        ]);
    }

    private function signedLink(string $email): string
    {
        return URL::temporarySignedRoute(
            'account.edit',
            now()->addHours(self::LIFETIME_HOURS),
            ['email' => $email],
        );
    }

    private function alreadyRegistered(string $email): bool
    {
        return User::query()->where('email', $email)->exists();
    }

    private function event(): ?Event
    {
        return Event::query()->first();
    }

    /**
     * @return array{confirmed: int, capacity: int|null}|null
     */
    private function seats(?Event $event): ?array
    {
        if ($event === null) {
            return null;
        }

        return [
            'confirmed' => $event->confirmedParticipantsCount(),
            'capacity' => $event->max_participants,
        ];
    }
}
