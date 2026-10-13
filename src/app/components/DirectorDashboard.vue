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
          <p class="text-[10px] text-gray-500">Espace Directeur</p>
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
              ? 'bg-indigo-50 text-indigo-700'
              : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
          ]"
        >
          <component :is="tab.icon" :class="['w-5 h-5', activeTab === tab.id ? 'text-indigo-600' : 'text-gray-400']" />
          {{ tab.label }}
        </button>
      </nav>

      <!-- Bouton Passerelle : Mode Enseignant (Double Rôle) -->
      <div class="px-3 pb-2 pt-1 border-t border-gray-100 shrink-0">
        <button
          @click="$emit('switch-portal', 'teacher'); isMobileMenuOpen = false"
          class="w-full flex items-center justify-between p-2.5 rounded-xl bg-linear-to-r from-violet-50 to-purple-50 hover:from-violet-100 hover:to-purple-100 border border-violet-200/90 text-violet-800 transition-all shadow-2xs group cursor-pointer"
          title="Gérer vos propres cours, groupes et fiches d'appel"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-7 h-7 rounded-lg bg-violet-600 text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
              👨‍🏫
            </div>
            <div class="text-left min-w-0">
              <p class="text-xs font-bold text-gray-900 group-hover:text-violet-900 leading-tight truncate">Espace Enseignant</p>
              <p class="text-[10px] text-violet-600 font-medium truncate">Mes cours & groupes</p>
            </div>
          </div>
          <span class="text-xs font-bold text-violet-500 group-hover:text-violet-700 group-hover:translate-x-0.5 transition-transform shrink-0">→</span>
        </button>
      </div>

      <!-- User Profile (Bottom of sidebar) -->
      <div class="p-4 border-t border-gray-200 flex flex-col gap-2 shrink-0">
        <button
          @click="showAccountModal = true; isMobileMenuOpen = false"
          class="flex items-center gap-2.5 p-2 rounded-xl border border-transparent hover:bg-gray-50 transition-all text-left group min-h-[44px]"
        >
          <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs shrink-0">
            {{ currentDisplayName ? currentDisplayName.charAt(0).toUpperCase() : 'D' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-gray-900 truncate group-hover:text-indigo-700">{{ currentDisplayName }}</p>
            <p class="text-[10px] text-gray-500 truncate">Mon Compte</p>
          </div>
          <UserCog class="w-4 h-4 text-gray-400 group-hover:text-indigo-600" />
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
          <button @click="isMobileMenuOpen = true" class="p-2 -ml-2 text-gray-600 hover:bg-gray-100 rounded-lg min-h-[44px] min-w-[44px] flex items-center justify-center">
            <Menu class="w-6 h-6" />
          </button>
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-white rounded-lg border border-gray-200 p-1 flex items-center justify-center shadow-xs">
              <img src="/logo.png" alt="Logo" class="w-full h-full object-contain" />
            </div>
            <h1 class="text-sm font-bold text-gray-900 leading-tight">Centre d'Études</h1>
          </div>
        </div>

        <button
          @click="$emit('switch-portal', 'teacher')"
          class="px-2.5 py-1.5 rounded-lg bg-violet-50 border border-violet-200 text-violet-700 text-xs font-bold flex items-center gap-1 hover:bg-violet-100 transition-colors shadow-2xs cursor-pointer"
          title="Basculer vers l'espace enseignant"
        >
          <span>👨‍🏫</span>
          <span class="text-[11px]">Mode Prof</span>
        </button>
      </header>

      <!-- Content Area -->
      <div class="flex-1 overflow-auto p-4 md:p-6 relative">
        <DashboardOverview v-if="activeTab === 'dashboard'" role="director" />
        <StudentsManagement v-else-if="activeTab === 'students'" />
        <DaycareManagement v-else-if="activeTab === 'daycare'" />
        <TeachersManagement v-else-if="activeTab === 'teachers'" />
        <ScheduleManagement v-else-if="activeTab === 'schedule'" />
        <SchoolCalendar v-else-if="activeTab === 'schoolcalendar'" />
        <AttendanceManagement v-else-if="activeTab === 'attendance'" />
        <PaymentsManagement v-else-if="activeTab === 'payments'" />
        <FinancialReports v-else-if="activeTab === 'reports'" />
      </div>
    </main>

    <!-- Modale de Gestion de Compte & Personnel -->
    <AccountManagementModal
      :is-open="showAccountModal"
      current-role="director"
      :current-user-name="currentDisplayName"
      @close="showAccountModal = false"
      @user-updated="handleUserUpdated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { LogOut, Users, DollarSign, Calendar, BookOpen, BarChart3, UserPlus, LayoutDashboard, Baby, UserCheck, UserCog, Menu } from 'lucide-vue-next';
import DashboardOverview from './DashboardOverview.vue';
import StudentsManagement from './StudentsManagement.vue';
import DaycareManagement from './DaycareManagement.vue';
import TeachersManagement from './TeachersManagement.vue';
import ScheduleManagement from './ScheduleManagement.vue';
import AttendanceManagement from './AttendanceManagement.vue';
import PaymentsManagement from './PaymentsManagement.vue';
import FinancialReports from './FinancialReports.vue';
import SchoolCalendar from './SchoolCalendar.vue';
import AccountManagementModal from './AccountManagementModal.vue';

const props = defineProps<{
  userName: string;
}>();

defineEmits<{
  (e: 'logout'): void;
  (e: 'switch-portal', portal: 'teacher'): void;
}>();

const currentDisplayName = ref(props.userName);
const showAccountModal = ref(false);
const isMobileMenuOpen = ref(false);

function handleUserUpdated(newName: string) {
  currentDisplayName.value = newName;
}

const selectTab = (tabId: Tab) => {
  activeTab.value = tabId;
  isMobileMenuOpen.value = false;
};

type Tab = 'dashboard' | 'students' | 'daycare' | 'teachers' | 'schedule' | 'attendance' | 'payments' | 'reports'| 'schoolcalendar';

const tabs = [
  { id: 'dashboard' as Tab, label: 'Tableau de bord', icon: LayoutDashboard },
  { id: 'students' as Tab, label: 'Élèves', icon: Users },
  { id: 'attendance' as Tab, label: 'Présences & Groupes', icon: UserCheck },
  { id: 'daycare' as Tab, label: 'Élèves de garde', icon: Baby },
  { id: 'teachers' as Tab, label: 'Enseignants', icon: UserPlus },
  { id: 'schedule' as Tab, label: 'Emploi du temps', icon: Calendar },
  { id: 'payments' as Tab, label: 'Paiements', icon: DollarSign },
  { id: 'schoolcalendar' as Tab, label: 'Calendrier scolaire', icon: Calendar },
  { id: 'reports' as Tab, label: 'Comptabilité', icon: BarChart3 },
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
