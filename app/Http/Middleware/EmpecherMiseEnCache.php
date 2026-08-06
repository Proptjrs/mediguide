<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Empêche la mise en cache des pages contenant un jeton CSRF.
 *
 * Sans cela, le navigateur peut réafficher une page de connexion issue de son
 * cache (retour arrière, reconnexion après déconnexion…) : le jeton qu'elle
 * contient ne correspond plus à la session courante et l'envoi du formulaire
 * échoue en « 419 Page Expired ». Forcer la revalidation garantit un jeton frais.
 */
class EmpecherMiseEnCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
