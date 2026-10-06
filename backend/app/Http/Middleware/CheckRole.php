<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $userRole = strtolower((string) $user->role);
        $roleSynonyms = [
            'director' => ['director', 'directeur', 'admin'],
            'directeur' => ['director', 'directeur', 'admin'],
            'secretary' => ['secretary', 'secretaire'],
            'secretaire' => ['secretary', 'secretaire'],
        ];

        $matched = false;
        foreach ($roles as $requiredRole) {
            $requiredLower = strtolower($requiredRole);
            $synonyms = $roleSynonyms[$requiredLower] ?? [$requiredLower];
            if (in_array($userRole, $synonyms, true)) {
                $matched = true;
                break;
            }
        }

        if (! $matched) {
            return response()->json([
                'message' => 'Accès refusé : cette action requiert des privilèges spécifiques (' . implode(', ', $roles) . ').'
            ], 403);
        }

        return $next($request);
    }
}
