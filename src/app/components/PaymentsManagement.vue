<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Gestion des Paiements</h2>
        <p class="text-sm text-gray-500 mt-1">Suivi des frais mensuels et règlements</p>
      </div>
      <button
        @click="openAddModal"
        class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
      >
        <Plus class="w-4 h-4" />
        Ajouter un paiement
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-green-50/80 border border-green-200 rounded-xl p-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
            <DollarSign class="w-5 h-5 text-green-600" />
          </div>
          <div>
            <p class="text-xs font-semibold text-green-700 uppercase">Total Payé</p>
            <p class="text-2xl font-bold text-green-900">{{ totalPaid.toFixed(2) }} TND</p>
          </div>
        </div>
      </div>
      <div class="bg-yellow-50/80 border border-yellow-200 rounded-xl p-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
            <Clock class="w-5 h-5 text-yellow-600" />
          </div>
          <div>
            <p class="text-xs font-semibold text-yellow-700 uppercase">En Attente</p>
            <p class="text-2xl font-bold text-yellow-900">{{ totalPending.toFixed(2) }} TND</p>
          </div>
        </div>
      </div>
      <div class="bg-red-50/80 border border-red-200 rounded-xl p-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
            <AlertCircle class="w-5 h-5 text-red-600" />
          </div>
          <div>
            <p class="text-xs font-semibold text-red-700 uppercase">En Retard</p>
            <p class="text-2xl font-bold text-red-900">{{ totalOverdue.toFixed(2) }} TND</p>
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
          placeholder="Rechercher par élève ou mois..."
          v-model="searchTerm"
          class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm shadow-sm"
        />
      </div>
      <div class="flex gap-2">
        <button
          v-for="status in (['all', 'paid', 'pending', 'overdue'] as const)"
          :key="status"
          @click="filterStatus = status"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-xs',
            filterStatus === status
              ? 'bg-indigo-600 text-white'
              : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
          ]"
        >
          {{ status === 'all' ? 'Tous' : getStatusLabel(status) }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Payments Table -->
    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
      <table class="w-full">
        <thead class="bg-gray-50/75 border-b border-gray-200">
          <tr>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Élève</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mois</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
            <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="payment in filteredPayments" :key="payment.id" class="hover:bg-gray-50/80 transition-colors">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ payment.studentName }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ payment.month }}</td>
            <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ Number(payment.amount).toFixed(2) }} TND</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ payment.date || '-' }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-1.5">
                <component :is="getStatusIcon(payment.status)" :class="['w-4 h-4', getStatusColor(payment.status)]" />
                <span class="text-xs font-medium" :class="getStatusTextColor(payment.status)">
                  {{ getStatusLabel(payment.status) }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  v-if="payment.status !== 'paid'"
                  @click="markAsPaid(payment)"
                  class="px-2.5 py-1 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700 transition-colors shadow-xs"
                  title="Marquer comme payé aujourd'hui"
                >
                  Marquer payé
                </button>
                <button
                  @click="openEditModal(payment)"
                  class="p-1.5 hover:bg-gray-100 text-gray-600 hover:text-blue-600 rounded-lg transition-colors"
                  title="Modifier"
                >
                  <Edit2 class="w-4 h-4" />
                </button>
                <button
                  @click="deletePayment(payment.id)"
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

      <div v-if="filteredPayments.length === 0" class="text-center py-12">
        <p class="text-gray-500 text-sm">Aucun paiement trouvé</p>
      </div>
    </div>

    <!-- Add/Edit Payment Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click="showModal = false">
      <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full" @click.stop>
        <h3 class="text-xl font-bold text-gray-900 mb-4">
          {{ isEditing ? 'Modifier le paiement' : 'Ajouter un paiement' }}
        </h3>
        <form @submit.prevent="savePayment" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nom de l'élève *</label>
            <input
              v-model="formData.studentName"
              type="text"
              required
              placeholder="Ex: Ahmed Ben Ali"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Mois de règlement *</label>
              <input
                v-model="formData.month"
                type="text"
                required
                placeholder="Ex: Octobre 2026"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Montant (TND) *</label>
              <input
                v-model.number="formData.amount"
                type="number"
                step="0.01"
                min="0"
                required
                placeholder="Ex: 150"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Date de paiement</label>
              <input
                v-model="formData.date"
                type="date"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Statut *</label>
              <select
                v-model="formData.status"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              >
                <option value="paid">Payé</option>
                <option value="pending">En attente</option>
                <option value="overdue">En retard</option>
              </select>
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
import { DollarSign, Clock, AlertCircle, CheckCircle, Search, Plus, Edit2, Trash2 } from 'lucide-vue-next';
import api from '../utils/api';

interface Payment {
  id: number;
  studentName: string;
  month: string;
  amount: number;
  date: string | null;
  status: 'paid' | 'pending' | 'overdue';
}

const payments = ref<Payment[]>([]);
const searchTerm = ref('');
const filterStatus = ref<'all' | 'paid' | 'pending' | 'overdue'>('all');
const loading = ref(false);
const submitting = ref(false);
const errorMessage = ref('');

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const formData = ref({
  studentName: '',
  month: 'Octobre 2026',
  amount: 150,
  date: new Date().toISOString().split('T')[0],
  status: 'paid' as 'paid' | 'pending' | 'overdue'
});

async function loadPayments() {
  loading.value = true;
  try {
    const res = await api.get('/api/payments');
    payments.value = res.data;
  } catch (e: any) {
    console.error('Erreur chargement paiements:', e);
  } finally {
    loading.value = false;
  }
}

onMounted(loadPayments);

const totalPaid = computed(() =>
  payments.value
    .filter((p) => p.status === 'paid')
    .reduce((sum, p) => sum + Number(p.amount), 0)
);

const totalPending = computed(() =>
  payments.value
    .filter((p) => p.status === 'pending')
    .reduce((sum, p) => sum + Number(p.amount), 0)
);

const totalOverdue = computed(() =>
  payments.value
    .filter((p) => p.status === 'overdue')
    .reduce((sum, p) => sum + Number(p.amount), 0)
);

const filteredPayments = computed(() => {
  return payments.value.filter((payment) => {
    const matchesSearch =
      payment.studentName.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      payment.month.toLowerCase().includes(searchTerm.value.toLowerCase());
    const matchesStatus = filterStatus.value === 'all' || payment.status === filterStatus.value;
    return matchesSearch && matchesStatus;
  });
});

function getStatusLabel(status: string) {
  switch (status) {
    case 'paid': return 'Payé';
    case 'pending': return 'En attente';
    case 'overdue': return 'En retard';
    default: return status;
  }
}

function getStatusIcon(status: string) {
  switch (status) {
    case 'paid': return CheckCircle;
    case 'pending': return Clock;
    case 'overdue': return AlertCircle;
    default: return Clock;
  }
}

function getStatusColor(status: string) {
  switch (status) {
    case 'paid': return 'text-green-600';
    case 'pending': return 'text-yellow-600';
    case 'overdue': return 'text-red-600';
    default: return 'text-gray-600';
  }
}

function getStatusTextColor(status: string) {
  switch (status) {
    case 'paid': return 'text-green-700';
    case 'pending': return 'text-yellow-700';
    case 'overdue': return 'text-red-700';
    default: return 'text-gray-700';
  }
}

function openAddModal() {
  isEditing.value = false;
  editingId.value = null;
  errorMessage.value = '';
  formData.value = {
    studentName: '',
    month: 'Octobre 2026',
    amount: 150,
    date: new Date().toISOString().split('T')[0],
    status: 'paid'
  };
  showModal.value = true;
}

function openEditModal(payment: Payment) {
  isEditing.value = true;
  editingId.value = payment.id;
  errorMessage.value = '';
  formData.value = {
    studentName: payment.studentName,
    month: payment.month,
    amount: Number(payment.amount),
    date: payment.date || '',
    status: payment.status
  };
  showModal.value = true;
}

async function savePayment() {
  submitting.value = true;
  errorMessage.value = '';
  try {
    if (isEditing.value && editingId.value) {
      const res = await api.put(`/api/payments/${editingId.value}`, formData.value);
      const idx = payments.value.findIndex(p => p.id === editingId.value);
      if (idx !== -1) {
        payments.value[idx] = res.data;
      }
    } else {
      const res = await api.post('/api/payments', formData.value);
      payments.value.unshift(res.data);
    }
    showModal.value = false;
  } catch (e: any) {
    console.error('Erreur sauvegarde paiement:', e);
    errorMessage.value = e.response?.data?.message || 'Erreur lors de la sauvegarde';
  } finally {
    submitting.value = false;
  }
}

async function markAsPaid(payment: Payment) {
  const today = new Date().toISOString().split('T')[0];
  const oldStatus = payment.status;
  const oldDate = payment.date;
  payment.status = 'paid';
  payment.date = today;
  try {
    await api.put(`/api/payments/${payment.id}`, { status: 'paid', date: today });
  } catch (e) {
    payment.status = oldStatus;
    payment.date = oldDate;
    console.error('Erreur validation paiement:', e);
    alert('Impossible de marquer ce paiement comme payé.');
  }
}

async function deletePayment(id: number) {
  if (!confirm('Voulez-vous vraiment supprimer cet enregistrement de paiement ?')) return;
  try {
    await api.delete(`/api/payments/${id}`);
    payments.value = payments.value.filter(p => p.id !== id);
  } catch (e) {
    console.error('Erreur suppression paiement:', e);
    alert('Impossible de supprimer ce paiement.');
  }
}
</script>
