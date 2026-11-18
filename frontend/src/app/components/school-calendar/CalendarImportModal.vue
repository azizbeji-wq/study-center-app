<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
    @click="$emit('close')"
  >
    <div
      class="bg-white rounded-2xl shadow-2xl p-6 max-w-lg w-full space-y-4 max-h-[92vh] overflow-y-auto mx-4 md:mx-auto"
      @click.stop
    >
      <div class="flex items-start justify-between border-b border-gray-100 pb-3">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ modalTitle }}</h3>
          <p class="text-xs text-gray-500 mt-0.5">{{ modalSubtitle }}</p>
        </div>
        <button @click="$emit('close')" aria-label="Fermer" class="text-gray-400 hover:text-gray-600 p-1 cursor-pointer">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Choix de l'école -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="block text-xs font-semibold text-gray-700">École primaire *</label>
          <button
            type="button"
            @click="$emit('openNewSchool')"
            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 hover:underline cursor-pointer"
          >
            <Plus class="w-3 h-3" />
            <span>Nouvelle école</span>
          </button>
        </div>
        <select
          v-model="importSchoolId"
          class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        >
          <option :value="null">Sélectionnez une école...</option>
          <option v-for="school in schools" :key="school.id" :value="school.id">
            {{ school.name }} {{ school.city ? `(${school.city})` : '' }}
          </option>
        </select>
      </div>

      <!-- Niveau et Section -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Niveau scolaire *</label>
          <select
            v-model.number="importLevel"
            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          >
            <option v-for="lvl in levels" :key="lvl" :value="lvl">
              {{ formatLevel(lvl) }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Classe / Section *</label>
          <select
            v-model="importSection"
            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          >
            <option v-for="sec in sectionOptions" :key="sec.value" :value="sec.value">
              {{ sec.label }}
            </option>
          </select>
        </div>
      </div>

      <!-- Zone d'upload de fichier -->
      <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1">
          {{ isEditing ? 'Remplacer le fichier (facultatif)' : 'Fichier emploi du temps *' }}
        </label>

        <p v-if="isEditing && editingItem" class="text-xs text-gray-500 mb-2">
          Fichier actuellement associé : <span class="font-semibold text-indigo-700">{{ editingItem.file_name }}</span>.
          Laissez vide pour conserver ce fichier.
        </p>

        <div
          @dragover.prevent="dragOver = true"
          @dragleave.prevent="dragOver = false"
          @drop.prevent="handleFileDrop"
          @click="triggerFileInput"
          :class="[
            'border-2 border-dashed rounded-xl p-5 text-center cursor-pointer transition-all',
            dragOver ? 'border-indigo-600 bg-indigo-50/60' : 'border-gray-300 hover:border-indigo-400 bg-gray-50/50'
          ]"
        >
          <input
            ref="fileInput"
            type="file"
            accept=".pdf,image/jpeg,image/png"
            class="hidden"
            @change="handleFileChange"
          />

          <div v-if="!selectedFile" class="space-y-1.5">
            <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 mx-auto flex items-center justify-center">
              <Upload class="w-5 h-5" />
            </div>
            <p class="text-xs font-bold text-gray-800">
              Glissez votre fichier ici ou cliquez pour parcourir
            </p>
            <p class="text-[11px] text-gray-400">
              Formats acceptés : PDF, JPG ou PNG (10 Mo max)
            </p>
          </div>

          <div v-else class="flex items-center justify-between p-3 bg-white rounded-lg border border-indigo-200">
            <div class="text-left min-w-0">
              <p class="text-xs font-bold text-gray-900 truncate">{{ selectedFile.name }}</p>
              <p class="text-[11px] text-gray-500">{{ (selectedFile.size / 1024).toFixed(1) }} Ko</p>
            </div>
            <button
              type="button"
              @click.stop="clearFile"
              class="text-xs text-rose-600 hover:underline font-semibold shrink-0 ml-3 cursor-pointer"
            >
              Retirer
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="importError"
        class="text-xs text-rose-700 bg-rose-50 p-2.5 rounded-lg border border-rose-200 flex items-center gap-2"
      >
        <AlertTriangle class="w-4 h-4 shrink-0 text-rose-600" />
        <span>{{ importError }}</span>
      </div>

      <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
        <button
          type="button"
          @click="$emit('close')"
          class="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50 cursor-pointer"
        >
          Annuler
        </button>
        <button
          type="button"
          :disabled="!canSave || isUploading"
          @click="saveImport"
          class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-xs flex items-center gap-1.5 cursor-pointer"
        >
          <span v-if="isUploading" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
          <span>{{ submitLabel }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { X, Plus, Upload, AlertTriangle } from 'lucide-vue-next';
import api from '../../utils/api';
import type { School, CalendarImport } from '../../types/calendar';
import { levels, sectionOptions, useSchoolCalendar } from '../../composables/useSchoolCalendar';

const props = defineProps<{
  isOpen: boolean;
  isEditing: boolean;
  editingItem: CalendarImport | null;
  schools: School[];
  defaultSchoolId: number | null;
  defaultLevel: number;
  defaultSection: string;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'saved', msg: string): void;
  (e: 'openNewSchool'): void;
}>();

const { formatLevel, formatSectionDisplay, normalizeSection, extractError } = useSchoolCalendar();

const MAX_SIZE = 10 * 1024 * 1024;
const ALLOWED_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

const importSchoolId = ref<number | null>(null);
const importLevel = ref(1);
const importSection = ref('A');
const selectedFile = ref<File | null>(null);
const dragOver = ref(false);
const isUploading = ref(false);
const importError = ref('');
const fileInput = ref<HTMLInputElement | null>(null);

const canSave = computed(
  () => importSchoolId.value !== null && (props.isEditing || selectedFile.value !== null)
);

const modalTitle = computed(() =>
  props.isEditing ? "Modifier l'affectation ou le fichier" : 'Importer un emploi du temps'
);

const modalSubtitle = computed(() =>
  props.isEditing
    ? 'Modifiez l’école, le niveau, la section ou remplacez le document'
    : 'PDF, JPG ou PNG — 10 Mo maximum'
);

const submitLabel = computed(() => {
  if (isUploading.value) {
    return props.isEditing ? 'Enregistrement...' : 'Importation...';
  }
  return props.isEditing ? 'Mettre à jour' : 'Importer';
});

watch(
  () => props.isOpen,
  isOpen => {
    if (!isOpen) return;
    importError.value = '';
    selectedFile.value = null;
    if (props.isEditing && props.editingItem) {
      importSchoolId.value = props.editingItem.school_id;
      importLevel.value = props.editingItem.level;
      importSection.value = normalizeSection(props.editingItem.section);
    } else {
      importSchoolId.value = props.defaultSchoolId || (props.schools[0]?.id ?? null);
      importLevel.value = props.defaultLevel || 1;
      importSection.value = props.defaultSection || 'A';
    }
  },
  { immediate: true }
);

function triggerFileInput() {
  fileInput.value?.click();
}

function setFile(file: File) {
  importError.value = '';
  if (!ALLOWED_TYPES.includes(file.type)) {
    importError.value = 'Format non accepté. Veuillez sélectionner un PDF, un JPG ou un PNG.';
    return;
  }
  if (file.size > MAX_SIZE) {
    importError.value = 'Fichier trop volumineux. La taille maximale autorisée est de 10 Mo.';
    return;
  }
  selectedFile.value = file;
}

function clearFile() {
  selectedFile.value = null;
  if (fileInput.value) fileInput.value.value = '';
}

function handleFileChange(e: Event) {
  const input = e.target as HTMLInputElement;
  if (input.files && input.files[0]) {
    setFile(input.files[0]);
  }
}

function handleFileDrop(e: DragEvent) {
  dragOver.value = false;
  if (e.dataTransfer?.files?.[0]) {
    setFile(e.dataTransfer.files[0]);
  }
}

const multipartConfig = {
  transformRequest: [
    (data: any, headers: any) => {
      if (headers) {
        if (typeof headers.delete === 'function') {
          headers.delete('Content-Type');
        } else {
          delete headers['Content-Type'];
          delete headers['content-type'];
        }
      }
      return data;
    }
  ]
};

async function saveImport() {
  if (importSchoolId.value === null) return;
  if (!props.isEditing && !selectedFile.value) return;

  isUploading.value = true;
  importError.value = '';

  const wasEditing = props.isEditing;

  try {
    const formData = new FormData();
    if (selectedFile.value) {
      formData.append('file', selectedFile.value);
    }
    formData.append('school_id', String(importSchoolId.value));
    formData.append('level', String(importLevel.value));
    formData.append('section', importSection.value);

    if (wasEditing && props.editingItem) {
      await api.post(
        `/api/school-calendar-imports/${props.editingItem.id}`,
        formData,
        multipartConfig
      );
    } else {
      await api.post('/api/school-calendar-imports', formData, multipartConfig);
    }

    const schoolName = props.schools.find(s => s.id === importSchoolId.value)?.name ?? '';
    const sectionLabel = formatSectionDisplay(importSection.value);
    const where = `${formatLevel(importLevel.value)}, ${sectionLabel}, ${schoolName}`;

    emit(
      'saved',
      wasEditing
        ? `Emploi du temps mis à jour avec succès : ${where}.`
        : `Nouvel emploi du temps importé avec succès : ${where}.`
    );
    emit('close');
  } catch (e) {
    importError.value = extractError(
      e,
      wasEditing
        ? 'La modification a échoué. Veuillez vérifier les champs et réessayer.'
        : "L'import a échoué. Vérifiez le fichier et réessayez."
    );
  } finally {
    isUploading.value = false;
  }
}
</script>
