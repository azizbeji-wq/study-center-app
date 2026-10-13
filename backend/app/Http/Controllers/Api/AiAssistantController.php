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
            'message' => 'required|string|max:4000',
            'history' => 'nullable|array',
            'history.*.text' => 'nullable|string',
            'history.*.sender' => 'nullable|string',
            'history.*.role' => 'nullable|string',
            'context' => 'nullable|array',
            'context.subject' => 'nullable|string|max:100',
            'context.level' => 'nullable|string|max:100',
            'context.tone' => 'nullable|string|max:50',
        ], [
            'message.required' => 'Le message est obligatoire.',
            'message.max' => 'Le message ne peut pas dépasser 4000 caractères.',
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
