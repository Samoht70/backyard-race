<?php

namespace Tests\Concerns;

use App\Models\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;

trait RegistersRunners
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function openEvent(array $attributes = []): Event
    {
        return Event::factory()->registration()->create($attributes);
    }

    protected function registrationLink(string $email): string
    {
        return URL::temporarySignedRoute('account.edit', now()->addHours(48), ['email' => $email]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function registrationPayload(array $overrides = []): array
    {
        return [
            'first_name' => 'Nouvelle',
            'last_name' => 'Recrue',
            'phone' => '06 12 34 56 78',
            'birth_date' => '1986-04-17',
            'emergency_contact_name' => 'Camille Berger',
            'emergency_contact_phone' => '06 87 65 43 21',
            'notes' => 'Allergie aux fruits à coque.',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function completeRegistration(string $email, array $overrides = []): TestResponse
    {
        $this->get($this->registrationLink($email));

        return $this->put(route('account.update'), $this->registrationPayload($overrides));
    }
}
