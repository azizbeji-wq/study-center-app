<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserGuideProgress;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GuideController extends Controller
{
    /**
     * GET /api/guide/progress
     * Retourne les clés complétées ET les booléens de démarrage (sans données personnelles).
     */
    public function progress(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Clés explicitement marquées par l'utilisateur
        $completedKeys = UserGuideProgress::where('user_id', $user->id)
            ->pluck('key')
            ->toArray();

        // Booléens de démarrage (détection automatique)
        // — uniquement pour le directeur et le secrétaire
        $startingChecks = null;
        if (in_array($user->role, ['director', 'secretary'])) {
            $startingChecks = $this->computeStartingChecks();
        }

        return response()->json([
            'completed_keys'  => $completedKeys,
            'starting_checks' => $startingChecks, // null pour les enseignants
        ]);
    }

    /**
     * POST /api/guide/progress/{key}
     * Marque une clé comme complétée pour l'utilisateur connecté.
     */
    public function markDone(Request $request, string $key): JsonResponse
    {
        if (!in_array($key, UserGuideProgress::validKeys(), true)) {
            return response()->json(['error' => 'Clé inconnue.'], 422);
        }

        $user = Auth::user();

        UserGuideProgress::firstOrCreate(
            ['user_id' => $user->id, 'key' => $key],
            ['completed_at' => now()]
        );

        return response()->json(['status' => 'ok', 'key' => $key]);
    }

    /**
     * DELETE /api/guide/progress/{key}
     * Annule une clé pour l'utilisateur connecté.
     */
    public function markUndone(Request $request, string $key): JsonResponse
    {
        if (!in_array($key, UserGuideProgress::validKeys(), true)) {
            return response()->json(['error' => 'Clé inconnue.'], 422);
        }

        $user = Auth::user();

        UserGuideProgress::where('user_id', $user->id)
            ->where('key', $key)
            ->delete();

        return response()->json(['status' => 'ok', 'key' => $key]);
    }

    /**
     * Calcule les booléens de démarrage à partir de la base de données.
     * Ne retourne JAMAIS de données personnelles (noms, montants…).
     */
    private function computeStartingChecks(): array
    {
        // Tarifs : on vérifie la table pricing (ou students avec monthly_fee non nul)
        $hasPricing = DB::table('students')
            ->whereNotNull('monthly_fee')
            ->where('monthly_fee', '>', 0)
            ->exists();

        return [
            // Au moins une matière créée
            'has_subjects'    => Subject::count() > 0,
            // Au moins un enseignant enregistré
            'has_teachers'    => Teacher::count() > 0,
            // Au moins un tarif configuré (mensualité non nulle)
            'has_pricing'     => $hasPricing,
            // Au moins un élève inscrit
            'has_students'    => Student::withoutTrashed()->count() > 0,
            // Au moins une feuille d'appel saisie
            'has_attendances' => Attendance::count() > 0,
        ];
    }
}

