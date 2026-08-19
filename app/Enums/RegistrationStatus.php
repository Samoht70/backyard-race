<?php

namespace App\Enums;

/**
 * The transition chain is NOT declared here: confirming and cancelling a
 * registration is BR-06's subject, and this enum is only the persisted name.
 */
enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('registration.status.pending'),
            self::Confirmed => __('registration.status.confirmed'),
            self::Cancelled => __('registration.status.cancelled'),
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases(),
        );
    }
}
