<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;

class EmbedBrandMarkListener
{
    public const CID = 'brand-mark';

    public function handle(MessageSending $event): void
    {
        $event->message->embedFromPath(public_path('logos/mark.png'), self::CID);
    }
}
