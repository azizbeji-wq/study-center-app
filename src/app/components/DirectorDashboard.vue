<template>
  <div class="size-full flex flex-col">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center">
            <BookOpen class="w-6 h-6 text-white" />
          </div>
          <div>
            <h1 class="text-xl text-gray-900">Centre d'Études</h1>
            <p class="text-sm text-gray-500">Espace Directeur</p>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <div class="text-right">
            <p class="text-sm text-gray-900">{{ userName }}</p>
            <p class="text-xs text-gray-500">Directeur</p>
          </div>
          <button
            @click="$emit('logout')"
            class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
            title="Déconnexion"
          >
            <LogOut class="w-5 h-5 text-gray-600" />
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
      <DashboardOverview v-if="activeTab === 'dashboard'" role="director" />
      <StudentsManagement v-else-if="activeTab === 'students'" />
      <DaycareManagement v-else-if="activeTab === 'daycare'" />
      <TeachersManagement v-else-if="activeTab === 'teachers'" />
      <ScheduleManagement v-else-if="activeTab === 'schedule'" />
      <SchoolCalendar v-else-if="activeTab === 'schoolcalendar'" />
      <PaymentsManagement v-else-if="activeTab === 'payments'" />
      <FinancialReports v-else-if="activeTab === 'reports'" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { LogOut, Users, DollarSign, Calendar, BookOpen, BarChart3, UserPlus, LayoutDashboard, Baby } from 'lucide-vue-next';
import DashboardOverview from './DashboardOverview.vue';
import StudentsManagement from './StudentsManagement.vue';
import DaycareManagement from './DaycareManagement.vue';
import TeachersManagement from './TeachersManagement.vue';
import ScheduleManagement from './ScheduleManagement.vue';
import PaymentsManagement from './PaymentsManagement.vue';
import FinancialReports from './FinancialReports.vue';
import SchoolCalendar from './SchoolCalendar.vue';

defineProps<{
  userName: string;
}>();

defineEmits<{
  (e: 'logout'): void;
}>();

type Tab = 'dashboard' | 'students' | 'daycare' | 'teachers' | 'schedule' | 'payments' | 'reports'| 'schoolcalendar';

const activeTab = ref<Tab>('dashboard');

const tabs = [
  { id: 'dashboard' as Tab, label: 'Tableau de bord', icon: LayoutDashboard },
  { id: 'students' as Tab, label: 'Élèves', icon: Users },
  { id: 'daycare' as Tab, label: 'Élèves de garde', icon: Baby },
  { id: 'teachers' as Tab, label: 'Enseignants', icon: UserPlus },
  { id: 'schedule' as Tab, label: 'Emploi du temps', icon: Calendar },
  { id: 'payments' as Tab, label: 'Paiements', icon: DollarSign },
  { id: 'schoolcalendar' as Tab, label: 'Calendrier scolaire', icon: Calendar },
  { id: 'reports' as Tab, label: 'Comptabilité', icon: BarChart3 },
];
</script>
