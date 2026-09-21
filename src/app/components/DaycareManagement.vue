<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Élèves de Garde</h2>
        <p class="text-sm text-gray-500 mt-1">Gestion des enfants de 6 à 11 ans</p>
      </div>
      <button
        @click="openAddModal"
        class="flex items-center gap-2 px-4 py-2 bg-orange-600 text-white font-medium rounded-lg hover:bg-orange-700 transition-colors shadow-sm"
      >
        <Plus class="w-4 h-4" />
        Ajouter un enfant
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
            <span class="text-lg">👶</span>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase font-semibold">Total Garde</p>
            <p class="text-2xl font-bold text-gray-900">{{ children.length }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white border border-green-200 rounded-xl p-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
            <span class="text-lg">✅</span>
          </div>
          <div>
            <p class="text-xs text-green-700 uppercase font-semibold">Présents</p>
            <p class="text-2xl font-bold text-green-900">{{ presentCount }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
            <span class="text-lg">❌</span>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase font-semibold">Absents</p>
            <p class="text-2xl font-bold text-gray-900">{{ absentCount }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
        <input
          type="text"
          placeholder="Rechercher un enfant ou parent..."
          v-model="searchTerm"
          class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm shadow-sm"
        />
      </div>
      <div class="flex gap-2">
        <button
          v-for="status in (['all', 'present', 'absent'] as const)"
          :key="status"
          @click="filterStatus = status"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-xs',
            filterStatus === status
              ? 'bg-orange-600 text-white'
              : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
          ]"
        >
          {{ status === 'all' ? 'Tous' : status === 'present' ? 'Présents' : 'Absents' }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-orange-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Children Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="child in filteredChildren"
        :key="child.id"
        class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-all space-y-3"
      >
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
              <span class="text-xl">👶</span>
            </div>
            <div>
              <h3 class="text-sm font-bold text-gray-900">{{ child.name }}</h3>
              <p class="text-xs font-semibold text-orange-600">{{ child.age }} ans</p>
            </div>
          </div>
          <!-- Toggle Attendance Button Badge -->
          <button
            @click="togglePresence(child)"
            :title="'Cliquer pour marquer comme ' + (child.status === 'present' ? 'absent' : 'présent')"
            :class="[
              'px-2.5 py-1 text-xs font-semibold rounded-full border transition-all cursor-pointer flex items-center gap-1',
              child.status === 'present'
                ? 'bg-green-100 text-green-800 border-green-200 hover:bg-green-200'
                : 'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200'
            ]"
          >
            <span class="w-1.5 h-1.5 rounded-full" :class="child.status === 'present' ? 'bg-green-500' : 'bg-gray-400'"></span>
            {{ child.status === 'present' ? 'Présent' : 'Absent' }}
          </button>
        </div>

        <div class="space-y-1.5 text-xs text-gray-600 bg-orange-50/40 p-3 rounded-lg border border-orange-100/60">
          <div class="flex items-center gap-2">
            <span class="text-gray-400 font-medium">Parent:</span>
            <span class="font-medium text-gray-900">{{ child.parentName }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span>📱</span>
            <span>{{ child.phone }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span>⏰</span>
            <span>{{ child.schedule }}</span>
          </div>
        </div>

        <div class="flex gap-2 pt-2 border-t border-gray-100">
          <button
            @click="selectedChild = child"
            class="flex-1 px-3 py-1.5 text-xs font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center gap-1"
          >
            <Eye class="w-3.5 h-3.5" />
            Détails
          </button>
          <button
            @click="openEditModal(child)"
            class="p-1.5 hover:bg-gray-100 text-gray-600 hover:text-blue-600 rounded-lg transition-colors"
            title="Modifier"
          >
            <Edit2 class="w-4 h-4" />
          </button>
          <button
            @click="deleteChild(child.id)"
            class="p-1.5 hover:bg-red-50 text-gray-600 hover:text-red-600 rounded-lg transition-colors"
            title="Supprimer"
          >
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <div v-if="!loading && filteredChildren.length === 0" class="text-center py-12">
      <p class="text-gray-500 text-sm">Aucun enfant trouvé</p>
    </div>

    <!-- Child Details Modal -->
    <div v-if="selectedChild" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click="selectedChild = null">
      <div class="bg-white rounded-xl p-6 max-w-md w-full shadow-2xl space-y-4" @click.stop>
        <div class="flex items-center justify-between border-b pb-3 border-gray-100">
          <h3 class="text-xl font-bold text-gray-900">Détails de l'enfant</h3>
          <button @click="selectedChild = null" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="flex items-center gap-4 bg-orange-50/50 p-4 rounded-xl border border-orange-100">
          <div class="w-14 h-14 bg-orange-200 rounded-full flex items-center justify-center text-2xl">👶</div>
          <div>
            <h4 class="text-lg font-bold text-gray-900">{{ selectedChild.name }}</h4>
            <p class="text-sm font-semibold text-orange-600">{{ selectedChild.age }} ans</p>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
            <p class="text-xs text-gray-400">Parent</p>
            <p class="font-medium text-gray-900">{{ selectedChild.parentName }}</p>
          </div>
          <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
            <p class="text-xs text-gray-400">Téléphone</p>
            <p class="font-medium text-gray-900">{{ selectedChild.phone }}</p>
          </div>
          <div class="col-span-2 bg-gray-50 p-3 rounded-lg border border-gray-100">
            <p class="text-xs text-gray-400">Horaires de garde</p>
            <p class="font-medium text-gray-900">{{ selectedChild.schedule }}</p>
          </div>
        </div>
        <div class="flex justify-end pt-2">
          <button @click="selectedChild = null" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200">
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Child Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click="showModal = false">
      <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full" @click.stop>
        <h3 class="text-xl font-bold text-gray-900 mb-4">
          {{ isEditing ? "Modifier l'enfant" : 'Ajouter un enfant' }}
        </h3>
        <form @submit.prevent="saveChild" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nom de l'enfant *</label>
            <input
              v-model="formData.name"
              type="text"
              required
              placeholder="Ex: Aya Mansouri"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none"
            />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Âge (ans) *</label>
              <input
                v-model.number="formData.age"
                type="number"
                min="3"
                max="15"
                required
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Statut *</label>
              <select
                v-model="formData.status"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-orange-500 focus:outline-none"
              >
                <option value="present">Présent</option>
                <option value="absent">Absent</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nom du parent *</label>
            <input
              v-model="formData.parentName"
              type="text"
              required
              placeholder="Ex: Mme Mansouri"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Téléphone parent *</label>
            <input
              v-model="formData.phone"
              type="text"
              required
              placeholder="Ex: 20 111 222"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Horaires *</label>
            <input
              v-model="formData.schedule"
              type="text"
              required
              placeholder="Ex: Lun-Ven 8h-17h"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none"
            />
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
              class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 disabled:opacity-50 transition-colors shadow-sm"
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
import { Plus, Search, Eye, Edit2, Trash2 } from 'lucide-vue-next';
import api from '../utils/api';

interface DaycareChild {
  id: number;
  name: string;
  age: number;
  parentName: string;
  phone: string;
  schedule: string;
  status: 'present' | 'absent';
}

const children = ref<DaycareChild[]>([]);
const searchTerm = ref('');
const filterStatus = ref<'all' | 'present' | 'absent'>('all');
const loading = ref(false);
const submitting = ref(false);
const errorMessage = ref('');

const selectedChild = ref<DaycareChild | null>(null);
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const formData = ref({
  name: '',
  age: 7,
  parentName: '',
  phone: '',
  schedule: 'Lun-Ven 8h-17h',
  status: 'present' as 'present' | 'absent'
});

async function loadChildren() {
  loading.value = true;
  try {
    const res = await api.get('/api/daycare');
    children.value = res.data;
  } catch (e: any) {
    console.error('Erreur chargement élèves de garde:', e);
  } finally {
    loading.value = false;
  }
}

onMounted(loadChildren);

const presentCount = computed(() => children.value.filter(c => c.status === 'present').length);
const absentCount = computed(() => children.value.filter(c => c.status === 'absent').length);

const filteredChildren = computed(() => {
  return children.value.filter((child) => {
    const matchesSearch =
      child.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      child.parentName.toLowerCase().includes(searchTerm.value.toLowerCase());
    const matchesStatus = filterStatus.value === 'all' || child.status === filterStatus.value;
    return matchesSearch && matchesStatus;
  });
});

function openAddModal() {
  isEditing.value = false;
  editingId.value = null;
  errorMessage.value = '';
  formData.value = {
    name: '',
    age: 7,
    parentName: '',
    phone: '',
    schedule: 'Lun-Ven 8h-17h',
    status: 'present'
  };
  showModal.value = true;
}

function openEditModal(child: DaycareChild) {
  isEditing.value = true;
  editingId.value = child.id;
  errorMessage.value = '';
  formData.value = {
    name: child.name,
    age: child.age,
    parentName: child.parentName,
    phone: child.phone,
    schedule: child.schedule,
    status: child.status
  };
  showModal.value = true;
}

async function saveChild() {
  submitting.value = true;
  errorMessage.value = '';
  try {
    if (isEditing.value && editingId.value) {
      const res = await api.put(`/api/daycare/${editingId.value}`, formData.value);
      const idx = children.value.findIndex(c => c.id === editingId.value);
      if (idx !== -1) {
        children.value[idx] = res.data;
      }
    } else {
      const res = await api.post('/api/daycare', formData.value);
      children.value.unshift(res.data);
    }
    showModal.value = false;
  } catch (e: any) {
    console.error('Erreur enregistrement enfant:', e);
    errorMessage.value = e.response?.data?.message || 'Erreur lors de l\'enregistrement';
  } finally {
    submitting.value = false;
  }
}

async function togglePresence(child: DaycareChild) {
  const newStatus: 'present' | 'absent' = child.status === 'present' ? 'absent' : 'present';
  const originalStatus = child.status;
  child.status = newStatus; // Optimistic update
  try {
    await api.put(`/api/daycare/${child.id}`, { status: newStatus });
  } catch (e) {
    child.status = originalStatus; // Rollback
    console.error('Erreur changement présence:', e);
    alert('Impossible de changer le statut de présence.');
  }
}

async function deleteChild(id: number) {
  if (!confirm('Voulez-vous vraiment retirer cet enfant de la garde ?')) return;
  try {
    await api.delete(`/api/daycare/${id}`);
    children.value = children.value.filter(c => c.id !== id);
    if (selectedChild.value?.id === id) {
      selectedChild.value = null;
    }
  } catch (e: any) {
    console.error('Erreur suppression:', e);
    alert('Impossible de supprimer cet enfant.');
  }
}
</script>
