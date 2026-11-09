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
      <div class="px-3 pt-3 pb-1 shrink-0 flex items-center gap-1.5">
        <button
          @click="isSearchOpen = true"
          class="flex-1 min-h-[44px] px-3 py-2 rounded-xl bg-gray-100/90 hover:bg-gray-200/90 border border-gray-200 text-gray-600 text-xs font-semibold flex items-center justify-between transition-colors cursor-pointer group shadow-2xs focus:outline-none focus:ring-2 focus:ring-emerald-500"
          aria-label="Recherche globale rapide (Touche /)"
        >
          <div class="flex items-center gap-2">
            <Search class="w-4 h-4 text-gray-400 group-hover:text-gray-600" />
            <span>Rechercher...</span>
          </div>
          <kbd class="px-1.5 py-0.5 text-[10px] bg-white border border-gray-300 rounded text-gray-500 font-mono shadow-2xs">/</kbd>
        </button>
        <button
          @click="isGuideOpen = true"
          class="min-h-[44px] min-w-[44px] px-2.5 rounded-xl bg-[#1F3A33]/10 hover:bg-[#1F3A33]/20 border border-[#1F3A33]/20 text-[#1F3A33] text-xs font-bold flex items-center justify-center gap-1 transition-colors cursor-pointer shadow-2xs focus:outline-none focus:ring-2 focus:ring-[#1F3A33]"
          aria-label="Guide d'utilisation (Touche ?)"
          title="Guide d'utilisation (Touche ?)"
        >
          <BookOpen class="w-4 h-4" />
          <span class="inline-flex items-center justify-center w-4 h-4 text-[10px] font-black rounded-full bg-[#1F3A33] text-[#F4F1E8]">?</span>
        </button>
      </div>

      <nav class="flex-1 overflow-y-auto py-3 px-3 flex flex-col gap-1">
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

      <!-- Bouton Passerelle : Mode Enseignant (Double Rôle Personnel & Prof) -->
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

        <div class="flex items-center gap-2">
          <button
            @click="isSearchOpen = true"
            class="px-2.5 py-1.5 rounded-lg bg-gray-100 border border-gray-200 text-gray-700 text-xs font-bold flex items-center gap-1.5 hover:bg-gray-200 transition-colors cursor-pointer min-h-[44px]"
            aria-label="Rechercher partout"
          >
            <Search class="w-4 h-4 text-gray-500" />
            <span class="text-xs">Rechercher</span>
          </button>

          <GuideButton color-scheme="emerald" keyboard-hint="Touche ?" @open="isGuideOpen = true" />

          <button
            @click="$emit('switch-portal', 'teacher')"
            class="px-2.5 py-1.5 rounded-lg bg-violet-50 border border-violet-200 text-violet-700 text-xs font-bold flex items-center gap-1 hover:bg-violet-100 transition-colors shadow-2xs cursor-pointer min-h-[44px]"
            title="Basculer vers l'espace enseignant"
          >
            <span>👨‍🏫</span>
            <span class="text-[11px]">Mode Prof</span>
          </button>
        </div>
      </header>

      <!-- Content Area -->
      <div class="flex-1 overflow-auto p-4 md:p-6 relative">
        <NewHereBanner
          :tab="activeTab as TabKey"
          role="secretary"
          :permissions="props.permissions"
          :has-tour-steps="currentTourSteps.length > 0"
          @start-tour="startTour"
        />

        <DashboardOverview
          v-if="activeTab === 'dashboard'"
          role="secretary"
          @quick-action="handleQuickAction"
          @open-unpaid-dues="handleOpenUnpaidDues"
        />
        <StudentsManagement
          v-else-if="activeTab === 'students'"
          :open-add-on-mount="shouldOpenStudentAdd"
          :initial-select-student-id="searchSelectedStudentId"
          @add-modal-closed="shouldOpenStudentAdd = false"
        />
        <DaycareManagement v-else-if="activeTab === 'daycare'" />
        <SchoolCalendar v-else-if="activeTab === 'schoolcalendar'" />
        <AttendanceManagement
          v-else-if="activeTab === 'attendance'"
          :open-batch-on-mount="shouldOpenAttendanceBatch"
          @batch-modal-closed="shouldOpenAttendanceBatch = false"
        />
        <TeachersManagement v-else-if="activeTab === 'teachers'" />
        <ScheduleManagement v-else-if="activeTab === 'schedule'" />
        <PaymentsManagement
          v-else-if="activeTab === 'payments'"
          :initial-main-tab="paymentsInitialMainTab"
          :initial-dues-filter="paymentsInitialDuesFilter"
          :open-add-on-mount="shouldOpenPaymentAdd"
          :initial-collect-student-id="searchCollectStudentId"
          :initial-extract-student-id="searchExtractStudentId"
          @add-modal-closed="shouldOpenPaymentAdd = false"
        />
        <FinancialReports v-else-if="activeTab === 'reports'" />
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

    <!-- Modale de Recherche Globale (Touche /) -->
    <GlobalSearchModal
      :is-open="isSearchOpen"
      @close="isSearchOpen = false"
      @view-student="handleSearchStudentView"
      @collect-student="handleSearchStudentCollect"
      @statement-student="handleSearchStudentStatement"
      @view-teacher="handleSearchTeacherView"
    />

    <!-- Tiroir Guide -->
    <GuideDrawer
      :is-open="isGuideOpen"
      role="secretary"
      :permissions="props.permissions"
      :current-tab="activeTab as TabKey"
      @close="isGuideOpen = false"
      @start-tour="startTour"
      @navigate-tab="(tab) => selectTab(tab as Tab)"
      @open-ai="isGuideOpen = false"
    />

    <!-- Visite guidée -->
    <TourOverlay
      :steps="currentTourSteps"
      :active="isTourActive"
      @finish="onTourFinish"
      @skip="isTourActive = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import {
  LogOut, Users, DollarSign, Calendar, BookOpen, UserPlus, LayoutDashboard,
  Baby, UserCheck, UserCog, Menu, BarChart3, Search
} from 'lucide-vue-next';
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
import GlobalSearchModal from './GlobalSearchModal.vue';
import GuideDrawer from './GuideDrawer.vue';
import GuideButton from './GuideButton.vue';
import NewHereBanner from './NewHereBanner.vue';
import TourOverlay from './TourOverlay.vue';
import { useGuide, loadProgress } from '../guide/useGuide';
import type { TabKey } from '../guide/types';

const props = defineProps<{
  userName: string;
  permissions?: string[];
}>();

defineEmits<{
  (e: 'logout'): void;
  (e: 'switch-portal', portal: 'teacher'): void;
}>();

const currentDisplayName = ref(props.userName);
const showAccountModal = ref(false);
const isMobileMenuOpen = ref(false);
const isSearchOpen = ref(false);
const isGuideOpen = ref(false);
const isTourActive = ref(false);

// ─── Guide ───────────────────────────────────────────────────────
const { getTourSteps, completeTour } = useGuide('secretary', props.permissions);
const currentTourSteps = computed(() => getTourSteps(activeTab.value as TabKey));

function startTour() {
  if (currentTourSteps.value.length > 0) {
    isTourActive.value = true;
  }
}

async function onTourFinish() {
  isTourActive.value = false;
  await completeTour(activeTab.value as TabKey);
}
// ─────────────────────────────────────────────────────────────────

const searchSelectedStudentId = ref<number | undefined>(undefined);
const searchCollectStudentId = ref<number | undefined>(undefined);
const searchExtractStudentId = ref<number | undefined>(undefined);

function handleSearchStudentView(student: any) {
  searchSelectedStudentId.value = student.id;
  selectTab('students');
}

function handleSearchStudentCollect(student: any) {
  searchCollectStudentId.value = student.id;
  selectTab('payments');
}

function handleSearchStudentStatement(student: any) {
  searchExtractStudentId.value = student.id;
  selectTab('payments');
}

function handleSearchTeacherView(teacher: any) {
  selectTab('teachers');
}

function onGlobalKeydown(e: KeyboardEvent) {
  if (e.key === '/' && !isSearchOpen.value) {
    const target = e.target as HTMLElement;
    if (
      target &&
      (target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.tagName === 'SELECT' ||
        target.isContentEditable)
    ) {
      return;
    }
    e.preventDefault();
    isSearchOpen.value = true;
  }
  if (e.key === '?' && !isGuideOpen.value) {
    const target = e.target as HTMLElement;
    if (
      target &&
      (target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.tagName === 'SELECT' ||
        target.isContentEditable)
    ) {
      return;
    }
    e.preventDefault();
    isGuideOpen.value = true;
  }
}

const shouldOpenStudentAdd = ref(false);
const shouldOpenAttendanceBatch = ref(false);
const shouldOpenPaymentAdd = ref(false);
const paymentsInitialMainTab = ref<'dues' | 'history'>('dues');
const paymentsInitialDuesFilter = ref<'all' | 'pending' | 'overdue'>('all');

function handleQuickAction(action: 'add-student' | 'take-attendance' | 'add-payment') {
  if (action === 'add-student') {
    activeTab.value = 'students';
    shouldOpenStudentAdd.value = true;
  } else if (action === 'take-attendance') {
    activeTab.value = 'attendance';
    shouldOpenAttendanceBatch.value = true;
  } else if (action === 'add-payment') {
    activeTab.value = 'payments';
    shouldOpenPaymentAdd.value = true;
  }
}

function handleOpenUnpaidDues() {
  paymentsInitialMainTab.value = 'dues';
  paymentsInitialDuesFilter.value = 'pending';
  activeTab.value = 'payments';
}

function handleUserUpdated(newName: string) {
  currentDisplayName.value = newName;
}

const selectTab = (tabId: Tab) => {
  activeTab.value = tabId;
  isMobileMenuOpen.value = false;
};

type Tab = 'dashboard' | 'students' | 'daycare' | 'teachers' | 'schedule' | 'attendance' | 'payments' | 'schoolcalendar' | 'reports';

const allTabs = [
  { id: 'dashboard' as Tab, label: 'Tableau de bord', icon: LayoutDashboard, permission: 'dashboard' },
  { id: 'students' as Tab, label: 'Élèves', icon: Users, permission: 'students' },
  { id: 'attendance' as Tab, label: 'Présences & Groupes', icon: UserCheck, permission: 'attendance' },
  { id: 'daycare' as Tab, label: 'Élèves de garde', icon: Baby, permission: 'daycare' },
  { id: 'teachers' as Tab, label: 'Enseignants', icon: UserPlus, permission: 'teachers' },
  { id: 'schedule' as Tab, label: 'Emploi du temps', icon: Calendar, permission: 'schedule' },
  { id: 'payments' as Tab, label: 'Paiements', icon: DollarSign, permission: 'payments' },
  { id: 'schoolcalendar' as Tab, label: 'Calendrier scolaire', icon: Calendar, permission: 'schoolcalendar' },
  { id: 'reports' as Tab, label: 'Comptabilité', icon: BarChart3, permission: 'reports' },
];

const tabs = computed(() => {
  const perms = props.permissions;
  if (!perms || !Array.isArray(perms)) {
    // Par défaut : tous sauf reports
    return allTabs.filter(t => t.id !== 'reports');
  }
  return allTabs.filter(t => perms.includes(t.permission));
});

const activeTab = ref<Tab>('dashboard');

const syncFromHash = () => {
  const hash = window.location.hash.replace('#', '') as Tab;
  if (hash && tabs.value.some(t => t.id === hash)) {
    activeTab.value = hash;
  } else if (!tabs.value.some(t => t.id === activeTab.value)) {
    activeTab.value = (tabs.value[0]?.id as Tab) || 'dashboard';
  }
};

watch(() => tabs.value, (newTabs) => {
  if (newTabs.length > 0 && !newTabs.some(t => t.id === activeTab.value)) {
    activeTab.value = newTabs[0].id;
  }
}, { immediate: true });

function onOpenGuideEvent() {
  isGuideOpen.value = true;
}

onMounted(() => {
  syncFromHash();
  window.addEventListener('hashchange', syncFromHash);
  window.addEventListener('keydown', onGlobalKeydown);
  window.addEventListener('open-guide-drawer', onOpenGuideEvent);
  loadProgress();
});

onUnmounted(() => {
  window.removeEventListener('hashchange', syncFromHash);
  window.removeEventListener('keydown', onGlobalKeydown);
  window.removeEventListener('open-guide-drawer', onOpenGuideEvent);
});

watch(activeTab, (newTab) => {
  if (window.location.hash !== `#${newTab}`) {
    window.location.hash = `#${newTab}`;
  }
});
</script>
