<?php

namespace App\Providers;

use App\Models\User;
use App\Support\AccessCode;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()
                ->where('email', Str::lower(trim($request->string('email')->value())))
                ->first();

            if ($user === null) {
                return null;
            }

            $code = AccessCode::normalise($request->string('password')->value());

            return $code !== '' && Hash::check($code, $user->password) ? $user : null;
        });
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('registration', fn (Request $request) => $request->isMethodSafe()
            ? Limit::none()
            : Limit::perMinute(6)->by((string) $request->ip()));
    }
}
