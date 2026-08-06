<?php

namespace App\Notifications;

use App\Models\RendezVous;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\Channels\TwilioChannel;
use Illuminate\Notifications\Notification;

/** F5 — Confirmation de RDV. Canal mail opérationnel (SMTP Brevo gratuit) ;
 *  canal SMS Twilio (trial) activable en ajoutant 'twilio' au tableau via(). */
class RdvConfirme extends Notification implements ShouldQueue
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
            ->subject('✅ Votre rendez-vous MediGuide est confirmé')
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("Votre rendez-vous avec {$m->utilisateur->fullName()} ({$m->specialite->nom}) est confirmé.")
            ->line('📅 ' . $this->rdv->date_heure->translatedFormat('l j F Y à H\hi'))
            ->line('📍 ' . $m->structure->nom . ' — ' . $m->structure->adresse)
            ->action('Voir mon tableau de bord', url('/dashboard'))
            ->line('Un rappel vous sera envoyé la veille. Présentez-vous 10 minutes en avance.')
            ->salutation('— L\'équipe MediGuide');
    }

    public function toSms(object $notifiable): string
    {
        return 'MediGuide : RDV confirme avec ' . $this->rdv->medecin->utilisateur->fullName()
             . ' le ' . $this->rdv->date_heure->format('d/m a H\\hi')
             . ' - ' . $this->rdv->medecin->structure->nom;
    }

    public function toArray(object $notifiable): array
    {
        return ['titre' => 'RDV confirme', 'rdv_id' => $this->rdv->id,
                'date' => $this->rdv->date_heure->toDateTimeString()];
    }
}
