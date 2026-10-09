<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Récupérer le profil de l'utilisateur connecté
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => $user->is_active ?? true,
            'created_at' => $user->created_at?->format('Y-m-d H:i'),
        ]);
    }

    /**
     * Modifier ses propres informations de profil (Nom, Email, Téléphone)
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:40',
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée par un autre compte.',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Modifier son propre mot de passe
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'L\'ancien mot de passe est obligatoire.',
            'new_password.required' => 'Le nouveau mot de passe est obligatoire.',
            'new_password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
            'new_password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'L\'ancien mot de passe est incorrect.',
                'errors' => [
                    'current_password' => ['L\'ancien mot de passe fourni ne correspond pas à votre mot de passe actuel.']
                ]
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Votre mot de passe a été modifié avec succès.'
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // GESTION DES UTILISATEURS DU CENTRE (RÉSERVÉ AU DIRECTEUR)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Lister les utilisateurs du centre
     */
    public function index(Request $request)
    {
        $users = User::select('id', 'name', 'email', 'phone', 'role', 'is_active', 'created_at')
            ->orderByRaw("CASE WHEN role = 'director' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'phone' => $u->phone,
                    'role' => $u->role,
                    'is_active' => (bool) $u->is_active,
                    'is_founder' => $u->id === 1,
                    'created_at' => $u->created_at?->format('d/m/Y H:i'),
                ];
            });

        return response()->json($users);
    }

    /**
     * Créer un nouveau compte (Secrétaire ou Directeur Adjoint)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:40',
            'password' => 'required|string|min:6',
            'role' => 'required|in:secretary,director',
        ], [
            'name.required' => 'Le nom complet est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.unique' => 'Cette adresse email est déjà enregistrée.',
            'password.required' => 'Le mot de passe initial est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
            'role.in' => 'Le rôle doit être soit Personnel, soit Directeur.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        \App\Models\ActivityLog::record(
            $request->user(),
            'account',
            'created',
            "Création du compte " . ($user->role === 'secretary' ? 'personnel' : 'directeur') . " : {$user->name} ({$user->email})",
            ['user_id' => $user->id, 'email' => $user->email, 'role' => $user->role],
            $request->ip()
        );

        return response()->json([
            'message' => 'Compte ' . ($user->role === 'secretary' ? 'personnel' : 'directeur') . ' créé avec succès.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_active' => true,
                'is_founder' => false,
                'created_at' => $user->created_at?->format('d/m/Y H:i'),
            ],
        ], 201);
    }

    /**
     * Mettre à jour un compte utilisateur
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:40',
            'role' => 'sometimes|in:secretary,director',
            'is_active' => 'sometimes|boolean',
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.unique' => 'Cet email est déjà pris.',
        ]);

        // Protection Idée 1 : Le compte directeur maître fondateur (ID 1) ne peut pas être rétrogradé en secrétaire
        if ($user->id === 1 && isset($validated['role']) && $validated['role'] !== 'director') {
            return response()->json([
                'message' => 'Le compte Directeur Fondateur ne peut pas changer de rôle.'
            ], 422);
        }

        // Protection Idée 1 : Le compte directeur maître fondateur ne peut pas être désactivé
        if ($user->id === 1 && isset($validated['is_active']) && !$validated['is_active']) {
            return response()->json([
                'message' => 'Le compte Directeur Fondateur ne peut pas être désactivé.'
            ], 422);
        }

        $user->update($validated);

        \App\Models\ActivityLog::record(
            $request->user(),
            'account',
            'updated',
            "Mise à jour du profil/compte de {$user->name}",
            ['user_id' => $user->id, 'role' => $user->role],
            $request->ip()
        );

        return response()->json([
            'message' => 'Compte mis à jour avec succès.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,
                'is_founder' => $user->id === 1,
            ],
        ]);
    }

    /**
     * Réinitialiser le mot de passe d'une secrétaire ou d'un utilisateur
     */
    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'password' => 'required|string|min:6',
        ], [
            'password.required' => 'Le nouveau mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        \App\Models\ActivityLog::record(
            $request->user(),
            'account',
            'password_reset',
            "Réinitialisation du mot de passe de {$user->name}",
            ['user_id' => $user->id],
            $request->ip()
        );

        return response()->json([
            'message' => 'Le mot de passe de ' . $user->name . ' a été réinitialisé avec succès.'
        ]);
    }

    /**
     * Supprimer un compte
     */
    public function destroy(Request $request, $id)
    {
        $currentUser = $request->user();

        // 1. Interdire l'auto-suppression
        if ((int) $id === (int) $currentUser->id) {
            return response()->json([
                'message' => 'Sécurité : Vous ne pouvez pas supprimer votre propre compte actuellement connecté.'
            ], 403);
        }

        // 2. Protection Idée 1 : Le compte directeur fondateur (ID 1) est sanctuarisé
        if ((int) $id === 1) {
            return response()->json([
                'message' => 'Sécurité : Le compte Directeur Maître Fondateur est sanctuarisé et ne peut pas être supprimé.'
            ], 403);
        }

        $user = User::findOrFail($id);
        $name = $user->name;
        $user->delete();

        \App\Models\ActivityLog::record(
            $currentUser,
            'account',
            'deleted',
            "Suppression du compte utilisateur {$name}",
            ['user_id' => $id],
            $request->ip()
        );

        return response()->json([
            'message' => "Le compte de {$name} a été supprimé avec succès."
        ]);
    }
}
