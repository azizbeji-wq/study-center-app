<template>
  <div class="space-y-6">

    <!-- En-tête -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Emplois du temps des écoles primaires</h2>
        <p class="text-sm text-gray-500 mt-0.5">
          Importez le fichier d'une classe, puis retrouvez-le ici en un clic.
        </p>
      </div>

      <button
        @click="openImport"
        class="flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm text-sm"
      >
        <Upload class="w-4 h-4" />
        Importer un emploi du temps
      </button>
    </div>

    <!-- Message de succès -->
    <div
      v-if="successMessage"
      class="text-sm text-green-800 bg-green-50 p-3 rounded-lg border border-green-200"
    >
      {{ successMessage }}
    </div>

    <!-- Recherche -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
      <p class="text-sm font-semibold text-gray-700 mb-3">Rechercher un emploi du temps</p>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <select
          v-model="filterSchoolId"
          aria-label="École"
          class="px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        >
          <option :value="null">Toutes les écoles</option>
          <option v-for="school in schools" :key="school.id" :value="school.id">
            {{ school.name }}
          </option>
        </select>

        <select
          v-model="filterLevel"
          aria-label="Niveau"
          class="px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        >
          <option :value="null">Tous les niveaux</option>
          <option v-for="lvl in levels" :key="lvl" :value="lvl">{{ formatLevel(lvl) }}</option>
        </select>

        <select
          v-model="filterSection"
          aria-label="Classe"
          class="px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        >
          <option :value="null">Toutes les classes</option>
          <option v-for="sec in sections" :key="sec" :value="sec">Classe {{ sec }}</option>
        </select>
      </div>

      <div
        v-if="errorMessage"
        class="mt-3 text-xs text-red-600 bg-red-50 p-2.5 rounded-lg border border-red-200"
      >
        {{ errorMessage }}
      </div>
    </div>

    <!-- Chargement -->
    <div v-if="loadingList" class="flex justify-center py-10">
      <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Aucun résultat -->
    <div
      v-else-if="imports.length === 0"
      class="bg-gray-50 border border-gray-200 rounded-xl p-10 text-center"
    >
      <h3 class="font-semibold text-gray-800">Aucun emploi du temps trouvé</h3>
      <p class="text-sm text-gray-500 mt-1">
        Aucun fichier ne correspond à cette recherche. Importez-en un avec le bouton ci-dessus.
      </p>
    </div>

    <!-- Cartes -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <article
        v-for="item in imports"
        :key="item.id"
        class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col"
      >
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
            <FileText v-if="isPdf(item)" class="w-5 h-5" />
            <ImageIcon v-else class="w-5 h-5" />
          </div>

          <div class="min-w-0">
            <h3 class="text-base font-bold text-gray-900">
              {{ formatLevel(item.level) }} - Classe {{ item.section }}
            </h3>
            <p class="text-sm text-gray-600 truncate">{{ item.school_name }}</p>
          </div>
        </div>

        <p class="text-xs text-gray-500 mt-3 truncate">{{ item.file_name }}</p>
        <p class="text-xs text-gray-400 mt-0.5">Importé le {{ formatDate(item.created_at) }}</p>

        <div class="mt-4 flex gap-2">
          <button
            @click="openViewer(item)"
            class="flex-1 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-sm rounded-lg border border-indigo-200 transition-colors"
          >
            Voir plus
          </button>
          <button
            @click="openEdit(item)"
            aria-label="Modifier"
            title="Modifier"
            class="p-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <Pencil class="w-4 h-4" />
          </button>
          <button
            @click="askDelete(item)"
            aria-label="Supprimer"
            title="Supprimer"
            class="p-2 border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition-colors"
          >
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </article>
    </div>

    <!-- Fenêtre de consultation du fichier -->
    <div
      v-if="viewerItem"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click="closeViewer"
    >
      <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col"
        @click.stop
      >
        <div class="flex items-start justify-between gap-3 p-5 border-b border-gray-100">
          <div class="min-w-0">
            <h3 class="text-lg font-bold text-gray-900">
              {{ formatLevel(viewerItem.level) }} - Classe {{ viewerItem.section }}
            </h3>
            <p class="text-sm text-gray-500 truncate">
              {{ viewerItem.school_name }} - {{ viewerItem.file_name }}
            </p>
          </div>
          <button
            @click="closeViewer"
            aria-label="Fermer"
            class="text-gray-400 hover:text-gray-600 p-1"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="flex-1 overflow-auto p-5 bg-gray-50 min-h-[300px]">
          <div v-if="viewerLoading" class="flex justify-center py-16">
            <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
          </div>

          <p v-else-if="viewerError" class="text-sm text-red-600 text-center py-16">
            {{ viewerError }}
          </p>

          <template v-else-if="viewerUrl">
            <iframe
              v-if="isPdf(viewerItem)"
              :src="viewerUrl"
              title="Emploi du temps"
              class="w-full h-[68vh] rounded-lg border border-gray-200 bg-white"
            ></iframe>
            <img
              v-else
              :src="viewerUrl"
              alt="Emploi du temps"
              class="max-w-full mx-auto rounded-lg border border-gray-200 bg-white"
            />
          </template>
        </div>

        <div class="flex flex-wrap justify-end gap-2 p-4 border-t border-gray-100">
          <a
            v-if="viewerUrl"
            :href="viewerUrl"
            target="_blank"
            rel="noopener"
            class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
          >
            <ExternalLink class="w-4 h-4" />
            Ouvrir dans un onglet
          </a>
          <a
            v-if="viewerUrl"
            :href="viewerUrl"
            :download="viewerItem.file_name"
            class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700"
          >
            <Download class="w-4 h-4" />
            Télécharger
          </a>
        </div>
      </div>
    </div>

    <!-- Fenêtre d'import / de modification -->
    <div
      v-if="showImportModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click="closeImport"
    >
      <div
        class="bg-white rounded-2xl shadow-2xl p-6 max-w-lg w-full space-y-4 max-h-[90vh] overflow-y-auto"
        @click.stop
      >
        <div class="flex items-start justify-between border-b border-gray-100 pb-3">
          <div>
            <h3 class="text-lg font-bold text-gray-900">{{ modalTitle }}</h3>
            <p class="text-xs text-gray-500">{{ modalSubtitle }}</p>
          </div>
          <button @click="closeImport" aria-label="Fermer" class="text-gray-400 hover:text-gray-600 p-1">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">École *</label>
          <select
            v-model="importSchoolId"
            class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          >
            <option :value="null">Choisir une école...</option>
            <option v-for="school in schools" :key="school.id" :value="school.id">
              {{ school.name }}
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Niveau *</label>
            <select
              v-model.number="importLevel"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            >
              <option v-for="lvl in levels" :key="lvl" :value="lvl">{{ formatLevel(lvl) }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Classe *</label>
            <select
              v-model="importSection"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            >
              <option v-for="sec in sections" :key="sec" :value="sec">Classe {{ sec }}</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">
            {{ isEditing ? 'Nouveau fichier (facultatif)' : 'Fichier *' }}
          </label>

          <p
            v-if="isEditing && editingItem"
            class="text-xs text-gray-500 mb-2"
          >
            Fichier actuel : <span class="font-semibold text-gray-700">{{ editingItem.file_name }}</span>.
            Laissez vide pour le conserver.
          </p>

          <div
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="handleFileDrop"
            @click="triggerFileInput"
            :class="[
              'border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-colors',
              dragOver ? 'border-indigo-600 bg-indigo-50/50' : 'border-gray-300 hover:border-indigo-400 bg-gray-50/50'
            ]"
          >
            <input
              ref="fileInput"
              type="file"
              accept=".pdf,image/jpeg,image/png"
              class="hidden"
              @change="handleFileChange"
            />

            <div v-if="!selectedFile" class="space-y-2">
              <div class="w-12 h-12 rounded-full bg-indigo-100 text-indigo-600 mx-auto flex items-center justify-center">
                <FileText class="w-6 h-6" />
              </div>
              <p class="text-sm font-semibold text-gray-700">
                Cliquez pour choisir un fichier ou glissez-le ici
              </p>
            </div>

            <div v-else class="flex items-center justify-between p-3 bg-white rounded-lg border border-indigo-200">
              <div class="text-left min-w-0">
                <p class="text-sm font-bold text-gray-900 truncate">{{ selectedFile.name }}</p>
                <p class="text-xs text-gray-500">{{ (selectedFile.size / 1024).toFixed(1) }} Ko</p>
              </div>
              <button
                type="button"
                @click.stop="clearFile"
                class="text-xs text-red-600 hover:underline font-semibold shrink-0 ml-3"
              >
                Retirer
              </button>
            </div>
          </div>
        </div>

        <div
          v-if="importError"
          class="text-xs text-red-600 bg-red-50 p-2.5 rounded-lg border border-red-200"
        >
          {{ importError }}
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
          <button
            type="button"
            @click="closeImport"
            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
          >
            Annuler
          </button>
          <button
            type="button"
            :disabled="!canSave || isUploading"
            @click="saveImport"
            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-sm"
          >
            {{ submitLabel }}
          </button>
        </div>
      </div>
    </div>

    <!-- Confirmation de suppression -->
    <div
      v-if="itemToDelete"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click="cancelDelete"
    >
      <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full space-y-4" @click.stop>
        <div>
          <h3 class="text-lg font-bold text-gray-900">Supprimer cet emploi du temps ?</h3>
          <p class="text-sm text-gray-600 mt-2">
            {{ formatLevel(itemToDelete.level) }} - Classe {{ itemToDelete.section }},
            {{ itemToDelete.school_name }}.
            Le fichier <span class="font-semibold">{{ itemToDelete.file_name }}</span>
            sera supprimé définitivement.
          </p>
        </div>

        <div
          v-if="deleteError"
          class="text-xs text-red-600 bg-red-50 p-2.5 rounded-lg border border-red-200"
        >
          {{ deleteError }}
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button
            type="button"
            @click="cancelDelete"
            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
          >
            Annuler
          </button>
          <button
            type="button"
            :disabled="isDeleting"
            @click="confirmDelete"
            class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 disabled:opacity-50 transition-colors"
          >
            {{ isDeleting ? 'Suppression...' : 'Supprimer' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import api from '../utils/api';
import {
  Upload,
  FileText,
  Image as ImageIcon,
  X,
  Download,
  ExternalLink,
  Pencil,
  Trash2
} from 'lucide-vue-next';

interface School {
  id: number;
  name: string;
}

interface CalendarImport {
  id: number;
  file_name: string;
  file_type: string | null;
  created_at: string;
  school_id: number;
  school_name: string;
  level: number;
  section: string;
}

const levels = [1, 2, 3, 4, 5, 6];
const sections = ['أ', 'ب', 'ج', 'د'];
const MAX_SIZE = 10 * 1024 * 1024;
const ALLOWED_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

const schools = ref<School[]>([]);
const imports = ref<CalendarImport[]>([]);
const loadingList = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const filterSchoolId = ref<number | null>(null);
const filterLevel = ref<number | null>(null);
const filterSection = ref<string | null>(null);

const viewerItem = ref<CalendarImport | null>(null);
const viewerUrl = ref('');
const viewerLoading = ref(false);
const viewerError = ref('');

const showImportModal = ref(false);
const editingItem = ref<CalendarImport | null>(null);
const importSchoolId = ref<number | null>(null);
const importLevel = ref(1);
const importSection = ref('أ');
const selectedFile = ref<File | null>(null);
const dragOver = ref(false);
const isUploading = ref(false);
const importError = ref('');
const fileInput = ref<HTMLInputElement | null>(null);

const itemToDelete = ref<CalendarImport | null>(null);
const isDeleting = ref(false);
const deleteError = ref('');

const isEditing = computed(() => editingItem.value !== null);

const canSave = computed(
  () => importSchoolId.value !== null && (isEditing.value || selectedFile.value !== null)
);

const modalTitle = computed(() =>
  isEditing.value ? "Modifier l'emploi du temps" : 'Importer un emploi du temps'
);

const modalSubtitle = computed(() =>
  isEditing.value
    ? 'Changez la classe ou remplacez le fichier'
    : 'PDF, JPG ou PNG - 10 Mo maximum'
);

const submitLabel = computed(() => {
  if (isUploading.value) {
    return isEditing.value ? 'Enregistrement...' : 'Import en cours...';
  }
  return isEditing.value ? 'Enregistrer' : 'Importer';
});

function formatLevel(level: number) {
  return level === 1 ? '1ère année' : `${level}ème année`;
}

function formatDate(value: string) {
  return new Date(value).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  });
}

function isPdf(item: CalendarImport) {
  return (item.file_type ?? '').includes('pdf') || item.file_name.toLowerCase().endsWith('.pdf');
}

function extractError(e: any, fallback: string) {
  const errors = e?.response?.data?.errors;
  if (errors) {
    const first = Object.values(errors)[0] as string[];
    if (first?.length) return first[0];
  }
  return e?.response?.data?.message || fallback;
}

function showSuccess(message: string) {
  successMessage.value = message;
  setTimeout(() => {
    if (successMessage.value === message) successMessage.value = '';
  }, 6000);
}

async function loadSchools() {
  try {
    const res = await api.get('/api/schools');
    schools.value = res.data;
  } catch (e) {
    errorMessage.value = extractError(e, 'Impossible de charger la liste des écoles.');
  }
}

async function loadImports() {
  loadingList.value = true;
  errorMessage.value = '';

  try {
    const res = await api.get('/api/school-calendar-imports', {
      params: {
        school_id: filterSchoolId.value ?? undefined,
        level: filterLevel.value ?? undefined,
        section: filterSection.value ?? undefined
      }
    });
    imports.value = res.data;
  } catch (e) {
    imports.value = [];
    errorMessage.value = extractError(e, 'Impossible de charger les emplois du temps.');
  } finally {
    loadingList.value = false;
  }
}

watch([filterSchoolId, filterLevel, filterSection], loadImports);

/* Consultation */

async function openViewer(item: CalendarImport) {
  viewerItem.value = item;
  viewerUrl.value = '';
  viewerError.value = '';
  viewerLoading.value = true;

  try {
    const res = await api.get(`/api/school-calendar-imports/${item.id}/file`, {
      responseType: 'blob'
    });
    viewerUrl.value = URL.createObjectURL(res.data);
  } catch (e) {
    viewerError.value = 'Impossible de charger le fichier. Réessayez dans un instant.';
  } finally {
    viewerLoading.value = false;
  }
}

function closeViewer() {
  if (viewerUrl.value) {
    URL.revokeObjectURL(viewerUrl.value);
  }
  viewerUrl.value = '';
  viewerItem.value = null;
  viewerError.value = '';
}

/* Création et modification */

function openImport() {
  editingItem.value = null;
  importSchoolId.value = filterSchoolId.value;
  importLevel.value = filterLevel.value ?? 1;
  importSection.value = filterSection.value ?? 'A';
  selectedFile.value = null;
  importError.value = '';
  showImportModal.value = true;
}

function openEdit(item: CalendarImport) {
  editingItem.value = item;
  importSchoolId.value = item.school_id;
  importLevel.value = item.level;
  importSection.value = item.section;
  selectedFile.value = null;
  importError.value = '';
  showImportModal.value = true;
}

function closeImport() {
  showImportModal.value = false;
  editingItem.value = null;
  selectedFile.value = null;
  importError.value = '';
}

function triggerFileInput() {
  fileInput.value?.click();
}

function setFile(file: File) {
  importError.value = '';

  if (!ALLOWED_TYPES.includes(file.type)) {
    importError.value = 'Format non accepté. Choisissez un PDF, un JPG ou un PNG.';
    return;
  }
  if (file.size > MAX_SIZE) {
    importError.value = 'Fichier trop volumineux. La taille maximale est de 10 Mo.';
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

// Laisse le navigateur envoyer le formulaire en multipart/form-data
// (sinon axios peut le convertir en JSON et le fichier est perdu).
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
  if (!isEditing.value && !selectedFile.value) return;

  isUploading.value = true;
  importError.value = '';

  const wasEditing = isEditing.value;

  try {
    const formData = new FormData();
    if (selectedFile.value) {
      formData.append('file', selectedFile.value);
    }
    formData.append('school_id', String(importSchoolId.value));
    formData.append('level', String(importLevel.value));
    formData.append('section', importSection.value);

    if (wasEditing && editingItem.value) {
      await api.post(
        `/api/school-calendar-imports/${editingItem.value.id}`,
        formData,
        multipartConfig
      );
    } else {
      await api.post('/api/school-calendar-imports', formData, multipartConfig);
    }

    const schoolName = schools.value.find(s => s.id === importSchoolId.value)?.name ?? '';
    const where = `${formatLevel(importLevel.value)}, classe ${importSection.value}, ${schoolName}`;
    showSuccess(
      wasEditing
        ? `Emploi du temps modifié : ${where}.`
        : `Emploi du temps importé : ${where}.`
    );

    filterSchoolId.value = importSchoolId.value;
    filterLevel.value = importLevel.value;
    filterSection.value = importSection.value;

    closeImport();
    await loadImports();
  } catch (e) {
    importError.value = extractError(
      e,
      wasEditing
        ? 'La modification a échoué. Réessayez.'
        : "L'import a échoué. Vérifiez le fichier et réessayez."
    );
  } finally {
    isUploading.value = false;
  }
}

/* Suppression */

function askDelete(item: CalendarImport) {
  itemToDelete.value = item;
  deleteError.value = '';
}

function cancelDelete() {
  if (isDeleting.value) return;
  itemToDelete.value = null;
  deleteError.value = '';
}

async function confirmDelete() {
  if (!itemToDelete.value) return;

  isDeleting.value = true;
  deleteError.value = '';

  try {
    await api.delete(`/api/school-calendar-imports/${itemToDelete.value.id}`);
    itemToDelete.value = null;
    showSuccess('Emploi du temps supprimé.');
    await loadImports();
  } catch (e) {
    deleteError.value = extractError(e, 'La suppression a échoué. Réessayez.');
  } finally {
    isDeleting.value = false;
  }
}

onMounted(async () => {
  await loadSchools();
  await loadImports();
});

onBeforeUnmount(() => {
  if (viewerUrl.value) URL.revokeObjectURL(viewerUrl.value);
});
</script>