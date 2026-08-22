<?php

namespace App\Notifications;

use App\Enums\RegistrationOutcome;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationProcessed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly RegistrationOutcome $outcome,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mailKey = $this->outcome->mailKey();
        $replacements = ['name' => $notifiable->first_name];

        return new MailMessage()
            ->subject(__($mailKey.'.subject', $replacements))
            ->greeting(__($mailKey.'.heading', $replacements))
            ->line(__($mailKey.'.body', $replacements))
            ->action(__($mailKey.'.action'), route('registration.show'))
            ->line(__($mailKey.'.closing', $replacements))
            ->salutation(__($mailKey.'.salutation'));
    }
}
