<template>
  <div class="space-y-6">
    <!-- En-tête -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-violet-100 text-violet-700 text-xs font-bold mb-1">
          <Calendar class="w-3.5 h-3.5" />
          <span>Planning & Planification des Cours</span>
        </div>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Mon Emploi du Temps</h2>
        <p class="text-xs text-gray-500 mt-0.5">Planifiez, modifiez et organisez vos séances hebdomadaires en toute autonomie</p>
      </div>

      <div class="flex items-center gap-2 shrink-0 flex-wrap">
        <!-- Bouton Nouveau Créneau -->
        <button
          @click="openCreateModal"
          class="px-3.5 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm shadow-violet-600/20 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>+ Planifier un cours</span>
        </button>

        <button
          @click="fetchSchedule"
          :disabled="loading"
          class="px-3 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 transition-colors flex items-center gap-1.5 shadow-2xs cursor-pointer"
          title="Rafraîchir"
        >
          <RotateCw :class="['w-3.5 h-3.5', loading ? 'animate-spin text-violet-600' : '']" />
          <span>Actualiser</span>
        </button>

        <button
          @click="printSchedule"
          class="px-3 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 transition-colors flex items-center gap-1.5 shadow-2xs cursor-pointer"
        >
          <Printer class="w-3.5 h-3.5 text-gray-500" />
          <span>Imprimer</span>
        </button>
      </div>
    </div>

    <!-- Toast de notification -->
    <div
      v-if="toastMessage"
      :class="[
        'p-3.5 rounded-xl flex items-center gap-2.5 text-xs font-semibold shadow-md animate-in fade-in',
        toastType === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'
      ]"
    >
      <CheckCircle2 v-if="toastType === 'success'" class="w-4 h-4 shrink-0" />
      <AlertCircle v-else class="w-4 h-4 shrink-0" />
      <span class="flex-1">{{ toastMessage }}</span>
      <button @click="toastMessage = ''" class="text-white/80 hover:text-white">
        <X class="w-4 h-4" />
      </button>
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
              Consultez vos cours du jour, modifiez vos créneaux ou effectuez l'appel rapidement.
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
          'px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-2xs cursor-pointer',
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
          'px-3 py-1.5 rounded-xl text-xs font-semibold transition-all shadow-2xs flex items-center gap-1.5 cursor-pointer',
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
      class="bg-white rounded-2xl border border-gray-200 p-12 text-center max-w-lg mx-auto space-y-4"
    >
      <div class="w-14 h-14 bg-violet-50 text-violet-600 rounded-2xl flex items-center justify-center mx-auto">
        <CalendarX class="w-7 h-7" />
      </div>
      <div>
        <h3 class="text-base font-bold text-gray-900">Aucun cours planifié</h3>
        <p class="text-xs text-gray-500 leading-relaxed mt-1">
          {{ selectedDay ? `Aucun cours programmé le ${selectedDay}.` : "Vous n'avez pas encore planifié de créneaux. Créez votre première séance facilement !" }}
        </p>
      </div>
      <div>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm shadow-violet-600/20 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>+ Planifier un premier cours</span>
        </button>
      </div>
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
            class="bg-white rounded-xl p-4 border border-gray-200/90 shadow-2xs hover:border-violet-300 hover:shadow-xs transition-all relative group flex flex-col justify-between"
          >
            <div>
              <!-- Horaire & Badge Matière / Actions CRUD -->
              <div class="flex items-start justify-between gap-2 mb-2">
                <span class="inline-flex items-center gap-1.5 text-xs font-extrabold text-violet-700 bg-violet-50 px-2.5 py-1 rounded-lg border border-violet-200/70">
                  <Clock class="w-3.5 h-3.5" />
                  {{ item.time }}
                </span>

                <!-- Boutons d'édition & suppression -->
                <div class="flex items-center gap-1">
                  <span class="text-[10px] font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-md mr-1">
                    {{ item.level }}
                  </span>

                  <button
                    @click="openEditModal(item)"
                    class="p-1 text-gray-400 hover:text-violet-600 hover:bg-violet-50 rounded-md transition-colors cursor-pointer"
                    title="Modifier ce cours"
                  >
                    <Pencil class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="handleDelete(item)"
                    class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors cursor-pointer"
                    title="Supprimer ce cours"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>

              <!-- Matière & Groupe -->
              <div class="mb-3">
                <h4 class="text-sm font-black text-gray-900 leading-snug">{{ item.subject }}</h4>
                <div v-if="item.group_name" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-200/70 mt-1">
                  <Users class="w-3 h-3" />
                  <span>{{ item.group_name }}</span>
                </div>
              </div>
            </div>

            <!-- Salle & Action d'appel -->
            <div class="pt-2.5 border-t border-gray-100 flex items-center justify-between text-xs mt-1">
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

    <!-- MODALE DE PLANIFICATION / MODIFICATION DE COURS (CRUD) -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-3 sm:p-4 overflow-y-auto"
      @click.self="showModal = false"
    >
      <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-lg my-6 flex flex-col border border-gray-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200"
      >
        <!-- Modal Header -->
        <div class="px-6 py-4.5 bg-gradient-to-r from-violet-700 via-purple-700 to-violet-800 text-white flex items-center justify-between shrink-0 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-md flex items-center justify-center font-bold text-lg border border-white/20 shadow-inner">
              <Calendar class="w-5 h-5 text-white" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-base font-black tracking-tight text-white">
                  {{ isEditing ? 'Modifier la Séance' : 'Planifier un Nouveau Cours' }}
                </h3>
                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-white/20 text-white border border-white/30">
                  Emploi du Temps
                </span>
              </div>
              <p class="text-xs text-violet-100/90 mt-0.5">
                {{ isEditing ? 'Mettez à jour les horaires, la salle ou le groupe' : 'Ajoutez un créneau à votre planning personnel' }}
              </p>
            </div>
          </div>

          <button
            @click="showModal = false"
            class="p-1.5 rounded-xl text-white/80 hover:text-white hover:bg-white/15 transition-colors cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Formulaire de Saisie -->
        <form @submit.prevent="handleSaveSchedule" class="p-6 space-y-4 text-left">
          <!-- Avertissement de conflit -->
          <div
            v-if="conflictError"
            :class="[
              'p-3.5 rounded-xl text-xs font-semibold flex items-start gap-2.5 border animate-in fade-in',
              conflictCanForce ? 'bg-amber-50 text-amber-900 border-amber-200' : 'bg-red-50 text-red-900 border-red-200'
            ]"
          >
            <AlertCircle class="w-4 h-4 shrink-0 mt-0.5 text-amber-600" />
            <div class="flex-1 space-y-2">
              <p>{{ conflictError }}</p>
              <label v-if="conflictCanForce" class="flex items-center gap-2 font-bold cursor-pointer text-amber-950">
                <input
                  v-model="form.force"
                  type="checkbox"
                  class="rounded text-violet-600 focus:ring-violet-500"
                />
                <span>Forcer la planification quand même (Ignorer l'avertissement de groupe)</span>
              </label>
            </div>
          </div>

          <!-- Champ 1 : Jour de la semaine -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-gray-700">
              Jour de la séance <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.day"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all cursor-pointer font-medium"
            >
              <option v-for="d in daysOfWeek" :key="d" :value="d">{{ d }}</option>
            </select>
          </div>

          <!-- Champ 2 : Créneau Horaire -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-gray-700 flex items-center justify-between">
              <span>Créneau Horaire <span class="text-red-500">*</span></span>
              <span class="text-[11px] font-normal text-violet-600">ex: 08:00 - 10:00</span>
            </label>
            <input
              v-model="form.time"
              type="text"
              required
              placeholder="08:00 - 10:00, 14:30 - 16:30..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all"
            />
            <!-- Suggestions rapides d'heures -->
            <div class="flex flex-wrap gap-1.5 pt-1">
              <span class="text-[10px] text-gray-400">Créneaux courants :</span>
              <button
                v-for="s in quickTimeSlots"
                :key="s"
                type="button"
                @click="form.time = s"
                class="px-2 py-0.5 text-[10px] font-semibold bg-violet-50 hover:bg-violet-100 text-violet-700 rounded-md border border-violet-200/60 cursor-pointer"
              >
                {{ s }}
              </button>
            </div>
          </div>

          <!-- Champ 3 : Groupe Pédagogique -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-gray-700 flex items-center justify-between">
              <span>Groupe d'Élèves</span>
              <span class="text-[11px] font-normal text-gray-500">Vos groupes actifs</span>
            </label>
            <select
              v-model="form.group_name"
              @change="onGroupSelect"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all cursor-pointer font-medium"
            >
              <option value="">-- Aucun groupe spécifique / Cours individuel --</option>
              <option v-for="g in teacherGroups" :key="g.group_name || g.name" :value="g.group_name || g.name">
                {{ g.group_name || g.name }} ({{ g.level || 'Général' }})
              </option>
            </select>
          </div>

          <!-- Champ 4 : Matière & Niveau -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="block text-xs font-bold text-gray-700">
                Matière Enseignée <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.subject"
                type="text"
                required
                placeholder="Mathématiques, Physique..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all"
              />
            </div>

            <div class="space-y-1.5">
              <label class="block text-xs font-bold text-gray-700">
                Niveau Scolaire <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.level"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all cursor-pointer"
              >
                <option disabled value="">Choisir le niveau</option>
                <optgroup label="Baccalauréat">
                  <option v-for="lvl in bacLevels" :key="lvl.id" :value="lvl.name">{{ lvl.name }}</option>
                </optgroup>
                <optgroup label="Secondaire">
                  <option v-for="lvl in secondaryLevels" :key="lvl.id" :value="lvl.name">{{ lvl.name }}</option>
                </optgroup>
                <optgroup label="Collège">
                  <option v-for="lvl in collegeLevels" :key="lvl.id" :value="lvl.name">{{ lvl.name }}</option>
                </optgroup>
                <option value="Autre">Autre niveau</option>
              </select>
            </div>
          </div>

          <!-- Champ 5 : Salle de Classe -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-gray-700 flex items-center justify-between">
              <span>Salle de Cours <span class="text-red-500">*</span></span>
              <span class="text-[11px] font-normal text-violet-600">Vérification automatique de disponibilité</span>
            </label>
            <div class="flex items-center gap-2">
              <input
                v-model="form.room"
                type="text"
                required
                placeholder="ex: Salle 1, Salle 2, Labo..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all"
              />
            </div>
            <!-- Suggestions rapides de salles -->
            <div class="flex flex-wrap gap-1.5 pt-1">
              <span class="text-[10px] text-gray-400">Salles courantes :</span>
              <button
                v-for="r in quickRooms"
                :key="r"
                type="button"
                @click="form.room = r"
                class="px-2 py-0.5 text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md border border-gray-200 cursor-pointer"
              >
                {{ r }}
              </button>
            </div>
          </div>

          <!-- Actions Footer de la modale -->
          <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
            <button
              type="button"
              @click="showModal = false"
              :disabled="submitting"
              class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-100 text-xs font-bold transition-all cursor-pointer disabled:opacity-50"
            >
              Annuler
            </button>

            <button
              type="submit"
              :disabled="submitting"
              class="px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold shadow-md shadow-violet-600/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
              <CheckCircle2 v-else class="w-4 h-4" />
              <span>{{ submitting ? 'Enregistrement...' : (isEditing ? 'Mettre à jour' : 'Enregistrer le cours') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import {
  Calendar, RotateCw, Printer, Clock, Users, MapPin, UserCheck, CalendarX,
  Plus, Pencil, Trash2, X, AlertCircle, CheckCircle2, Loader2
} from 'lucide-vue-next';
import api from '../utils/api';
import { TUNISIAN_LEVELS } from '../utils/tunisianEducation';

const props = defineProps<{
  teacherName?: string;
  subjects?: string[];
}>();

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
const submitting = ref(false);
const scheduleList = ref<ScheduleItem[]>([]);
const teacherGroups = ref<any[]>([]);
const selectedDay = ref('');

const toastMessage = ref('');
const toastType = ref<'success' | 'error'>('success');

// État Modal CRUD
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const conflictError = ref('');
const conflictCanForce = ref(false);

const daysOfWeek = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

const quickTimeSlots = [
  '08:00 - 10:00',
  '10:00 - 12:00',
  '14:00 - 16:00',
  '16:00 - 18:00',
  '18:00 - 20:00'
];

const quickRooms = ['Salle 1', 'Salle 2', 'Salle 3', 'Salle 4', 'Labo Info'];

const collegeLevels = computed(() => TUNISIAN_LEVELS.filter(l => l.cycle === 'college'));
const secondaryLevels = computed(() => TUNISIAN_LEVELS.filter(l => l.cycle === 'lycee' && !l.id.startsWith('4_bac')));
const bacLevels = computed(() => TUNISIAN_LEVELS.filter(l => l.id.startsWith('4_bac')));

const defaultLevel = computed(() => bacLevels.value[0]?.name || '4ème Année Bac Mathématiques');

const form = reactive({
  day: 'Lundi',
  time: '08:00 - 10:00',
  group_name: '',
  subject: '',
  level: '',
  room: 'Salle 1',
  force: false,
});

// Nom du jour actuel en français
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

function showToast(msg: string, type: 'success' | 'error' = 'success') {
  toastMessage.value = msg;
  toastType.value = type;
  setTimeout(() => {
    if (toastMessage.value === msg) {
      toastMessage.value = '';
    }
  }, 4000);
}

// 1. Charger l'emploi du temps
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

// 2. Charger les groupes de l'enseignant
async function fetchTeacherGroups() {
  try {
    const res = await api.get('/api/teacher/groups');
    teacherGroups.value = res.data || [];
  } catch (err) {
    console.error('Erreur chargement groupes enseignant:', err);
  }
}

// Quand l'enseignant sélectionne un groupe, pré-remplir la matière et le niveau
function onGroupSelect() {
  if (!form.group_name) return;
  const match = teacherGroups.value.find(g => (g.group_name || g.name) === form.group_name);
  if (match) {
    if (match.level) {
      form.level = match.level;
    }
    if (match.subjects && match.subjects.length > 0) {
      form.subject = match.subjects[0];
    } else if (match.subject) {
      form.subject = match.subject;
    }
  }
}

// 3. Ouvrir la modale en mode Création
function openCreateModal() {
  isEditing.value = false;
  editingId.value = null;
  conflictError.value = '';
  conflictCanForce.value = false;

  form.day = selectedDay.value || currentDayName.value || 'Lundi';
  form.time = '08:00 - 10:00';
  form.group_name = teacherGroups.value[0]?.group_name || teacherGroups.value[0]?.name || '';
  form.subject = props.subjects?.[0] || 'Mathématiques';
  form.level = defaultLevel.value;
  form.room = 'Salle 1';
  form.force = false;

  if (form.group_name) {
    onGroupSelect();
  }

  showModal.value = true;
}

// 4. Ouvrir la modale en mode Édition
function openEditModal(item: ScheduleItem) {
  isEditing.value = true;
  editingId.value = item.id;
  conflictError.value = '';
  conflictCanForce.value = false;

  form.day = item.day;
  form.time = item.time;
  form.group_name = item.group_name || '';
  form.subject = item.subject;
  form.level = item.level;
  form.room = item.room;
  form.force = false;

  showModal.value = true;
}

// 5. Enregistrer le créneau (Création ou Mise à jour)
async function handleSaveSchedule() {
  submitting.value = true;
  conflictError.value = '';

  const payload = {
    day: form.day,
    time: form.time,
    subject: form.subject,
    level: form.level,
    room: form.room,
    group_name: form.group_name || null,
    teacher: props.teacherName || undefined,
    force: form.force,
  };

  try {
    if (isEditing.value && editingId.value) {
      await api.put(`/api/schedules/${editingId.value}`, payload);
      showToast('Séance de cours mise à jour avec succès.', 'success');
    } else {
      await api.post('/api/schedules', payload);
      showToast('Nouveau cours planifié avec succès.', 'success');
    }

    showModal.value = false;
    fetchSchedule();
  } catch (err: any) {
    console.error('Erreur sauvegarde planning:', err);
    const status = err.response?.status;
    const msg = err.response?.data?.message || 'Erreur lors de la planification.';

    if (status === 422) {
      conflictError.value = msg; // Conflit de salle : refus strict
      conflictCanForce.value = false;
    } else if (status === 409) {
      conflictError.value = msg; // Conflit de groupe : avertissement passable avec force
      conflictCanForce.value = true;
    } else {
      showToast(msg, 'error');
    }
  } finally {
    submitting.value = false;
  }
}

// 6. Supprimer un créneau
async function handleDelete(item: ScheduleItem) {
  if (!confirm(`Supprimer la séance de ${item.subject} (${item.day} à ${item.time}) ?`)) {
    return;
  }

  try {
    await api.delete(`/api/schedules/${item.id}`);
    showToast(`Séance de ${item.subject} supprimée.`, 'success');
    fetchSchedule();
  } catch (err: any) {
    console.error('Erreur suppression séance:', err);
    showToast(err.response?.data?.message || 'Impossible de supprimer la séance.', 'error');
  }
}

function printSchedule() {
  window.print();
}

onMounted(() => {
  fetchSchedule();
  fetchTeacherGroups();
});
</script>
