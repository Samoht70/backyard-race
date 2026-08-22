<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationReceived extends Notification implements ShouldQueue
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
            ->subject(__('mail.registration_received.subject'))
            ->greeting(__('mail.registration_received.heading', ['name' => $notifiable->first_name]))
            ->line(__('mail.registration_received.body'))
            ->line(__('mail.registration_received.code'))
            ->line(view('mail.access-code', ['code' => $this->code]))
            ->line(__('mail.registration_received.keep'))
            ->action(__('mail.registration_received.action'), route('login'))
            ->line(__('mail.registration_received.encouragement'))
            ->salutation(__('mail.registration_received.salutation'));
    }
}
