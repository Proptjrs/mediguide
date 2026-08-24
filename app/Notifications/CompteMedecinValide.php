<?php

namespace App\Notifications;

use App\Models\Medecin;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Annonce au médecin que son inscription vient d'être validée.
 *
 * Sans ce message, la validation ne se voyait que du côté de l'administration :
 * le médecin, lui, restait devant un compte refusé sans savoir quand — ni même
 * si — il serait activé. Il n'avait d'autre recours que de réessayer de se
 * connecter au hasard. L'attente prend fin ici.
 */
class CompteMedecinValide extends Notification
{
    use Queueable;

    public function __construct(private Medecin $medecin) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $utilisateur = $this->medecin->utilisateur;

        return (new MailMessage())
            ->subject('MediGuide — votre inscription est validée')
            ->greeting("Bonjour Docteur {$utilisateur->nom},")
            ->line("Votre numéro d'Ordre {$this->medecin->num_ordre} a été vérifié par "
                . "l'administration : votre compte est désormais actif.")
            ->line('Vous pouvez vous connecter et consulter votre agenda, déclarer vos '
                . 'indisponibilités et clore vos rendez-vous.')
            ->action('Accéder à mon espace', route('login'))
            ->line("Votre planning de consultation est établi par l'administration de "
                . "l'hôpital ; vous pouvez le consulter depuis votre espace.")
            ->salutation('MediGuide — Centre Hospitalier Roi Baudouin');
    }
}
