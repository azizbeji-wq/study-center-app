<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Support role-based quick login
        $role = $request->input('role');
        if ($role === 'director' || $role === 'secretary') {
            $email = $role === 'director' ? 'director@studycenter.com' : 'secretary@studycenter.com';
            if (Auth::attempt(['email' => $email, 'password' => $role . '123'])) {
                $user = Auth::user();
                $token = $user->createToken('auth_token')->plainTextToken;
                return response()->json([
                    'status' => 'success',
                    'user' => [
                        'name' => $user->name,
                        'role' => $user->role,
                        'email' => $user->email,
                    ],
                    'token' => $token,
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
                $token = $user->createToken('auth_token')->plainTextToken;
                return response()->json([
                    'status' => 'success',
                    'user' => [
                        'name' => $user->name,
                        'role' => $user->role,
                        'email' => $user->email,
                    ],
                    'token' => $token,
                ]);
            }
        }

        return response()->json([
            'message' => 'Identifiants invalides'
        ], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté avec succès']);
    }
}
