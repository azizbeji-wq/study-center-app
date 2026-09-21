<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Tableau de Bord</h2>
        <p class="text-sm text-gray-500 mt-1">Vue d'ensemble en temps réel de votre centre d'études</p>
      </div>
      <button
        @click="loadStats"
        class="text-xs bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-50 shadow-xs flex items-center gap-1.5 font-medium"
      >
        <span :class="{ 'animate-spin': loading }">🔄</span> Actualiser
      </button>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
          <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
            <Users class="w-5 h-5 text-blue-600" />
          </div>
          <span class="text-xs text-green-600 flex items-center gap-1 font-medium bg-green-50 px-2 py-0.5 rounded-full">
            <TrendingUp class="w-3 h-3" />
            {{ statsData.activeStudents }} actifs
          </span>
        </div>
        <p class="text-2xl font-bold text-gray-900 mb-1">{{ statsData.totalStudents }}</p>
        <p class="text-sm font-medium text-gray-500">Total Élèves</p>
        <p class="text-xs text-gray-400 mt-1">{{ statsData.activeStudents }} réguliers</p>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
          <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
            <Baby class="w-5 h-5 text-orange-600" />
          </div>
          <span class="text-xs text-orange-700 font-medium bg-orange-50 px-2 py-0.5 rounded-full">
            Garderie
          </span>
        </div>
        <p class="text-2xl font-bold text-gray-900 mb-1">{{ daycareCount }}</p>
        <p class="text-sm font-medium text-gray-500">Élèves de garde</p>
        <p class="text-xs text-gray-400 mt-1">6-11 ans inscrits</p>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
          <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
            <UserPlus class="w-5 h-5 text-indigo-600" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 mb-1">{{ statsData.totalTeachers }}</p>
        <p class="text-sm font-medium text-gray-500">Enseignants</p>
        <p class="text-xs text-gray-400 mt-1">Corps professoral</p>
      </div>

      <div v-if="showRevenue" class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
          <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
            <DollarSign class="w-5 h-5 text-green-600" />
          </div>
          <span class="text-xs text-green-700 font-semibold bg-green-50 px-2 py-0.5 rounded-full">
            Encaissé
          </span>
        </div>
        <p class="text-2xl font-bold text-gray-900 mb-1">{{ Number(statsData.totalRevenue).toFixed(2) }} TND</p>
        <p class="text-sm font-medium text-gray-500">Revenus perçus</p>
        <p class="text-xs text-amber-600 font-medium mt-1">{{ statsData.pendingPayments }} en attente</p>
      </div>

      <div v-else class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
          <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
            <Calendar class="w-5 h-5 text-purple-600" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 mb-1">{{ statsData.weeklyLessons }}</p>
        <p class="text-sm font-medium text-gray-500">Leçons/Semaine</p>
        <p class="text-xs text-gray-400 mt-1">Planning d'activités</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Teachers Performance -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
          <BookOpen class="w-5 h-5 text-indigo-600" />
          <h3 class="text-lg font-bold text-gray-900">Enseignants & Matières</h3>
        </div>
        <div class="space-y-3">
          <div
            v-for="teacher in topTeachers"
            :key="teacher.name"
            class="flex items-center justify-between p-3.5 bg-gray-50/75 rounded-xl border border-gray-100"
          >
            <div class="flex-1">
              <p class="text-sm font-bold text-gray-900">{{ teacher.name }}</p>
              <p class="text-xs text-indigo-600 font-medium">{{ teacher.subject }}</p>
            </div>
            <div class="flex gap-4 text-right">
              <div>
                <p class="text-sm font-bold text-gray-900">{{ teacher.students }}</p>
                <p class="text-[10px] uppercase font-semibold text-gray-400">élèves</p>
              </div>
              <div>
                <p class="text-sm font-bold text-indigo-600">{{ teacher.lessons }}</p>
                <p class="text-[10px] uppercase font-semibold text-gray-400">leçons/sem</p>
              </div>
            </div>
          </div>
          <div v-if="topTeachers.length === 0" class="text-center py-6 text-sm text-gray-400">
            Aucun enseignant enregistré
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Activité Récente</h3>
        <div class="space-y-3">
          <div
            v-for="activity in recentActivity"
            :key="activity.id"
            class="flex items-start gap-3 p-3 hover:bg-gray-50/80 rounded-xl transition-colors border border-gray-100/70"
          >
            <div
              :class="[
                'w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0',
                activity.type === 'payment' ? 'bg-green-100 text-green-600' :
                activity.type === 'student' ? 'bg-blue-100 text-blue-600' :
                'bg-purple-100 text-purple-600'
              ]"
            >
              <DollarSign v-if="activity.type === 'payment'" class="w-4 h-4" />
              <Users v-if="activity.type === 'student'" class="w-4 h-4" />
              <Calendar v-if="activity.type === 'schedule'" class="w-4 h-4" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">{{ activity.message }}</p>
              <p class="text-xs text-gray-400 mt-0.5">{{ activity.time }}</p>
            </div>
          </div>
          <div v-if="recentActivity.length === 0" class="text-center py-6 text-sm text-gray-400">
            Aucune activité récente
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Users, UserPlus, DollarSign, Calendar, TrendingUp, BookOpen, Baby } from 'lucide-vue-next';
import api from '../utils/api';

const props = withDefaults(defineProps<{
  role?: 'director' | 'secretary'
}>(), {
  role: 'director'
});

const showRevenue = computed(() => props.role === 'director');

const loading = ref(false);
const daycareCount = ref(0);

const statsData = ref({
  totalStudents: 0,
  activeStudents: 0,
  totalTeachers: 0,
  totalRevenue: 0,
  pendingPayments: 0,
  weeklyLessons: 0,
});

const topTeachers = ref<Array<{ name: string; subject: string; students: number; lessons: number }>>([]);
const recentActivity = ref<Array<{ id: string | number; type: string; message: string; time: string }>>([]);

async function loadStats() {
  loading.value = true;
  try {
    const [statsRes, daycareRes] = await Promise.all([
      api.get('/api/stats'),
      api.get('/api/daycare')
    ]);

    if (statsRes.data.stats) {
      statsData.value = statsRes.data.stats;
    }
    if (statsRes.data.topTeachers) {
      topTeachers.value = statsRes.data.topTeachers;
    }
    if (statsRes.data.recentActivity) {
      recentActivity.value = statsRes.data.recentActivity;
    }
    if (Array.isArray(daycareRes.data)) {
      daycareCount.value = daycareRes.data.length;
    }
  } catch (e) {
    console.error('Erreur chargement statistiques:', e);
  } finally {
    loading.value = false;
  }
}

onMounted(loadStats);
</script>
