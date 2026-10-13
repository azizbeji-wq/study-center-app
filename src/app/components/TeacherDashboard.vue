<template>
  <div class="size-full flex bg-gray-50 overflow-hidden">
    <!-- Overlay Mobile -->
    <div
      v-if="isMobileMenuOpen"
      class="fixed inset-0 bg-gray-900/50 z-40 md:hidden transition-opacity"
      @click="isMobileMenuOpen = false"
    ></div>

    <!-- Sidebar / Drawer -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 flex flex-col transition-transform duration-300 md:translate-x-0 md:static shrink-0',
        isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
      ]"
    >
      <!-- Sidebar Header (Logo) -->
      <div class="h-16 flex items-center px-6 border-b border-gray-200 gap-3 shrink-0">
        <div class="w-8 h-8 bg-white rounded-lg border border-gray-200 p-1 flex items-center justify-center shadow-xs">
          <img src="/logo.png" alt="Logo" class="w-full h-full object-contain" />
        </div>
        <div>
          <h1 class="text-base font-bold text-gray-900 leading-tight">Centre d'Études</h1>
          <p class="text-[10px] font-semibold text-violet-600">Espace Enseignant</p>
        </div>
      </div>

      <!-- Navigation Links -->
      <nav class="flex-1 overflow-y-auto py-4 px-3 flex flex-col gap-1">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="selectTab(tab.id)"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all text-left text-sm font-medium w-full min-h-[44px]',
            activeTab === tab.id
              ? 'bg-violet-50 text-violet-700 font-bold'
              : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
          ]"
        >
          <component
            :is="tab.icon"
            :class="['w-5 h-5 shrink-0', activeTab === tab.id ? 'text-violet-600' : 'text-gray-400']"
          />
          <span class="truncate">{{ tab.label }}</span>
          <span
            v-if="tab.badge"
            class="ml-auto text-[10px] px-2 py-0.5 rounded-full font-bold bg-violet-100 text-violet-800"
          >
            {{ tab.badge }}
          </span>
        </button>
      </nav>

      <!-- Résumé Enseignant & Profil (Bas de sidebar) -->
      <div class="p-4 border-t border-gray-200 flex flex-col gap-2 shrink-0">
        <!-- Carte Enseignant -->
        <button
          @click="showAccountModal = true; isMobileMenuOpen = false"
          class="flex items-center gap-2.5 p-2 rounded-xl border border-transparent hover:bg-violet-50/60 transition-all text-left group min-h-[44px]"
        >
          <div class="w-9 h-9 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
            {{ currentDisplayName ? currentDisplayName.charAt(0).toUpperCase() : 'P' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-gray-900 truncate group-hover:text-violet-700">{{ currentDisplayName }}</p>
            <p class="text-[10px] text-gray-500 truncate">{{ teacherProfile?.subject || 'Enseignant' }}</p>
          </div>
          <UserCog class="w-4 h-4 text-gray-400 group-hover:text-violet-600 shrink-0" />
        </button>

        <button
          @click="$emit('logout')"
          class="flex items-center gap-2.5 p-2 rounded-xl text-gray-600 hover:bg-red-50 hover:text-red-600 transition-all text-left w-full min-h-[44px]"
        >
          <LogOut class="w-5 h-5 text-gray-400 group-hover:text-red-500" />
          <span class="text-sm font-medium">Déconnexion</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0">
      <!-- Mobile Header -->
      <header class="md:hidden bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 shrink-0">
        <div class="flex items-center gap-3">
          <button
            @click="isMobileMenuOpen = true"
            class="p-2 -ml-2 text-gray-600 hover:bg-gray-100 rounded-lg min-h-[44px] min-w-[44px] flex items-center justify-center"
          >
            <Menu class="w-6 h-6" />
          </button>
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-white rounded-lg border border-gray-200 p-1 flex items-center justify-center shadow-xs">
              <img src="/logo.png" alt="Logo" class="w-full h-full object-contain" />
            </div>
            <div>
              <h1 class="text-sm font-bold text-gray-900 leading-tight">Centre d'Études</h1>
              <p class="text-[10px] font-semibold text-violet-600">Portail Enseignant</p>
            </div>
          </div>
        </div>

        <button
          @click="showAccountModal = true"
          class="w-8 h-8 rounded-lg bg-violet-100 text-violet-700 flex items-center justify-center font-bold text-xs"
        >
          {{ currentDisplayName ? currentDisplayName.charAt(0).toUpperCase() : 'P' }}
        </button>
      </header>

      <!-- Bannière d'accueil & métriques Enseignant -->
      <div v-if="teacherProfile" class="px-4 md:px-6 pt-4 shrink-0 hidden sm:block">
        <div class="bg-white rounded-2xl p-4 border border-gray-200/90 shadow-2xs flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-violet-600 to-purple-500 text-white flex items-center justify-center font-black text-sm shadow-sm">
              {{ currentDisplayName.charAt(0).toUpperCase() }}
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h2 class="text-sm font-black text-gray-900">{{ currentDisplayName }}</h2>
                <span class="text-[10px] bg-violet-50 text-violet-700 border border-violet-200/80 px-2 py-0.5 rounded-full font-bold">
                  {{ teacherProfile.subject }}
                </span>
              </div>
              <p class="text-xs text-gray-500 mt-0.5">
                {{ teacherProfile.groups_count }} groupe(s) assigné(s) • {{ teacherProfile.student_count }} élève(s) inscrit(s)
              </p>
            </div>
          </div>

          <!-- Métriques rapides -->
          <div class="flex items-center gap-3 text-xs">
            <div class="bg-gray-50 px-3 py-2 rounded-xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] font-bold uppercase">Cours/semaine</span>
              <span class="font-extrabold text-gray-900">{{ teacherProfile.weekly_lessons }} séances</span>
            </div>

            <div class="bg-gray-50 px-3 py-2 rounded-xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] font-bold uppercase">Régularisation</span>
              <span
                :class="[
                  'font-extrabold inline-flex items-center gap-1',
                  teacherProfile.payout_status === 'paid' ? 'text-emerald-600' : 'text-amber-600'
                ]"
              >
                <span :class="['w-1.5 h-1.5 rounded-full', teacherProfile.payout_status === 'paid' ? 'bg-emerald-500' : 'bg-amber-500']"></span>
                {{ teacherProfile.payout_status === 'paid' ? 'Rémunéré' : 'En attente' }}
              </span>
            </div>

            <button
              @click="selectTab('attendance')"
              class="px-3 py-2 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-sm shadow-violet-600/20 cursor-pointer"
            >
              <UserCheck class="w-3.5 h-3.5" />
              <span>Faire l'appel</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Content Area -->
      <div class="flex-1 overflow-auto p-4 md:p-6 relative">
        <!-- Onglet 1 : Emploi du Temps -->
        <TeacherSchedule
          v-if="activeTab === 'schedule'"
          @take-attendance="handleTakeAttendanceFromSchedule"
        />

        <!-- Onglet 2 : Mes Groupes & Élèves -->
        <div v-else-if="activeTab === 'groups'" class="space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-violet-100 text-violet-700 text-xs font-bold mb-1">
                <Users class="w-3.5 h-3.5" />
                <span>Groupes Pédagogiques</span>
              </div>
              <h2 class="text-2xl font-black text-gray-900 tracking-tight">Mes Groupes & Élèves</h2>
              <p class="text-xs text-gray-500 mt-0.5">Effectifs de vos classes, niveaux scolaires et créneaux associés</p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <button
                @click="showCreateGroupModal = true"
                class="px-3.5 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm shadow-violet-600/20 cursor-pointer"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>+ Créer un groupe</span>
              </button>

              <button
                @click="fetchGroups"
                :disabled="loadingGroups"
                class="px-3 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 transition-colors flex items-center gap-1.5 shadow-2xs shrink-0 cursor-pointer"
              >
                <RotateCw :class="['w-3.5 h-3.5', loadingGroups ? 'animate-spin text-violet-600' : '']" />
                <span>Actualiser</span>
              </button>
            </div>
          </div>

          <!-- Chargement -->
          <div v-if="loadingGroups" class="flex justify-center py-16">
            <div class="w-8 h-8 border-4 border-violet-600 border-t-transparent rounded-full animate-spin"></div>
          </div>

          <!-- Aucun groupe -->
          <div
            v-else-if="groupsList.length === 0"
            class="bg-white rounded-2xl border border-gray-200 p-12 text-center max-w-lg mx-auto space-y-4"
          >
            <div class="w-14 h-14 bg-violet-50 text-violet-600 rounded-2xl flex items-center justify-center mx-auto">
              <Users class="w-7 h-7" />
            </div>
            <h3 class="text-base font-bold text-gray-900">Aucun groupe assigné</h3>
            <p class="text-xs text-gray-500 leading-relaxed">
              Aucun groupe d'élèves n'est actuellement lié à votre compte enseignant. Vous pouvez créer directement vos propres groupes pédagogiques.
            </p>
            <div class="pt-2">
              <button
                @click="showCreateGroupModal = true"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm shadow-violet-600/20 cursor-pointer"
              >
                <Plus class="w-4 h-4" />
                <span>+ Créer un groupe</span>
              </button>
            </div>
          </div>

          <!-- Liste des cartes de groupe -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div
              v-for="grp in groupsList"
              :key="grp.group_name"
              class="bg-white rounded-2xl p-5 border border-gray-200/90 shadow-xs hover:border-violet-300 hover:shadow-md transition-all flex flex-col justify-between"
            >
              <div>
                <div class="flex items-start justify-between gap-2 mb-3">
                  <div class="w-10 h-10 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center font-bold">
                    <Users class="w-5 h-5" />
                  </div>
                  <span class="text-xs font-extrabold text-violet-700 bg-violet-50 px-2.5 py-1 rounded-full border border-violet-200/70">
                    {{ grp.student_count }} élève(s)
                  </span>
                </div>

                <h3 class="text-base font-black text-gray-900 mb-1">{{ grp.group_name }}</h3>
                <p class="text-xs text-gray-500 mb-3">
                  Niveau : <span class="font-bold text-gray-700">{{ grp.level }}</span>
                </p>

                <!-- Matières -->
                <div class="flex flex-wrap gap-1.5 mb-4">
                  <span
                    v-for="sub in grp.subjects"
                    :key="sub"
                    class="text-[11px] font-semibold bg-gray-100 text-gray-700 px-2 py-0.5 rounded-md"
                  >
                    {{ sub }}
                  </span>
                </div>

                <!-- Créneaux hebdomadaires -->
                <div v-if="grp.schedule_slots && grp.schedule_slots.length > 0" class="space-y-1.5 pt-3 border-t border-gray-100 text-xs text-gray-600">
                  <div v-for="(slot, sIdx) in grp.schedule_slots" :key="sIdx" class="flex items-center justify-between text-[11px]">
                    <span class="font-bold text-gray-800">{{ slot.day }}</span>
                    <span>{{ slot.time }} (Salle {{ slot.room }})</span>
                  </div>
                </div>
              </div>

              <!-- Actions : Gérer les élèves + Faire l'appel -->
              <div class="mt-5 pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
                <button
                  @click="openGroupStudents(grp)"
                  class="px-2.5 py-1.5 bg-violet-50 hover:bg-violet-100 text-violet-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer border border-violet-200/60"
                  title="Ajouter ou gérer les élèves de ce groupe"
                >
                  <UserPlus class="w-3.5 h-3.5" />
                  <span>Élèves ({{ grp.student_count }})</span>
                </button>

                <button
                  @click="openAttendanceForGroup(grp.group_name, grp.subjects?.[0])"
                  class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1 shadow-sm shadow-violet-600/20 cursor-pointer"
                >
                  <UserCheck class="w-3.5 h-3.5" />
                  <span>Faire l'appel</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Onglet 3 : Faire l'Appel -->
        <TeacherAttendance
          v-else-if="activeTab === 'attendance'"
          :initial-group="attendanceInitialGroup"
          :initial-subject="attendanceInitialSubject"
          :teacher-name="currentDisplayName"
        />
      </div>
    </main>

    <!-- Modale Mon Compte Enseignant -->
    <AccountManagementModal
      :is-open="showAccountModal"
      current-role="teacher"
      :current-user-name="currentDisplayName"
      @close="showAccountModal = false"
      @user-updated="handleUserUpdated"
    />

    <!-- Modale Création de Groupe Enseignant -->
    <CreateGroupModal
      :is-open="showCreateGroupModal"
      :subjects="teacherSubjects"
      @close="showCreateGroupModal = false"
      @group-created="handleGroupCreated"
    />

    <!-- Modale Gestion des Élèves du Groupe -->
    <GroupStudentsModal
      :is-open="showGroupStudentsModal"
      :group="selectedGroupForStudents"
      @close="showGroupStudentsModal = false"
      @students-updated="handleStudentsUpdated"
      @open-attendance="(groupName, subjectName) => { showGroupStudentsModal = false; openAttendanceForGroup(groupName, subjectName); }"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import {
  LogOut, Calendar, Users, UserCheck, UserCog, Menu, RotateCw, Plus, UserPlus
} from 'lucide-vue-next';
import TeacherSchedule from './TeacherSchedule.vue';
import TeacherAttendance from './TeacherAttendance.vue';
import AccountManagementModal from './AccountManagementModal.vue';
import CreateGroupModal from './CreateGroupModal.vue';
import GroupStudentsModal from './GroupStudentsModal.vue';
import api from '../utils/api';

const props = defineProps<{
  userName: string;
}>();

defineEmits<{
  (e: 'logout'): void;
}>();

const currentDisplayName = ref(props.userName);
const showAccountModal = ref(false);
const showCreateGroupModal = ref(false);
const showGroupStudentsModal = ref(false);
const selectedGroupForStudents = ref<any>(null);
const isMobileMenuOpen = ref(false);

const teacherProfile = ref<any>(null);
const groupsList = ref<any[]>([]);
const loadingGroups = ref(false);

function openGroupStudents(grp: any) {
  selectedGroupForStudents.value = grp;
  showGroupStudentsModal.value = true;
}

function handleStudentsUpdated(groupName: string, newCount: number) {
  const match = groupsList.value.find(g => g.group_name === groupName || g.name === groupName);
  if (match) {
    match.student_count = newCount;
  }
  fetchGroups();
}

const teacherSubjects = computed<string[]>(() => {
  const list: string[] = [];
  if (teacherProfile.value?.subjects_list && Array.isArray(teacherProfile.value.subjects_list)) {
    list.push(...teacherProfile.value.subjects_list);
  }
  if (teacherProfile.value?.subject) {
    const parts = teacherProfile.value.subject.split(',').map((s: string) => s.trim()).filter(Boolean);
    list.push(...parts);
  }
  return Array.from(new Set(list));
});

function handleGroupCreated() {
  fetchGroups();
  fetchProfile();
}

const attendanceInitialGroup = ref('');
const attendanceInitialSubject = ref('');

type Tab = 'schedule' | 'groups' | 'attendance';

interface NavTab {
  id: Tab;
  label: string;
  icon: any;
  badge?: string;
}

const tabs: NavTab[] = [
  { id: 'schedule', label: 'Emploi du temps', icon: Calendar },
  { id: 'groups', label: 'Mes Groupes', icon: Users },
  { id: 'attendance', label: "Faire l'Appel", icon: UserCheck },
];

const activeTab = ref<Tab>('schedule');

const selectTab = (tabId: string) => {
  activeTab.value = tabId as Tab;
  isMobileMenuOpen.value = false;
};

function handleUserUpdated(newName: string) {
  currentDisplayName.value = newName;
}

function handleTakeAttendanceFromSchedule(data: { group_name?: string; subject_name?: string }) {
  attendanceInitialGroup.value = data.group_name || '';
  attendanceInitialSubject.value = data.subject_name || '';
  selectTab('attendance');
}

function openAttendanceForGroup(groupName: string, subjectName?: string) {
  attendanceInitialGroup.value = groupName;
  attendanceInitialSubject.value = subjectName || '';
  selectTab('attendance');
}

async function fetchProfile() {
  try {
    const res = await api.get('/api/teacher/profile');
    if (res.data?.teacher) {
      teacherProfile.value = res.data.teacher;
    }
  } catch (err) {
    console.error('Erreur profil enseignant:', err);
  }
}

async function fetchGroups() {
  loadingGroups.value = true;
  try {
    const res = await api.get('/api/teacher/groups');
    groupsList.value = res.data || [];
  } catch (err) {
    console.error('Erreur chargement groupes:', err);
  } finally {
    loadingGroups.value = false;
  }
}

const syncFromHash = () => {
  const hash = window.location.hash.replace('#', '') as Tab;
  if (hash && tabs.some(t => t.id === hash)) {
    activeTab.value = hash;
  }
};

onMounted(() => {
  syncFromHash();
  window.addEventListener('hashchange', syncFromHash);
  fetchProfile();
  fetchGroups();
});

onUnmounted(() => {
  window.removeEventListener('hashchange', syncFromHash);
});

watch(activeTab, (newTab) => {
  if (window.location.hash !== `#${newTab}`) {
    window.location.hash = `#${newTab}`;
  }
});
</script>
