<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = route('password.reset', ['token' => $this->token, 'email' => $notifiable->email]);

        return (new MailMessage)
            ->subject('Réinitialisez votre mot de passe CaisseFlow')
            ->markdown('mail.password-reset', [
                'resetUrl' => $resetUrl,
                'recipientEmail' => $notifiable->email,
                'expiration' => config('auth.passwords.users.expire'),
            ]);
    }
}
