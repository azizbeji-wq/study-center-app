<template>
  <div
    v-if="item"
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
    @click="$emit('close')"
  >
    <div
      class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[94vh] flex flex-col overflow-hidden mx-4 md:mx-auto"
      @click.stop
    >
      <!-- En-tête -->
      <div class="flex items-center justify-between gap-3 p-4 border-b border-gray-100 bg-gray-50/50">
        <div class="min-w-0">
          <h3 class="text-base sm:text-lg font-bold text-gray-900 flex items-center gap-2">
            <span>{{ formatLevel(item.level) }}</span>
            <span>•</span>
            <span class="text-indigo-600">{{ formatSectionDisplay(item.section) }}</span>
          </h3>
          <p class="text-xs text-gray-500 truncate mt-0.5">
            {{ item.school_name }} — {{ item.file_name }}
          </p>
        </div>

        <div class="flex items-center gap-2">
          <a
            v-if="viewerUrl"
            :href="viewerUrl"
            target="_blank"
            rel="noopener"
            class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-100 transition-colors"
          >
            <ExternalLink class="w-3.5 h-3.5" />
            <span>Ouvrir dans un onglet</span>
          </a>
          <a
            v-if="viewerUrl"
            :href="viewerUrl"
            :download="item.file_name"
            class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-xs"
          >
            <Download class="w-3.5 h-3.5" />
            <span>Télécharger</span>
          </a>
          <button
            @click="$emit('close')"
            aria-label="Fermer"
            class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Corps d'aperçu -->
      <div class="flex-1 overflow-auto p-4 bg-gray-100 flex items-center justify-center min-h-[420px]">
        <div v-if="loading" class="flex flex-col items-center gap-2 py-16">
          <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
          <p class="text-xs text-gray-500">Chargement du document...</p>
        </div>

        <p v-else-if="error" class="text-sm text-rose-600 text-center py-16 bg-white p-6 rounded-xl border border-rose-200">
          {{ error }}
        </p>

        <template v-else-if="viewerUrl">
          <iframe
            v-if="isPdf(item)"
            :src="viewerUrl"
            title="Emploi du temps"
            class="w-full h-[72vh] rounded-lg border border-gray-300 bg-white shadow-sm"
          ></iframe>
          <div v-else class="max-h-[72vh] overflow-auto flex items-center justify-center">
            <img
              :src="viewerUrl"
              alt="Emploi du temps"
              class="max-w-full max-h-[70vh] object-contain rounded-lg border border-gray-300 bg-white shadow-sm"
            />
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ExternalLink, Download, X } from 'lucide-vue-next';
import type { CalendarImport } from '../../types/calendar';
import { useSchoolCalendar } from '../../composables/useSchoolCalendar';

defineProps<{
  item: CalendarImport | null;
  viewerUrl: string;
  loading: boolean;
  error: string;
}>();

defineEmits<{
  (e: 'close'): void;
}>();

const { formatLevel, formatSectionDisplay, isPdf } = useSchoolCalendar();
</script>
