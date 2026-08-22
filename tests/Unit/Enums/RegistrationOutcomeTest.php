<?php

namespace Tests\Unit\Enums;

use App\Enums\RegistrationOutcome;
use App\Enums\RegistrationTransition;
use App\Services\RegistrationLifecycle\CancelledRegistrationState;
use App\Services\RegistrationLifecycle\ConfirmedRegistrationState;
use App\Services\RegistrationLifecycle\PendingRegistrationState;
use App\Services\RegistrationLifecycle\RegistrationLifecycleState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RegistrationOutcomeTest extends TestCase
{
    /**
     * @return array<string, array{RegistrationLifecycleState, RegistrationTransition, RegistrationOutcome}>
     */
    public static function treatments(): array
    {
        return [
            'confirmer une inscription en attente' => [
                new PendingRegistrationState,
                RegistrationTransition::Confirm,
                RegistrationOutcome::Approved,
            ],
            'annuler une inscription en attente' => [
                new PendingRegistrationState,
                RegistrationTransition::Cancel,
                RegistrationOutcome::Refused,
            ],
            'annuler une inscription confirmée' => [
                new ConfirmedRegistrationState,
                RegistrationTransition::Cancel,
                RegistrationOutcome::Cancelled,
            ],
            'remettre une inscription annulée en attente' => [
                new CancelledRegistrationState,
                RegistrationTransition::Reopen,
                RegistrationOutcome::Reopened,
            ],
        ];
    }

    #[Test]
    #[DataProvider('treatments')]
    public function it_reads_the_outcome_on_the_state_left_and_the_transition(
        RegistrationLifecycleState $leaving,
        RegistrationTransition $transition,
        RegistrationOutcome $expected,
    ): void {
        $this->assertSame($expected, RegistrationOutcome::of($leaving, $transition));
    }

    #[Test]
    public function it_gives_every_outcome_a_copy_of_its_own(): void
    {
        $keys = array_map(
            fn (RegistrationOutcome $outcome): string => $outcome->mailKey(),
            RegistrationOutcome::cases(),
        );

        $this->assertSame($keys, array_values(array_unique($keys)));
    }
}
