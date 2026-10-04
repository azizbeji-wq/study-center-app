<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Gestion des Enseignants</h2>
        <p class="text-sm text-gray-500 mt-1">
          {{ teachers.length }} enseignants • Rémunération par séance (% ou montant fixe)
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button
          @click="loadSampleTeachers"
          class="flex items-center gap-2 px-3.5 py-2 bg-indigo-50 text-indigo-700 font-medium rounded-lg hover:bg-indigo-100 transition-colors border border-indigo-200 text-sm shadow-xs"
          title="Charger des exemples de professeurs tunisiens"
        >
          <Sparkles class="w-4 h-4 text-indigo-600" />
          Charger Exemples
        </button>

        <button
          @click="openAddModal"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
        >
          <Plus class="w-4 h-4" />
          Ajouter un enseignant
        </button>
      </div>
    </div>

    <!-- Search -->
    <div class="relative">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
      <input
        type="text"
        placeholder="Rechercher par nom ou matière..."
        v-model="searchTerm"
        class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm shadow-sm"
      />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Teachers Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="teacher in filteredTeachers"
        :key="teacher.id"
        class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-all space-y-4"
      >
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-indigo-50 border border-indigo-100 rounded-full flex items-center justify-center">
              <span class="text-base font-bold text-indigo-600">{{ getInitial(teacher.name) }}</span>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-900">{{ teacher.name }}</h3>
              <p class="text-xs font-medium text-indigo-600">{{ teacher.subject }}</p>
            </div>
          </div>
          <div class="flex gap-1">
            <button
              @click="openEditModal(teacher)"
              class="p-1.5 hover:bg-gray-100 text-gray-600 hover:text-blue-600 rounded-lg transition-colors"
              title="Modifier"
            >
              <Edit2 class="w-4 h-4" />
            </button>
            <button
              @click="deleteTeacher(teacher.id)"
              class="p-1.5 hover:bg-red-50 text-gray-600 hover:text-red-600 rounded-lg transition-colors"
              title="Supprimer"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>

        <div class="space-y-1.5 text-xs text-gray-600 bg-gray-50/75 p-3 rounded-lg border border-gray-100">
          <p class="flex items-center gap-2"><span>📱</span> <span>{{ teacher.phone }}</span></p>
          <p class="flex items-center gap-2"><span>✉️</span> <span>{{ teacher.email }}</span></p>
        </div>

        <div class="pt-2 border-t border-gray-100">
          <div class="grid grid-cols-3 gap-2 text-center">
            <div class="bg-blue-50/70 p-2 rounded-lg border border-blue-100">
              <p class="text-[10px] uppercase font-semibold text-gray-500">Élèves</p>
              <p class="text-sm font-bold text-gray-900">{{ teacher.studentCount }}</p>
            </div>
            <div class="bg-purple-50/70 p-2 rounded-lg border border-purple-100">
              <p class="text-[10px] uppercase font-semibold text-gray-500">Leçons/sem</p>
              <p class="text-sm font-bold text-gray-900">{{ teacher.weeklyLessons }}</p>
            </div>
            <div class="bg-emerald-50/70 p-2 rounded-lg border border-emerald-100">
              <p class="text-[10px] uppercase font-semibold text-emerald-700">Gain / Séance</p>
              <p class="text-xs font-bold text-emerald-800 mt-0.5">
                {{ teacher.remuneration_type === 'percentage' || (teacher.rate_percentage && teacher.rate_percentage > 0)
                    ? (teacher.rate_percentage || 70) + '%'
                    : (teacher.salary_per_lesson || 30) + ' TND' }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!loading && filteredTeachers.length === 0" class="text-center py-12">
      <p class="text-gray-500 text-sm">Aucun enseignant trouvé</p>
    </div>

    <!-- Add/Edit Teacher Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4"
      @click="showModal = false"
    >
      <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-lg w-full space-y-4" @click.stop>
        <div class="flex items-center justify-between border-b pb-3 border-gray-100">
          <div>
            <h3 class="text-xl font-bold text-gray-900">
              {{ isEditing ? "Modifier l'enseignant" : 'Ajouter un enseignant' }}
            </h3>
            <p class="text-xs text-gray-500">Programme tunisien & Mode de rémunération par séance</p>
          </div>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-lg">×</button>
        </div>

        <form @submit.prevent="saveTeacher" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nom complet *</label>
            <input
              v-model="formData.name"
              type="text"
              required
              placeholder="Ex: Prof. Karim Belhadj"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <!-- Tunisian Subject Select -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
                Matière enseignée (Système Tunisien) *
              </label>
              <span class="text-[11px] text-indigo-600 font-medium">Collège & Lycée</span>
            </div>
            <div class="relative">
              <select
                v-model="formData.subject"
                required
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none font-medium text-gray-900"
              >
                <option value="" disabled>Sélectionner une matière</option>
                <option v-for="sub in tunisianSubjects" :key="sub" :value="sub">
                  {{ sub }}
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Téléphone *</label>
              <input
                v-model="formData.phone"
                type="text"
                required
                placeholder="Ex: 21 345 678"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Leçons / sem</label>
              <input
                v-model.number="formData.weeklyLessons"
                type="number"
                min="0"
                placeholder="Ex: 4"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Email *</label>
            <input
              v-model="formData.email"
              type="email"
              required
              placeholder="Ex: karim.belhadj@email.tn"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <!-- Gain par séance (Pourcentage % ou montant fixe TND) -->
          <div class="bg-emerald-50/70 p-4 rounded-xl border border-emerald-200/80 space-y-3">
            <label class="block text-xs font-bold text-emerald-900 uppercase tracking-wider">
              Gain de l'enseignant par séance
            </label>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Type de rémunération</label>
                <select
                  v-model="formData.remuneration_type"
                  class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-emerald-500"
                >
                  <option value="percentage">Pourcentage (%) du groupe</option>
                  <option value="fixed">Montant Fixe (TND / séance)</option>
                </select>
              </div>

              <div>
                <template v-if="formData.remuneration_type === 'percentage'">
                  <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Part Enseignant (%) *
                  </label>
                  <input
                    v-model.number="formData.rate_percentage"
                    type="number"
                    min="1"
                    max="100"
                    step="1"
                    placeholder="Ex: 70%"
                    class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500"
                  />
                </template>
                <template v-else>
                  <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Tarif par séance (TND) *
                  </label>
                  <input
                    v-model.number="formData.salary_per_lesson"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="Ex: 35 TND"
                    class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500"
                  />
                </template>
              </div>
            </div>

            <!-- Gain simulator badge -->
            <div class="text-[11px] text-emerald-800 bg-white/80 p-2 rounded-lg border border-emerald-200">
              <span v-if="formData.remuneration_type === 'percentage'">
                💡 <strong>Calcul automatique :</strong> Sur une séance de groupe de 100 TND récoltés, le professeur touchera <strong>{{ formData.rate_percentage || 70 }} TND</strong> et le centre conservera {{ 100 - (formData.rate_percentage || 70) }} TND.
              </span>
              <span v-else>
                💡 <strong>Tarif fixe :</strong> L'enseignant touchera <strong>{{ formData.salary_per_lesson || 30 }} TND</strong> pour chaque séance assurée, quel que soit le nombre d'élèves.
              </span>
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
import { Plus, Search, Edit2, Trash2, Users, Calendar, Sparkles } from 'lucide-vue-next';
import api from '../utils/api';
import { ALL_TUNISIAN_SUBJECTS, SAMPLE_TEACHERS_TUNISIA } from '../utils/tunisianEducation';

interface Teacher {
  id: number;
  name: string;
  subject: string;
  phone: string;
  email: string;
  studentCount: number;
  weeklyLessons: number;
  remuneration_type?: 'percentage' | 'fixed';
  rate_percentage?: number;
  salary_per_lesson?: number;
}

const tunisianSubjects = ALL_TUNISIAN_SUBJECTS;
const teachers = ref<Teacher[]>([]);
const searchTerm = ref('');
const loading = ref(false);
const submitting = ref(false);
const errorMessage = ref('');

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const formData = ref<{
  name: string;
  subject: string;
  phone: string;
  email: string;
  weeklyLessons: number;
  remuneration_type: 'percentage' | 'fixed';
  rate_percentage: number;
  salary_per_lesson: number;
}>({
  name: '',
  subject: 'Mathématiques',
  phone: '',
  email: '',
  weeklyLessons: 4,
  remuneration_type: 'percentage',
  rate_percentage: 70,
  salary_per_lesson: 35
});

function getInitial(name: string): string {
  const clean = name.replace(/^prof\.?\s*/i, '').trim();
  return (clean.charAt(0) || name.charAt(0) || 'P').toUpperCase();
}

async function loadTeachers() {
  loading.value = true;
  try {
    const res = await api.get('/api/teachers');
    teachers.value = res.data;
  } catch (e: any) {
    console.error('Erreur chargement enseignants:', e);
  } finally {
    loading.value = false;
  }
}

onMounted(loadTeachers);

const filteredTeachers = computed(() => {
  return teachers.value.filter((teacher) =>
    teacher.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
    teacher.subject.toLowerCase().includes(searchTerm.value.toLowerCase())
  );
});

function openAddModal() {
  isEditing.value = false;
  editingId.value = null;
  errorMessage.value = '';
  formData.value = {
    name: '',
    subject: tunisianSubjects[0],
    phone: '',
    email: '',
    weeklyLessons: 4,
    remuneration_type: 'percentage',
    rate_percentage: 70,
    salary_per_lesson: 35
  };
  showModal.value = true;
}

function openEditModal(teacher: Teacher) {
  isEditing.value = true;
  editingId.value = teacher.id;
  errorMessage.value = '';
  formData.value = {
    name: teacher.name,
    subject: teacher.subject,
    phone: teacher.phone,
    email: teacher.email,
    weeklyLessons: teacher.weeklyLessons || 0,
    remuneration_type: teacher.remuneration_type || (teacher.rate_percentage ? 'percentage' : 'fixed'),
    rate_percentage: teacher.rate_percentage || 70,
    salary_per_lesson: teacher.salary_per_lesson || 35
  };
  showModal.value = true;
}

async function loadSampleTeachers() {
  if (!confirm('Voulez-vous charger des exemples d\'enseignants tunisiens avec leurs matières et pourcentages ?')) return;

  loading.value = true;
  try {
    for (const sample of SAMPLE_TEACHERS_TUNISIA) {
      const exists = teachers.value.some(t => t.name.toLowerCase() === sample.name.toLowerCase());
      if (!exists) {
        const res = await api.post('/api/teachers', sample);
        teachers.value.unshift(res.data);
      }
    }
  } catch (e) {
    console.error('Erreur import exemples:', e);
    // Add locally to view immediately
    SAMPLE_TEACHERS_TUNISIA.forEach((sample, i) => {
      if (!teachers.value.some(t => t.name.toLowerCase() === sample.name.toLowerCase())) {
        teachers.value.unshift({
          id: Date.now() + i,
          ...sample,
          remuneration_type: sample.remuneration_type as 'percentage' | 'fixed',
          studentCount: 8
        });
      }
    });
  } finally {
    loading.value = false;
  }
}

async function saveTeacher() {
  submitting.value = true;
  errorMessage.value = '';
  try {
    if (isEditing.value && editingId.value) {
      const res = await api.put(`/api/teachers/${editingId.value}`, formData.value);
      const idx = teachers.value.findIndex(t => t.id === editingId.value);
      if (idx !== -1) {
        teachers.value[idx] = res.data;
      }
    } else {
      const res = await api.post('/api/teachers', formData.value);
      teachers.value.push(res.data);
    }
    showModal.value = false;
  } catch (e: any) {
    console.error('Erreur enregistrement enseignant:', e);
    errorMessage.value = e.response?.data?.message || 'Erreur lors de l\'enregistrement';
  } finally {
    submitting.value = false;
  }
}

async function deleteTeacher(id: number) {
  if (!confirm('Voulez-vous vraiment supprimer cet enseignant ?')) return;
  try {
    await api.delete(`/api/teachers/${id}`);
    teachers.value = teachers.value.filter(t => t.id !== id);
  } catch (e: any) {
    console.error('Erreur suppression enseignant:', e);
    alert('Impossible de supprimer cet enseignant.');
  }
}
</script>
