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
            ->greeting('Bienvenue sur CaisseFlow')
            ->line('Un administrateur a créé un accès pour votre adresse e-mail.')
            ->line('Utilisez le bouton ci-dessous pour renseigner votre nom et choisir votre mot de passe.')
            ->action('Créer mon mot de passe', $this->invitationUrl)
            ->line('Ce lien est valable pendant 72 heures. Si vous n’êtes pas concerné, ignorez ce message.');
    }
}
