<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Vérifie que l'utilisateur a l'un des rôles autorisés.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // role est un Enum — on compare la valeur string
        if (!in_array($request->user()->role->value, $roles, true)) {
            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}
