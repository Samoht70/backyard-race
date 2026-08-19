<?php

namespace Tests\Feature\Auth;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use App\Notifications\RegistrationLink;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RegistersRunners;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase, RegistersRunners;

    private const EMAIL = 'recrue@backyard.test';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->openEvent();

        Notification::fake();
    }

    #[Test]
    public function it_renders_the_email_step(): void
    {
        $this->get(route('account.create'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('auth/register/Start')
                    ->where('open', true)
            );
    }

    #[Test]
    public function it_mails_a_link_without_creating_an_account(): void
    {
        $this->post(route('account.store'), ['email' => self::EMAIL])
            ->assertRedirect(route('account.create'));

        Notification::assertSentOnDemand(
            RegistrationLink::class,
            fn (RegistrationLink $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routeNotificationFor('mail') === self::EMAIL
                && str_contains($notification->toMail($notifiable)->actionUrl ?? '', route('account.edit', absolute: false))
        );

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function it_refuses_an_email_that_already_has_an_account(): void
    {
        User::factory()->create(['email' => self::EMAIL]);

        $this->from(route('account.create'))
            ->post(route('account.store'), ['email' => self::EMAIL])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_refuses_a_tampered_link(): void
    {
        $link = $this->registrationLink(self::EMAIL);
        $tampered = str_replace(urlencode(self::EMAIL), urlencode('intrus@backyard.test'), $link);

        $this->assertNotSame($link, $tampered);

        $this->get($tampered)->assertRedirect(route('account.create'));
    }

    #[Test]
    public function it_refuses_an_expired_link(): void
    {
        $link = $this->registrationLink(self::EMAIL);

        $this->travel(49)->hours();

        $this->get($link)->assertRedirect(route('account.create'));
    }

    #[Test]
    public function it_renders_the_registration_form_from_a_valid_link(): void
    {
        $this->get($this->registrationLink(self::EMAIL))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('auth/register/Complete')
                    ->where('email', self::EMAIL)
            );
    }

    #[Test]
    public function it_refuses_to_complete_without_a_confirmed_email(): void
    {
        $this->put(route('account.update'), $this->registrationPayload())
            ->assertRedirect(route('account.create'));

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function it_creates_the_account_and_shows_the_code_once(): void
    {
        $code = $this->register();

        $this->get(route('account.show'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('auth/register/Code')
                    ->where('code', $code)
            );

        $this->get(route('account.show'))->assertRedirect(route('login'));
    }

    #[Test]
    public function it_leaves_the_new_runner_signed_out_until_they_use_the_code(): void
    {
        $code = $this->register();

        $this->assertGuest();

        $this->post(route('login.store'), ['email' => self::EMAIL, 'password' => $code]);

        $this->assertAuthenticated();
    }

    #[Test]
    public function it_gives_a_new_account_the_participant_role(): void
    {
        $this->register();

        $this->assertTrue($this->registered()->hasRole(Role::Participant->value));
    }

    #[Test]
    public function it_gives_a_new_account_no_administration_permission(): void
    {
        $this->register();

        foreach (Permission::cases() as $permission) {
            $this->assertFalse($this->registered()->can($permission->value));
        }
    }

    private function register(): string
    {
        $this->completeRegistration(self::EMAIL)
            ->assertRedirect(route('account.show'));

        $code = session('account.access_code');

        $this->assertIsString($code);

        return $code;
    }

    private function registered(): User
    {
        return User::query()->where('email', self::EMAIL)->sole();
    }
}
