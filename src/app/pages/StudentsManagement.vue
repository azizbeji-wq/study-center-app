<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl text-gray-900">Gestion des Étudiants</h2>
        <p class="text-sm text-gray-500 mt-1">{{ students.length }} étudiants</p>
      </div>
      <button @click="openAddModal" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
        <Plus class="w-4 h-4" />
        Ajouter un étudiant
      </button>
    </div>

    <!-- Search -->
    <div class="relative">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
      <input
        type="text"
        placeholder="Rechercher un étudiant..."
        v-model="searchTerm"
        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
      />
    </div>

    <!-- Students Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="student in filteredStudents" :key="student.id" class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-3">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
              <span class="text-lg text-indigo-600">{{ student.name.charAt(0) }}</span>
            </div>
            <div>
              <h3 class="text-sm text-gray-900">{{ student.name }}</h3>
              <p class="text-xs text-indigo-600">{{ student.level }}</p>
            </div>
          </div>
          <div class="flex gap-1">
            <button @click="openEditModal(student)" class="p-1 hover:bg-gray-100 rounded" title="Modifier">
              <Edit2 class="w-4 h-4 text-gray-600" />
            </button>
            <button @click="deleteStudent(student.id)" class="p-1 hover:bg-gray-100 rounded" title="Supprimer">
              <Trash2 class="w-4 h-4 text-red-600" />
            </button>
          </div>
        </div>
        <div class="space-y-2 mb-3">
          <p class="text-xs text-gray-600">📱 {{ student.phone }}</p>
          <p class="text-xs text-gray-600">Statut: {{ student.status }}</p>
        </div>
      </div>
    </div>

    <div v-if="filteredStudents.length === 0" class="text-center py-12">
      <p class="text-gray-500">Aucun étudiant trouvé</p>
    </div>

    <!-- Add Student Modal -->
    <div v-if="showAddModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-96">
        <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">Ajouter un étudiant</h3>
        <form @submit.prevent="submitAdd" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nom</label>
            <input v-model="newStudent.name" type="text" required class="w-full mt-1 border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Niveau</label>
            <input v-model="newStudent.level" type="text" required class="w-full mt-1 border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Téléphone</label>
            <input v-model="newStudent.phone" type="text" required class="w-full mt-1 border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Statut</label>
            <input v-model="newStudent.status" type="text" required class="w-full mt-1 border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>
          <div class="flex justify-end space-x-2">
            <button type="button" @click="showAddModal = false" class="px-4 py-2 bg-gray-200 text-gray-800 rounded">Annuler</button>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Student Modal -->
    <div v-if="showEditModal && editStudentData" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-96">
        <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">Modifier l'étudiant</h3>
        <form @submit.prevent="submitEdit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nom</label>
            <input v-model="editStudentData.name" type="text" required class="w-full mt-1 border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Niveau</label>
            <input v-model="editStudentData.level" type="text" required class="w-full mt-1 border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Téléphone</label>
            <input v-model="editStudentData.phone" type="text" required class="w-full mt-1 border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Statut</label>
            <input v-model="editStudentData.status" type="text" required class="w-full mt-1 border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
          </div>
          <div class="flex justify-end space-x-2">
            <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-gray-200 text-gray-800 rounded">Annuler</button>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Plus, Search, Edit2, Trash2 } from 'lucide-vue-next';

interface Student {
  id: number;
  name: string;
  level: string;
  phone: string;
  status: string;
}

// Reactive state
const students = ref<Student[]>([]);
const searchTerm = ref('');

// Modal state
const showAddModal = ref(false);
const showEditModal = ref(false);
const newStudent = ref({ name: '', level: '', phone: '', status: '' });
const editStudentData = ref<Student | null>(null);

async function loadStudents() {
  try {
    const response = await axios.get('/api/students');
    students.value = response.data;
  } catch (e) {
    console.error('Failed to load students', e);
  }
}

onMounted(loadStudents);

const filteredStudents = computed(() => {
  return students.value.filter((s) =>
    s.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
    s.level.toLowerCase().includes(searchTerm.value.toLowerCase())
  );
});

function openAddModal() {
  showAddModal.value = true;
  newStudent.value = { name: '', level: '', phone: '', status: '' };
}

async function submitAdd() {
  try {
    const response = await axios.post('/api/students', newStudent.value);
    students.value.push(response.data);
    showAddModal.value = false;
  } catch (e) {
    console.error('Failed to add student', e);
    alert('Error adding student');
  }
}

function openEditModal(student: Student) {
  editStudentData.value = { ...student };
  showEditModal.value = true;
}

async function submitEdit() {
  if (!editStudentData.value) return;
  try {
    const { id, ...payload } = editStudentData.value;
    const response = await axios.put(`/api/students/${id}`, payload);
    const idx = students.value.findIndex((s) => s.id === id);
    if (idx !== -1) students.value.splice(idx, 1, response.data);
    showEditModal.value = false;
  } catch (e) {
    console.error('Failed to update student', e);
    alert('Error updating student');
  }
}

async function deleteStudent(id: number) {
  if (!confirm('Are you sure you want to delete this student?')) return;
  try {
    await axios.delete(`/api/students/${id}`);
    students.value = students.value.filter((s) => s.id !== id);
  } catch (e) {
    console.error('Failed to delete student', e);
    alert('Could not delete student');
  }
}
</script>
