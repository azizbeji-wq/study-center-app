import { GoogleGenAI, ThinkingLevel, type Content } from '@google/genai';
import { Router } from 'express';
import { computeDues, hasPermission, resolveTeacherForUser, round2 } from '../helpers';
import { authRequired, AuthedRequest } from '../middleware';
import { store, UserRecord } from '../store';

export const aiRouter = Router();

export type ChatbotRoleKey = 'general' | 'pedagogic' | 'management' | 'communication';
export type ChatbotModelId = 'gemini-4-hydrogen' | 'gemini-3.1-flash-lite';

const ALLOWED_MODELS: ChatbotModelId[] = ['gemini-4-hydrogen', 'gemini-3.1-flash-lite'];

function resolveModel(requested?: unknown): ChatbotModelId {
  if (typeof requested === 'string' && ALLOWED_MODELS.includes(requested as ChatbotModelId)) {
    return requested as ChatbotModelId;
  }
  return 'gemini-4-hydrogen';
}

function getAiClient(): GoogleGenAI {
  const apiKey = process.env.GEMINI_API_KEY?.trim();
  if (!apiKey) {
    const err = new Error('MISSING_API_KEY');
    (err as any).statusCode = 503;
    throw err;
  }

  return new GoogleGenAI({
    apiKey,
    httpOptions: {
      headers: {
        'User-Agent': 'aistudio-build',
      },
    },
  });
}

function buildCenterContext(user: UserRecord): string {
  const activeStudents = store.students.filter((s) => s.status === 'active').length;
  const totalTeachers = store.teachers.length;
  const totalGroups = store.groups.length;
  const daycareCount = store.daycareChildren.length;
  const subjectsList = store.subjects.filter((s) => s.is_active).map((s) => s.name).join(', ');

  if (user.role === 'teacher') {
    const teacher = resolveTeacherForUser(user);
    const teacherGroups = teacher
      ? store.groups
          .filter((g) => g.teacher_id === teacher.id || g.user_id === user.id)
          .map((g) => `${g.name} (${g.subject || 'Général'}, ${g.student_ids.length} élèves)`)
          .join(' ; ')
      : 'Aucun groupe assigné';
    const teacherSchedule = teacher
      ? store.schedules
          .filter((s) => s.teacher.toLowerCase().includes(teacher.name.toLowerCase()))
          .map((s) => `${s.day} ${s.time} — ${s.subject} (${s.group_name || s.level}, ${s.room})`)
          .join(' ; ')
      : '';

    return [
      `Utilisateur connecté : ${user.name} (Rôle : Enseignant).`,
      `Matières du centre : ${subjectsList}.`,
      `Groupes de l'enseignant : ${teacherGroups || 'Aucun'}.`,
      teacherSchedule ? `Emploi du temps de l'enseignant : ${teacherSchedule}.` : '',
    ]
      .filter(Boolean)
      .join('\n');
  }

  const lines = [
    `Utilisateur connecté : ${user.name} (Rôle : ${user.role === 'director' ? 'Directeur' : 'Secrétaire / Personnel'}).`,
    `Effectifs actuels : ${activeStudents} élèves actifs, ${totalTeachers} enseignants, ${totalGroups} groupes pédagogiques, ${daycareCount} enfants en garderie.`,
    `Matières enseignées : ${subjectsList}.`,
  ];

  if (user.role === 'director' || hasPermission(user, 'reports')) {
    const dues = computeDues();
    const totalPaid = round2(
      store.payments.filter((p) => p.status === 'paid').reduce((sum, p) => sum + Number(p.amount), 0)
    );
    const totalExpenses = round2(store.expenses.reduce((sum, e) => sum + Number(e.amount), 0));
    lines.push(
      `Indicateurs financiers (accès direction/rapports) : ${totalPaid} TND encaissés, ${totalExpenses} TND de dépenses enregistrées, ${dues.totals.pendingCount} dossiers en attente (${dues.totals.pending} TND) et ${dues.totals.overdueCount} en retard (${dues.totals.overdue} TND).`
    );
  }

  return lines.join('\n');
}

function buildSystemInstruction(roleKey: ChatbotRoleKey, user: UserRecord): string {
  const baseContext = buildCenterContext(user);

  const roleInstructions: Record<ChatbotRoleKey, string> = {
    general: `Tu es l'Assistant Polyvalent de "Centre d'Études - Gestion", un centre de soutien scolaire et garderie en Tunisie.
Aide l'utilisateur sur la gestion quotidienne du centre, l'utilisation des modules (Élèves, Présences, Paiements, Garderie, Emplois du temps, Groupes, Comptabilité, Portail Enseignant) et les besoins pédagogiques courants.`,

    pedagogic: `Tu es un Conseiller Pédagogique et Enseignant Expert du système éducatif tunisien (Primaire, Collège, Lycée & Baccalauréat tunisien : Mathématiques, Sciences Physiques, SVT, Français, Anglais, Arabe, Informatique, Économie & Gestion, Philosophie).
Aide à concevoir des plans de cours, fiches de révision, exercices progressifs avec corrigés détaillés, et stratégies d'accompagnement par niveau.`,

    management: `Tu es un Conseiller en Organisation et Gestion de Centre d'Études en Tunisie.
Tu aides la direction et le personnel à optimiser les emplois du temps, la répartition des groupes, le suivi des présences, le recouvrement des séances impayées (monnaie : TND / DT) et l'organisation administrative.
Ne révèle jamais de données financières ou salariales si l'utilisateur n'y a pas accès dans son contexte.`,

    communication: `Tu es un Spécialiste en Communication Scolaire (Parents & Élèves) pour un centre d'études tunisien.
Rédige des messages clairs, chaleureux, professionnels et prêts à envoyer (WhatsApp, SMS ou email) en français (avec formules adaptées au contexte tunisien et montants en TND/DT) pour : rappels de règlement, suivi d'absence, changements d'horaire, bilans d'assiduité ou félicitations.`,
  };

  const selectedInstruction = roleInstructions[roleKey] || roleInstructions.general;

  return `${selectedInstruction}

Règles de réponse :
- Réponds toujours en français clair, structuré et directement actionnable (sauf si l'utilisateur demande explicitement une autre langue comme l'arabe ou l'anglais).
- Adapte la longueur à la question : sois concis pour les demandes rapides et structuré avec des listes ou étapes pour les demandes complexes.
- N'invente pas de noms d'élèves ou de montants absents du contexte ci-dessous.

Contexte actuel du centre :
${baseContext}`;
}

interface IncomingHistoryItem {
  role: 'user' | 'model';
  text: string;
}

function normalizeHistory(rawHistory: unknown): Content[] {
  if (!Array.isArray(rawHistory)) return [];
  const cleaned: Content[] = [];
  for (const item of rawHistory.slice(-20)) {
    if (!item || typeof item !== 'object') continue;
    const rec = item as Partial<IncomingHistoryItem & { sender?: string }>;
    const text = typeof rec.text === 'string' ? rec.text.trim() : '';
    if (!text) continue;
    const role: 'user' | 'model' =
      rec.role === 'model' || rec.sender === 'bot' || rec.sender === 'assistant' ? 'model' : 'user';
    cleaned.push({ role, parts: [{ text }] });
  }
  return cleaned;
}

function formatGeminiError(err: any): { status: number; message: string; code: string } {
  if (err?.message === 'MISSING_API_KEY') {
    return {
      status: 503,
      code: 'MISSING_API_KEY',
      message:
        "Aucune clé API Gemini n'est configurée. Veuillez vérifier votre clé dans le panneau Settings > Secrets.",
    };
  }

  const rawMsg = String(err?.message || err || '');
  const statusNum = Number(err?.status || err?.statusCode || 0);

  if (
    statusNum === 400 &&
    (rawMsg.includes('API_KEY_INVALID') || rawMsg.toLowerCase().includes('api key'))
  ) {
    return {
      status: 400,
      code: 'API_KEY_INVALID',
      message:
        'La clé API Gemini est invalide. Veuillez la vérifier dans le panneau Settings > Secrets.',
    };
  }

  if (statusNum === 403 || rawMsg.includes('PERMISSION_DENIED')) {
    return {
      status: 403,
      code: 'PERMISSION_DENIED',
      message:
        "Accès refusé par l'API Gemini (403). Veuillez vérifier la clé sélectionnée dans Settings > Secrets.",
    };
  }

  if (statusNum === 429 || rawMsg.includes('RESOURCE_EXHAUSTED')) {
    return {
      status: 429,
      code: 'RESOURCE_EXHAUSTED',
      message:
        'Quota de requêtes Gemini atteint temporairement (429). Réessayez dans quelques instants ou utilisez une clé avec facturation dans Settings > Secrets.',
    };
  }

  if (statusNum === 404 || rawMsg.includes('NOT_FOUND')) {
    return {
      status: 404,
      code: 'MODEL_NOT_FOUND',
      message: 'Le modèle Gemini demandé est introuvable ou indisponible.',
    };
  }

  return {
    status: statusNum >= 400 && statusNum < 600 ? statusNum : 502,
    code: 'GEMINI_ERROR',
    message: rawMsg || "Une erreur est survenue lors de l'appel à Gemini.",
  };
}

function isRetryableError(err: any): boolean {
  const status = Number(err?.status || err?.statusCode || 0);
  if (status === 500 || status === 502 || status === 503 || status === 504) return true;
  const msg = String(err?.message || '').toLowerCase();
  return msg.includes('fetch failed') || msg.includes('econnreset') || msg.includes('timeout');
}

async function withRetry<T>(fn: () => Promise<T>, maxAttempts = 3): Promise<T> {
  let lastErr: any;
  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    try {
      return await fn();
    } catch (err) {
      lastErr = err;
      if (attempt === maxAttempts || !isRetryableError(err)) {
        throw err;
      }
      await new Promise((resolve) => setTimeout(resolve, 400 * Math.pow(2, attempt - 1)));
    }
  }
  throw lastErr;
}

function buildThinkingConfig(model: ChatbotModelId) {
  if (model === 'gemini-3.1-flash-lite') {
    return { thinkingLevel: ThinkingLevel.MINIMAL };
  }
  return { thinkingLevel: ThinkingLevel.MEDIUM };
}

// Non-streaming multi-turn endpoint (also aliased as /api/ai/ask)
async function handleChatJson(req: AuthedRequest, res: any) {
  const { message, history, role, model: requestedModel } = req.body || {};
  const trimmed = typeof message === 'string' ? message.trim() : '';
  if (!trimmed) {
    return res.status(422).json({ message: 'Le message est obligatoire.' });
  }
  if (trimmed.length > 4000) {
    return res.status(422).json({ message: 'Le message ne peut pas dépasser 4000 caractères.' });
  }

  const chatbotRole: ChatbotRoleKey = ['general', 'pedagogic', 'management', 'communication'].includes(role)
    ? role
    : 'general';
  const model = resolveModel(requestedModel);

  try {
    const ai = getAiClient();
    const chat = ai.chats.create({
      model,
      config: {
        systemInstruction: buildSystemInstruction(chatbotRole, req.user!),
        thinkingConfig: buildThinkingConfig(model),
      },
      history: normalizeHistory(history),
    });

    const response = await withRetry(() => chat.sendMessage({ message: trimmed }));
    const reply = response.text ?? '';

    res.json({
      reply,
      model,
      role: chatbotRole,
      timestamp: new Date().toISOString(),
    });
  } catch (err: any) {
    const formatted = formatGeminiError(err);
    res.status(formatted.status).json({
      message: formatted.message,
      code: formatted.code,
    });
  }
}

aiRouter.post('/ai/chat', authRequired, handleChatJson);
aiRouter.post('/ai/ask', authRequired, handleChatJson);

// Streaming SSE multi-turn endpoint
aiRouter.post('/ai/chat/stream', authRequired, async (req: AuthedRequest, res) => {
  const { message, history, role, model: requestedModel } = req.body || {};
  const trimmed = typeof message === 'string' ? message.trim() : '';
  if (!trimmed) {
    return res.status(422).json({ message: 'Le message est obligatoire.' });
  }
  if (trimmed.length > 4000) {
    return res.status(422).json({ message: 'Le message ne peut pas dépasser 4000 caractères.' });
  }

  const chatbotRole: ChatbotRoleKey = ['general', 'pedagogic', 'management', 'communication'].includes(role)
    ? role
    : 'general';
  const model = resolveModel(requestedModel);

  res.setHeader('Content-Type', 'text/event-stream; charset=utf-8');
  res.setHeader('Cache-Control', 'no-cache, no-transform');
  res.setHeader('Connection', 'keep-alive');
  res.flushHeaders?.();

  const sendEvent = (payload: Record<string, unknown>) => {
    res.write(`data: ${JSON.stringify(payload)}\n\n`);
  };

  try {
    const ai = getAiClient();
    const chat = ai.chats.create({
      model,
      config: {
        systemInstruction: buildSystemInstruction(chatbotRole, req.user!),
        thinkingConfig: buildThinkingConfig(model),
      },
      history: normalizeHistory(history),
    });

    const stream = await withRetry(() => chat.sendMessageStream({ message: trimmed }));

    for await (const chunk of stream) {
      const textChunk = chunk.text;
      if (textChunk) {
        sendEvent({ chunk: textChunk });
      }
    }

    sendEvent({ done: true, model, role: chatbotRole });
    res.end();
  } catch (err: any) {
    const formatted = formatGeminiError(err);
    sendEvent({ error: formatted.message, code: formatted.code, status: formatted.status });
    res.end();
  }
});
