<template>
  <!-- Bandeau "Nouveau ici ?" affiché au premier passage sur un écran -->
  <transition
    enter-active-class="transition-all duration-300 ease-out"
    enter-from-class="opacity-0 -translate-y-2"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition-all duration-200 ease-in"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 -translate-y-2"
  >
    <div
      v-if="visible"
      role="region"
      aria-label="Découvrir cet écran"
      class="rounded-2xl border border-[#1F3A33]/20 bg-[#1F3A33]/5 px-4 py-3 flex flex-wrap items-center gap-3 mb-5"
    >
      <div class="flex items-center gap-2 flex-1 min-w-0">
        <div class="w-7 h-7 rounded-lg bg-[#1F3A33] flex items-center justify-center shrink-0">
          <Lightbulb class="w-4 h-4 text-[#F4F1E8]" />
        </div>
        <p class="text-sm font-medium text-[#1F3A33]">
          Nouveau ici ? Faites la visite guidée de cet écran.
        </p>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <button
          @click="startTour"
          class="min-h-[44px] px-4 py-2 bg-[#1F3A33] text-[#F4F1E8] font-bold text-sm rounded-xl hover:bg-[#1F3A33]/90 transition-colors focus:outline-none focus:ring-2 focus:ring-[#1F3A33] focus:ring-offset-2"
        >
          Commencer la visite
        </button>
        <button
          @click="dismiss"
          class="min-h-[44px] px-4 py-2 border border-[#1F3A33]/30 text-[#1F3A33] font-semibold text-sm rounded-xl hover:bg-[#1F3A33]/10 transition-colors focus:outline-none focus:ring-2 focus:ring-[#1F3A33]"
        >
          Plus tard
        </button>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Lightbulb } from 'lucide-vue-next';
import type { TabKey } from '../guide/types';
import { useGuide } from '../guide/useGuide';
import type { UserRole } from '../guide/types';

const props = defineProps<{
  tab: TabKey;
  role: UserRole;
  permissions?: string[];
  hasTourSteps: boolean;
}>();

const emit = defineEmits<{
  (e: 'start-tour'): void;
}>();

const { isBannerDismissed, dismissBanner } = useGuide(props.role, props.permissions);

const visible = ref(false);

function checkVisibility() {
  visible.value = props.hasTourSteps && !isBannerDismissed(props.tab);
}

onMounted(checkVisibility);

watch(() => props.tab, checkVisibility);

async function dismiss() {
  visible.value = false;
  await dismissBanner(props.tab);
}

function startTour() {
  visible.value = false;
  dismissBanner(props.tab);
  emit('start-tour');
}
</script>

