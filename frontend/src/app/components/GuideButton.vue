<template>
  <!-- Bouton "Guide" à placer dans les en-têtes mobiles des dashboards -->
  <button
    @click="$emit('open')"
    class="px-2.5 py-1.5 rounded-lg border flex items-center gap-1.5 font-bold text-sm transition-colors min-h-[44px] focus:outline-none focus:ring-2 focus:ring-offset-1"
    :class="[
      colorScheme === 'indigo'
        ? 'bg-indigo-50 border-indigo-200 text-indigo-700 hover:bg-indigo-100 focus:ring-indigo-500'
        : colorScheme === 'emerald'
        ? 'bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100 focus:ring-emerald-500'
        : 'bg-violet-50 border-violet-200 text-violet-700 hover:bg-violet-100 focus:ring-violet-500'
    ]"
    aria-label="Ouvrir le guide d'utilisation"
    :title="keyboardHint"
  >
    <BookOpen class="w-4 h-4" />
    <span class="text-xs">Guide</span>
    <span
      class="inline-flex items-center justify-center w-4 h-4 text-[9px] font-black rounded-full"
      :class="[
        colorScheme === 'indigo' ? 'bg-indigo-600 text-white'
        : colorScheme === 'emerald' ? 'bg-emerald-600 text-white'
        : 'bg-violet-600 text-white'
      ]"
      aria-hidden="true"
    >?</span>
  </button>
</template>

<script setup lang="ts">
import { BookOpen } from 'lucide-vue-next';

defineProps<{
  colorScheme?: 'indigo' | 'emerald' | 'violet';
  keyboardHint?: string;
}>();

defineEmits<{
  (e: 'open'): void;
}>();
</script>

