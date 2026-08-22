<?php

namespace App\Support;

final class OrganiserAddress
{
    public static function configured(): ?string
    {
        $configured = config('race.organiser_email');

        if (! is_string($configured)) {
            return null;
        }

        $address = EmailAddress::normalise($configured);

        return filter_var($address, FILTER_VALIDATE_EMAIL) === false ? null : $address;
    }
}
