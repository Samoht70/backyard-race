<?php

namespace Tests\Feature\Manage;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationEditTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_sheet_with_its_allowed_transitions(): void
    {
        $participant = $this->registration(RegistrationStatus::Confirmed);

        $this->actingAs($this->manager())
            ->get(route('manage.registrations.edit', $participant))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('manage/registrations/Edit')
                ->where('registration.id', $participant->id)
                ->where('registration.allowed_transitions', ['cancel']));
    }

    #[Test]
    public function it_refuses_a_participant(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->actingAs($participant->user)
            ->get(route('manage.registrations.edit', $participant))
            ->assertForbidden();
    }

    #[Test]
    public function it_corrects_a_pending_registration(): void
    {
        $this->assertCorrects(RegistrationStatus::Pending);
    }

    #[Test]
    public function it_corrects_a_confirmed_registration(): void
    {
        $this->assertCorrects(RegistrationStatus::Confirmed);
    }

    #[Test]
    public function it_corrects_a_cancelled_registration(): void
    {
        $this->assertCorrects(RegistrationStatus::Cancelled);
    }

    #[Test]
    public function it_corrects_the_runner_identity_and_email(): void
    {
        $participant = $this->registration(RegistrationStatus::Confirmed);

        $this->actingAs($this->manager())
            ->put(route('manage.registrations.update', $participant), $this->payload([
                'first_name' => 'Marie-Ange',
                'last_name' => 'Berger',
                'email' => 'marie.berger@example.test',
            ]))
            ->assertSessionHasNoErrors();

        $runner = $participant->user->refresh();

        $this->assertSame('Marie-Ange', $runner->first_name);
        $this->assertSame('Berger', $runner->last_name);
        $this->assertSame('marie.berger@example.test', $runner->email);
    }

    #[Test]
    public function it_refuses_an_email_already_taken(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);
        User::factory()->create(['email' => 'deja.pris@example.test']);

        $this->actingAs($this->manager())
            ->put(
                route('manage.registrations.update', $participant),
                $this->payload(['email' => 'deja.pris@example.test']),
            )
            ->assertSessionHasErrors('email');
    }

    #[Test]
    public function it_keeps_an_unchanged_email(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->actingAs($this->manager())
            ->put(
                route('manage.registrations.update', $participant),
                $this->payload(['email' => $participant->user->email]),
            )
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function it_still_refuses_an_underage_birth_date(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->actingAs($this->manager())
            ->put(
                route('manage.registrations.update', $participant),
                $this->payload(['birth_date' => now()->subYears(17)->format('Y-m-d')]),
            )
            ->assertSessionHasErrors('birth_date');
    }

    #[Test]
    public function it_never_lets_the_status_be_mass_assigned(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->actingAs($this->manager())
            ->put(
                route('manage.registrations.update', $participant),
                $this->payload(['status' => RegistrationStatus::Confirmed->value]),
            )
            ->assertSessionHasNoErrors();

        $this->assertSame(RegistrationStatus::Pending, $participant->refresh()->status);
    }

    #[Test]
    public function it_flashes_a_confirmation_to_the_manager(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->actingAs($this->manager())
            ->put(route('manage.registrations.update', $participant), $this->payload())
            ->assertSessionHas('inertia.flash_data.toast.message', 'Fiche mise à jour.');
    }

    #[Test]
    public function it_shows_the_declared_pps_number_to_the_manager(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);
        $participant->update(['pps_number' => 'PPS12345678']);

        $this->actingAs($this->manager())
            ->get(route('manage.registrations.edit', $participant))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('manage/registrations/Edit')
                ->where('registration.pps_number', 'PPS12345678'));
    }

    #[Test]
    public function it_refuses_a_pps_number_posted_by_the_manager(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);
        $participant->update(['pps_number' => 'PPS12345678']);

        $this->actingAs($this->manager())
            ->put(
                route('manage.registrations.update', $participant),
                $this->payload(['pps_number' => 'PPS99990000']),
            )
            ->assertSessionHasErrors('pps_number');

        $this->assertSame('PPS12345678', $participant->refresh()->pps_number);
    }

    private function assertCorrects(RegistrationStatus $status): void
    {
        $participant = $this->registration($status);

        $this->actingAs($this->manager())
            ->put(
                route('manage.registrations.update', $participant),
                $this->payload(['phone' => '07 98 76 54 32']),
            )
            ->assertRedirect(route('manage.registrations.edit', $participant));

        $corrected = $participant->refresh();

        $this->assertSame('07 98 76 54 32', $corrected->phone);
        $this->assertSame($status, $corrected->status);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'first_name' => 'Zoé',
            'last_name' => 'Ancel',
            'email' => 'zoe.ancel@example.test',
            'phone' => '06 12 34 56 78',
            'birth_date' => '1986-04-17',
            'emergency_contact_name' => 'Camille Berger',
            'emergency_contact_phone' => '06 87 65 43 21',
            'notes' => 'Allergie aux fruits à coque.',
            ...$overrides,
        ];
    }

    private function registration(RegistrationStatus $status): Participant
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return Participant::factory()->create([
            'event_id' => Event::factory()->registration()->create()->id,
            'user_id' => User::factory()->participant()->create()->id,
            'status' => $status,
        ]);
    }

    private function manager(): User
    {
        return User::factory()->manager()->create();
    }
}
