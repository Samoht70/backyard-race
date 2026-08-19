<?php

namespace App\Enums;

/**
 * The nine abilities of the event: the single source read by the roles seeder,
 * the `can:` route middleware and the shared `auth.permissions` prop. No
 * label() — permissions are never displayed, there is no account admin screen.
 */
enum Permission: string
{
    case ManageEvent = 'manage-event';
    case ManageParticipants = 'manage-participants';
    case ManageLaps = 'manage-laps';
    case ValidateLaps = 'validate-laps';
    case ManageDocuments = 'manage-documents';
    case ManageRoute = 'manage-route';
    case ManageGallery = 'manage-gallery';
    case ViewStatistics = 'view-statistics';
    case FinishEvent = 'finish-event';
}
