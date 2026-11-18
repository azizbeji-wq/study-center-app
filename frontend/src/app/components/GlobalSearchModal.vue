<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-start justify-center pt-12 sm:pt-20 px-4 bg-gray-900/60 backdrop-blur-xs transition-opacity animate-in fade-in duration-150"
    @click.self="close"
    role="dialog"
    aria-modal="true"
    aria-label="Recherche globale"
  >
    <div
      class="bg-white rounded-2xl shadow-2xl border border-gray-200 w-full max-w-2xl overflow-hidden flex flex-col max-h-[80vh] transition-all transform animate-in zoom-in-95 duration-150"
      @keydown="handleKeydown"
    >
      <!-- Barre d'entrée de recherche -->
      <div class="p-3.5 sm:p-4 border-b border-gray-100 flex items-center gap-3 bg-white">
        <Search class="w-5 h-5 text-gray-400 shrink-0 ml-1" />
        <input
          ref="searchInputRef"
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher un élève (nom, niveau, groupe) ou un enseignant..."
          class="flex-1 text-sm sm:text-base text-gray-900 placeholder:text-gray-400 bg-transparent border-0 focus:outline-none focus:ring-0 min-h-[44px]"
          aria-label="Champ de recherche globale"
          @input="onInput"
        />
        <div v-if="loading" class="shrink-0 mr-1">
          <Loader2 class="w-5 h-5 text-indigo-600 animate-spin" />
        </div>
        <button
          v-else-if="searchQuery"
          @click="searchQuery = ''; performSearch()"
          class="p-2 text-gray-400 hover:text-gray-600 rounded-lg min-h-[44px] min-w-[44px] flex items-center justify-center cursor-pointer"
          aria-label="Effacer la recherche"
        >
          <X class="w-4 h-4" />
        </button>
        <button
          @click="close"
          class="px-2.5 py-1 text-xs font-semibold text-gray-500 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-lg min-h-[44px] flex items-center justify-center cursor-pointer transition-colors"
          aria-label="Fermer la recherche (Échap)"
        >
          Échap
        </button>
      </div>

      <!-- Corps des résultats -->
      <div class="flex-1 overflow-y-auto p-3 sm:p-4 divide-y divide-gray-100 space-y-4">
        <!-- Message si moins de 2 caractères -->
        <div
          v-if="searchQuery.trim().length < 2 && !loading"
          class="py-12 text-center text-gray-400 space-y-2"
        >
          <Search class="w-8 h-8 mx-auto text-gray-300 stroke-1" />
          <p class="text-sm">Tapez au moins 2 caractères pour rechercher</p>
          <p class="text-xs text-gray-400">
            Recherche dans les élèves par nom, niveau, groupe pédagogique, et enseignants.
          </p>
        </div>

        <!-- Aucun résultat -->
        <div
          v-else-if="!loading && hasSearched && totalResults === 0"
          class="py-12 text-center text-gray-500 space-y-3"
        >
          <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto">
            <Search class="w-6 h-6 stroke-[1.5]" />
          </div>
          <div class="space-y-1">
            <h4 class="text-sm font-bold text-gray-800">Aucun résultat. Essayez un mot plus court.</h4>
            <p class="text-xs text-gray-400">
              Vérifiez l'orthographe du nom de l'élève ou le libellé du groupe.
            </p>
          </div>
        </div>

        <!-- Section Élèves -->
        <div v-if="students.length > 0" class="pt-2">
          <div class="flex items-center justify-between mb-2 px-1">
            <div class="flex items-center gap-2">
              <GraduationCap class="w-4 h-4 text-indigo-600" />
              <span class="text-xs font-black uppercase tracking-wider text-gray-500">Élèves</span>
            </div>
            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">
              {{ students.length }} résultat{{ students.length > 1 ? 's' : '' }}
            </span>
          </div>

          <div class="space-y-2">
            <div
              v-for="(student, sIdx) in students"
              :key="'student-' + student.id"
              :class="[
                'p-3 rounded-xl border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3',
                selectedItemIndex === sIdx
                  ? 'border-indigo-500 bg-indigo-50/50 ring-2 ring-indigo-500/30'
                  : 'border-gray-200/80 bg-white hover:border-gray-300 hover:bg-gray-50/50'
              ]"
              @mouseenter="selectedItemIndex = sIdx"
            >
              <!-- Info élève -->
              <div class="flex items-start gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm shrink-0">
                  {{ student.name.charAt(0).toUpperCase() }}
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-bold text-gray-900 truncate">{{ student.name }}</span>
                    <span
                      v-if="student.group_name"
                      class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200"
                    >
                      {{ student.group_name }}
                    </span>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-gray-500 mt-0.5 flex-wrap">
                    <span v-if="student.level">{{ student.level }}</span>
                    <span v-if="student.phone">• Tél: {{ student.phone }}</span>
                    <span v-if="student.parent_phone" class="text-gray-400">• Parent: {{ student.parent_phone }}</span>
                  </div>
                </div>
              </div>

              <!-- Actions autorisées pour cet élève -->
              <div class="flex items-center gap-1.5 shrink-0 flex-wrap sm:flex-nowrap">
                <button
                  type="button"
                  @click.stop="triggerStudentAction('view', student)"
                  class="min-h-[44px] px-3 py-1.5 rounded-xl text-xs font-bold bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 transition-colors flex items-center gap-1.5 cursor-pointer shadow-2xs focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  :aria-label="`Voir la fiche de ${student.name}`"
                >
                  <Eye class="w-3.5 h-3.5 text-gray-500" />
                  <span>Voir la fiche</span>
                </button>

                <button
                  v-if="student.can_pay"
                  type="button"
                  @click.stop="triggerStudentAction('pay', student)"
                  class="min-h-[44px] px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition-colors flex items-center gap-1.5 cursor-pointer shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                  :aria-label="`Encaisser un paiement pour ${student.name}`"
                >
                  <Banknote class="w-3.5 h-3.5" />
                  <span>Encaisser</span>
                </button>

                <button
                  v-if="student.can_statement"
                  type="button"
                  @click.stop="triggerStudentAction('statement', student)"
                  class="min-h-[44px] px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition-colors flex items-center gap-1.5 cursor-pointer shadow-2xs focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  :aria-label="`Consulter l'extrait de compte de ${student.name}`"
                >
                  <FileText class="w-3.5 h-3.5 text-indigo-600" />
                  <span>Extrait</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Section Enseignants -->
        <div v-if="teachers.length > 0" class="pt-2">
          <div class="flex items-center justify-between mb-2 px-1">
            <div class="flex items-center gap-2">
              <Users class="w-4 h-4 text-violet-600" />
              <span class="text-xs font-black uppercase tracking-wider text-gray-500">Enseignants</span>
            </div>
            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-violet-50 text-violet-700">
              {{ teachers.length }} résultat{{ teachers.length > 1 ? 's' : '' }}
            </span>
          </div>

          <div class="space-y-2">
            <div
              v-for="(teacher, tIdx) in teachers"
              :key="'teacher-' + teacher.id"
              :class="[
                'p-3 rounded-xl border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3',
                selectedItemIndex === (students.length + tIdx)
                  ? 'border-violet-500 bg-violet-50/50 ring-2 ring-violet-500/30'
                  : 'border-gray-200/80 bg-white hover:border-gray-300 hover:bg-gray-50/50'
              ]"
              @mouseenter="selectedItemIndex = students.length + tIdx"
            >
              <!-- Info prof -->
              <div class="flex items-start gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center font-bold text-sm shrink-0">
                  {{ teacher.name.charAt(0).toUpperCase() }}
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-bold text-gray-900 truncate">{{ teacher.name }}</span>
                    <span
                      v-if="teacher.subject"
                      class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-violet-50 text-violet-700 border border-violet-200"
                    >
                      {{ teacher.subject }}
                    </span>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-gray-500 mt-0.5 flex-wrap">
                    <span v-if="teacher.phone">Tél: {{ teacher.phone }}</span>
                    <span v-if="teacher.email">• Email: {{ teacher.email }}</span>
                  </div>
                </div>
              </div>

              <!-- Actions enseignant -->
              <div class="flex items-center gap-1.5 shrink-0">
                <button
                  type="button"
                  @click.stop="triggerTeacherAction(teacher)"
                  class="min-h-[44px] px-3 py-1.5 rounded-xl text-xs font-bold bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 transition-colors flex items-center gap-1.5 cursor-pointer shadow-2xs focus:outline-none focus:ring-2 focus:ring-violet-500"
                  :aria-label="`Consulter la fiche de l'enseignant ${teacher.name}`"
                >
                  <Eye class="w-3.5 h-3.5 text-gray-500" />
                  <span>Consulter</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pied de modal avec raccourcis clavier -->
      <div class="p-3 bg-gray-50/80 border-t border-gray-200 text-xs text-gray-500 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-4">
          <span class="inline-flex items-center gap-1">
            <kbd class="px-1.5 py-0.5 text-[10px] bg-white border border-gray-300 rounded shadow-2xs font-mono">↑</kbd>
            <kbd class="px-1.5 py-0.5 text-[10px] bg-white border border-gray-300 rounded shadow-2xs font-mono">↓</kbd>
            <span>Naviguer</span>
          </span>
          <span class="inline-flex items-center gap-1">
            <kbd class="px-1.5 py-0.5 text-[10px] bg-white border border-gray-300 rounded shadow-2xs font-mono">Entrée</kbd>
            <span>Sélectionner</span>
          </span>
          <span class="inline-flex items-center gap-1">
            <kbd class="px-1.5 py-0.5 text-[10px] bg-white border border-gray-300 rounded shadow-2xs font-mono">Échap</kbd>
            <span>Fermer</span>
          </span>
        </div>
        <span class="text-[11px] text-gray-400">Raccourci universel : touche « / »</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import {
  Search, X, Loader2, GraduationCap, Users, Eye, Banknote, FileText
} from 'lucide-vue-next';
import api from '../utils/api';

const props = defineProps<{
  isOpen: boolean;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'view-student', student: any): void;
  (e: 'collect-student', student: any): void;
  (e: 'statement-student', student: any): void;
  (e: 'view-teacher', teacher: any): void;
}>();

const searchQuery = ref('');
const searchInputRef = ref<HTMLInputElement | null>(null);
const loading = ref(false);
const hasSearched = ref(false);

const students = ref<any[]>([]);
const teachers = ref<any[]>([]);
const selectedItemIndex = ref(0);

const totalResults = computed(() => students.value.length + teachers.value.length);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

function onInput() {
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
  debounceTimer = setTimeout(() => {
    performSearch();
  }, 250);
}

async function performSearch() {
  const q = searchQuery.value.trim();
  if (q.length < 2) {
    students.value = [];
    teachers.value = [];
    hasSearched.value = false;
    selectedItemIndex.value = 0;
    return;
  }

  loading.value = true;
  hasSearched.value = true;

  try {
    const res = await api.get('/api/search', {
      params: { q },
    });
    students.value = res.data?.students || [];
    teachers.value = res.data?.teachers || [];
    selectedItemIndex.value = 0;
  } catch (err: any) {
    console.error('Erreur recherche globale:', err);
    students.value = [];
    teachers.value = [];
  } finally {
    loading.value = false;
  }
}

function handleKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    e.preventDefault();
    close();
    return;
  }

  const count = totalResults.value;
  if (count === 0) return;

  if (e.key === 'ArrowDown') {
    e.preventDefault();
    selectedItemIndex.value = (selectedItemIndex.value + 1) % count;
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    selectedItemIndex.value = (selectedItemIndex.value - 1 + count) % count;
  } else if (e.key === 'Enter') {
    e.preventDefault();
    activateCurrentSelection();
  }
}

function activateCurrentSelection() {
  const sLen = students.value.length;
  if (selectedItemIndex.value < sLen) {
    const student = students.value[selectedItemIndex.value];
    if (student) {
      triggerStudentAction('view', student);
    }
  } else {
    const teacherIdx = selectedItemIndex.value - sLen;
    const teacher = teachers.value[teacherIdx];
    if (teacher) {
      triggerTeacherAction(teacher);
    }
  }
}

function triggerStudentAction(action: 'view' | 'pay' | 'statement', student: any) {
  if (action === 'view') {
    emit('view-student', student);
  } else if (action === 'pay') {
    emit('collect-student', student);
  } else if (action === 'statement') {
    emit('statement-student', student);
  }
  close();
}

function triggerTeacherAction(teacher: any) {
  emit('view-teacher', teacher);
  close();
}

function close() {
  emit('close');
}

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      searchQuery.value = '';
      students.value = [];
      teachers.value = [];
      hasSearched.value = false;
      selectedItemIndex.value = 0;
      nextTick(() => {
        searchInputRef.value?.focus();
      });
    }
  }
);
</script>
