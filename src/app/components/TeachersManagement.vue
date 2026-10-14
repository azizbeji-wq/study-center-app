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
          v-if="isDirector"
          @click="openExtractModal()"
          class="flex items-center gap-2 px-3.5 py-2 bg-indigo-50 text-indigo-700 font-semibold rounded-lg hover:bg-indigo-100 transition-colors border border-indigo-200 text-sm shadow-xs cursor-pointer"
          title="Exporter un extrait officiel des paiements pour les enseignants (PDF / Excel)"
        >
          <FileText class="w-4 h-4 text-indigo-600" />
          <span>Extraits de Paiements</span>
        </button>

        <button
          v-if="isDirector"
          @click="showDirectorSharesModal = true"
          class="flex items-center gap-2 px-3.5 py-2 bg-emerald-50 text-emerald-700 font-semibold rounded-lg hover:bg-emerald-100 transition-colors border border-emerald-200 text-sm shadow-xs cursor-pointer"
          title="Consulter le récapitulatif détaillé des parts professeurs"
        >
          <DollarSign class="w-4 h-4 text-emerald-600" />
          <span>Bilan des Parts</span>
        </button>

        <button
          @click="openAddModal"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          Ajouter un enseignant
        </button>
      </div>
    </div>

    <!-- Director Financial Summary Banner -->
    <TeacherFinancialBanner
      v-if="isDirector"
      :totalGrossRevenue="totalGrossRevenue"
      :totalTeachersShare="totalTeachersShare"
      :totalCenterShare="totalCenterShare"
      :avgTeacherShare="avgTeacherShare"
      :teachersCount="teachers.length"
      @openSharesModal="showDirectorSharesModal = true"
    />

    <!-- Search and Director Payout Filters -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
        <input
          type="text"
          placeholder="Rechercher par nom ou matière..."
          v-model="searchTerm"
          class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-base shadow-sm"
        />
      </div>

      <!-- Director Payout Status Filter -->
      <div v-if="isDirector" class="inline-flex rounded-xl bg-gray-200/80 p-1 border border-gray-300/60 shrink-0">
        <button
          type="button"
          @click="payoutFilter = 'all'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
            payoutFilter === 'all'
              ? 'bg-white text-indigo-950 shadow-xs'
              : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          Tous ({{ teachers.length }})
        </button>
        <button
          type="button"
          @click="payoutFilter = 'paid'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1 cursor-pointer',
            payoutFilter === 'paid'
              ? 'bg-emerald-600 text-white shadow-xs'
              : 'text-emerald-800 hover:text-emerald-950'
          ]"
        >
          <Check class="w-3.5 h-3.5" />
          <span>A pris son argent ({{ paidTeachersCount }})</span>
        </button>
        <button
          type="button"
          @click="payoutFilter = 'pending'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1 cursor-pointer',
            payoutFilter === 'pending'
              ? 'bg-amber-500 text-white shadow-xs'
              : 'text-amber-800 hover:text-amber-950'
          ]"
        >
          <Clock class="w-3.5 h-3.5" />
          <span>En attente ({{ pendingTeachersCount }})</span>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Teachers Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <TeacherCard
        v-for="teacher in filteredTeachers"
        :key="teacher.id"
        :teacher="teacher"
        :isDirector="isDirector"
        :isTogglingPayout="togglingPayoutId === teacher.id"
        @extract="openExtractModal"
        @edit="openEditModal"
        @delete="deleteTeacher"
        @togglePayout="togglePayoutStatus"
        @openDischarge="openDischargeReceipt"
      />
    </div>

    <div v-if="!loading && filteredTeachers.length === 0" class="text-center py-12">
      <p class="text-gray-500 text-sm">Aucun enseignant trouvé</p>
    </div>

    <!-- Add/Edit Teacher Modal -->
    <TeacherFormModal
      :isOpen="showModal"
      :isEditing="isEditing"
      :editingTeacher="editingTeacher"
      :currentUser="currentUser"
      @close="showModal = false"
      @saved="onTeacherSaved"
    />

    <!-- Director Detailed Shares Breakdown Modal -->
    <TeacherSharesModal
      :isOpen="showDirectorSharesModal"
      :teachers="teachers"
      :togglingPayoutId="togglingPayoutId"
      :totalStudentsCount="totalStudentsCount"
      :totalMonthlySessions="totalMonthlySessions"
      :totalGrossRevenue="totalGrossRevenue"
      :totalTeachersShare="totalTeachersShare"
      :totalCenterShare="totalCenterShare"
      :paidTeachersCount="paidTeachersCount"
      @close="showDirectorSharesModal = false"
      @togglePayout="togglePayoutStatus"
    />

    <!-- Official Teacher Payout Discharge Receipt Modal -->
    <TeacherDischargeReceiptModal
      :isOpen="showDischargeModal"
      :teacher="dischargeTeacher"
      @close="showDischargeModal = false"
    />

    <!-- Official Payment Extract Modal (Director only) -->
    <PaymentExtractModal
      :isOpen="showExtractModal"
      initialType="teachers"
      :initialTeacherId="selectedTeacherExtractId"
      @close="showExtractModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Plus, Search, DollarSign, FileText, Check, Clock } from 'lucide-vue-next';
import TeacherFinancialBanner from './teachers/TeacherFinancialBanner.vue';
import TeacherCard from './teachers/TeacherCard.vue';
import TeacherFormModal from './teachers/TeacherFormModal.vue';
import TeacherSharesModal from './teachers/TeacherSharesModal.vue';
import TeacherDischargeReceiptModal from './teachers/TeacherDischargeReceiptModal.vue';
import PaymentExtractModal from './PaymentExtractModal.vue';
import { useTeachers } from '../composables/useTeachers';
import type { Teacher } from '../types/teachers';

const {
  teachers,
  searchTerm,
  payoutFilter,
  loading,
  togglingPayoutId,
  currentUser,
  isDirector,
  totalGrossRevenue,
  totalTeachersShare,
  totalCenterShare,
  avgTeacherShare,
  totalStudentsCount,
  totalMonthlySessions,
  paidTeachersCount,
  pendingTeachersCount,
  filteredTeachers,
  loadTeachers,
  togglePayoutStatus,
  deleteTeacher
} = useTeachers();

const showModal = ref(false);
const showDirectorSharesModal = ref(false);
const showExtractModal = ref(false);
const selectedTeacherExtractId = ref<number | null>(null);
const isEditing = ref(false);
const editingTeacher = ref<Teacher | null>(null);

const dischargeTeacher = ref<Teacher | null>(null);
const showDischargeModal = ref(false);

function openExtractModal(teacherId: number | null = null) {
  selectedTeacherExtractId.value = teacherId;
  showExtractModal.value = true;
}

function openAddModal() {
  isEditing.value = false;
  editingTeacher.value = null;
  showModal.value = true;
}

function openEditModal(teacher: Teacher) {
  isEditing.value = true;
  editingTeacher.value = teacher;
  showModal.value = true;
}

function onTeacherSaved(teacher: Teacher) {
  const idx = teachers.value.findIndex(t => t.id === teacher.id);
  if (idx !== -1) {
    teachers.value[idx] = teacher;
  } else {
    teachers.value.push(teacher);
  }
}

function openDischargeReceipt(teacher: Teacher) {
  dischargeTeacher.value = teacher;
  showDischargeModal.value = true;
}

onMounted(loadTeachers);
</script>

<style>
@media print {
  body * {
    visibility: hidden;
  }
  #printable-teacher-discharge, #printable-teacher-discharge * {
    visibility: visible;
  }
  #printable-teacher-discharge {
    position: fixed;
    left: 50%;
    top: 40px;
    transform: translateX(-50%);
    width: 650px;
    max-width: 95%;
    margin: 0;
    padding: 24px;
    background: white;
    border: 2px solid #059669 !important;
    border-radius: 16px;
    box-shadow: none;
    z-index: 99999;
  }
}
</style>
