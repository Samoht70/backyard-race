<?php

namespace App\Services\EventLifecycle;

use App\Enums\EventStatus;
use App\Exceptions\EventTransitionRefusedException;
use App\Models\Event;

interface EventLifecycleState
{
    public function status(): EventStatus;

    public function nextStatus(): ?EventStatus;

    public function previousStatus(): ?EventStatus;

    /**
     * @return list<string>
     */
    public function refusals(Event $event): array;

    /**
     * @return list<string>
     */
    public function revertRefusals(Event $event): array;

    /**
     * @throws EventTransitionRefusedException
     */
    public function advance(Event $event): EventLifecycleState;

    /**
     * @throws EventTransitionRefusedException
     */
    public function revert(Event $event): EventLifecycleState;

    public function allowsRegistration(): bool;

    public function isVisibleToParticipants(): bool;

    public function isEditable(): bool;

    public function isRacing(): bool;

    /**
     * @return list<string>
     */
    public function frozenAttributes(): array;
}
