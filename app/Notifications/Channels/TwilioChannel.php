<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\{Http, Log};
use RuntimeException;

/**
 * Canal SMS Twilio (mémoire, chap. 4.2.6).
 *
 * Trois états, selon la configuration :
 *  - TWILIO_SID absent  -> le message est journalisé ([SMS simulé]), l'e-mail reste
 *    le canal opérationnel. C'est le mode utilisé en développement et en soutenance.
 *  - TWILIO_SID présent -> appel réel de l'API. Un échec est journalisé avec le
 *    détail renvoyé par Twilio, puis relancé pour que la file d'attente réessaie
 *    (queue:work --tries=3) : un SMS perdu ne doit pas l'être en silence.
 *
 * Le basculement vers l'API d'un opérateur sénégalais (Orange, Expresso) ne
 * changera que cette classe — le code métier n'en dépend pas (pattern Channel).
 */
class TwilioChannel
{
    /** Twilio répond 201 Created lorsque le message est accepté. */
    private const TIMEOUT_SECONDES = 8;

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $numero = $notifiable->telephone ?? null;
        if (! $numero) {
            return;
        }

        $message = $notification->toSms($notifiable);
        $sid = config('services.twilio.sid');

        if (! $sid) {
            Log::info('[SMS simulé] ' . $numero . ' : ' . $message);

            return;
        }

        $reponse = Http::asForm()
            ->timeout(self::TIMEOUT_SECONDES)
            ->withBasicAuth($sid, config('services.twilio.token'))
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => config('services.twilio.from'),
                'To' => $numero,
                'Body' => $message,
            ]);

        if ($reponse->failed()) {
            Log::error('Échec envoi SMS Twilio', [
                'destinataire' => $numero,
                'statut' => $reponse->status(),
                'code_twilio' => $reponse->json('code'),
                'message_twilio' => $reponse->json('message'),
            ]);

            // Relancé pour laisser la file réessayer (cf. queue:work --tries=3).
            throw new RuntimeException(
                'Envoi SMS refusé par Twilio (HTTP ' . $reponse->status() . ') : '
                . ($reponse->json('message') ?? 'raison non précisée')
            );
        }

        Log::info('SMS envoyé', ['destinataire' => $numero, 'sid_message' => $reponse->json('sid')]);
    }
}
