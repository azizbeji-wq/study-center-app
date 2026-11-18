<template>
  <div class="space-y-6">

    <!-- En-tête principal -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2.5">
          <Calendar class="w-7 h-7 text-indigo-600" />
          Calendrier Scolaire des Écoles Primaires
        </h2>
        <p class="text-sm text-gray-500 mt-1">
          Gestion complète des écoles partenaires et des emplois du temps importés par classe.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <button
          @click="openSchoolModal()"
          class="flex items-center gap-2 px-3.5 py-2 bg-white text-gray-700 font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors shadow-xs text-sm cursor-pointer"
        >
          <Building2 class="w-4 h-4 text-indigo-600" />
          <span>Ajouter une école</span>
        </button>

        <button
          @click="openImport()"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm text-sm cursor-pointer"
        >
          <Upload class="w-4 h-4" />
          <span>Importer un emploi du temps</span>
        </button>
      </div>
    </div>

    <!-- Messages de succès et d'erreur généraux -->
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform -translate-y-2 opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="successMessage"
        class="flex items-center justify-between text-sm text-emerald-800 bg-emerald-50 px-4 py-3 rounded-xl border border-emerald-200 shadow-xs"
      >
        <div class="flex items-center gap-2.5">
          <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
          <span>{{ successMessage }}</span>
        </div>
        <button @click="successMessage = ''" class="text-emerald-600 hover:text-emerald-800 p-1 cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>
    </transition>

    <div
      v-if="errorMessage"
      class="flex items-center justify-between text-sm text-red-800 bg-red-50 px-4 py-3 rounded-xl border border-red-200 shadow-xs"
    >
      <div class="flex items-center gap-2.5">
        <AlertTriangle class="w-5 h-5 text-red-600 shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>
      <button @click="errorMessage = ''" class="text-red-600 hover:text-red-800 p-1 cursor-pointer">
        <X class="w-4 h-4" />
      </button>
    </div>

    <!-- Statistiques rapides -->
    <CalendarStats
      :importsCount="imports.length"
      :schoolsCount="schools.length"
      :distinctLevelsCount="distinctLevelsCount"
      :latestImportText="latestImportText"
    />

    <!-- Onglets de navigation : Emplois du temps vs Gestion des écoles -->
    <div class="flex items-center gap-2 border-b border-gray-200 pb-2">
      <button
        @click="activeView = 'imports'"
        :class="[
          'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer',
          activeView === 'imports'
            ? 'bg-indigo-600 text-white shadow-xs'
            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
        ]"
      >
        <FileText class="w-4 h-4" />
        <span>Emplois du temps importés</span>
        <span
          :class="[
            'text-xs px-2 py-0.5 rounded-full font-bold',
            activeView === 'imports' ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-700'
          ]"
        >
          {{ imports.length }}
        </span>
      </button>

      <button
        @click="activeView = 'schools'"
        :class="[
          'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer',
          activeView === 'schools'
            ? 'bg-indigo-600 text-white shadow-xs'
            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
        ]"
      >
        <Building2 class="w-4 h-4" />
        <span>Gestion des Écoles</span>
        <span
          :class="[
            'text-xs px-2 py-0.5 rounded-full font-bold',
            activeView === 'schools' ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-700'
          ]"
        >
          {{ schools.length }}
        </span>
      </button>
    </div>

    <!-- VUE 1 : Emplois du temps -->
    <CalendarImportsGrid
      v-if="activeView === 'imports'"
      :imports="filteredImports"
      :schools="schools"
      v-model:searchQuery="searchQuery"
      v-model:filterSchoolId="filterSchoolId"
      v-model:filterLevel="filterLevel"
      v-model:filterSection="filterSection"
      :loadingList="loadingList"
      :hasActiveFilters="hasActiveFilters"
      @resetFilters="resetFilters"
      @openImport="openImport"
      @openViewer="openViewer"
      @openEdit="openEdit"
      @askDelete="askDelete"
    />

    <!-- VUE 2 : Gestion des Écoles -->
    <CalendarSchoolsGrid
      v-else-if="activeView === 'schools'"
      :schools="filteredSchools"
      v-model:searchQuery="schoolSearchQuery"
      :getSchoolImportCount="getSchoolImportCount"
      @openSchoolModal="openSchoolModal"
      @viewSchoolImports="viewSchoolImports"
      @askDeleteSchool="askDeleteSchool"
    />

    <!-- Modal Visualisation (Viewer) -->
    <CalendarViewerModal
      :item="viewerItem"
      :viewerUrl="viewerUrl"
      :loading="viewerLoading"
      :error="viewerError"
      @close="closeViewer"
    />

    <!-- Modal Import / Modification -->
    <CalendarImportModal
      :isOpen="showImportModal"
      :isEditing="isEditing"
      :editingItem="editingItem"
      :schools="schools"
      :defaultSchoolId="filterSchoolId"
      :defaultLevel="filterLevel ?? 1"
      :defaultSection="filterSection ?? 'A'"
      @close="closeImport"
      @saved="onImportSaved"
      @openNewSchool="openSchoolModal(null, true)"
    />

    <!-- Modal École -->
    <SchoolFormModal
      :isOpen="showSchoolModal"
      :editingSchool="editingSchool"
      @close="closeSchoolModal"
      @saved="onSchoolSaved"
    />

    <!-- Modal Confirmation Suppression Emploi du Temps -->
    <CalendarDeleteConfirmModal
      :isOpen="!!itemToDelete"
      title="Supprimer cet emploi du temps ?"
      :message="deleteItemMessage"
      :error="deleteError"
      :isDeleting="isDeleting"
      confirmLabel="Supprimer définitivement"
      @cancel="cancelDelete"
      @confirm="confirmDelete"
    />

    <!-- Modal Confirmation Suppression École -->
    <CalendarDeleteConfirmModal
      :isOpen="!!schoolToDelete"
      title="Supprimer cette école ?"
      :message="deleteSchoolMessage"
      :error="deleteSchoolError"
      :isDeleting="isDeletingSchool"
      confirmLabel="Supprimer l'école"
      @cancel="schoolToDelete = null"
      @confirm="confirmDeleteSchool"
    />

  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { Calendar, Building2, Upload, FileText, CheckCircle2, AlertTriangle, X } from 'lucide-vue-next';
import api from '../utils/api';
import CalendarStats from './school-calendar/CalendarStats.vue';
import CalendarImportsGrid from './school-calendar/CalendarImportsGrid.vue';
import CalendarSchoolsGrid from './school-calendar/CalendarSchoolsGrid.vue';
import CalendarViewerModal from './school-calendar/CalendarViewerModal.vue';
import CalendarImportModal from './school-calendar/CalendarImportModal.vue';
import SchoolFormModal from './school-calendar/SchoolFormModal.vue';
import CalendarDeleteConfirmModal from './school-calendar/CalendarDeleteConfirmModal.vue';
import { useSchoolCalendar } from '../composables/useSchoolCalendar';
import type { School, CalendarImport } from '../types/calendar';

const {
  schools,
  imports,
  loadingList,
  errorMessage,
  successMessage,
  searchQuery,
  filterSchoolId,
  filterLevel,
  filterSection,
  schoolSearchQuery,
  hasActiveFilters,
  filteredImports,
  filteredSchools,
  distinctLevelsCount,
  latestImportText,
  formatLevel,
  formatSectionDisplay,
  getSchoolImportCount,
  extractError,
  showSuccess,
  resetFilters,
  loadSchools,
  loadImports
} = useSchoolCalendar();

const activeView = ref<'imports' | 'schools'>('imports');

// Viewer state
const viewerItem = ref<CalendarImport | null>(null);
const viewerUrl = ref('');
const viewerLoading = ref(false);
const viewerError = ref('');

// Import modal state
const showImportModal = ref(false);
const editingItem = ref<CalendarImport | null>(null);
const isEditing = computed(() => editingItem.value !== null);

// School modal state
const showSchoolModal = ref(false);
const editingSchool = ref<School | null>(null);
const returnToImportAfterSchool = ref(false);

// Deletions
const itemToDelete = ref<CalendarImport | null>(null);
const isDeleting = ref(false);
const deleteError = ref('');

const schoolToDelete = ref<School | null>(null);
const isDeletingSchool = ref(false);
const deleteSchoolError = ref('');

const deleteItemMessage = computed(() => {
  if (!itemToDelete.value) return '';
  return `Cette action supprimera définitivement le fichier <span class="font-semibold">${itemToDelete.value.file_name}</span> pour
  <span class="font-semibold text-gray-800">${formatLevel(itemToDelete.value.level)} (Classe ${formatSectionDisplay(itemToDelete.value.section)})</span>
  à ${itemToDelete.value.school_name}.`;
});

const deleteSchoolMessage = computed(() => {
  if (!schoolToDelete.value) return '';
  return `Êtes-vous sûr de vouloir supprimer l'école <span class="font-semibold text-gray-900">${schoolToDelete.value.name}</span> ?
  Tous les emplois du temps associés seront également supprimés.`;
});

function viewSchoolImports(schoolId: number) {
  activeView.value = 'imports';
  filterSchoolId.value = schoolId;
  filterLevel.value = null;
  filterSection.value = null;
  searchQuery.value = '';
}

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
    viewerError.value = 'Impossible de charger le fichier. Vérifiez votre connexion et réessayez.';
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

function openImport() {
  editingItem.value = null;
  showImportModal.value = true;
}

function openEdit(item: CalendarImport) {
  editingItem.value = item;
  showImportModal.value = true;
}

function closeImport() {
  showImportModal.value = false;
  editingItem.value = null;
}

async function onImportSaved(msg: string) {
  showSuccess(msg);
  await loadImports();
}

function openSchoolModal(school: School | null = null, fromImport = false) {
  editingSchool.value = school;
  returnToImportAfterSchool.value = fromImport;
  showSchoolModal.value = true;
}

function closeSchoolModal() {
  showSchoolModal.value = false;
  editingSchool.value = null;
  returnToImportAfterSchool.value = false;
}

async function onSchoolSaved(payload: { id: number; name: string; isEdit: boolean }) {
  showSuccess(`École "${payload.name}" ${payload.isEdit ? 'mise à jour' : 'ajoutée'}.`);
  await loadSchools();
  if (returnToImportAfterSchool.value) {
    filterSchoolId.value = payload.id;
  }
}

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
    showSuccess('Emploi du temps supprimé avec succès.');
    await loadImports();
  } catch (e) {
    deleteError.value = extractError(e, 'La suppression a échoué. Réessayez.');
  } finally {
    isDeleting.value = false;
  }
}

function askDeleteSchool(school: School) {
  schoolToDelete.value = school;
  deleteSchoolError.value = '';
}

async function confirmDeleteSchool() {
  if (!schoolToDelete.value) return;

  isDeletingSchool.value = true;
  deleteSchoolError.value = '';

  try {
    await api.delete(`/api/schools/${schoolToDelete.value.id}`);
    const name = schoolToDelete.value.name;
    schoolToDelete.value = null;
    showSuccess(`École "${name}" et ses emplois du temps associés ont été supprimés.`);
    await loadSchools();
    await loadImports();
  } catch (e) {
    deleteSchoolError.value = extractError(e, "Impossible de supprimer l'école.");
  } finally {
    isDeletingSchool.value = false;
  }
}

watch([filterSchoolId, filterLevel, filterSection], () => {
  loadImports();
});

onMounted(async () => {
  await Promise.all([loadSchools(), loadImports()]);
});

onBeforeUnmount(() => {
  if (viewerUrl.value) URL.revokeObjectURL(viewerUrl.value);
});
</script>