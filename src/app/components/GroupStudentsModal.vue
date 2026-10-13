<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-3 sm:p-4 overflow-y-auto"
    @click.self="handleClose"
  >
    <div
      class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-6 flex flex-col border border-gray-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200 max-h-[90vh]"
    >
      <!-- Modal Header -->
      <div class="px-6 py-4.5 bg-gradient-to-r from-violet-700 via-purple-700 to-violet-800 text-white flex items-center justify-between shrink-0 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-md flex items-center justify-center font-bold text-lg border border-white/20 shadow-inner">
            <Users class="w-5 h-5 text-white" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-base font-black tracking-tight text-white">{{ group?.group_name || group?.name }}</h2>
              <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-white/20 text-white border border-white/30">
                {{ groupStudents.length }} élève(s)
              </span>
            </div>
            <p class="text-xs text-violet-100/90 mt-0.5">
              {{ group?.level || 'Niveau scolaire' }} • {{ group?.subjects?.[0] || group?.subject || 'Matière' }}
            </p>
          </div>
        </div>

        <button
          @click="handleClose"
          class="p-1.5 rounded-xl text-white/80 hover:text-white hover:bg-white/15 transition-colors cursor-pointer"
          title="Fermer"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Messages Toast / Feedback -->
      <div v-if="toastMessage" class="px-6 pt-4 shrink-0">
        <div
          :class="[
            'p-3 rounded-xl flex items-start gap-2.5 text-xs font-semibold animate-in fade-in border',
            toastType === 'success'
              ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
              : toastType === 'warning'
              ? 'bg-amber-50 text-amber-800 border-amber-200'
              : 'bg-red-50 text-red-800 border-red-200'
          ]"
        >
          <CheckCircle2 v-if="toastType === 'success'" class="w-4 h-4 shrink-0 mt-0.5 text-emerald-600" />
          <AlertCircle v-else class="w-4 h-4 shrink-0 mt-0.5 text-amber-600" />
          <span class="flex-1">{{ toastMessage }}</span>
          <button @click="toastMessage = ''" class="text-gray-400 hover:text-gray-700">
            <X class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- Navigation d'onglets internes -->
      <div class="px-6 pt-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-gray-50/50">
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="activeSubTab = 'list'"
            :class="[
              'px-3.5 py-2 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5 cursor-pointer',
              activeSubTab === 'list'
                ? 'border-violet-600 text-violet-700 bg-white rounded-t-lg shadow-2xs'
                : 'border-transparent text-gray-500 hover:text-gray-900'
            ]"
          >
            <Users class="w-3.5 h-3.5" />
            <span>Élèves Inscrits ({{ groupStudents.length }})</span>
          </button>

          <button
            type="button"
            @click="activeSubTab = 'add'"
            :class="[
              'px-3.5 py-2 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5 cursor-pointer',
              activeSubTab === 'add'
                ? 'border-violet-600 text-violet-700 bg-white rounded-t-lg shadow-2xs'
                : 'border-transparent text-gray-500 hover:text-gray-900'
            ]"
          >
            <UserPlus class="w-3.5 h-3.5 text-violet-600" />
            <span>+ Ajouter un Élève Existant</span>
          </button>
        </div>

        <button
          @click="fetchGroupStudents"
          :disabled="loadingStudents"
          title="Actualiser la liste"
          class="p-1.5 text-gray-400 hover:text-violet-600 rounded-lg hover:bg-gray-100 transition-colors"
        >
          <RotateCw :class="['w-3.5 h-3.5', loadingStudents ? 'animate-spin text-violet-600' : '']" />
        </button>
      </div>

      <!-- Modal Body (Scrollable) -->
      <div class="p-6 overflow-y-auto space-y-4 flex-1">
        <!-- VUE 1 : LISTE DES ÉLÈVES INSCRITS DANS LE GROUPE -->
        <div v-if="activeSubTab === 'list'" class="space-y-3">
          <!-- Filtre de recherche rapide dans le groupe -->
          <div v-if="groupStudents.length > 5" class="relative">
            <Search class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="groupSearchQuery"
              type="text"
              placeholder="Filtrer parmi les élèves du groupe..."
              class="w-full pl-9 pr-3 py-2 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:outline-none"
            />
          </div>

          <!-- Chargement -->
          <div v-if="loadingStudents" class="flex flex-col items-center justify-center py-12 text-gray-400">
            <Loader2 class="w-7 h-7 text-violet-600 animate-spin mb-2" />
            <span class="text-xs">Chargement des élèves du groupe...</span>
          </div>

          <!-- État vide : aucun élève -->
          <div
            v-else-if="filteredGroupStudents.length === 0"
            class="text-center py-10 px-4 bg-gray-50/80 rounded-2xl border border-dashed border-gray-200 space-y-3"
          >
            <div class="w-12 h-12 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center mx-auto">
              <Users class="w-6 h-6" />
            </div>
            <div>
              <p class="text-xs font-bold text-gray-800">
                {{ groupStudents.length === 0 ? 'Aucun élève dans ce groupe pour le moment' : 'Aucun élève correspondant à la recherche' }}
              </p>
              <p class="text-[11px] text-gray-500 mt-0.5">
                Vous pouvez ajouter des élèves déjà enregistrés au centre d'études en un clic.
              </p>
            </div>
            <button
              type="button"
              @click="activeSubTab = 'add'"
              class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer"
            >
              <UserPlus class="w-3.5 h-3.5" />
              <span>Ajouter un élève maintenant</span>
            </button>
          </div>

          <!-- Liste des élèves inscrits -->
          <div v-else class="divide-y divide-gray-100 border border-gray-200 rounded-2xl overflow-hidden bg-white">
            <div
              v-for="(student, idx) in filteredGroupStudents"
              :key="student.id"
              class="p-3.5 flex items-center justify-between gap-3 hover:bg-gray-50/70 transition-colors"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-violet-100 text-violet-700 font-black text-xs flex items-center justify-center shrink-0">
                  {{ (student.name || 'E').charAt(0).toUpperCase() }}
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-400 font-bold">#{{ idx + 1 }}</span>
                    <h4 class="text-xs font-bold text-gray-900 truncate">{{ student.name }}</h4>
                    <span v-if="student.level" class="text-[10px] font-semibold bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md shrink-0">
                      {{ student.level }}
                    </span>
                  </div>
                  <div class="flex items-center gap-3 text-[11px] text-gray-500 mt-0.5">
                    <span v-if="student.phone" class="flex items-center gap-1">
                      <Phone class="w-3 h-3 text-gray-400" />
                      {{ student.phone }}
                    </span>
                    <span v-if="student.parent_phone" class="text-gray-400">
                      Parent : {{ student.parent_phone }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Action : Retirer du groupe -->
              <button
                type="button"
                @click="removeStudentFromGroup(student)"
                :disabled="actionStudentId === student.id"
                class="px-2.5 py-1.5 text-xs text-red-600 hover:text-red-700 hover:bg-red-50 rounded-xl transition-colors font-semibold flex items-center gap-1 cursor-pointer shrink-0 disabled:opacity-50"
                title="Retirer l'élève de ce groupe"
              >
                <Loader2 v-if="actionStudentId === student.id" class="w-3.5 h-3.5 animate-spin" />
                <Trash2 v-else class="w-3.5 h-3.5" />
                <span class="hidden sm:inline">Retirer</span>
              </button>
            </div>
          </div>
        </div>

        <!-- VUE 2 : AJOUTER UN ÉLÈVE EXISTANT AU GROUPE -->
        <div v-else class="space-y-4">
          <div class="bg-violet-50/70 border border-violet-100 rounded-2xl p-4 flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-violet-600 text-white flex items-center justify-center shrink-0">
              <UserPlus class="w-4 h-4" />
            </div>
            <div class="text-xs text-violet-950">
              <p class="font-bold">Ajout d'élèves existants au groupe « {{ group?.group_name || group?.name }} »</p>
              <p class="text-violet-700/90 text-[11px] mt-0.5">
                Recherchez parmi tous les élèves enregistrés dans le centre d'études pour les assigner instantanément à votre classe.
              </p>
            </div>
          </div>

          <!-- Barre de recherche -->
          <div class="flex flex-col sm:flex-row gap-2">
            <div class="relative flex-1">
              <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                v-model="existingSearchQuery"
                type="text"
                placeholder="Rechercher par nom d'élève, téléphone, niveau..."
                class="w-full pl-10 pr-3.5 py-2.5 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:outline-none"
              />
              <button
                v-if="existingSearchQuery"
                @click="existingSearchQuery = ''"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <X class="w-3.5 h-3.5" />
              </button>
            </div>

            <!-- Filtre : Même niveau uniquement -->
            <button
              type="button"
              @click="filterSameLevelOnly = !filterSameLevelOnly"
              :class="[
                'px-3 py-2 text-xs font-bold rounded-xl border transition-all flex items-center gap-1.5 shrink-0 cursor-pointer',
                filterSameLevelOnly
                  ? 'bg-violet-600 text-white border-violet-600 shadow-xs'
                  : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
              ]"
            >
              <Filter class="w-3.5 h-3.5" />
              <span>Niveau similaire</span>
            </button>
          </div>

          <!-- Chargement de la base élèves -->
          <div v-if="loadingAllStudents" class="flex flex-col items-center justify-center py-12 text-gray-400">
            <Loader2 class="w-7 h-7 text-violet-600 animate-spin mb-2" />
            <span class="text-xs">Recherche dans la base des élèves...</span>
          </div>

          <!-- Aucun élève trouvé -->
          <div
            v-else-if="filteredExistingCandidates.length === 0"
            class="text-center py-10 px-4 bg-gray-50/80 rounded-2xl border border-gray-200 space-y-2"
          >
            <p class="text-xs font-bold text-gray-800">Aucun élève disponible trouvé</p>
            <p class="text-[11px] text-gray-500">
              Tous les élèves correspondants sont peut-être déjà inscrits dans ce groupe, ou aucun élève ne correspond à votre recherche.
            </p>
            <button
              v-if="filterSameLevelOnly"
              type="button"
              @click="filterSameLevelOnly = false"
              class="text-xs text-violet-600 font-bold hover:underline"
            >
              Afficher tous les niveaux
            </button>
          </div>

          <!-- Liste des candidats à ajouter -->
          <div v-else class="space-y-2">
            <div class="text-[11px] font-bold text-gray-400 px-1 flex items-center justify-between">
              <span>Candidats disponibles ({{ filteredExistingCandidates.length }})</span>
              <span>Cliquez sur "+ Ajouter"</span>
            </div>

            <div class="divide-y divide-gray-100 border border-gray-200 rounded-2xl overflow-hidden bg-white max-h-72 overflow-y-auto">
              <div
                v-for="candidate in filteredExistingCandidates"
                :key="candidate.id"
                class="p-3 sm:p-3.5 flex items-center justify-between gap-3 hover:bg-violet-50/40 transition-colors"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-9 h-9 rounded-xl bg-gray-100 text-gray-700 font-black text-xs flex items-center justify-center shrink-0">
                    {{ (candidate.name || 'E').charAt(0).toUpperCase() }}
                  </div>
                  <div class="min-w-0">
                    <div class="flex items-center gap-2">
                      <h4 class="text-xs font-bold text-gray-900 truncate">{{ candidate.name }}</h4>
                      <span v-if="candidate.level" class="text-[10px] font-semibold bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md shrink-0">
                        {{ candidate.level }}
                      </span>
                    </div>
                    <div class="flex items-center gap-3 text-[11px] text-gray-500 mt-0.5">
                      <span v-if="candidate.phone" class="flex items-center gap-1">
                        <Phone class="w-3 h-3 text-gray-400" />
                        {{ candidate.phone }}
                      </span>
                      <span v-if="candidate.group_name" class="text-[10px] text-violet-600 font-medium">
                        Groupe act. : {{ candidate.group_name }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Bouton Ajouter -->
                <button
                  type="button"
                  @click="addStudentToGroup(candidate)"
                  :disabled="actionStudentId === candidate.id"
                  class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 cursor-pointer shrink-0 disabled:opacity-50"
                >
                  <Loader2 v-if="actionStudentId === candidate.id" class="w-3.5 h-3.5 animate-spin" />
                  <Plus v-else class="w-3.5 h-3.5" />
                  <span>Ajouter</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between shrink-0 bg-gray-50/50">
        <span class="text-xs text-gray-500">
          Total actuel : <strong class="text-gray-900">{{ groupStudents.length }}</strong> élève(s) rattaché(s)
        </span>
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="emit('openAttendance', group?.group_name || group?.name, group?.subjects?.[0] || group?.subject)"
            class="px-3 py-2 bg-violet-50 hover:bg-violet-100 text-violet-700 border border-violet-200/70 text-xs font-bold rounded-xl transition-all cursor-pointer flex items-center gap-1.5 shadow-2xs"
            title="Ouvrir la feuille de présence et exporter en PDF"
          >
            <Printer class="w-3.5 h-3.5" />
            <span>Fiche d'Appel (PDF)</span>
          </button>
          <button
            type="button"
            @click="handleClose"
            class="px-4 py-2 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl transition-all cursor-pointer shadow-xs"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
  Users, UserPlus, X, CheckCircle2, AlertCircle, Loader2, Phone, Trash2,
  Search, Filter, Plus, RotateCw, Printer
} from 'lucide-vue-next';
import api from '../utils/api';

const props = defineProps<{
  isOpen: boolean;
  group: any;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'studentsUpdated', groupName: string, newCount: number): void;
  (e: 'openAttendance', groupName: string, subjectName?: string): void;
}>();

const activeSubTab = ref<'list' | 'add'>('list');
const groupStudents = ref<any[]>([]);
const allStudentsList = ref<any[]>([]);

const groupSearchQuery = ref('');
const existingSearchQuery = ref('');
const filterSameLevelOnly = ref(false);

const loadingStudents = ref(false);
const loadingAllStudents = ref(false);
const actionStudentId = ref<number | null>(null);

const toastMessage = ref('');
const toastType = ref<'success' | 'warning' | 'error'>('success');

// Filtrer les élèves du groupe
const filteredGroupStudents = computed(() => {
  if (!groupSearchQuery.value.trim()) return groupStudents.value;
  const q = groupSearchQuery.value.toLowerCase().trim();
  return groupStudents.value.filter(s =>
    (s.name && s.name.toLowerCase().includes(q)) ||
    (s.phone && s.phone.includes(q)) ||
    (s.level && s.level.toLowerCase().includes(q))
  );
});

// Candidats parmi tous les élèves existants (qui ne sont pas déjà dans ce groupe)
const filteredExistingCandidates = computed(() => {
  const currentIds = new Set(groupStudents.value.map(s => s.id));
  let candidates = allStudentsList.value.filter(s => !currentIds.has(s.id));

  // Filtre même niveau si activé
  if (filterSameLevelOnly.value && props.group?.level) {
    const grpLevel = props.group.level.toLowerCase().trim();
    candidates = candidates.filter(s => s.level && s.level.toLowerCase().trim() === grpLevel);
  }

  // Filtre recherche textuelle
  if (existingSearchQuery.value.trim()) {
    const q = existingSearchQuery.value.toLowerCase().trim();
    candidates = candidates.filter(s =>
      (s.name && s.name.toLowerCase().includes(q)) ||
      (s.phone && s.phone.includes(q)) ||
      (s.level && s.level.toLowerCase().includes(q))
    );
  }

  return candidates;
});

// Identifiant du groupe à utiliser pour les requêtes (id ou group_name)
const groupIdOrName = computed(() => {
  return props.group?.id || props.group?.group_name || props.group?.name;
});

watch(
  () => props.isOpen,
  (open) => {
    if (open && props.group) {
      toastMessage.value = '';
      activeSubTab.value = 'list';
      groupSearchQuery.value = '';
      existingSearchQuery.value = '';
      fetchGroupStudents();
      fetchAllStudents();
    }
  },
  { immediate: true }
);

function showToast(msg: string, type: 'success' | 'warning' | 'error' = 'success') {
  toastType.value = type;
  toastMessage.value = msg;
  setTimeout(() => {
    if (toastMessage.value === msg) {
      toastMessage.value = '';
    }
  }, 5000);
}

// 1. Charger les élèves actuellement dans ce groupe
async function fetchGroupStudents() {
  if (!props.group) return;
  const identifier = props.group.group_name || props.group.name || props.group.id;
  loadingStudents.value = true;
  try {
    const res = await api.get(`/api/teacher/groups/${encodeURIComponent(identifier)}/students`);
    groupStudents.value = res.data || [];
  } catch (err) {
    console.error('Erreur chargement élèves du groupe:', err);
    showToast('Impossible de charger les élèves du groupe.', 'error');
  } finally {
    loadingStudents.value = false;
  }
}

// 2. Charger tous les élèves existants pour la sélection
async function fetchAllStudents() {
  loadingAllStudents.value = true;
  try {
    const res = await api.get('/api/students');
    allStudentsList.value = res.data || [];
  } catch (err) {
    console.error('Erreur chargement tous les élèves:', err);
  } finally {
    loadingAllStudents.value = false;
  }
}

// 3. Attacher un élève existant au groupe
async function addStudentToGroup(student: any) {
  if (!groupIdOrName.value) return;
  actionStudentId.value = student.id;
  try {
    const res = await api.post(`/api/groups/${groupIdOrName.value}/students`, {
      student_id: student.id,
    });

    showToast(res.data?.message || `Élève ${student.name} rattaché avec succès.`, 'success');

    // Mettre à jour la liste locale
    if (!groupStudents.value.some(s => s.id === student.id)) {
      groupStudents.value.push({
        ...student,
        current_status: 'present',
      });
    }

    emit('studentsUpdated', props.group?.group_name || props.group?.name, groupStudents.value.length);
  } catch (err: any) {
    console.error('Erreur attachement élève:', err);
    const msg = err.response?.data?.message || "Erreur lors de l'ajout de l'élève au groupe.";
    showToast(msg, err.response?.status === 409 ? 'warning' : 'error');
  } finally {
    actionStudentId.value = null;
  }
}

// 4. Retirer un élève du groupe
async function removeStudentFromGroup(student: any) {
  if (!groupIdOrName.value) return;
  if (!confirm(`Êtes-vous sûr de vouloir retirer ${student.name} de ce groupe ?`)) return;

  actionStudentId.value = student.id;
  try {
    const res = await api.delete(`/api/groups/${groupIdOrName.value}/students/${student.id}`);
    showToast(res.data?.message || `Élève ${student.name} retiré du groupe.`, 'success');

    // Mettre à jour la liste locale
    groupStudents.value = groupStudents.value.filter(s => s.id !== student.id);

    emit('studentsUpdated', props.group?.group_name || props.group?.name, groupStudents.value.length);
  } catch (err: any) {
    console.error('Erreur retrait élève:', err);
    const msg = err.response?.data?.message || "Erreur lors du retrait de l'élève.";
    showToast(msg, 'error');
  } finally {
    actionStudentId.value = null;
  }
}

function handleClose() {
  emit('close');
}
</script>
