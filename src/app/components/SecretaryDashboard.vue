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
          <p class="text-[10px] text-gray-500">Espace Personnel</p>
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
              ? 'bg-emerald-50 text-emerald-700'
              : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
          ]"
        >
          <component :is="tab.icon" :class="['w-5 h-5', activeTab === tab.id ? 'text-emerald-600' : 'text-gray-400']" />
          {{ tab.label }}
        </button>
      </nav>

      <!-- User Profile (Bottom of sidebar) -->
      <div class="p-4 border-t border-gray-200 flex flex-col gap-2 shrink-0">
        <button
          @click="showAccountModal = true; isMobileMenuOpen = false"
          class="flex items-center gap-2.5 p-2 rounded-xl border border-transparent hover:bg-gray-50 transition-all text-left group min-h-[44px]"
        >
          <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">
            {{ currentDisplayName ? currentDisplayName.charAt(0).toUpperCase() : 'S' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-gray-900 truncate group-hover:text-emerald-700">{{ currentDisplayName }}</p>
            <p class="text-[10px] text-gray-500 truncate">Mon Compte</p>
          </div>
          <UserCog class="w-4 h-4 text-gray-400 group-hover:text-emerald-600" />
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
      </header>

      <!-- Content Area -->
      <div class="flex-1 overflow-auto p-4 md:p-6 relative">
        <DashboardOverview v-if="activeTab === 'dashboard'" role="secretary" />
        <StudentsManagement v-else-if="activeTab === 'students'" />
        <DaycareManagement v-else-if="activeTab === 'daycare'" />
        <SchoolCalendar v-else-if="activeTab === 'schoolcalendar'" />
        <AttendanceManagement v-else-if="activeTab === 'attendance'" />
        <TeachersManagement v-else-if="activeTab === 'teachers'" />
        <ScheduleManagement v-else-if="activeTab === 'schedule'" />
        <PaymentsManagement v-else-if="activeTab === 'payments'" />
      </div>
    </main>

    <!-- Modale de Mon Compte (Personnel) -->
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
import { LogOut, Users, DollarSign, Calendar, BookOpen, UserPlus, LayoutDashboard, Baby, UserCheck, UserCog, Menu } from 'lucide-vue-next';
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
const isMobileMenuOpen = ref(false);

function handleUserUpdated(newName: string) {
  currentDisplayName.value = newName;
}

const selectTab = (tabId: Tab) => {
  activeTab.value = tabId;
  isMobileMenuOpen.value = false;
};

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
