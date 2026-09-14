<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque les comptes désactivés par un administrateur (voir
 * Admin\UserController::update). Le token reste valide mais toute
 * requête authentifiée échoue tant que le compte n'est pas réactivé.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            abort(403, 'Ce compte a été désactivé.');
        }

        return $next($request);
    }
}
