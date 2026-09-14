<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restreint l'accès aux routes d'administration.
 *
 * Volontairement séparé de la vérification d'authentification (`auth:sanctum`)
 * et de l'autorisation fine par ressource (Policies) : ce middleware ne fait
 * qu'une chose (SRP) — vérifier le rôle — et se combine avec les autres
 * dans routes/api.php.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Réservé aux administrateurs.');
        }

        return $next($request);
    }
}
