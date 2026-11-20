<template>
  <!-- ══════════════════════════════════════════════════════════════════
       TIROIR DU GUIDE — rôle="dialog", accessible, responsive
  ═══════════════════════════════════════════════════════════════════ -->

  <!-- Fond semi-transparent -->
  <Teleport to="body">
    <transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[70] bg-gray-900/40 backdrop-blur-sm"
        aria-hidden="true"
        @click="close"
      />
    </transition>

    <!-- Tiroir -->
    <transition
      enter-active-class="transition-transform duration-300 ease-out"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full"
    >
      <div
        v-if="isOpen"
        ref="drawerRef"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        class="fixed right-0 top-0 bottom-0 z-[71] w-full sm:w-96 bg-white shadow-2xl flex flex-col safe-area-inset-right overflow-hidden"
        @keydown.escape="close"
      >
        <!-- En-tête -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 shrink-0 bg-white">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-[#1F3A33] flex items-center justify-center shrink-0">
              <BookOpen class="w-4 h-4 text-[#F4F1E8]" />
            </div>
            <h2 :id="titleId" class="text-base font-bold text-gray-900">Guide d'utilisation</h2>
          </div>
          <button
            ref="closeButtonRef"
            @click="close"
            class="min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors focus:outline-none focus:ring-2 focus:ring-[#1F3A33]"
            aria-label="Fermer le guide"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Corps du tiroir -->
        <div class="flex-1 overflow-y-auto">

          <!-- ── Section 1 : Pour démarrer (checklist) ── -->
          <div
            v-if="checklist.length > 0"
            class="px-5 py-4 border-b border-gray-100"
          >
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-bold text-gray-900">Pour démarrer</h3>
              <span class="text-xs text-gray-500 font-medium">
                {{ checklistDoneCount }} / {{ checklistTotal }}
              </span>
            </div>

            <!-- Barre de progression -->
            <div class="mb-4">
              <div
                role="progressbar"
                :aria-valuenow="checklistDoneCount"
                :aria-valuemin="0"
                :aria-valuemax="checklistTotal"
                :aria-label="`${checklistDoneCount} étape${checklistDoneCount > 1 ? 's' : ''} sur ${checklistTotal} terminée${checklistDoneCount > 1 ? 's' : ''}`"
                class="h-2 bg-gray-200 rounded-full overflow-hidden"
              >
                <div
                  class="h-full bg-[#1F3A33] rounded-full transition-all duration-500"
                  :style="{ width: `${checklistProgress}%` }"
                />
              </div>
              <p class="text-xs text-gray-500 mt-1" aria-live="polite">
                {{ checklistDoneCount }} étape{{ checklistDoneCount > 1 ? 's' : '' }} sur {{ checklistTotal }} terminée{{ checklistDoneCount > 1 ? 's' : '' }}
              </p>
            </div>

            <!-- Éléments de la liste -->
            <ul class="space-y-2">
              <li
                v-for="item in checklist"
                :key="item.key"
                class="flex items-start gap-3 p-3 rounded-xl border transition-colors"
                :class="isDone(item.key)
                  ? 'bg-[#1F3A33]/5 border-[#1F3A33]/20'
                  : 'bg-gray-50 border-gray-200'"
              >
                <button
                  @click="toggleChecklist(item)"
                  class="shrink-0 w-5 h-5 mt-0.5 rounded-full border-2 flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-[#1F3A33]"
                  :class="isDone(item.key)
                    ? 'bg-[#1F3A33] border-[#1F3A33]'
                    : 'border-gray-300 bg-white'"
                  :aria-label="isDone(item.key) ? `Marquer non fait : ${item.label}` : `Marquer fait : ${item.label}`"
                  :aria-pressed="isDone(item.key)"
                >
                  <Check v-if="isDone(item.key)" class="w-3 h-3 text-[#F4F1E8]" />
                </button>
                <div class="flex-1 min-w-0">
                  <p
                    class="text-sm font-semibold"
                    :class="isDone(item.key) ? 'text-gray-400 line-through' : 'text-gray-900'"
                  >
                    {{ item.label }}
                  </p>
                  <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ item.description }}</p>
                    <button
                      v-if="item.targetTab && !isDone(item.key)"
                      @click="navigateToTab(item.targetTab)"
                      class="mt-1.5 text-xs font-bold text-[#1F3A33] hover:underline focus:outline-none focus:ring-1 focus:ring-[#1F3A33] rounded cursor-pointer"
                    >
                      Aller à cet onglet &rarr;
                    </button>
                  </div>
                </li>
              </ul>
            </div>

          <!-- ── Section 2 : Lancer la visite guidée ── -->
          <div
            v-if="currentTourSteps.length > 0"
            class="px-5 py-4 border-b border-gray-100"
          >
            <h3 class="text-sm font-bold text-gray-900 mb-2">Visite guidée</h3>
            <p class="text-xs text-gray-500 mb-3">
              Découvrez les fonctions clés de cet écran pas à pas.
            </p>
            <button
              @click="startTour"
              class="w-full min-h-[44px] flex items-center justify-center gap-2 px-4 py-2.5 bg-[#1F3A33] hover:bg-[#1F3A33]/90 text-[#F4F1E8] font-bold text-sm rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-[#1F3A33] focus:ring-offset-2"
            >
              <Play class="w-4 h-4" />
              Lancer la visite guidée de cette page
            </button>
            <p v-if="isTourDone(currentTab)" class="text-xs text-[#1F3A33] font-medium mt-2 text-center">
              Visite déjà effectuée. Vous pouvez la relancer.
            </p>
          </div>

          <!-- ── Section 2b : Connexion intelligente Assistant IA ── -->
          <div
            v-if="currentAssistantConfig"
            class="px-5 py-4 border-b border-gray-100 bg-indigo-50/40"
          >
            <div class="flex items-center justify-between gap-2 mb-1.5">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center shrink-0">
                  <Sparkles class="w-3.5 h-3.5" />
                </div>
                <h3 class="text-sm font-bold text-gray-900">Assistant IA sur cet écran</h3>
              </div>
              <span class="text-[11px] font-medium text-indigo-700 truncate">
                {{ currentAssistantConfig.contextLabel }}
              </span>
            </div>
            <p class="text-xs text-gray-600 mb-3 leading-relaxed">
              Posez une question ciblée sur cette page ou choisissez une suggestion prête à l'emploi.
            </p>

            <div class="space-y-1.5 mb-3">
              <button
                v-for="(suggestion, sIdx) in currentAssistantConfig.suggestions"
                :key="sIdx"
                type="button"
                @click="askAssistantSuggestion(suggestion)"
                class="w-full text-left px-3 py-2 rounded-xl bg-white hover:bg-indigo-50 border border-indigo-100 hover:border-indigo-300 text-xs font-medium text-gray-800 hover:text-indigo-900 transition-colors flex items-center justify-between gap-2 cursor-pointer"
              >
                <span class="leading-snug">{{ suggestion.label }}</span>
                <ArrowUpRight class="w-3.5 h-3.5 text-indigo-500 shrink-0" />
              </button>
            </div>

            <button
              type="button"
              @click="askAssistantFree"
              class="w-full min-h-[42px] flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition-colors shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 cursor-pointer"
            >
              <MessageSquare class="w-4 h-4" />
              <span>Poser une question sur cet écran</span>
            </button>
          </div>

          <!-- ── Section 3 : Sur cette page ── -->
          <div
            v-if="currentPageContent"
            class="px-5 py-4 border-b border-gray-100"
          >
            <h3 class="text-sm font-bold text-gray-900 mb-3">Sur cette page</h3>
            <div class="space-y-2">
              <details
                v-for="(step, idx) in currentPageContent.steps"
                :key="idx"
                class="group rounded-xl border border-gray-200 bg-gray-50 overflow-hidden"
              >
                <summary
                  class="flex items-center justify-between gap-3 px-4 py-3 cursor-pointer list-none min-h-[44px] select-none focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1F3A33] rounded-xl"
                >
                  <span class="text-sm font-semibold text-gray-800">{{ step.title }}</span>
                  <ChevronDown class="w-4 h-4 text-gray-400 transition-transform group-open:rotate-180 shrink-0" />
                </summary>
                <div class="px-4 pb-3 text-sm text-gray-600 leading-relaxed border-t border-gray-200 pt-2">
                  {{ step.description }}
                </div>
              </details>
            </div>
          </div>

          <!-- ── Section 3b : Scénarios métiers Pas à pas ── -->
          <div
            v-if="workflows.length > 0"
            class="px-5 py-4 border-b border-gray-100"
          >
            <div class="flex items-center justify-between gap-2 mb-1">
              <div class="flex items-center gap-2">
                <Compass class="w-4 h-4 text-[#1F3A33] shrink-0" />
                <h3 class="text-sm font-bold text-gray-900">Scénarios pas à pas</h3>
              </div>
              <span class="text-xs text-gray-500 font-medium">{{ workflows.length }} parcours</span>
            </div>
            <p class="text-xs text-gray-500 mb-3">
              Parcours guidés multi-écrans pour réussir vos tâches clés.
            </p>

            <div class="space-y-2">
              <details
                v-for="wf in workflows"
                :key="wf.id"
                class="group rounded-xl border border-gray-200 bg-white overflow-hidden"
              >
                <summary
                  class="flex items-center justify-between gap-3 px-4 py-3 cursor-pointer list-none min-h-[44px] select-none hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1F3A33] rounded-xl"
                >
                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 leading-snug">{{ wf.title }}</p>
                    <p class="text-[11px] text-gray-500 mt-0.5">{{ wf.duration }} · {{ wf.steps.length }} étapes</p>
                  </div>
                  <ChevronDown class="w-4 h-4 text-gray-400 transition-transform group-open:rotate-180 shrink-0" />
                </summary>
                <div class="px-4 pb-3.5 pt-2.5 border-t border-gray-100 bg-gray-50/70 space-y-3">
                  <p class="text-xs text-gray-600 leading-relaxed">{{ wf.summary }}</p>
                  <ol class="space-y-2.5">
                    <li
                      v-for="(wStep, wIdx) in wf.steps"
                      :key="wIdx"
                      class="rounded-lg border border-gray-200/80 bg-white p-2.5"
                    >
                      <p class="text-xs font-bold text-gray-900">{{ wStep.title }}</p>
                      <p class="text-xs text-gray-600 mt-0.5 leading-relaxed">{{ wStep.description }}</p>
                      <button
                        v-if="wStep.targetTab"
                        type="button"
                        @click="navigateToTab(wStep.targetTab)"
                        class="mt-1.5 inline-flex items-center gap-1 text-xs font-bold text-[#1F3A33] hover:underline focus:outline-none focus:ring-1 focus:ring-[#1F3A33] rounded cursor-pointer"
                      >
                        <span>{{ wStep.actionLabel || 'Aller à cet onglet' }}</span>
                        <span>&rarr;</span>
                      </button>
                    </li>
                  </ol>
                </div>
              </details>
            </div>
          </div>

          <!-- ── Section 4 : Manuel complet (HTML) ── -->
          <div class="px-5 py-4">
            <h3 class="text-sm font-bold text-gray-900 mb-2">Manuel complet</h3>
            <p class="text-xs text-gray-500 mb-3">
              Consultez le guide officiel complet de l'application dans un nouvel onglet.
            </p>
            <a
              href="/guide_utilisation.html"
              target="_blank"
              class="w-full min-h-[44px] flex items-center justify-center gap-2 px-4 py-2.5 border-2 border-[#1F3A33] text-[#1F3A33] font-bold text-sm rounded-xl hover:bg-[#1F3A33]/5 transition-colors focus:outline-none focus:ring-2 focus:ring-[#1F3A33] no-underline"
            >
              <BookOpen class="w-4 h-4" />
              <span>Ouvrir le manuel officiel</span>
              <span>↗</span>
            </a>
          </div>

        </div><!-- fin corps -->
      </div>
    </transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { BookOpen, X, Check, ChevronDown, Play, Sparkles, MessageSquare, ArrowUpRight, Compass } from 'lucide-vue-next';
import { useGuide } from '../guide/useGuide';
import type { UserRole, TabKey, GuideAssistantSuggestion } from '../guide/types';
import { openAssistantFromGuide } from '../utils/assistantBridge';

// ─── Props & Emits ────────────────────────────────────────────
const props = defineProps<{
  isOpen: boolean;
  role: UserRole;
  permissions?: string[];
  currentTab: TabKey;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'start-tour'): void;
  (e: 'navigate-tab', tab: TabKey): void;
}>();

// ─── Guide composable ─────────────────────────────────────────
const {
  checklist,
  workflows,
  checklistDoneCount,
  checklistTotal,
  checklistProgress,
  getPageContent,
  getTourSteps,
  getAssistantConfig,
  isDone,
  isTourDone,
  markDone,
  markUndone,
} = useGuide(props.role, props.permissions);

// ─── Contenu dynamique selon l'onglet actif ───────────────────
const currentPageContent = computed(() => getPageContent(props.currentTab));
const currentTourSteps = computed(() => getTourSteps(props.currentTab));
const currentAssistantConfig = computed(() => getAssistantConfig(props.currentTab));

// ─── Références DOM ───────────────────────────────────────────
const drawerRef = ref<HTMLElement | null>(null);
const closeButtonRef = ref<HTMLButtonElement | null>(null);
const titleId = 'guide-drawer-title';
let previouslyFocused: HTMLElement | null = null;

// ─── Gestion du focus (accessibilité) ────────────────────────
watch(
  () => props.isOpen,
  async (open) => {
    if (open) {
      previouslyFocused = document.activeElement as HTMLElement;
      await nextTick();
      closeButtonRef.value?.focus();
    } else {
      previouslyFocused?.focus();
      previouslyFocused = null;
    }
  }
);

// ─── Fermeture ────────────────────────────────────────────────
function close() {
  emit('close');
}

// ─── Actions ─────────────────────────────────────────────────
async function toggleChecklist(item: { key: string }) {
  if (isDone(item.key)) {
    await markUndone(item.key);
  } else {
    await markDone(item.key);
  }
}

function startTour() {
  close();
  emit('start-tour');
}

function navigateToTab(tab: TabKey) {
  emit('navigate-tab', tab);
  close();
}

function askAssistantSuggestion(suggestion: GuideAssistantSuggestion) {
  const cfg = currentAssistantConfig.value;
  close();
  openAssistantFromGuide({
    prompt: suggestion.prompt,
    autoSend: true,
    contextLabel: cfg?.contextLabel || currentPageContent.value?.title,
    roleMode: suggestion.roleMode || cfg?.defaultRoleMode || 'general',
    suggestedPrompts: cfg?.suggestions.map(s => s.prompt) || [],
  });
}

function askAssistantFree() {
  const cfg = currentAssistantConfig.value;
  close();
  openAssistantFromGuide({
    autoSend: false,
    contextLabel: cfg?.contextLabel || currentPageContent.value?.title,
    roleMode: cfg?.defaultRoleMode || 'general',
    suggestedPrompts: cfg?.suggestions.map(s => s.prompt) || [],
  });
}

// ─── Fermeture sur clic extérieur (gérée par le fond) ────────
// Piège le focus dans le tiroir
function handleKeydown(e: KeyboardEvent) {
  if (!props.isOpen || !drawerRef.value) return;
  if (e.key !== 'Tab') return;

  const focusable = drawerRef.value.querySelectorAll<HTMLElement>(
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"]), details summary'
  );
  const first = focusable[0];
  const last = focusable[focusable.length - 1];

  if (e.shiftKey) {
    if (document.activeElement === first) {
      e.preventDefault();
      last?.focus();
    }
  } else {
    if (document.activeElement === last) {
      e.preventDefault();
      first?.focus();
    }
  }
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
</script>

