<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationLink extends Notification
{
    public function __construct(
        private readonly string $url,
        private readonly int $lifetimeHours,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__('mail.registration_link.subject'))
            ->greeting(__('mail.registration_link.heading'))
            ->line(__('mail.registration_link.body'))
            ->action(__('mail.registration_link.action'), $this->url)
            ->line(__('mail.registration_link.expires', ['hours' => $this->lifetimeHours]))
            ->line(__('mail.registration_link.ignore'))
            ->salutation(__('mail.registration_link.salutation'));
    }
}
