<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Support role-based quick login (uniquement en environnement local)
        $role = $request->input('role');
        if (app()->environment('local') && in_array($role, ['director', 'secretary', 'teacher'], true)) {
            $email = match ($role) {
                'director' => 'director@studycenter.com',
                'teacher' => 'teacher@studycenter.com',
                default => 'secretary@studycenter.com',
            };
            if (Auth::attempt(['email' => $email, 'password' => $role . '123'])) {
                $user = Auth::user();
                $token = $user->createToken('auth_token', ['*'], now()->addMinutes(120))->plainTextToken;
                return response()->json([
                    'status' => 'success',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                        'email' => $user->email,
                        'permissions' => $user->getEffectivePermissions(),
                        'teacher_id' => $user->teacher?->id,
                    ],
                    'token' => $token,
                    'expires_in' => 7200,
                    'login_time' => now()->toISOString(),
                ]);
            }
        }

        // 2. Support standard email + password credentials
        if ($request->filled('email') && $request->filled('password')) {
            $credentials = [
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ];

            if (Auth::attempt($credentials)) {
                $user = Auth::user();

                if (isset($user->is_active) && !$user->is_active) {
                    return response()->json([
                        'message' => 'Ce compte a été désactivé par la direction. Veuillez contacter le Directeur.'
                    ], 403);
                }

                $token = $user->createToken('auth_token', ['*'], now()->addMinutes(120))->plainTextToken;

                $roleLabel = match ($user->role) {
                    'director' => 'Directeur',
                    'teacher' => 'Enseignant',
                    default => 'Personnel',
                };

                \App\Models\ActivityLog::record(
                    $user,
                    'account',
                    'login',
                    "Connexion de {$user->name} ({$roleLabel})",
                    ['email' => $user->email],
                    $request->ip()
                );

                return response()->json([
                    'status' => 'success',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                        'email' => $user->email,
                        'permissions' => $user->getEffectivePermissions(),
                        'teacher_id' => $user->teacher?->id,
                    ],
                    'token' => $token,
                    'expires_in' => 7200,
                    'login_time' => now()->toISOString(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Adresse email ou mot de passe incorrect.'
        ], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté avec succès']);
    }
}