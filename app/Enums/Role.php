<?php

namespace App\Enums;

/**
 * A role is a bundle of permissions at assignment time only: no access decision
 * anywhere may test a role name.
 */
enum Role: string
{
    case Manager = 'manager';
    case Participant = 'participant';
}
