<template>
  <!-- ══════════════════════════════════════════════════════════════
       VISITE GUIDÉE — Overlay + bulle positionnée
  ═══════════════════════════════════════════════════════════════ -->
  <Teleport to="body">
    <div
      v-if="active"
      class="fixed inset-0 z-[80]"
      aria-live="polite"
      :aria-label="`Étape ${currentIndex + 1} sur ${steps.length} : ${currentStep?.title}`"
    >
      <!-- Fond assombri (box-shadow) avec découpe de l'élément ciblé -->
      <div
        class="fixed inset-0 pointer-events-none"
        :style="overlayStyle"
      />

      <!-- Couverture cliquable autour (ferme au clic hors bulle) -->
      <div
        class="fixed inset-0"
        style="z-index: 1"
        @click="skipTour"
      />

      <!-- Bulle d'aide positionnée -->
      <div
        v-if="currentStep"
        ref="bubbleRef"
        class="fixed z-[82] bg-white rounded-2xl shadow-2xl border border-gray-200 p-5 w-80 max-w-[calc(100vw-2rem)]"
        :style="bubbleStyle"
        role="alertdialog"
        aria-modal="true"
        :aria-label="`Visite guidée, étape ${currentIndex + 1} sur ${steps.length}`"
        @keydown.escape.stop="skipTour"
        @keydown.arrow-right.stop="next"
        @keydown.arrow-left.stop="prev"
      >
        <!-- En-tête de la bulle -->
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">
            Étape {{ currentIndex + 1 }} sur {{ steps.length }}
          </span>
          <button
            @click="skipTour"
            class="min-w-[32px] min-h-[32px] flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400"
            aria-label="Passer la visite guidée"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Titre & texte -->
        <h3 class="text-sm font-bold text-gray-900 mb-1">{{ currentStep.title }}</h3>
        <p class="text-sm text-gray-600 leading-relaxed mb-4">{{ currentStep.text }}</p>

        <!-- Indicateurs de progression -->
        <div class="flex items-center justify-center gap-1.5 mb-4">
          <span
            v-for="(_, i) in steps"
            :key="i"
            class="rounded-full transition-all duration-200"
            :class="i === currentIndex
              ? 'w-5 h-2 bg-[#1F3A33]'
              : 'w-2 h-2 bg-gray-300'"
          />
        </div>

        <!-- Boutons de navigation -->
        <div class="flex items-center gap-2">
          <button
            v-if="currentIndex > 0"
            @click="prev"
            class="min-h-[44px] px-3 py-2 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm hover:bg-gray-50 transition-colors flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-[#1F3A33]"
            aria-label="Étape précédente"
          >
            <ChevronLeft class="w-4 h-4" />
            Précédent
          </button>

          <button
            @click="next"
            class="flex-1 min-h-[44px] px-4 py-2 rounded-xl bg-[#1F3A33] hover:bg-[#1F3A33]/90 text-[#F4F1E8] font-bold text-sm flex items-center justify-center gap-1 transition-colors focus:outline-none focus:ring-2 focus:ring-[#1F3A33] focus:ring-offset-2"
          >
            {{ isLast ? 'Terminer' : 'Suivant' }}
            <ChevronRight v-if="!isLast" class="w-4 h-4" />
            <Check v-else class="w-4 h-4" />
          </button>
        </div>

        <!-- Lien "Passer" -->
        <div class="text-center mt-2">
          <button
            @click="skipTour"
            class="text-xs text-gray-400 hover:text-gray-600 underline focus:outline-none focus:ring-1 focus:ring-gray-400 rounded"
          >
            Passer
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { X, ChevronLeft, ChevronRight, Check } from 'lucide-vue-next';
import type { TourStep } from '../guide/types';

// ─── Props & Emits ────────────────────────────────────────────
const props = defineProps<{
  steps: TourStep[];
  active: boolean;
}>();

const emit = defineEmits<{
  (e: 'finish'): void;
  (e: 'skip'): void;
}>();

// ─── État local ───────────────────────────────────────────────
const currentIndex = ref(0);
const bubbleRef = ref<HTMLElement | null>(null);

// Rect de l'élément ciblé courant
const targetRect = ref<DOMRect | null>(null);
const bubblePosition = ref<{ top: number; left: number } | null>(null);

// Préférence de mouvement réduit
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// ─── Computed ─────────────────────────────────────────────────
const currentStep = computed(() => props.steps[currentIndex.value] ?? null);
const isLast = computed(() => currentIndex.value === props.steps.length - 1);

// Style du fond assombri avec découpe de l'élément
const overlayStyle = computed(() => {
  const rect = targetRect.value;
  if (!rect) {
    return {
      background: 'rgba(0,0,0,0.5)',
      zIndex: '81',
    };
  }
  const pad = 6; // px de marge autour de l'élément
  const x = Math.max(0, rect.left - pad);
  const y = Math.max(0, rect.top - pad);
  const w = rect.width + pad * 2;
  const h = rect.height + pad * 2;

  return {
    zIndex: '81',
    boxShadow: `0 0 0 9999px rgba(0,0,0,0.5)`,
    // On utilise clip-path pour épargner la zone de l'élément
    // Si clip-path n'est pas supporté, on tombe sur un fond plein
    clipPath: `polygon(
      0% 0%, 100% 0%, 100% 100%, 0% 100%,
      0% ${y}px,
      ${x}px ${y}px,
      ${x}px ${y + h}px,
      ${x + w}px ${y + h}px,
      ${x + w}px ${y}px,
      0% ${y}px
    )`,
    background: 'transparent',
  };
});

// Position de la bulle (au-dessus ou en-dessous de l'élément)
const bubbleStyle = computed(() => {
  const pos = bubblePosition.value;
  if (!pos) {
    return {
      top: '50%',
      left: '50%',
      transform: 'translate(-50%, -50%)',
      zIndex: '82',
    };
  }
  return {
    top: `${pos.top}px`,
    left: `${pos.left}px`,
    zIndex: '82',
  };
});

// ─── Mise à jour de la cible ──────────────────────────────────
function updateTarget() {
  const step = currentStep.value;
  if (!step) return;

  const el = document.querySelector(step.target) as HTMLElement | null;
  if (!el) {
    // Élément absent : sauter l'étape silencieusement et passer à la suivante
    if (currentIndex.value < props.steps.length - 1) {
      currentIndex.value++;
      nextTick(() => updateTarget());
    } else {
      finish();
    }
    return;
  }

  // Scroll doux vers l'élément (sauf prefers-reduced-motion)
  el.scrollIntoView({
    behavior: prefersReducedMotion ? 'instant' : 'smooth',
    block: 'center',
  });

  // Attendre la fin du scroll pour obtenir le rect correct
  setTimeout(() => {
    const rect = el.getBoundingClientRect();
    targetRect.value = rect;
    computeBubblePosition(rect);
  }, prefersReducedMotion ? 0 : 350);
}

function computeBubblePosition(rect: DOMRect) {
  const bubbleH = 260; // hauteur estimée de la bulle
  const bubbleW = 320;
  const pad = 12;
  const vp = { w: window.innerWidth, h: window.innerHeight };

  let top: number;
  let left: number;

  // Essayer en-dessous
  const spaceBelow = vp.h - rect.bottom - pad;
  if (spaceBelow >= bubbleH) {
    top = rect.bottom + 12;
  } else {
    // Au-dessus
    top = Math.max(pad, rect.top - bubbleH - 12);
  }

  // Centré horizontalement sur l'élément, mais dans la fenêtre
  left = rect.left + rect.width / 2 - bubbleW / 2;
  left = Math.max(pad, Math.min(left, vp.w - bubbleW - pad));

  bubblePosition.value = { top, left };
}

// ─── Navigation ───────────────────────────────────────────────
async function next() {
  if (isLast.value) {
    finish();
  } else {
    currentIndex.value++;
    await nextTick();
    updateTarget();
  }
}

function prev() {
  if (currentIndex.value > 0) {
    currentIndex.value--;
    nextTick(() => updateTarget());
  }
}

function finish() {
  emit('finish');
  reset();
}

function skipTour() {
  emit('skip');
  reset();
}

function reset() {
  currentIndex.value = 0;
  targetRect.value = null;
  bubblePosition.value = null;
}

// ─── Watchers ─────────────────────────────────────────────────
watch(
  () => props.active,
  async (active) => {
    if (active && props.steps.length > 0) {
      currentIndex.value = 0;
      await nextTick();
      updateTarget();
    } else {
      reset();
    }
  }
);

// ─── Repositionnement sur resize / scroll ────────────────────
function onResize() {
  if (props.active && targetRect.value) {
    updateTarget();
  }
}

onMounted(() => {
  window.addEventListener('resize', onResize, { passive: true });
  window.addEventListener('scroll', onResize, { passive: true, capture: true });
});

onUnmounted(() => {
  window.removeEventListener('resize', onResize);
  window.removeEventListener('scroll', onResize, true);
});
</script>

