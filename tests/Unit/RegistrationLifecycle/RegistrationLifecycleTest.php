<?php

namespace Tests\Unit\RegistrationLifecycle;

use App\Enums\RegistrationStatus;
use App\Enums\RegistrationTransition;
use App\Exceptions\RegistrationTransitionRefusedException;
use App\Services\RegistrationLifecycle\CancelledRegistrationState;
use App\Services\RegistrationLifecycle\ConfirmedRegistrationState;
use App\Services\RegistrationLifecycle\PendingRegistrationState;
use App\Services\RegistrationLifecycle\RegistrationLifecycleFactory;
use App\Services\RegistrationLifecycle\RegistrationLifecycleState;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationLifecycleTest extends TestCase
{
    #[Test]
    public function it_confirms_a_pending_registration(): void
    {
        $this->assertInstanceOf(
            ConfirmedRegistrationState::class,
            $this->state(RegistrationStatus::Pending)->confirm(),
        );
    }

    #[Test]
    public function it_cancels_a_pending_registration(): void
    {
        $this->assertInstanceOf(
            CancelledRegistrationState::class,
            $this->state(RegistrationStatus::Pending)->cancel(),
        );
    }

    #[Test]
    public function it_cancels_a_confirmed_registration(): void
    {
        $this->assertInstanceOf(
            CancelledRegistrationState::class,
            $this->state(RegistrationStatus::Confirmed)->cancel(),
        );
    }

    #[Test]
    public function it_reopens_a_cancelled_registration(): void
    {
        $this->assertInstanceOf(
            PendingRegistrationState::class,
            $this->state(RegistrationStatus::Cancelled)->reopen(),
        );
    }

    #[Test]
    public function it_refuses_to_confirm_a_cancelled_registration(): void
    {
        $this->expectException(RegistrationTransitionRefusedException::class);

        $this->state(RegistrationStatus::Cancelled)->confirm();
    }

    #[Test]
    public function it_refuses_to_confirm_a_confirmed_registration(): void
    {
        $this->expectException(RegistrationTransitionRefusedException::class);

        $this->state(RegistrationStatus::Confirmed)->confirm();
    }

    #[Test]
    public function it_refuses_to_cancel_a_cancelled_registration(): void
    {
        $this->expectException(RegistrationTransitionRefusedException::class);

        $this->state(RegistrationStatus::Cancelled)->cancel();
    }

    #[Test]
    public function it_refuses_to_reopen_a_pending_registration(): void
    {
        $this->expectException(RegistrationTransitionRefusedException::class);

        $this->state(RegistrationStatus::Pending)->reopen();
    }

    #[Test]
    public function it_refuses_to_reopen_a_confirmed_registration(): void
    {
        $this->expectException(RegistrationTransitionRefusedException::class);

        $this->state(RegistrationStatus::Confirmed)->reopen();
    }

    #[Test]
    public function it_lets_only_a_pending_registration_be_corrected_by_its_runner(): void
    {
        $this->assertTrue($this->state(RegistrationStatus::Pending)->isEditableByRunner());
        $this->assertFalse($this->state(RegistrationStatus::Confirmed)->isEditableByRunner());
        $this->assertFalse($this->state(RegistrationStatus::Cancelled)->isEditableByRunner());
    }

    #[Test]
    public function it_counts_only_a_confirmed_registration_against_the_capacity(): void
    {
        $this->assertFalse($this->state(RegistrationStatus::Pending)->consumesASeat());
        $this->assertTrue($this->state(RegistrationStatus::Confirmed)->consumesASeat());
        $this->assertFalse($this->state(RegistrationStatus::Cancelled)->consumesASeat());
    }

    /** A state can list a transition its method refuses, or refuse one it lists. */
    #[Test]
    public function it_agrees_with_its_own_allowed_transitions(): void
    {
        foreach (RegistrationStatus::cases() as $status) {
            foreach (RegistrationTransition::cases() as $transition) {
                $allowed = in_array($transition, $this->state($status)->allowedTransitions(), true);

                $this->assertSame($allowed, $this->accepts($status, $transition), sprintf(
                    '%s says %s is %s but behaves otherwise.',
                    $status->value,
                    $transition->value,
                    $allowed ? 'allowed' : 'refused',
                ));
            }
        }
    }

    #[Test]
    public function it_maps_every_status_to_a_state(): void
    {
        foreach (RegistrationStatus::cases() as $status) {
            $this->assertSame($status, $this->state($status)->status());
        }
    }

    private function accepts(RegistrationStatus $status, RegistrationTransition $transition): bool
    {
        $state = $this->state($status);

        return rescue(
            fn (): bool => $transition->apply($state) instanceof RegistrationLifecycleState,
            false,
            false,
        );
    }

    private function state(RegistrationStatus $status): RegistrationLifecycleState
    {
        return app(RegistrationLifecycleFactory::class)->fromStatus($status);
    }
}
