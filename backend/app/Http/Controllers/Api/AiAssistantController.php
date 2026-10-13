<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * POST /api/ai/ask
     * Point d'entrée principal du chatbot assistant Gemini pour Study Center.
     */
    public function ask(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array|max:10',
            'history.*.text' => 'nullable|string|max:1000',
            'history.*.sender' => 'nullable|string|max:20',
            'history.*.role' => 'nullable|string|max:20',
            'context' => 'nullable|array',
            'context.subject' => 'nullable|string|max:100',
            'context.level' => 'nullable|string|max:100',
            'context.tone' => 'nullable|string|max:50',
        ], [
            'message.required' => 'Le message est obligatoire.',
            'message.max' => 'Le message ne peut pas dépasser 1000 caractères.',
            'history.max' => 'L\'historique ne peut pas dépasser 10 éléments.',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $message = trim($data['message']);
        $history = $data['history'] ?? [];
        $context = $data['context'] ?? [];

        // Appel au service Gemini
        $result = $this->geminiService->chat($user, $message, $history, $context);

        // Journal d'activité léger (évite de spammer les logs mais garde trace de l'usage pédagogique)
        if (app()->environment('production')) {
            ActivityLog::record(
                $user,
                'ai_assistant',
                'interaction',
                "Interaction avec l'assistant IA Gemini par {$user->name} (" . ($user->role) . ")",
                ['preview' => mb_substr($message, 0, 80)],
                $request->ip()
            );
        }

        return response()->json($result);
    }
}
