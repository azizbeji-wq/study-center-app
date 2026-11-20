<template>
  <Teleport to="body">
    <!-- Floating Launch Button -->
    <button
      v-if="!isOpen"
      type="button"
      @click="openChat"
      class="fixed bottom-5 right-5 z-[60] flex items-center gap-2.5 px-4 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-600/25 border border-indigo-500 transition-all cursor-pointer group focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
      aria-label="Ouvrir l'assistant IA"
    >
      <div class="w-7 h-7 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
        <Sparkles class="w-4 h-4 text-white group-hover:scale-110 transition-transform" />
      </div>
      <div class="text-left leading-tight">
        <span class="block text-xs font-bold tracking-tight">Assistant IA</span>
        <span class="block text-[10px] text-indigo-100 font-medium">
          {{ messages.length > 0 ? `${messages.length} message${messages.length > 1 ? 's' : ''}` : 'Poser une question' }}
        </span>
      </div>
    </button>

    <!-- Backdrop on mobile -->
    <div
      v-if="isOpen"
      class="fixed inset-0 z-[64] bg-gray-900/30 backdrop-blur-[1px] sm:hidden"
      @click="isOpen = false"
    />

    <!-- Chat Panel -->
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 translate-y-4 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-4 scale-95"
    >
      <section
        v-if="isOpen"
        role="dialog"
        aria-modal="true"
        aria-label="Assistant IA du Centre d'Études"
        :class="[
          'fixed z-[65] bg-white border border-gray-200 shadow-2xl flex flex-col overflow-hidden transition-all duration-200',
          'inset-0 sm:inset-auto sm:bottom-5 sm:right-5 sm:rounded-2xl',
          isExpanded
            ? 'sm:w-[540px] sm:h-[min(720px,calc(100vh-2.5rem))]'
            : 'sm:w-[410px] sm:h-[min(620px,calc(100vh-2.5rem))]'
        ]"
        @keydown.escape="isOpen = false"
      >
        <!-- Header -->
        <header class="px-4 py-3 bg-white border-b border-gray-200 shrink-0">
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                <Bot class="w-5 h-5" />
              </div>
              <div class="min-w-0">
                <h2 class="text-sm font-bold text-gray-900 leading-tight truncate">
                  Assistant Centre d'Études
                </h2>
                <p class="text-[11px] text-gray-500 truncate">
                  {{ currentRoleMeta.subtitle }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-1 shrink-0">
              <button
                type="button"
                @click="isExpanded = !isExpanded"
                class="hidden sm:flex w-8 h-8 items-center justify-center rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors cursor-pointer"
                :title="isExpanded ? 'Réduire la fenêtre' : 'Agrandir la fenêtre'"
                :aria-label="isExpanded ? 'Réduire la fenêtre' : 'Agrandir la fenêtre'"
              >
                <Minimize2 v-if="isExpanded" class="w-4 h-4" />
                <Maximize2 v-else class="w-4 h-4" />
              </button>

              <button
                type="button"
                @click="clearConversation"
                :disabled="isStreaming || messages.length === 0"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 disabled:opacity-40 disabled:pointer-events-none transition-colors cursor-pointer"
                title="Nouvelle conversation"
                aria-label="Nouvelle conversation"
              >
                <Trash2 class="w-4 h-4" />
              </button>

              <button
                type="button"
                @click="isOpen = false"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors cursor-pointer"
                title="Fermer"
                aria-label="Fermer l'assistant"
              >
                <X class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Role Selector & Model Mode -->
          <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-1 overflow-x-auto pb-0.5 max-w-full">
              <button
                v-for="roleOption in availableRoles"
                :key="roleOption.key"
                type="button"
                @click="selectedRole = roleOption.key"
                :class="[
                  'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-colors whitespace-nowrap cursor-pointer border',
                  selectedRole === roleOption.key
                    ? 'bg-indigo-50 text-indigo-700 border-indigo-200'
                    : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'
                ]"
              >
                <component :is="roleOption.icon" class="w-3 h-3 shrink-0" />
                <span>{{ roleOption.label }}</span>
              </button>
            </div>

            <!-- Model Speed Switch -->
            <div
              class="inline-flex items-center bg-gray-100 rounded-lg p-0.5 border border-gray-200 shrink-0"
              title="Choisir entre le mode standard et le mode réponse rapide"
            >
              <button
                type="button"
                @click="selectedModel = 'gemini-4-hydrogen'"
                :class="[
                  'px-2 py-0.5 rounded-md text-[10px] font-semibold transition-colors cursor-pointer flex items-center gap-1',
                  selectedModel === 'gemini-4-hydrogen'
                    ? 'bg-white text-indigo-700 shadow-2xs'
                    : 'text-gray-600 hover:text-gray-900'
                ]"
              >
                <Sparkles class="w-2.5 h-2.5" />
                <span>Standard</span>
              </button>
              <button
                type="button"
                @click="selectedModel = 'gemini-3.1-flash-lite'"
                :class="[
                  'px-2 py-0.5 rounded-md text-[10px] font-semibold transition-colors cursor-pointer flex items-center gap-1',
                  selectedModel === 'gemini-3.1-flash-lite'
                    ? 'bg-white text-amber-700 shadow-2xs'
                    : 'text-gray-600 hover:text-gray-900'
                ]"
              >
                <Zap class="w-2.5 h-2.5" />
                <span>Rapide</span>
              </button>
            </div>
          </div>
        </header>

        <!-- Contextual Screen Banner (when opened from Guide) -->
        <div
          v-if="screenContextLabel"
          class="px-4 py-2.5 bg-indigo-50/80 border-b border-indigo-100 shrink-0"
        >
          <div class="flex items-center justify-between gap-2">
            <p class="text-[11px] font-semibold text-indigo-900 truncate">
              Contexte actif : <span class="font-bold">{{ screenContextLabel }}</span>
            </p>
            <button
              type="button"
              @click="clearScreenContext"
              class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-900 cursor-pointer shrink-0"
            >
              Effacer
            </button>
          </div>
          <div v-if="contextualPrompts.length > 0 && !isStreaming" class="flex flex-wrap gap-1.5 mt-2">
            <button
              v-for="(q, idx) in contextualPrompts"
              :key="idx"
              type="button"
              @click="sendSuggestedPrompt(q)"
              class="text-left px-2.5 py-1 rounded-lg bg-white hover:bg-indigo-100/70 border border-indigo-200/80 text-[11px] font-medium text-indigo-800 transition-colors cursor-pointer line-clamp-1"
            >
              {{ q }}
            </button>
          </div>
        </div>

        <!-- Scrollable Message Thread -->
        <div
          ref="threadRef"
          class="flex-1 overflow-y-auto px-4 py-4 space-y-4 bg-gray-50/70"
        >
          <!-- Empty State + Starter Prompts -->
          <div v-if="messages.length === 0" class="py-4 space-y-4">
            <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-2xs">
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                  <component :is="currentRoleMeta.icon" class="w-4 h-4" />
                </div>
                <div>
                  <p class="text-xs font-bold text-gray-900">
                    Bonjour {{ userName }}, mode « {{ currentRoleMeta.label }} » actif
                  </p>
                  <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                    {{ currentRoleMeta.description }}
                  </p>
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide px-1">
                Suggestions rapides
              </p>
              <button
                v-for="(promptText, idx) in currentRoleMeta.starters"
                :key="idx"
                type="button"
                @click="sendSuggestedPrompt(promptText)"
                class="w-full text-left px-3.5 py-2.5 rounded-xl bg-white hover:bg-indigo-50/70 border border-gray-200 hover:border-indigo-200 text-xs font-medium text-gray-700 hover:text-indigo-800 transition-colors cursor-pointer shadow-2xs"
              >
                {{ promptText }}
              </button>
            </div>
          </div>

          <!-- Conversation History -->
          <div
            v-for="msg in messages"
            :key="msg.id"
            :class="[
              'flex gap-2.5',
              msg.role === 'user' ? 'justify-end' : 'justify-start'
            ]"
          >
            <div
              v-if="msg.role === 'model'"
              class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0 mt-0.5"
            >
              <Bot class="w-4 h-4" />
            </div>

            <div
              :class="[
                'max-w-[85%] rounded-2xl px-3.5 py-2.5 text-xs leading-relaxed shadow-2xs',
                msg.role === 'user'
                  ? 'bg-indigo-600 text-white rounded-br-sm'
                  : 'bg-white border border-gray-200 text-gray-800 rounded-bl-sm'
              ]"
            >
              <div
                v-if="msg.role === 'user'"
                class="whitespace-pre-wrap break-words"
              >
                {{ msg.text }}
              </div>
              <div v-else class="space-y-1.5">
                <div
                  v-if="msg.text"
                  class="whitespace-pre-wrap break-words space-y-1"
                  v-html="formatBotMessage(msg.text)"
                />
                <div v-else-if="msg.streaming" class="flex items-center gap-1.5 text-gray-400 py-0.5">
                  <Loader2 class="w-3.5 h-3.5 animate-spin text-indigo-600" />
                  <span>Rédaction en cours...</span>
                </div>

                <div
                  v-if="msg.text && !msg.streaming"
                  class="flex items-center justify-between gap-2 pt-1.5 mt-1 border-t border-gray-100 text-[10px] text-gray-400"
                >
                  <span>{{ formatTime(msg.timestamp) }}</span>
                  <button
                    type="button"
                    @click="copyMessage(msg)"
                    class="inline-flex items-center gap-1 text-gray-500 hover:text-indigo-600 font-medium cursor-pointer"
                  >
                    <Check v-if="copiedId === msg.id" class="w-3 h-3 text-emerald-600" />
                    <Copy v-else class="w-3 h-3" />
                    <span>{{ copiedId === msg.id ? 'Copié' : 'Copier' }}</span>
                  </button>
                </div>
              </div>
            </div>

            <div
              v-if="msg.role === 'user'"
              class="w-7 h-7 rounded-lg bg-gray-200 text-gray-700 flex items-center justify-center shrink-0 mt-0.5"
            >
              <User class="w-4 h-4" />
            </div>
          </div>

          <!-- Error Banner -->
          <div
            v-if="errorMessage"
            role="alert"
            class="flex items-start gap-2.5 p-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs"
          >
            <AlertCircle class="w-4 h-4 text-red-600 shrink-0 mt-0.5" />
            <div class="flex-1 min-w-0">
              <p class="font-semibold">Impossible d'obtenir une réponse</p>
              <p class="mt-0.5 text-red-700 leading-relaxed">{{ errorMessage }}</p>
            </div>
          </div>
        </div>

        <!-- Input Composer -->
        <footer class="p-3 bg-white border-t border-gray-200 shrink-0">
          <form @submit.prevent="handleSend" class="flex items-end gap-2">
            <textarea
              ref="inputRef"
              v-model="draft"
              rows="1"
              :disabled="isStreaming"
              placeholder="Écrivez votre message... (Entrée pour envoyer)"
              class="flex-1 resize-none rounded-xl border border-gray-300 bg-gray-50 focus:bg-white px-3.5 py-2.5 text-xs text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 max-h-28 overflow-y-auto disabled:opacity-60"
              @keydown.enter.exact.prevent="handleSend"
              @input="autoResizeTextarea"
            />
            <button
              type="submit"
              :disabled="isStreaming || !draft.trim()"
              class="h-10 w-10 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white flex items-center justify-center shrink-0 transition-colors cursor-pointer shadow-xs"
              aria-label="Envoyer le message"
            >
              <Loader2 v-if="isStreaming" class="w-4 h-4 animate-spin" />
              <Send v-else class="w-4 h-4" />
            </button>
          </form>
        </footer>
      </section>
    </transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import {
  AlertCircle,
  Bot,
  Briefcase,
  Check,
  Copy,
  GraduationCap,
  Loader2,
  Maximize2,
  MessageCircle,
  Minimize2,
  Send,
  Sparkles,
  Trash2,
  User,
  X,
  Zap,
} from 'lucide-vue-next';
import { getStoredToken } from '../utils/session';
import { subscribeOpenAssistant, type OpenAssistantPayload } from '../utils/assistantBridge';

type ChatbotRoleKey = 'general' | 'pedagogic' | 'management' | 'communication';
type ChatbotModelId = 'gemini-4-hydrogen' | 'gemini-3.1-flash-lite';

interface ChatMessage {
  id: string;
  role: 'user' | 'model';
  text: string;
  timestamp: string;
  streaming?: boolean;
}

const props = defineProps<{
  userRole: 'director' | 'secretary' | 'teacher';
  userName: string;
}>();

const isOpen = ref(false);
const isExpanded = ref(false);
const isStreaming = ref(false);
const draft = ref('');
const errorMessage = ref('');
const copiedId = ref<string | null>(null);

const selectedRole = ref<ChatbotRoleKey>(props.userRole === 'teacher' ? 'pedagogic' : 'general');
const selectedModel = ref<ChatbotModelId>('gemini-4-hydrogen');
const messages = ref<ChatMessage[]>([]);
const screenContextLabel = ref('');
const contextualPrompts = ref<string[]>([]);

const threadRef = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLTextAreaElement | null>(null);
let unsubscribeAssistantBridge: (() => void) | null = null;

const storageKey = computed(() => `studycenter_chat_${props.userRole}_${props.userName}`);

const allRoleOptions = [
  {
    key: 'general' as ChatbotRoleKey,
    label: 'Assistant Centre',
    subtitle: 'Aide générale & plateforme',
    icon: Sparkles,
    description:
      'Posez vos questions sur les fonctionnalités du centre, la gestion quotidienne ou demandez une aide rapide.',
    starters: [
      'Comment sont calculés les impayés des élèves et de la garderie ?',
      'Résume la situation actuelle du centre (effectifs et matières).',
      'Explique les 3 formules de rémunération des enseignants.',
    ],
  },
  {
    key: 'pedagogic' as ChatbotRoleKey,
    label: 'Pédagogie',
    subtitle: 'Programme tunisien & cours',
    icon: GraduationCap,
    description:
      'Concevez des fiches de révision, séries d’exercices et plans de séance adaptés au programme tunisien.',
    starters: [
      'Prépare un plan de séance d’1h30 en Mathématiques niveau Bac Math sur les nombres complexes.',
      'Crée 5 exercices progressifs de Français pour la 9ème Année de Base avec corrigé.',
      'Propose une méthode pour aider un élève en difficulté en Sciences Physiques.',
    ],
  },
  {
    key: 'management' as ChatbotRoleKey,
    label: 'Gestion & Suivi',
    subtitle: 'Organisation & administration',
    icon: Briefcase,
    description:
      'Optimisez l’organisation des groupes, les emplois du temps, le suivi d’assiduité et le recouvrement.',
    starters: [
      'Propose une check-list hebdomadaire pour le suivi des présences et des paiements.',
      'Comment organiser efficacement les créneaux des salles sans conflits ?',
      'Quelles bonnes pratiques appliquer pour réduire les retards de paiement ?',
    ],
  },
  {
    key: 'communication' as ChatbotRoleKey,
    label: 'Messages Parents',
    subtitle: 'Rédaction WhatsApp & SMS',
    icon: MessageCircle,
    description:
      'Rédigez des messages professionnels et courtois prêts à envoyer aux parents sur WhatsApp ou par SMS.',
    starters: [
      'Rédige un message WhatsApp courtois pour rappeler le règlement de 4 séances en attente.',
      'Écris un message pour informer les parents d’un report de séance au samedi matin.',
      'Rédige un court bilan positif à envoyer aux parents après un mois assidu.',
    ],
  },
];

const availableRoles = computed(() => {
  if (props.userRole === 'teacher') {
    return allRoleOptions.filter((r) => r.key !== 'management');
  }
  return allRoleOptions;
});

const currentRoleMeta = computed(
  () => availableRoles.value.find((r) => r.key === selectedRole.value) || availableRoles.value[0]
);

function handleOpenFromGuide(payload: OpenAssistantPayload) {
  if (payload.roleMode) {
    const allowed = availableRoles.value.some((r) => r.key === payload.roleMode);
    if (allowed) {
      selectedRole.value = payload.roleMode;
    }
  }
  screenContextLabel.value = payload.contextLabel || '';
  contextualPrompts.value = Array.isArray(payload.suggestedPrompts)
    ? payload.suggestedPrompts.slice(0, 3)
    : [];

  openChat();

  if (payload.prompt && payload.prompt.trim()) {
    nextTick(() => {
      if (payload.autoSend && !isStreaming.value) {
        sendSuggestedPrompt(payload.prompt!.trim());
      } else {
        draft.value = payload.prompt!.trim();
        autoResizeTextarea();
        inputRef.value?.focus();
      }
    });
  }
}

function clearScreenContext() {
  screenContextLabel.value = '';
  contextualPrompts.value = [];
}

onMounted(() => {
  loadHistory();
  unsubscribeAssistantBridge = subscribeOpenAssistant(handleOpenFromGuide);
});

onUnmounted(() => {
  if (unsubscribeAssistantBridge) {
    unsubscribeAssistantBridge();
    unsubscribeAssistantBridge = null;
  }
});

watch(
  () => [props.userRole, props.userName],
  () => {
    if (props.userRole === 'teacher' && selectedRole.value === 'management') {
      selectedRole.value = 'pedagogic';
    }
    loadHistory();
  }
);

function loadHistory() {
  try {
    const raw = sessionStorage.getItem(storageKey.value);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed)) {
        messages.value = parsed.map((m) => ({ ...m, streaming: false }));
        return;
      }
    }
  } catch {
    // Ignore storage read errors
  }
  messages.value = [];
}

function saveHistory() {
  try {
    const serializable = messages.value
      .filter((m) => !m.streaming && m.text.trim() !== '')
      .slice(-40);
    sessionStorage.setItem(storageKey.value, JSON.stringify(serializable));
  } catch {
    // Ignore storage quota errors
  }
}

async function scrollToBottom() {
  await nextTick();
  if (threadRef.value) {
    threadRef.value.scrollTop = threadRef.value.scrollHeight;
  }
}

function openChat() {
  isOpen.value = true;
  nextTick(() => {
    scrollToBottom();
    inputRef.value?.focus();
  });
}

function clearConversation() {
  if (isStreaming.value) return;
  messages.value = [];
  errorMessage.value = '';
  sessionStorage.removeItem(storageKey.value);
}

function autoResizeTextarea() {
  const el = inputRef.value;
  if (!el) return;
  el.style.height = 'auto';
  el.style.height = `${Math.min(el.scrollHeight, 112)}px`;
}

function sendSuggestedPrompt(text: string) {
  draft.value = text;
  handleSend();
}

async function handleSend() {
  const text = draft.value.trim();
  if (!text || isStreaming.value) return;

  errorMessage.value = '';
  draft.value = '';
  if (inputRef.value) {
    inputRef.value.style.height = 'auto';
  }

  const historySnapshot = messages.value
    .filter((m) => !m.streaming && m.text.trim() !== '')
    .slice(-20)
    .map((m) => ({ role: m.role, text: m.text }));

  const userMsg: ChatMessage = {
    id: `u_${Date.now()}`,
    role: 'user',
    text,
    timestamp: new Date().toISOString(),
  };

  const botMsg: ChatMessage = {
    id: `m_${Date.now() + 1}`,
    role: 'model',
    text: '',
    timestamp: new Date().toISOString(),
    streaming: true,
  };

  messages.value.push(userMsg, botMsg);
  isStreaming.value = true;
  await scrollToBottom();

  const botIndex = messages.value.length - 1;

  try {
    const token = getStoredToken();
    const res = await fetch('/api/ai/chat/stream', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'text/event-stream',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: JSON.stringify({
        message: screenContextLabel.value
          ? `[Écran actif : ${screenContextLabel.value}] ${text}`
          : text,
        history: historySnapshot,
        role: selectedRole.value,
        model: selectedModel.value,
      }),
    });

    if (!res.ok) {
      let msg = `Erreur serveur (${res.status})`;
      try {
        const errJson = await res.json();
        if (errJson?.message) msg = errJson.message;
      } catch {
        // Fallback status text
      }
      throw new Error(msg);
    }

    const reader = res.body?.getReader();
    if (!reader) {
      throw new Error('Flux de réponse indisponible dans ce navigateur.');
    }

    const decoder = new TextDecoder();
    let buffer = '';

    while (true) {
      const { value, done } = await reader.read();
      if (done) break;

      buffer += decoder.decode(value, { stream: true });
      const lines = buffer.split('\n');
      buffer = lines.pop() ?? '';

      for (const rawLine of lines) {
        const line = rawLine.trim();
        if (!line.startsWith('data:')) continue;
        const jsonPart = line.slice(5).trim();
        if (!jsonPart) continue;

        let evt: any;
        try {
          evt = JSON.parse(jsonPart);
        } catch {
          continue;
        }

        if (evt.error) {
          throw new Error(evt.error);
        }

        if (typeof evt.chunk === 'string' && evt.chunk.length > 0) {
          messages.value[botIndex].text += evt.chunk;
          await scrollToBottom();
        }
      }
    }

    messages.value[botIndex].streaming = false;
    messages.value[botIndex].timestamp = new Date().toISOString();
    if (!messages.value[botIndex].text.trim()) {
      messages.value.splice(botIndex, 1);
      throw new Error('Aucune réponse générée par le modèle.');
    }
    saveHistory();
  } catch (err: any) {
    if (messages.value[botIndex] && !messages.value[botIndex].text.trim()) {
      messages.value.splice(botIndex, 1);
    } else if (messages.value[botIndex]) {
      messages.value[botIndex].streaming = false;
    }
    errorMessage.value = err?.message || "Erreur lors de la communication avec l'assistant.";
  } finally {
    isStreaming.value = false;
    await scrollToBottom();
  }
}

function escapeHtml(text: string): string {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function formatBotMessage(raw: string): string {
  const safe = escapeHtml(raw);
  return safe
    .replace(/\*\*(.+?)\*\*/g, '<strong class="font-semibold text-gray-900">$1</strong>')
    .replace(/`([^`]+)`/g, '<code class="px-1 py-0.5 rounded bg-gray-100 text-indigo-700 text-[11px] font-mono">$1</code>');
}

function formatTime(iso: string): string {
  try {
    return new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  } catch {
    return '';
  }
}

async function copyMessage(msg: ChatMessage) {
  try {
    await navigator.clipboard.writeText(msg.text);
    copiedId.value = msg.id;
    window.setTimeout(() => {
      if (copiedId.value === msg.id) copiedId.value = null;
    }, 1800);
  } catch {
    // Clipboard unavailable
  }
}
</script>
