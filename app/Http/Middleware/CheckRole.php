<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Middleware du mémoire (chap. 4.2.2) : vérifie le champ role de users. */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless(
            $request->user() && in_array($request->user()->role, $roles),
            403,
            'Accès réservé au rôle : ' . implode(', ', $roles)
        );

        return $next($request);
    }
}
