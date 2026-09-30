<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $invitationUrl) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre accès à CaisseFlow')
            ->markdown('mail.user-invitation', [
                'invitationUrl' => $this->invitationUrl,
                'recipientEmail' => $notifiable->email,
            ]);
    }
}
