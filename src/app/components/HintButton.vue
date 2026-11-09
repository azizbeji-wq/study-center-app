<template>
  <!-- Bouton rond d'aide "?" avec bulle contextuelle -->
  <div class="relative inline-flex">
    <button
      ref="btnRef"
      @click="toggleOpen"
      :aria-expanded="isOpen"
      :aria-controls="bubbleId"
      class="inline-flex items-center justify-center w-11 h-11 rounded-full border border-gray-200 bg-gray-50 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-[#1F3A33] shrink-0"
      :aria-label="`Aide : ${label}`"
    >
      <span class="text-sm font-bold select-none">?</span>
    </button>

    <!-- Bulle d'aide -->
    <transition
      enter-active-class="transition-all duration-150 ease-out"
      enter-from-class="opacity-0 scale-95 translate-y-1"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition-all duration-100 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        :id="bubbleId"
        role="tooltip"
        class="absolute z-50 bottom-full mb-2 left-1/2 -translate-x-1/2 w-64 bg-gray-900 text-white text-xs rounded-xl px-3 py-2.5 shadow-xl leading-relaxed"
        @click.stop
      >
        <p>{{ text }}</p>
        <!-- Flèche basse -->
        <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900" />
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
  label: string;
  text: string;
}>();

const isOpen = ref(false);
const btnRef = ref<HTMLElement | null>(null);
const bubbleId = `hint-${Math.random().toString(36).slice(2, 8)}`;

function toggleOpen() {
  isOpen.value = !isOpen.value;
}

function handleOutsideClick(e: MouseEvent) {
  if (isOpen.value && btnRef.value && !btnRef.value.contains(e.target as Node)) {
    isOpen.value = false;
  }
}

function handleEscape(e: KeyboardEvent) {
  if (e.key === 'Escape' && isOpen.value) {
    isOpen.value = false;
    btnRef.value?.focus();
  }
}

onMounted(() => {
  document.addEventListener('click', handleOutsideClick);
  document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick);
  document.removeEventListener('keydown', handleEscape);
});
</script>

