<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Gestion des Élèves</h2>
        <p class="text-sm text-gray-500 mt-1">{{ students.length }} élèves inscrits</p>
      </div>
      <button
        @click="openAddModal"
        class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
      >
        <Plus class="w-4 h-4" />
        Ajouter un élève
      </button>
    </div>

    <!-- Search & Filter -->
    <div class="relative">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
      <input
        type="text"
        placeholder="Rechercher par nom ou niveau..."
        v-model="searchTerm"
        class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm shadow-sm"
      />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Students Table -->
    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
      <table class="w-full">
        <thead class="bg-gray-50/75 border-b border-gray-200">
          <tr>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nom</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Niveau</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Matières</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Téléphone</th>
            <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="student in filteredStudents" :key="student.id" class="hover:bg-gray-50/80 transition-colors">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ student.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ student.level }}</td>
            <td class="px-6 py-4">
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="(sub, idx) in (student.subjects || []).slice(0, 2)"
                  :key="idx"
                  class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-indigo-50 text-indigo-700 rounded-md border border-indigo-100"
                >
                  <BookOpen class="w-3 h-3 text-indigo-500" />
                  {{ sub.subject }}
                </span>
                <span v-if="(student.subjects || []).length > 2" class="inline-flex px-2 py-0.5 text-xs font-medium text-gray-500 bg-gray-100 rounded-md">
                  +{{ student.subjects.length - 2 }}
                </span>
                <span v-if="!student.subjects || student.subjects.length === 0" class="text-xs text-gray-400">
                  Aucune
                </span>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ student.phone }}</td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-1.5">
                <button
                  @click="selectedStudent = student"
                  class="p-1.5 hover:bg-gray-100 text-gray-600 hover:text-indigo-600 rounded-lg transition-colors"
                  title="Voir détails"
                >
                  <Eye class="w-4 h-4" />
                </button>
                <button
                  @click="openEditModal(student)"
                  class="p-1.5 hover:bg-gray-100 text-gray-600 hover:text-blue-600 rounded-lg transition-colors"
                  title="Modifier"
                >
                  <Edit2 class="w-4 h-4" />
                </button>
                <button
                  @click="deleteStudent(student.id)"
                  class="p-1.5 hover:bg-red-50 text-gray-600 hover:text-red-600 rounded-lg transition-colors"
                  title="Supprimer"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="filteredStudents.length === 0" class="text-center py-12">
        <p class="text-gray-500 text-sm">Aucun élève trouvé</p>
      </div>
    </div>

    <!-- Student Details Modal -->
    <div v-if="selectedStudent" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click="selectedStudent = null">
      <div class="bg-white rounded-xl p-6 max-w-lg w-full shadow-2xl space-y-5" @click.stop>
        <div class="flex items-center justify-between border-b pb-3 border-gray-100">
          <h3 class="text-xl font-bold text-gray-900">Détails de l'élève</h3>
          <button @click="selectedStudent = null" class="text-gray-400 hover:text-gray-600 text-lg leading-none">✕</button>
        </div>
        <div class="space-y-4">
          <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Nom complet</p>
            <p class="text-lg font-medium text-gray-900 mt-0.5">{{ selectedStudent.name }}</p>
          </div>
          <div class="grid grid-cols-2 gap-4 bg-gray-50 p-3.5 rounded-lg border border-gray-100">
            <div>
              <p class="text-xs text-gray-500">Niveau</p>
              <p class="font-medium text-gray-800 mt-0.5">{{ selectedStudent.level }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Téléphone</p>
              <p class="font-medium text-gray-800 mt-0.5">{{ selectedStudent.phone }}</p>
            </div>
          </div>
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Matières et Enseignants</p>
            <div v-if="selectedStudent.subjects && selectedStudent.subjects.length > 0" class="space-y-2">
              <div
                v-for="(sub, idx) in selectedStudent.subjects"
                :key="idx"
                class="flex items-center justify-between p-3 bg-indigo-50/50 rounded-lg border border-indigo-100/70"
              >
                <div class="flex items-center gap-2">
                  <BookOpen class="w-4 h-4 text-indigo-600" />
                  <span class="text-sm font-medium text-gray-900">{{ sub.subject }}</span>
                </div>
                <span class="text-sm text-gray-600">{{ sub.teacher }}</span>
              </div>
            </div>
            <p v-else class="text-sm text-gray-400 italic">Aucune matière assignée.</p>
          </div>
        </div>
        <div class="flex justify-end pt-2">
          <button @click="selectedStudent = null" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200">
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Student Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click="closeModal">
      <div class="bg-white rounded-xl p-6 max-w-lg w-full shadow-2xl" @click.stop>
        <h3 class="text-xl font-bold text-gray-900 mb-4">
          {{ isEditing ? "Modifier l'élève" : 'Ajouter un élève' }}
        </h3>
        <form @submit.prevent="saveStudent" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nom complet *</label>
            <input
              v-model="formData.name"
              type="text"
              required
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none text-sm"
              placeholder="Ex: Ahmed Ben Ali"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Niveau *</label>
              <input
                v-model="formData.level"
                type="text"
                required
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none text-sm"
                placeholder="Ex: 7ème année"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Téléphone *</label>
              <input
                v-model="formData.phone"
                type="text"
                required
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none text-sm"
                placeholder="Ex: 20 123 456"
              />
            </div>
          </div>

        

          <!-- Subject enrollments -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Matières & Enseignants</label>
              <button
                type="button"
                @click="addSubjectRow"
                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1"
              >
                <Plus class="w-3 h-3" /> Ajouter matière
              </button>
            </div>
            <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
              <div v-for="(sub, idx) in formData.subjects" :key="idx" class="flex items-center gap-2">
                <input
                  v-model="sub.subject"
                  type="text"
                  required
                  placeholder="Matière"
                  class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                />
                <select
                  v-model="sub.teacher_id"
                  required
                  class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                >
                  <option :value="undefined" disabled>Choisir enseignant</option>
                  <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }} ({{ t.subject }})</option>
                </select>
                <button
                  type="button"
                  @click="removeSubjectRow(idx)"
                  class="p-1.5 text-gray-400 hover:text-red-600 rounded"
                  title="Supprimer"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          <div v-if="errorMessage" class="text-xs text-red-600 bg-red-50 p-2.5 rounded-lg border border-red-200">
            {{ errorMessage }}
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
            <button
              type="button"
              @click="closeModal"
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
import { Plus, Search, Edit2, Trash2, Eye, BookOpen } from 'lucide-vue-next';
import api from '../utils/api';

interface SubjectEnrollment {
  subject: string;
  teacher?: string;
  teacher_id?: number;
}

interface Student {
  id: number;
  name: string;
  level: string;
  phone: string;
  subjects: SubjectEnrollment[];
}

interface Teacher {
  id: number;
  name: string;
  subject: string;
}

const students = ref<Student[]>([]);
const teachers = ref<Teacher[]>([]);
const searchTerm = ref('');
const loading = ref(false);
const submitting = ref(false);
const errorMessage = ref('');

const selectedStudent = ref<Student | null>(null);
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const formData = ref<{
  name: string;
  level: string;
  phone: string;
  subjects: Array<{ subject: string; teacher_id?: number }>;
}>({
  name: '',
  level: '',
  phone: '',
  subjects: []
});

async function loadData() {
  loading.value = true;
  try {
    const [studentsRes, teachersRes] = await Promise.all([
      api.get('/api/students'),
      api.get('/api/teachers')
    ]);
    students.value = studentsRes.data;
    teachers.value = teachersRes.data;
  } catch (e: any) {
    console.error('Erreur chargement élèves:', e);
  } finally {
    loading.value = false;
  }
}

onMounted(loadData);

const filteredStudents = computed(() => {
  return students.value.filter((student) =>
    student.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
    student.level.toLowerCase().includes(searchTerm.value.toLowerCase())
  );
});

function openAddModal() {
  isEditing.value = false;
  editingId.value = null;
  errorMessage.value = '';
  formData.value = {
    name: '',
    level: '',
    phone: '',
    subjects: []
  };
  showModal.value = true;
}

function openEditModal(student: Student) {
  isEditing.value = true;
  editingId.value = student.id;
  errorMessage.value = '';
  formData.value = {
    name: student.name,
    level: student.level,
    phone: student.phone,
    subjects: (student.subjects || []).map(s => ({
      subject: s.subject,
      teacher_id: s.teacher_id
    }))
  };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  errorMessage.value = '';
}

function addSubjectRow() {
  formData.value.subjects.push({
    subject: '',
    teacher_id: teachers.value.length > 0 ? teachers.value[0].id : undefined
  });
}

function removeSubjectRow(index: number) {
  formData.value.subjects.splice(index, 1);
}

async function saveStudent() {
  submitting.value = true;
  errorMessage.value = '';
  try {
    if (isEditing.value && editingId.value) {
      const res = await api.put(`/api/students/${editingId.value}`, formData.value);
      const idx = students.value.findIndex(s => s.id === editingId.value);
      if (idx !== -1) {
        students.value[idx] = res.data;
      }
    } else {
      const res = await api.post('/api/students', formData.value);
      students.value.unshift(res.data);
    }
    closeModal();
  } catch (e: any) {
    console.error('Erreur sauvegarde élève:', e);
    errorMessage.value = e.response?.data?.message || 'Erreur lors de la sauvegarde';
  } finally {
    submitting.value = false;
  }
}

async function deleteStudent(id: number) {
  if (!confirm('Voulez-vous vraiment supprimer cet élève ?')) return;
  try {
    await api.delete(`/api/students/${id}`);
    students.value = students.value.filter(s => s.id !== id);
    if (selectedStudent.value?.id === id) {
      selectedStudent.value = null;
    }
  } catch (e: any) {
    console.error('Erreur suppression:', e);
    alert('Erreur lors de la suppression de l\'élève');
  }
}
</script>
