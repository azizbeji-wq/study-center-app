<template>
  <div class="size-full flex flex-col">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-white rounded-lg border border-gray-200 p-1 flex items-center justify-center shadow-xs">
            <img src="/logo.png" alt="Logo Centre d'Études" class="w-full h-full object-contain" />
          </div>
          <div>
            <h1 class="text-xl font-bold text-gray-900">Centre d'Études</h1>
            <p class="text-xs text-gray-500">Espace Secrétaire</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="showAccountModal = true"
            class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl border border-gray-200 hover:border-emerald-300 hover:bg-emerald-50/50 transition-all text-left group shadow-2xs"
            title="Mon profil & Sécurité"
          >
            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs group-hover:bg-emerald-600 group-hover:text-white transition-colors">
              {{ currentDisplayName ? currentDisplayName.charAt(0).toUpperCase() : 'S' }}
            </div>
            <div>
              <p class="text-xs font-bold text-gray-900 group-hover:text-emerald-900 flex items-center gap-1">
                <span>{{ currentDisplayName }}</span>
                <UserCog class="w-3 h-3 text-gray-400 group-hover:text-emerald-600" />
              </p>
              <p class="text-[10px] text-gray-500">Secrétaire • Mon Compte</p>
            </div>
          </button>

          <button
            @click="$emit('logout')"
            class="p-2 hover:bg-red-50 text-gray-400 hover:text-red-600 rounded-lg transition-colors border border-transparent hover:border-red-200"
            title="Déconnexion"
          >
            <LogOut class="w-5 h-5" />
          </button>
        </div>
      </div>
    </header>

    <!-- Tabs Navigation -->
    <div class="bg-white border-b border-gray-200 px-6">
      <nav class="flex gap-1 overflow-x-auto">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'flex items-center gap-2 px-4 py-3 border-b-2 transition-colors whitespace-nowrap',
            activeTab === tab.id
              ? 'border-indigo-600 text-indigo-600'
              : 'border-transparent text-gray-600 hover:text-gray-900'
          ]"
        >
          <component :is="tab.icon" class="w-4 h-4" />
          <span class="text-sm">{{ tab.label }}</span>
        </button>
      </nav>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-auto p-6">
      <DashboardOverview v-if="activeTab === 'dashboard'" role="secretary" />
      <StudentsManagement v-else-if="activeTab === 'students'" />
      <DaycareManagement v-else-if="activeTab === 'daycare'" />
      <SchoolCalendar v-else-if="activeTab === 'schoolcalendar'" />
      <AttendanceManagement v-else-if="activeTab === 'attendance'" />
      <TeachersManagement v-else-if="activeTab === 'teachers'" />
      <ScheduleManagement v-else-if="activeTab === 'schedule'" />
      <PaymentsManagement v-else-if="activeTab === 'payments'" />
    </div>

    <!-- Modale de Mon Compte (Secrétaire) -->
    <AccountManagementModal
      :is-open="showAccountModal"
      current-role="secretary"
      :current-user-name="currentDisplayName"
      @close="showAccountModal = false"
      @user-updated="handleUserUpdated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { LogOut, Users, DollarSign, Calendar, BookOpen, UserPlus, LayoutDashboard, Baby, UserCheck, UserCog } from 'lucide-vue-next';
import DashboardOverview from './DashboardOverview.vue';
import StudentsManagement from './StudentsManagement.vue';
import DaycareManagement from './DaycareManagement.vue';
import TeachersManagement from './TeachersManagement.vue';
import ScheduleManagement from './ScheduleManagement.vue';
import AttendanceManagement from './AttendanceManagement.vue';
import PaymentsManagement from './PaymentsManagement.vue';
import SchoolCalendar from './SchoolCalendar.vue';
import AccountManagementModal from './AccountManagementModal.vue';

const props = defineProps<{
  userName: string;
}>();

defineEmits<{
  (e: 'logout'): void;
}>();

const currentDisplayName = ref(props.userName);
const showAccountModal = ref(false);

function handleUserUpdated(newName: string) {
  currentDisplayName.value = newName;
}

type Tab = 'dashboard' | 'students' | 'daycare' | 'teachers' | 'schedule' | 'attendance' | 'payments' | 'schoolcalendar';

const tabs = [
  { id: 'dashboard' as Tab, label: 'Tableau de bord', icon: LayoutDashboard },
  { id: 'students' as Tab, label: 'Élèves', icon: Users },
  { id: 'attendance' as Tab, label: 'Présences & Groupes', icon: UserCheck },
  { id: 'daycare' as Tab, label: 'Élèves de garde', icon: Baby },
  { id: 'teachers' as Tab, label: 'Enseignants', icon: UserPlus },
  { id: 'schedule' as Tab, label: 'Emploi du temps', icon: Calendar },
  { id: 'payments' as Tab, label: 'Paiements', icon: DollarSign },
  { id: 'schoolcalendar' as Tab, label: 'Calendrier scolaire', icon: Calendar }
];

const activeTab = ref<Tab>('dashboard');

const syncFromHash = () => {
  const hash = window.location.hash.replace('#', '') as Tab;
  if (hash && tabs.some(t => t.id === hash)) {
    activeTab.value = hash;
  }
};

onMounted(() => {
  syncFromHash();
  window.addEventListener('hashchange', syncFromHash);
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
