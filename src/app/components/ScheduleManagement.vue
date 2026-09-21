<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Emploi du Temps</h2>
        <p class="text-sm text-gray-500 mt-1">Planification hebdomadaire des cours</p>
      </div>
      <div class="flex items-center gap-3">
        <button
          @click="openAddModal"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
        >
          <Plus class="w-4 h-4" />
          Planifier un cours
        </button>
      </div>
    </div>

    <!-- Level Filter -->
    <div class="flex flex-wrap gap-2">
      <button
        v-for="level in levels"
        :key="level"
        @click="selectedLevel = level"
        :class="[
          'px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-xs',
          selectedLevel === level
            ? 'bg-indigo-600 text-white'
            : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
        ]"
      >
        {{ level }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Schedule Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="daySchedule in scheduleByDay"
        :key="daySchedule.day"
        class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm flex flex-col"
      >
        <div class="bg-indigo-600 px-4 py-3 flex items-center justify-between">
          <h3 class="text-sm font-bold text-white tracking-wide">{{ daySchedule.day }}</h3>
          <span class="text-xs text-indigo-200 font-medium">{{ daySchedule.items.length }} cours</span>
        </div>
        <div class="p-4 space-y-3 flex-1 bg-gray-50/50">
          <template v-if="daySchedule.items.length > 0">
            <div
              v-for="item in daySchedule.items"
              :key="item.id"
              class="border-l-4 border-indigo-500 pl-3.5 pr-2 py-2.5 bg-white rounded-lg border border-gray-200/80 shadow-xs relative group"
            >
              <div class="flex items-start justify-between">
                <div class="text-xs font-semibold text-indigo-600">{{ item.time }}</div>
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button
                    @click="openEditModal(item)"
                    class="p-1 hover:bg-gray-100 text-gray-500 hover:text-blue-600 rounded"
                    title="Modifier"
                  >
                    <Edit2 class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="deleteSchedule(item.id)"
                    class="p-1 hover:bg-red-50 text-gray-500 hover:text-red-600 rounded"
                    title="Supprimer"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
              <div class="text-sm font-bold text-gray-900 mt-1">{{ item.subject }}</div>
              <div class="text-xs font-medium text-gray-600 mt-0.5">{{ item.teacher }}</div>
              <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100 text-xs text-gray-500">
                <span class="bg-gray-100 px-2 py-0.5 rounded font-medium">{{ item.level }}</span>
                <span class="font-medium text-gray-600">{{ item.room }}</span>
              </div>
            </div>
          </template>
          <div v-else class="text-xs text-gray-400 text-center py-8">
            Aucun cours ce jour
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Course Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click="showModal = false">
      <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full" @click.stop>
        <h3 class="text-xl font-bold text-gray-900 mb-4">
          {{ isEditing ? 'Modifier la séance' : 'Planifier un cours' }}
        </h3>
        <form @submit.prevent="saveSchedule" class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Jour *</label>
              <select
                v-model="formData.day"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              >
                <option v-for="d in days" :key="d" :value="d">{{ d }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Horaire *</label>
              <input
                v-model="formData.time"
                type="text"
                required
                placeholder="Ex: 08:00 - 10:00"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Matière *</label>
            <input
              v-model="formData.subject"
              type="text"
              required
              placeholder="Ex: Mathématiques"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Enseignant *</label>
            <input
              v-model="formData.teacher"
              type="text"
              required
              placeholder="Ex: Prof. Karim Belhadj"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Niveau scolaire *</label>
              <input
                v-model="formData.level"
                type="text"
                required
                placeholder="Ex: 7ème"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Salle *</label>
              <input
                v-model="formData.room"
                type="text"
                required
                placeholder="Ex: Salle 101"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
          </div>

          <div v-if="errorMessage" class="text-xs text-red-600 bg-red-50 p-2.5 rounded-lg border border-red-200">
            {{ errorMessage }}
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-sm"
            >
              {{ submitting ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Plus, Edit2, Trash2 } from 'lucide-vue-next';
import api from '../utils/api';

interface ScheduleItem {
  id: number;
  day: string;
  time: string;
  subject: string;
  teacher: string;
  level: string;
  room: string;
}

const schedules = ref<ScheduleItem[]>([]);
const loading = ref(false);
const submitting = ref(false);
const errorMessage = ref('');

const days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
const levels = ['Tous', '7ème', '8ème', '9ème', '1ère', 'Bac'];

const selectedLevel = ref('Tous');

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const formData = ref({
  day: 'Lundi',
  time: '08:00 - 10:00',
  subject: '',
  teacher: '',
  level: '7ème',
  room: 'Salle 101'
});

async function loadSchedules() {
  loading.value = true;
  try {
    const res = await api.get('/api/schedules');
    schedules.value = res.data;
  } catch (e) {
    console.error('Erreur chargement emploi du temps:', e);
  } finally {
    loading.value = false;
  }
}

onMounted(loadSchedules);

const filteredSchedule = computed(() => {
  if (selectedLevel.value === 'Tous') {
    return schedules.value;
  }
  return schedules.value.filter((item) => item.level.toLowerCase().includes(selectedLevel.value.toLowerCase()));
});

const scheduleByDay = computed(() => {
  return days.map((day) => ({
    day,
    items: filteredSchedule.value.filter((item) => item.day === day),
  }));
});

function openAddModal() {
  isEditing.value = false;
  editingId.value = null;
  errorMessage.value = '';
  formData.value = {
    day: 'Lundi',
    time: '08:00 - 10:00',
    subject: '',
    teacher: '',
    level: '7ème',
    room: 'Salle 101'
  };
  showModal.value = true;
}

function openEditModal(item: ScheduleItem) {
  isEditing.value = true;
  editingId.value = item.id;
  errorMessage.value = '';
  formData.value = {
    day: item.day,
    time: item.time,
    subject: item.subject,
    teacher: item.teacher,
    level: item.level,
    room: item.room
  };
  showModal.value = true;
}

async function saveSchedule() {
  submitting.value = true;
  errorMessage.value = '';
  try {
    if (isEditing.value && editingId.value) {
      const res = await api.put(`/api/schedules/${editingId.value}`, formData.value);
      const idx = schedules.value.findIndex(s => s.id === editingId.value);
      if (idx !== -1) {
        schedules.value[idx] = res.data;
      }
    } else {
      const res = await api.post('/api/schedules', formData.value);
      schedules.value.push(res.data);
    }
    showModal.value = false;
  } catch (e: any) {
    console.error('Erreur enregistrement cours:', e);
    errorMessage.value = e.response?.data?.message || 'Erreur lors de l\'enregistrement';
  } finally {
    submitting.value = false;
  }
}

async function deleteSchedule(id: number) {
  if (!confirm('Voulez-vous vraiment supprimer ce cours ?')) return;
  try {
    await api.delete(`/api/schedules/${id}`);
    schedules.value = schedules.value.filter(s => s.id !== id);
  } catch (e) {
    console.error('Erreur suppression cours:', e);
    alert('Impossible de supprimer ce cours.');
  }
}
</script>
