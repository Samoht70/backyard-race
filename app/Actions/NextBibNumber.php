<?php

namespace App\Actions;

use App\Models\Event;

final class NextBibNumber
{
    /**
     * Reading this outside the event row lock lets two confirmations pick the
     * same number, which the unique index then refuses as a query error.
     */
    public function __invoke(Event $event): int
    {
        return (int) $event->participants()->max('bib_number') + 1;
    }
}
