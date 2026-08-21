<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $code,
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
        return new MailMessage()
            ->subject(__('mail.registration_confirmed.subject'))
            ->greeting(__('mail.registration_confirmed.heading', ['name' => $notifiable->first_name]))
            ->line(__('mail.registration_confirmed.body'))
            ->line(__('mail.registration_confirmed.code', ['code' => $this->code]))
            ->line(__('mail.registration_confirmed.keep'))
            ->action(__('mail.registration_confirmed.action'), route('login'))
            ->line(__('mail.registration_confirmed.encouragement'))
            ->salutation(__('mail.registration_confirmed.salutation'));
    }
}
