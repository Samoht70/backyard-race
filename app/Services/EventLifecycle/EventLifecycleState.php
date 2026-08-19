<?php

namespace App\Services\EventLifecycle;

use App\Enums\EventStatus;
use App\Exceptions\EventTransitionRefusedException;
use App\Models\Event;

/**
 * The event lifecycle, declared once. Each state owns the single step out of
 * itself, so skipping a status or going back is not rejected — it cannot be
 * asked for: no caller can express `running -> registration`.
 */
interface EventLifecycleState
{
    public function status(): EventStatus;

    /**
     * The only status this one may move to, null once the lifecycle is over.
     */
    public function nextStatus(): ?EventStatus;

    /**
     * Translated reasons the next step is refused; empty means it may proceed.
     * Already translated rather than keys: an interpolated __() key is
     * invisible to Larastan's checkMissingTranslations.
     *
     * @return list<string>
     */
    public function refusals(Event $event): array;

    /**
     * @throws EventTransitionRefusedException when the lifecycle is over or a
     *                                         precondition is missing
     */
    public function advance(Event $event): EventLifecycleState;

    public function allowsRegistration(): bool;

    public function isVisibleToParticipants(): bool;

    public function isEditable(): bool;

    /**
     * Attributes the manager may no longer change in this status.
     *
     * @return list<string>
     */
    public function frozenAttributes(): array;
}
