<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Gestion des Paiements</h2>
        <p class="text-sm text-gray-500 mt-1">Séances des élèves par enseignant et mois de garderie</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-if="isDirector"
          @click="openExtractModal('teachers')"
          class="flex items-center gap-2 px-3.5 py-2 bg-emerald-50 text-emerald-800 font-bold rounded-lg hover:bg-emerald-100 transition-colors border border-emerald-200 text-sm shadow-xs"
          title="Exporter un extrait officiel des paiements pour les enseignants ou les élèves (PDF / Excel)"
        >
          <FileText class="w-4 h-4 text-emerald-600" />
          <span>Extraits de Paiements</span>
        </button>

        <button
          @click="openAddModal()"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
        >
          <Plus class="w-4 h-4" />
          Enregistrer un paiement
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <PaymentStats
      :totalPaid="totalPaid"
      :pendingTotal="dues.totals.pending"
      :pendingCount="dues.totals.pendingCount"
      :overdueTotal="dues.totals.overdue"
      :overdueCount="dues.totals.overdueCount"
    />

    <div v-if="loadError" class="text-xs text-red-600 bg-red-50 p-3 rounded-lg border border-red-200">
      {{ loadError }}
    </div>

    <!-- Search -->
    <div class="relative">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
      <input
        type="text"
        placeholder="Rechercher un élève, un enseignant ou un enfant de garde..."
        v-model="searchTerm"
        class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-base shadow-sm"
      />
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <template v-else>
      <!-- Onglets principaux : Soldes à régler vs Historique des paiements -->
      <div class="flex items-center gap-2 border-b border-gray-200 pb-3">
        <button
          type="button"
          @click="activeMainTab = 'dues'"
          :class="[
            'px-4 py-2 rounded-xl font-bold text-sm flex items-center gap-2 transition-all cursor-pointer',
            activeMainTab === 'dues'
              ? 'bg-indigo-600 text-white shadow-sm'
              : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
          ]"
        >
          <Clock class="w-4 h-4" />
          <span>Soldes à régler (Impayés)</span>
          <span
            :class="[
              'px-2 py-0.5 rounded-full text-xs font-bold',
              activeMainTab === 'dues' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-800'
            ]"
          >
            {{ dues.students.length + dues.daycare.length }}
          </span>
        </button>

        <button
          type="button"
          @click="activeMainTab = 'history'"
          :class="[
            'px-4 py-2 rounded-xl font-bold text-sm flex items-center gap-2 transition-all cursor-pointer',
            activeMainTab === 'history'
              ? 'bg-indigo-600 text-white shadow-sm'
              : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
          ]"
        >
          <CheckCircle class="w-4 h-4" />
          <span>Historique des paiements encaissés</span>
          <span
            :class="[
              'px-2 py-0.5 rounded-full text-xs font-bold',
              activeMainTab === 'history' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800'
            ]"
          >
            {{ paidCount }}
          </span>
        </button>
      </div>

      <!-- Soldes à régler -->
      <PaymentDuesTable
        v-if="activeMainTab === 'dues'"
        :dues="dues"
        v-model:duesKind="duesKind"
        v-model:duesFilter="duesFilter"
        :searchTerm="searchTerm"
        :isDirector="isDirector"
        @collectStudent="payload => openAddModal({ kind: 'student', ...payload })"
        @collectDaycare="id => openAddModal({ kind: 'daycare', daycareChildId: id })"
        @extractStudent="id => openExtractModal('students', id)"
      />

      <!-- Historique des paiements -->
      <PaymentHistoryTable
        v-if="activeMainTab === 'history'"
        :payments="payments"
        :searchTerm="searchTerm"
        :isDirector="isDirector"
        @extractTeacher="id => openExtractModal('teachers', id)"
        @extractStudent="id => openExtractModal('students', id)"
        @printReceipt="openReceipt"
        @edit="openEditModal"
        @delete="deletePayment"
      />
    </template>

    <!-- Add/Edit Payment Modal -->
    <PaymentFormModal
      :isOpen="showModal"
      :isEditing="isEditing"
      :editingPayment="editingPayment"
      :students="students"
      :children="children"
      :dues="dues"
      :prefill="formPrefill"
      @close="showModal = false"
      @saved="onPaymentSaved"
    />

    <!-- Printable Receipt Modal -->
    <PaymentReceiptModal
      :isOpen="showReceiptModal"
      :payment="receiptPayment"
      @close="showReceiptModal = false"
    />

    <!-- Official Payment Extract Modal (Director only) -->
    <PaymentExtractModal
      :isOpen="showExtractModal"
      :initialType="extractInitialType"
      :initialTeacherId="extractInitialTeacherId"
      :initialStudentId="extractInitialStudentId"
      @close="showExtractModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Clock, CheckCircle, Search, Plus, FileText } from 'lucide-vue-next';
import PaymentStats from './payments/PaymentStats.vue';
import PaymentDuesTable from './payments/PaymentDuesTable.vue';
import PaymentHistoryTable from './payments/PaymentHistoryTable.vue';
import PaymentFormModal from './payments/PaymentFormModal.vue';
import PaymentReceiptModal from './payments/PaymentReceiptModal.vue';
import PaymentExtractModal from './PaymentExtractModal.vue';
import { usePayments } from '../composables/usePayments';
import type { Payment, Kind } from '../types/payments';

const {
  payments,
  students,
  children,
  dues,
  loading,
  loadError,
  totalPaid,
  loadPayments,
  loadDues,
  loadData,
  deletePayment
} = usePayments();

const activeMainTab = ref<'dues' | 'history'>('dues');
const searchTerm = ref('');
const duesKind = ref<Kind>('student');
const duesFilter = ref<'all' | 'pending' | 'overdue'>('all');

const showModal = ref(false);
const isEditing = ref(false);
const editingPayment = ref<Payment | null>(null);
const formPrefill = ref<{ kind?: Kind; studentId?: number; teacherId?: number; daycareChildId?: number }>({});

// Extract modal state (Director only)
const showExtractModal = ref(false);
const extractInitialType = ref<'teachers' | 'students'>('teachers');
const extractInitialTeacherId = ref<number | null>(null);
const extractInitialStudentId = ref<number | null>(null);

// Receipt modal state
const receiptPayment = ref<Payment | null>(null);
const showReceiptModal = ref(false);

const currentUser = ref<{ role?: string; name?: string } | null>(null);
try {
  const saved = localStorage.getItem('user');
  if (saved) currentUser.value = JSON.parse(saved);
} catch {}

const isDirector = computed<boolean>(() => {
  return Boolean(currentUser.value?.role === 'director' || localStorage.getItem('user')?.includes('director'));
});

const paidCount = computed(() => {
  const term = searchTerm.value.trim().toLowerCase();
  return payments.value.filter(p => {
    if (p.status !== 'paid') return false;
    if (!term) return true;
    return (
      p.studentName.toLowerCase().includes(term) ||
      (p.month || '').toLowerCase().includes(term) ||
      (p.teacherName || '').toLowerCase().includes(term)
    );
  }).length;
});

function openExtractModal(type: 'teachers' | 'students' = 'teachers', entityId: number | null = null) {
  extractInitialType.value = type;
  if (type === 'teachers') {
    extractInitialTeacherId.value = entityId;
    extractInitialStudentId.value = null;
  } else {
    extractInitialStudentId.value = entityId;
    extractInitialTeacherId.value = null;
  }
  showExtractModal.value = true;
}

function openAddModal(
  prefill: { kind?: Kind; studentId?: number; teacherId?: number; daycareChildId?: number } = {}
) {
  isEditing.value = false;
  editingPayment.value = null;
  formPrefill.value = prefill;
  showModal.value = true;
}

function openEditModal(payment: Payment) {
  isEditing.value = true;
  editingPayment.value = payment;
  formPrefill.value = {};
  showModal.value = true;
}

function openReceipt(payment: Payment) {
  receiptPayment.value = payment;
  showReceiptModal.value = true;
}

async function onPaymentSaved() {
  await Promise.all([loadPayments(), loadDues()]);
}

onMounted(loadData);
</script>

<style>
@media print {
  body * {
    visibility: hidden;
  }
  #printable-receipt, #printable-receipt * {
    visibility: visible;
  }
  #printable-receipt {
    position: fixed;
    left: 50%;
    top: 50px;
    transform: translateX(-50%);
    width: 600px;
    max-width: 90%;
    margin: 0;
    padding: 24px;
    background: white;
    border: 2px solid #4f46e5 !important;
    border-radius: 12px;
    box-shadow: none;
    z-index: 99999;
  }
}
</style>