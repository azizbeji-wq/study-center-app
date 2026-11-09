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
                    class="mt-1.5 text-xs font-bold text-[#1F3A33] hover:underline focus:outline-none focus:ring-1 focus:ring-[#1F3A33] rounded"
                  >
                    Aller a cet onglet &rarr;
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
              Visite deja effectuee. Vous pouvez la relancer.
            </p>
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

          <!-- ── Section 4 : Besoin d'aide (chat IA) ── -->
          <div class="px-5 py-4">
            <h3 class="text-sm font-bold text-gray-900 mb-2">Besoin d'aide ?</h3>
            <p class="text-xs text-gray-500 mb-3">
              Posez votre question a l'assistant IA qui connait l'application.
            </p>
            <button
              @click="openAiAssistant"
              class="w-full min-h-[44px] flex items-center justify-center gap-2 px-4 py-2.5 border-2 border-[#1F3A33] text-[#1F3A33] font-bold text-sm rounded-xl hover:bg-[#1F3A33]/5 transition-colors focus:outline-none focus:ring-2 focus:ring-[#1F3A33]"
            >
              <MessageCircle class="w-4 h-4" />
              Ouvrir l'assistant IA
            </button>
          </div>

        </div><!-- fin corps -->
      </div>
    </transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { BookOpen, X, Check, ChevronDown, Play, MessageCircle } from 'lucide-vue-next';
import { useGuide } from '../guide/useGuide';
import type { UserRole, TabKey } from '../guide/types';

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
  (e: 'open-ai'): void;
}>();

// ─── Guide composable ─────────────────────────────────────────
const {
  checklist,
  checklistDoneCount,
  checklistTotal,
  checklistProgress,
  getPageContent,
  getTourSteps,
  isDone,
  isTourDone,
  markDone,
  markUndone,
} = useGuide(props.role, props.permissions);

// ─── Contenu dynamique selon l'onglet actif ───────────────────
const currentPageContent = computed(() => getPageContent(props.currentTab));
const currentTourSteps = computed(() => getTourSteps(props.currentTab));

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

function openAiAssistant() {
  emit('open-ai');
  close();
  window.dispatchEvent(new CustomEvent('open-ai-chat'));
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

