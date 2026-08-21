<?php

namespace Tests\Feature;

use App\Notifications\RegistrationLink;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RegistersRunners;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    use RefreshDatabase, RegistersRunners;

    private const REGISTRATIONS_PER_MINUTE = 6;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->openEvent();

        Notification::fake();
    }

    #[Test]
    public function it_signs_the_registration_link_with_the_scheme_the_runner_sees(): void
    {
        $this->post(route('account.store'), ['email' => 'scheme@backyard.test'], $this->proxiedBy('203.0.113.9'));

        Notification::assertSentOnDemand(
            RegistrationLink::class,
            fn (RegistrationLink $notification, array $channels, AnonymousNotifiable $notifiable): bool => str_starts_with(
                $notification->toMail($notifiable)->actionUrl ?? '',
                'https://backyard-race.test/',
            ),
        );
    }

    #[Test]
    public function it_meters_registrations_per_runner_rather_than_per_proxy(): void
    {
        for ($attempt = 1; $attempt <= self::REGISTRATIONS_PER_MINUTE; $attempt++) {
            $this->post(
                route('account.store'),
                ['email' => "eager-{$attempt}@backyard.test"],
                $this->proxiedBy('203.0.113.9'),
            )->assertRedirect(route('account.create'));
        }

        $this->post(
            route('account.store'),
            ['email' => 'seventh@backyard.test'],
            $this->proxiedBy('203.0.113.9'),
        )->assertTooManyRequests();

        $this->post(
            route('account.store'),
            ['email' => 'somebody-else@backyard.test'],
            $this->proxiedBy('198.51.100.4'),
        )->assertRedirect(route('account.create'));
    }

    /**
     * @return array<string, string>
     */
    private function proxiedBy(string $clientIp): array
    {
        return [
            'X-Forwarded-For' => $clientIp,
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'backyard-race.test',
            'X-Forwarded-Port' => '443',
        ];
    }
}
