<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
    @click="$emit('cancel')"
  >
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full space-y-4 max-h-[90vh] overflow-y-auto mx-4 md:mx-auto" @click.stop>
      <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
          <Trash2 class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-base font-bold text-gray-900">{{ title }}</h3>
          <p class="text-xs text-gray-600 mt-1" v-html="message"></p>
        </div>
      </div>

      <div
        v-if="error"
        class="text-xs text-rose-700 bg-rose-50 p-2.5 rounded-lg border border-rose-200"
      >
        {{ error }}
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50 cursor-pointer"
        >
          Annuler
        </button>
        <button
          type="button"
          :disabled="isDeleting"
          @click="$emit('confirm')"
          class="px-4 py-2 bg-rose-600 text-white text-xs font-semibold rounded-lg hover:bg-rose-700 disabled:opacity-50 transition-colors shadow-xs cursor-pointer"
        >
          {{ isDeleting ? 'Suppression...' : (confirmLabel || 'Supprimer définitivement') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Trash2 } from 'lucide-vue-next';

defineProps<{
  isOpen: boolean;
  title: string;
  message: string;
  error: string;
  isDeleting: boolean;
  confirmLabel?: string;
}>();

defineEmits<{
  (e: 'cancel'): void;
  (e: 'confirm'): void;
}>();
</script>
