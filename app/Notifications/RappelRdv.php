<?php

namespace App\Notifications;

use App\Models\RendezVous;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\Channels\TwilioChannel;
use Illuminate\Notifications\Notification;

/** F5 — Rappel J-1, déclenché par le scheduler (routes/console.php). */
class RappelRdv extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RendezVous $rdv) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', TwilioChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = $this->rdv->medecin;

        return (new MailMessage)
            ->subject('⏰ Rappel : rendez-vous demain — MediGuide')
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line('Rappel : vous avez rendez-vous demain à ' . $this->rdv->date_heure->format('H\hi')
                 . ' avec ' . $m->utilisateur->fullName() . ' (' . $m->specialite->nom . ').')
            ->line('📍 ' . $m->structure->nom . ' — ' . $m->structure->adresse)
            ->action('Voir mon rendez-vous', url('/dashboard'))
            ->line('Présentez-vous 10 minutes en avance. Pour annuler, utilisez votre espace patient.')
            ->salutation('— L\'équipe MediGuide');
    }

    public function toSms(object $notifiable): string
    {
        return 'MediGuide : rappel, RDV demain a ' . $this->rdv->date_heure->format('H\\hi')
             . ' avec ' . $this->rdv->medecin->utilisateur->fullName();
    }

    public function toArray(object $notifiable): array
    {
        return ['titre' => 'Rappel J-1', 'rdv_id' => $this->rdv->id];
    }
}
