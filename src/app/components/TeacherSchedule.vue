<template>
  <div class="space-y-6">
    <!-- En-tête -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-violet-100 text-violet-700 text-xs font-bold mb-1">
          <Calendar class="w-3.5 h-3.5" />
          <span>Planning Hebdomadaire Personnel</span>
        </div>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Mon Emploi du Temps</h2>
        <p class="text-xs text-gray-500 mt-0.5">Vos créneaux de cours, salles attribuées et groupes pris en charge</p>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <button
          @click="fetchSchedule"
          :disabled="loading"
          class="px-3 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 transition-colors flex items-center gap-1.5 shadow-2xs"
          title="Rafraîchir"
        >
          <RotateCw :class="['w-3.5 h-3.5', loading ? 'animate-spin text-violet-600' : '']" />
          <span>Actualiser</span>
        </button>
        <button
          @click="printSchedule"
          class="px-3.5 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-md shadow-violet-600/20"
        >
          <Printer class="w-3.5 h-3.5" />
          <span>Imprimer mon planning</span>
        </button>
      </div>
    </div>

    <!-- Bannière Cours du Jour (Aujourd'hui) -->
    <div
      v-if="todayItems.length > 0"
      class="bg-gradient-to-r from-violet-900 via-indigo-900 to-slate-900 text-white rounded-2xl p-4 md:p-5 shadow-lg border border-violet-800/40"
    >
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-violet-500/20 border border-violet-400/30 flex items-center justify-center text-amber-300 shrink-0">
            <Clock class="w-5 h-5" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-extrabold uppercase tracking-wider text-amber-400">Aujourd'hui ({{ currentDayName }})</span>
              <span class="text-[11px] bg-white/10 px-2 py-0.5 rounded-full font-semibold">{{ todayItems.length }} séance(s)</span>
            </div>
            <p class="text-xs text-violet-200 mt-0.5">
              Consultez vos cours du jour et effectuez l'appel rapidement.
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <div
            v-for="item in todayItems"
            :key="item.id"
            class="bg-white/10 hover:bg-white/15 backdrop-blur-md rounded-xl p-2.5 px-3 border border-white/10 flex items-center gap-3 text-xs transition-colors"
          >
            <div>
              <span class="font-bold text-white">{{ item.time }}</span>
              <span class="text-violet-200 ml-1.5 font-medium">{{ item.subject }}</span>
              <span v-if="item.group_name" class="text-amber-300 font-semibold ml-1.5">({{ item.group_name }})</span>
            </div>
            <button
              @click="$emit('take-attendance', { group_name: item.group_name, subject_name: item.subject })"
              class="px-2 py-1 bg-violet-500 hover:bg-violet-400 text-white rounded-lg text-[10px] font-bold transition-colors cursor-pointer"
            >
              Faire l'appel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtres par jour -->
    <div class="flex flex-wrap items-center gap-1.5">
      <button
        @click="selectedDay = ''"
        :class="[
          'px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-2xs',
          selectedDay === ''
            ? 'bg-violet-600 text-white shadow-violet-600/20'
            : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
        ]"
      >
        Toute la semaine ({{ scheduleList.length }})
      </button>
      <button
        v-for="day in daysOfWeek"
        :key="day"
        @click="selectedDay = day"
        :class="[
          'px-3 py-1.5 rounded-xl text-xs font-semibold transition-all shadow-2xs flex items-center gap-1.5',
          selectedDay === day
            ? 'bg-violet-600 text-white'
            : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50',
          day === currentDayName ? 'border-violet-300 ring-1 ring-violet-200' : ''
        ]"
      >
        <span>{{ day }}</span>
        <span
          :class="[
            'text-[10px] px-1.5 py-0.2 rounded-full font-bold',
            selectedDay === day ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'
          ]"
        >
          {{ countForDay(day) }}
        </span>
      </button>
    </div>

    <!-- Chargement -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-16 bg-white rounded-2xl border border-gray-200">
      <div class="w-10 h-10 border-4 border-violet-600 border-t-transparent rounded-full animate-spin mb-3"></div>
      <p class="text-xs font-semibold text-gray-500">Chargement de votre emploi du temps...</p>
    </div>

    <!-- État vide -->
    <div
      v-else-if="filteredGroupedSchedule.length === 0"
      class="bg-white rounded-2xl border border-gray-200 p-12 text-center max-w-lg mx-auto space-y-3"
    >
      <div class="w-14 h-14 bg-violet-50 text-violet-600 rounded-2xl flex items-center justify-center mx-auto">
        <CalendarX class="w-7 h-7" />
      </div>
      <h3 class="text-base font-bold text-gray-900">Aucun cours planifié</h3>
      <p class="text-xs text-gray-500 leading-relaxed">
        {{ selectedDay ? `Aucun cours programmé le ${selectedDay}.` : "Aucun cours n'a encore été planifié dans le système pour votre nom d'enseignant." }}
      </p>
    </div>

    <!-- Grille de l'emploi du temps -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 print:grid-cols-2">
      <div
        v-for="group in filteredGroupedSchedule"
        :key="group.day"
        class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden flex flex-col"
      >
        <!-- En-tête du jour -->
        <div
          :class="[
            'px-5 py-3.5 flex items-center justify-between border-b',
            group.day === currentDayName
              ? 'bg-gradient-to-r from-violet-600 to-purple-600 text-white border-violet-700'
              : 'bg-gray-50/80 text-gray-900 border-gray-100'
          ]"
        >
          <div class="flex items-center gap-2">
            <Calendar class="w-4 h-4 opacity-80" />
            <h3 class="text-sm font-black tracking-tight">{{ group.day }}</h3>
            <span
              v-if="group.day === currentDayName"
              class="text-[10px] font-extrabold bg-amber-400 text-violet-950 px-2 py-0.5 rounded-full"
            >
              Aujourd'hui
            </span>
          </div>
          <span
            :class="[
              'text-xs font-bold px-2 py-0.5 rounded-full',
              group.day === currentDayName ? 'bg-white/20 text-white' : 'bg-gray-200/70 text-gray-700'
            ]"
          >
            {{ group.items.length }} cours
          </span>
        </div>

        <!-- Liste des séances du jour -->
        <div class="p-4 space-y-3.5 flex-1 bg-gray-50/30">
          <div
            v-for="item in group.items"
            :key="item.id"
            class="bg-white rounded-xl p-4 border border-gray-200/90 shadow-2xs hover:border-violet-300 hover:shadow-xs transition-all relative group"
          >
            <!-- Horaire & Badge Matière -->
            <div class="flex items-start justify-between gap-2 mb-2">
              <span class="inline-flex items-center gap-1.5 text-xs font-extrabold text-violet-700 bg-violet-50 px-2.5 py-1 rounded-lg border border-violet-200/70">
                <Clock class="w-3.5 h-3.5" />
                {{ item.time }}
              </span>

              <span class="text-[11px] font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-md">
                {{ item.level }}
              </span>
            </div>

            <!-- Matière & Groupe -->
            <div class="mb-3">
              <h4 class="text-sm font-black text-gray-900 leading-snug">{{ item.subject }}</h4>
              <div v-if="item.group_name" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-200/70 mt-1">
                <Users class="w-3 h-3" />
                <span>{{ item.group_name }}</span>
              </div>
            </div>

            <!-- Salle & Action d'appel -->
            <div class="pt-2.5 border-t border-gray-100 flex items-center justify-between text-xs">
              <div class="flex items-center gap-1 text-gray-600 font-semibold text-[11px]">
                <MapPin class="w-3.5 h-3.5 text-gray-400" />
                <span>Salle {{ item.room }}</span>
              </div>

              <button
                @click="$emit('take-attendance', { group_name: item.group_name, subject_name: item.subject })"
                class="px-2.5 py-1 bg-violet-50 hover:bg-violet-600 text-violet-700 hover:text-white rounded-lg text-[11px] font-bold transition-all flex items-center gap-1 border border-violet-200 hover:border-violet-600 cursor-pointer"
                title="Pointer les présences pour cette séance"
              >
                <UserCheck class="w-3.5 h-3.5" />
                <span>Faire l'appel</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Calendar, RotateCw, Printer, Clock, Users, MapPin, UserCheck, CalendarX } from 'lucide-vue-next';
import api from '../utils/api';

const emit = defineEmits<{
  (e: 'take-attendance', data: { group_name?: string; subject_name?: string }): void;
}>();

interface ScheduleItem {
  id: number;
  day: string;
  time: string;
  subject: string;
  teacher: string;
  level: string;
  room: string;
  group_name?: string;
}

const loading = ref(false);
const scheduleList = ref<ScheduleItem[]>([]);
const selectedDay = ref('');

const daysOfWeek = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

// Trouver le nom du jour actuel en français
const currentDayName = computed(() => {
  const dayIndex = new Date().getDay(); // 0 = Dimanche, 1 = Lundi, ...
  const map: Record<number, string> = {
    0: 'Dimanche',
    1: 'Lundi',
    2: 'Mardi',
    3: 'Mercredi',
    4: 'Jeudi',
    5: 'Vendredi',
    6: 'Samedi',
  };
  return map[dayIndex] || 'Lundi';
});

// Cours d'aujourd'hui
const todayItems = computed(() => {
  return scheduleList.value.filter(item => item.day.toLowerCase() === currentDayName.value.toLowerCase());
});

// Regroupement par jour
const groupedSchedule = computed(() => {
  return daysOfWeek
    .map(day => {
      const items = scheduleList.value.filter(item => item.day.toLowerCase() === day.toLowerCase());
      return { day, items };
    })
    .filter(g => g.items.length > 0);
});

// Filtrage
const filteredGroupedSchedule = computed(() => {
  if (!selectedDay.value) {
    return groupedSchedule.value;
  }
  return groupedSchedule.value.filter(g => g.day.toLowerCase() === selectedDay.value.toLowerCase());
});

function countForDay(day: string): number {
  return scheduleList.value.filter(item => item.day.toLowerCase() === day.toLowerCase()).length;
}

async function fetchSchedule() {
  loading.value = true;
  try {
    const res = await api.get('/api/teacher/schedule');
    scheduleList.value = res.data || [];
  } catch (err) {
    console.error('Erreur chargement planning enseignant:', err);
  } finally {
    loading.value = false;
  }
}

function printSchedule() {
  window.print();
}

onMounted(() => {
  fetchSchedule();
});
</script>
