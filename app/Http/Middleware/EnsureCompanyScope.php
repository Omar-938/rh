<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyScope
{
    /**
     * Vérifie que l'utilisateur appartient à une entreprise active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->company_id) {
            abort(403, 'Aucune entreprise associée à ce compte.');
        }

        if ($user->company && $user->company->deleted_at) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Votre compte entreprise a été désactivé.');
        }

        return $next($request);
    }
}
