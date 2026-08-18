<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Prévient l'ancienne adresse qu'elle ne pilote plus le compte.
 *
 * Sans cet avertissement, un administrateur — ou quelqu'un qui aurait pris la
 * main sur un compte d'administration — pourrait rediriger le compte d'un
 * patient vers sa propre boîte, confirmer la nouvelle adresse et en prendre le
 * contrôle sans que le titulaire n'en sache jamais rien. Le message n'empêche
 * pas le changement : il le rend visible chez la personne concernée, ce qui est
 * le propre d'une opération sensible.
 */
class AdresseModifiee extends Notification
{
    use Queueable;

    public function __construct(
        private string $ancienne,
        private User $compte,
        private User $auteur,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('MediGuide — votre adresse a été modifiée')
            ->greeting('Bonjour,')
            ->line("L'adresse électronique du compte de {$this->compte->fullName()} vient d'être "
                . "modifiée par l'administration ({$this->auteur->fullName()}).")
            ->line("Cette adresse — {$this->ancienne} — n'est plus rattachée au compte : les "
                . 'prochains messages partiront vers la nouvelle adresse.')
            ->line("Si vous n'êtes pas à l'origine de ce changement, signalez-le immédiatement à "
                . "l'administration de la structure.")
            ->salutation('MediGuide — Centre Hospitalier Roi Baudouin, Guédiawaye');
    }
}
