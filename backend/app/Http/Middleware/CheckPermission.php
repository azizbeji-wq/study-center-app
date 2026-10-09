<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Gérer la requête entrante et vérifier la permission de l'utilisateur
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        if (! $user->hasPermission($permission)) {
            return response()->json([
                'message' => "Accès refusé : vous n'avez pas l'autorisation requise ({$permission})."
            ], 403);
        }

        return $next($request);
    }
}
