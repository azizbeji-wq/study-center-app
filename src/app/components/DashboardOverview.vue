<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Tableau de Bord</h2>
        <p class="text-xs text-gray-500 mt-1">Vue d'ensemble en temps réel et gestion opérationnelle du centre</p>
      </div>
      <button
        @click="loadStats"
        class="min-h-[44px] px-4 py-2 text-xs bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 shadow-xs flex items-center justify-center gap-2 font-bold transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer"
        aria-label="Actualiser les données du tableau de bord"
      >
        <RotateCw :class="['w-4 h-4 text-gray-600', { 'animate-spin': loading }]" />
        <span>Actualiser les données</span>
      </button>
    </div>

    <!-- BLOC ACTIONS RAPIDES (Directeur et Secrétaire) -->
    <div class="bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 text-white rounded-2xl p-5 shadow-md border border-indigo-700/50">
      <div class="flex items-center justify-between mb-3.5">
        <div class="flex items-center gap-2">
          <Zap class="w-4 h-4 text-amber-400" />
          <h3 class="text-xs font-black uppercase tracking-wider text-indigo-100">Actions Rapides</h3>
        </div>
        <span class="text-[11px] text-indigo-300 font-medium">Accès direct aux tâches fréquentes</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <!-- Action 1 : Ajouter un élève -->
        <button
          @click="$emit('quick-action', 'add-student')"
          class="min-h-[44px] px-4 py-3 bg-white/10 hover:bg-white/20 active:bg-white/25 backdrop-blur-md rounded-xl text-left font-bold text-xs text-white border border-white/15 transition-all flex items-center gap-3 shadow-sm hover:scale-[1.01] focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-900 cursor-pointer"
          title="Ouvrir le formulaire d'inscription d'un nouvel élève"
        >
          <div class="w-8 h-8 rounded-lg bg-indigo-500/30 flex items-center justify-center shrink-0 text-white">
            <UserPlus class="w-4 h-4" />
          </div>
          <div class="min-w-0">
            <p class="leading-tight truncate">Ajouter un élève</p>
            <p class="text-[10px] font-medium text-indigo-200 truncate">Inscription & matières</p>
          </div>
        </button>

        <!-- Action 2 : Faire l'appel -->
        <button
          @click="$emit('quick-action', 'take-attendance')"
          class="min-h-[44px] px-4 py-3 bg-white/10 hover:bg-white/20 active:bg-white/25 backdrop-blur-md rounded-xl text-left font-bold text-xs text-white border border-white/15 transition-all flex items-center gap-3 shadow-sm hover:scale-[1.01] focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-900 cursor-pointer"
          title="Pointer les présences d'une classe ou séance"
        >
          <div class="w-8 h-8 rounded-lg bg-emerald-500/30 flex items-center justify-center shrink-0 text-emerald-300">
            <UserCheck class="w-4 h-4" />
          </div>
          <div class="min-w-0">
            <p class="leading-tight truncate">Faire l'appel</p>
            <p class="text-[10px] font-medium text-emerald-200 truncate">Présences de groupe</p>
          </div>
        </button>

        <!-- Action 3 : Encaisser un paiement -->
        <button
          @click="$emit('quick-action', 'add-payment')"
          class="min-h-[44px] px-4 py-3 bg-white/10 hover:bg-white/20 active:bg-white/25 backdrop-blur-md rounded-xl text-left font-bold text-xs text-white border border-white/15 transition-all flex items-center gap-3 shadow-sm hover:scale-[1.01] focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-900 cursor-pointer"
          title="Enregistrer un nouveau paiement et éditer un reçu"
        >
          <div class="w-8 h-8 rounded-lg bg-amber-500/30 flex items-center justify-center shrink-0 text-amber-300">
            <DollarSign class="w-4 h-4" />
          </div>
          <div class="min-w-0">
            <p class="leading-tight truncate">Encaisser un paiement</p>
            <p class="text-[10px] font-medium text-amber-200 truncate">Reçus & cotisations</p>
          </div>
        </button>
      </div>
    </div>

    <!-- Stats Grid (avec Indicateur Impayés Cliquable) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total Élèves -->
      <div class="bg-white border border-gray-200/90 rounded-2xl p-5 shadow-xs">
        <div class="flex items-center justify-between mb-3">
          <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
            <Users class="w-5 h-5 text-blue-600" />
          </div>
          <span class="text-xs text-green-600 flex items-center gap-1 font-semibold bg-green-50 px-2.5 py-0.5 rounded-full border border-green-200">
            <TrendingUp class="w-3 h-3" />
            {{ statsData.activeStudents }} actifs
          </span>
        </div>
        <p class="text-2xl font-black text-gray-900 mb-1">{{ statsData.totalStudents }}</p>
        <p class="text-xs font-bold text-gray-700">Total Élèves</p>
        <p class="text-[11px] text-gray-400 mt-0.5">{{ statsData.activeStudents }} réguliers</p>
      </div>

      <!-- Garderie -->
      <div class="bg-white border border-gray-200/90 rounded-2xl p-5 shadow-xs">
        <div class="flex items-center justify-between mb-3">
          <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
            <Baby class="w-5 h-5 text-orange-600" />
          </div>
          <span class="text-xs text-orange-700 font-semibold bg-orange-50 px-2.5 py-0.5 rounded-full border border-orange-200">
            Garderie
          </span>
        </div>
        <p class="text-2xl font-black text-gray-900 mb-1">{{ daycareCount }}</p>
        <p class="text-xs font-bold text-gray-700">Élèves de garde</p>
        <p class="text-[11px] text-gray-400 mt-0.5">Enfants inscrits</p>
      </div>

      <!-- INDICATEUR IMPAYÉS À RELANCER (CLIQUABLE) -->
      <div
        role="button"
        tabindex="0"
        @click="$emit('open-unpaid-dues')"
        @keydown.enter.prevent="$emit('open-unpaid-dues')"
        @keydown.space.prevent="$emit('open-unpaid-dues')"
        :class="[
          'bg-white rounded-2xl p-5 shadow-xs transition-all cursor-pointer group border-2',
          unpaidCount > 0
            ? 'border-amber-300 hover:border-amber-500 hover:shadow-md bg-gradient-to-br from-white via-amber-50/20 to-amber-100/30'
            : 'border-emerald-200 hover:border-emerald-400'
        ]"
        class="focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 min-h-[44px]"
        :title="unpaidCount > 0 ? 'Cliquer pour ouvrir la liste des impayés à relancer' : 'Aucun impayé en attente'"
        aria-label="Impayés à relancer. Ouvrir les paiements filtrés."
      >
        <div class="flex items-center justify-between mb-3">
          <div
            :class="[
              'w-10 h-10 rounded-xl flex items-center justify-center',
              unpaidCount > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'
            ]"
          >
            <AlertCircle v-if="unpaidCount > 0" class="w-5 h-5" />
            <ShieldCheck v-else class="w-5 h-5" />
          </div>
          <span
            v-if="unpaidCount > 0"
            class="text-[11px] font-extrabold text-amber-800 bg-amber-100 px-2.5 py-0.5 rounded-full border border-amber-300 group-hover:bg-amber-200 transition-colors flex items-center gap-1"
          >
            <span>À relancer</span>
            <span class="text-xs">→</span>
          </span>
          <span
            v-else
            class="text-[11px] font-extrabold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full"
          >
            À jour
          </span>
        </div>

        <p class="text-2xl font-black text-gray-900 mb-1">
          {{ unpaidAmount.toFixed(2) }} <span class="text-sm font-bold text-gray-500">TND</span>
        </p>
        <p class="text-xs font-bold text-gray-900 flex items-center gap-1.5">
          <span>Impayés à relancer</span>
        </p>
        <p class="text-[11px] font-semibold text-amber-700 mt-0.5">
          {{ unpaidCount }} élève(s) en attente / retard
        </p>
      </div>

      <!-- Recettes ou Leçons -->
      <div v-if="showRevenue" class="bg-white border border-gray-200/90 rounded-2xl p-5 shadow-xs">
        <div class="flex items-center justify-between mb-3">
          <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
            <DollarSign class="w-5 h-5 text-green-600" />
          </div>
          <span class="text-xs text-green-700 font-semibold bg-green-50 px-2.5 py-0.5 rounded-full border border-green-200">
            Encaissé
          </span>
        </div>
        <p class="text-2xl font-black text-gray-900 mb-1">{{ Number(statsData.totalRevenue).toFixed(2) }} TND</p>
        <p class="text-xs font-bold text-gray-700">Revenus perçus</p>
        <p class="text-[11px] text-gray-400 mt-0.5">Mois en cours</p>
      </div>

      <div v-else class="bg-white border border-gray-200/90 rounded-2xl p-5 shadow-xs">
        <div class="flex items-center justify-between mb-3">
          <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
            <Calendar class="w-5 h-5 text-purple-600" />
          </div>
        </div>
        <p class="text-2xl font-black text-gray-900 mb-1">{{ statsData.weeklyLessons }}</p>
        <p class="text-xs font-bold text-gray-700">Leçons / Semaine</p>
        <p class="text-[11px] text-gray-400 mt-0.5">Planning d'activités</p>
      </div>
    </div>

    <!-- Section Détails Enseignants & Activité -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Teachers Performance -->
      <div class="bg-white border border-gray-200/90 rounded-2xl p-5 shadow-xs">
        <div class="flex items-center gap-2 mb-4">
          <BookOpen class="w-5 h-5 text-indigo-600" />
          <h3 class="text-base font-black text-gray-900">Enseignants & Matières</h3>
        </div>
        <div class="space-y-3">
          <div
            v-for="teacher in topTeachers"
            :key="teacher.name"
            class="flex items-center justify-between p-3.5 bg-gray-50/75 rounded-xl border border-gray-100"
          >
            <div class="flex-1 min-w-0 pr-3">
              <p class="text-sm font-bold text-gray-900 truncate">{{ teacher.name }}</p>
              <p class="text-xs text-indigo-600 font-medium truncate">{{ teacher.subject }}</p>
              <div v-if="showRevenue && teacher.teacher_share !== undefined" class="mt-1 flex items-center gap-2 text-[11px]">
                <span class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 inline-flex items-center gap-1">
                  <Coins class="w-3 h-3 text-emerald-600" />
                  <span>Part: {{ teacher.teacher_share }} TND/m</span>
                </span>
                <span v-if="teacher.center_share !== undefined" class="text-indigo-700 font-semibold bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-100">
                  Centre: +{{ teacher.center_share }} TND
                </span>
              </div>
            </div>
            <div class="flex gap-4 text-right shrink-0">
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
      <div class="bg-white border border-gray-200/90 rounded-2xl p-5 shadow-xs">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <Activity class="w-5 h-5 text-indigo-600" />
            <h3 class="text-base font-black text-gray-900">Activité Récente</h3>
          </div>
          <span class="text-xs text-gray-400 font-medium">Historique en direct</span>
        </div>
        <div class="space-y-3">
          <div
            v-for="activity in recentActivity"
            :key="activity.id"
            class="flex items-start gap-3 p-3 hover:bg-gray-50/80 rounded-xl transition-colors border border-gray-100/70"
          >
            <div
              :class="[
                'w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0',
                activity.type === 'payment' ? 'bg-emerald-100 text-emerald-600' :
                activity.type === 'student' ? 'bg-blue-100 text-blue-600' :
                activity.type === 'daycare' ? 'bg-orange-100 text-orange-600' :
                activity.type === 'expense' ? 'bg-rose-100 text-rose-600' :
                activity.type === 'teacher' ? 'bg-teal-100 text-teal-600' :
                activity.type === 'account' ? 'bg-indigo-100 text-indigo-600' :
                activity.type === 'attendance' ? 'bg-cyan-100 text-cyan-600' :
                'bg-purple-100 text-purple-600'
              ]"
            >
              <DollarSign v-if="activity.type === 'payment'" class="w-4.5 h-4.5" />
              <Users v-else-if="activity.type === 'student'" class="w-4.5 h-4.5" />
              <Baby v-else-if="activity.type === 'daycare'" class="w-4.5 h-4.5" />
              <Receipt v-else-if="activity.type === 'expense'" class="w-4.5 h-4.5" />
              <UserPlus v-else-if="activity.type === 'teacher'" class="w-4.5 h-4.5" />
              <ShieldCheck v-else-if="activity.type === 'account'" class="w-4.5 h-4.5" />
              <UserCheck v-else-if="activity.type === 'attendance'" class="w-4.5 h-4.5" />
              <Calendar v-else class="w-4.5 h-4.5" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-gray-900 leading-snug">{{ activity.message }}</p>
              <div class="flex flex-wrap items-center gap-2 mt-1.5">
                <span
                  class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-bold"
                  :class="activity.user_role === 'secretary'
                    ? 'bg-purple-50 text-purple-700 border border-purple-200'
                    : 'bg-indigo-50 text-indigo-700 border border-indigo-200'"
                >
                  <User class="w-3 h-3" />
                  <span>Par {{ activity.user_name || 'Directeur' }}</span>
                  <span class="text-[10px] font-medium opacity-80">({{ activity.user_role === 'secretary' ? 'Personnel' : 'Directeur' }})</span>
                </span>
                <span class="text-xs text-gray-400 flex items-center gap-1">
                  <Clock class="w-3 h-3" />
                  {{ activity.time }}
                </span>
              </div>
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
import {
  Users, UserPlus, DollarSign, Calendar, TrendingUp, BookOpen, Baby,
  Activity, User, Clock, Receipt, ShieldCheck, UserCheck, Zap, AlertCircle, RotateCw, Coins
} from 'lucide-vue-next';
import api from '../utils/api';

const props = withDefaults(defineProps<{
  role?: 'director' | 'secretary';
}>(), {
  role: 'director'
});

defineEmits<{
  (e: 'quick-action', action: 'add-student' | 'take-attendance' | 'add-payment'): void;
  (e: 'open-unpaid-dues'): void;
}>();

const showRevenue = computed(() => props.role === 'director');

const loading = ref(false);
const daycareCount = ref(0);
const unpaidCount = ref(0);
const unpaidAmount = ref(0);

const statsData = ref({
  totalStudents: 0,
  activeStudents: 0,
  totalTeachers: 0,
  totalRevenue: 0,
  pendingPayments: 0,
  weeklyLessons: 0,
});

const topTeachers = ref<Array<{
  name: string;
  subject: string;
  students: number;
  lessons: number;
  teacher_share?: number;
  center_share?: number;
}>>([]);

interface ActivityItem {
  id: string | number;
  type: string;
  message: string;
  time: string;
  user_name?: string;
  user_role?: string;
  action?: string;
}

const recentActivity = ref<ActivityItem[]>([]);

async function loadStats() {
  loading.value = true;
  try {
    const [statsRes, daycareRes, duesRes] = await Promise.all([
      api.get('/api/stats'),
      api.get('/api/daycare'),
      api.get('/api/payment-dues')
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
    if (duesRes.data && duesRes.data.totals) {
      const pCount = duesRes.data.totals.pendingCount || 0;
      const oCount = duesRes.data.totals.overdueCount || 0;
      unpaidCount.value = pCount + oCount;

      const pAmount = duesRes.data.totals.pending || 0;
      const oAmount = duesRes.data.totals.overdue || 0;
      unpaidAmount.value = pAmount + oAmount;
    }
  } catch (e) {
    console.error('Erreur chargement statistiques:', e);
  } finally {
    loading.value = false;
  }
}

onMounted(loadStats);
</script>
