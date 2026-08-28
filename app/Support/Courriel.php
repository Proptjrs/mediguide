<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

/**
 * Enveloppe les envois de courriel pour qu'une panne du serveur de messagerie
 * n'annule pas l'action de l'utilisateur.
 *
 * L'envoi se fait au moment même de la requête : tant que le serveur SMTP
 * répond, tout va bien. Mais s'il est injoignable — coupure réseau, port
 * bloqué, opérateur lent — l'exception remontait jusqu'au navigateur et
 * l'inscription échouait avec une erreur 500, alors que le compte venait
 * pourtant d'être créé. L'utilisateur se retrouvait devant une page d'erreur
 * pour un compte qui existait.
 *
 * Le courriel est un accessoire de l'action, pas l'action elle-même : son échec
 * est consigné dans le journal du serveur et l'utilisateur poursuit son
 * parcours. Les liens de confirmation et de réinitialisation peuvent être
 * redemandés depuis l'application.
 */
final class Courriel
{
    /**
     * Exécute un envoi et absorbe la panne du serveur de messagerie.
     *
     * @return bool vrai si le message est parti
     */
    public static function tenter(callable $envoi, string $quoi = 'courriel'): bool
    {
        try {
            $envoi();

            return true;
        } catch (TransportExceptionInterface $e) {
            Log::warning("Courriel non remis ({$quoi}) : le serveur de messagerie n'a pas répondu.", [
                'erreur' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            Log::warning("Courriel non remis ({$quoi}).", ['erreur' => $e->getMessage()]);
        }

        return false;
    }
}
