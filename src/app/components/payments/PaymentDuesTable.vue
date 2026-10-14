<template>
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
    <div class="px-5 py-4 border-b border-gray-200 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
      <div>
        <h3 class="text-base font-bold text-gray-900">Soldes à régler</h3>
        <p class="text-xs text-gray-500 mt-0.5">{{ rulesText }}</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="k in kinds"
          :key="k.value"
          @click="$emit('update:duesKind', k.value)"
          :class="[
            'px-3.5 py-1.5 rounded-lg text-sm font-medium transition-colors',
            duesKind === k.value
              ? 'bg-indigo-600 text-white'
              : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
          ]"
        >
          {{ k.label }} ({{ k.value === 'student' ? dues.students.length : dues.daycare.length }})
        </button>
        <span class="w-px bg-gray-200 mx-1"></span>
        <button
          v-for="f in duesFilters"
          :key="f.value"
          @click="$emit('update:duesFilter', f.value)"
          :class="[
            'px-3.5 py-1.5 rounded-lg text-sm font-medium transition-colors',
            duesFilter === f.value
              ? 'bg-gray-800 text-white'
              : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
          ]"
        >
          {{ f.label }}
        </button>
      </div>
    </div>

    <!-- Élèves -->
    <div v-if="duesKind === 'student'" class="overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead class="bg-gray-50/75 border-b border-gray-200">
          <tr>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Élève</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Enseignant</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Séances non payées</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Prix / séance</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant dû</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in filteredStudentDues" :key="row.key" class="hover:bg-gray-50/80 transition-colors">
            <td class="px-5 py-3.5">
              <p class="text-sm font-semibold text-gray-900">{{ row.studentName }}</p>
              <p v-if="row.groupName" class="text-xs text-gray-500">{{ row.groupName }}</p>
            </td>
            <td class="px-5 py-3.5">
              <p class="text-sm text-gray-900">{{ row.teacherName }}</p>
              <p class="text-xs text-gray-500">{{ row.subjects.join(', ') }}</p>
            </td>
            <td class="px-5 py-3.5">
              <p class="text-sm font-bold text-gray-900">{{ row.sessionsUnpaid }} séance(s)</p>
              <p class="text-xs text-gray-500">{{ row.sessionsAttended }} suivies · {{ row.sessionsPaid }} payées</p>
            </td>
            <td class="px-5 py-3.5 text-sm text-gray-700">
              {{ row.unitAmount > 0 ? row.unitAmount.toFixed(2) + ' TND' : 'À définir' }}
              <p v-if="row.priceSource === 'monthly'" class="text-[11px] text-gray-400">
                prix mensuel ÷ {{ dues.pricing.sessionsPerMonth }}
              </p>
              <p v-else-if="row.priceSource === 'default'" class="text-[11px] text-amber-600">
                tarif par défaut
              </p>
            </td>
            <td class="px-5 py-3.5">
              <span :class="['text-sm font-bold', row.unitAmount > 0 ? 'text-gray-900' : 'text-gray-400']">
                {{ row.unitAmount > 0 ? row.amountDue.toFixed(2) + ' TND' : 'À définir' }}
              </span>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-1.5">
                <component :is="getStatusIcon(row.status)" :class="['w-4 h-4', getStatusColor(row.status)]" />
                <span class="text-xs font-medium" :class="getStatusTextColor(row.status)">
                  {{ getStatusLabel(row.status) }}
                </span>
              </div>
            </td>
            <td class="px-5 py-3.5 text-right">
              <div class="flex items-center justify-end gap-1.5">
                <button
                  v-if="isDirector"
                  @click="$emit('extractStudent', row.studentId)"
                  class="p-1 hover:bg-indigo-50 text-indigo-600 rounded-md transition-colors"
                  title="Générer l'extrait de compte de cet élève"
                >
                  <FileText class="w-4 h-4" />
                </button>
                <button
                  @click="$emit('collectStudent', { studentId: row.studentId, teacherId: row.teacherId })"
                  class="px-2.5 py-1 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700 transition-colors shadow-xs"
                >
                  Encaisser
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="filteredStudentDues.length === 0" class="text-center py-10">
        <p class="text-gray-500 text-sm">Aucun solde à régler pour les élèves</p>
      </div>
    </div>

    <!-- Garderie -->
    <div v-else class="overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead class="bg-gray-50/75 border-b border-gray-200">
          <tr>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Enfant</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mois non payés</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tarif mensuel</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant dû</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in filteredDaycareDues" :key="row.key" class="hover:bg-gray-50/80 transition-colors">
            <td class="px-5 py-3.5">
              <p class="text-sm font-semibold text-gray-900">👶 {{ row.name }}</p>
              <p class="text-xs text-gray-500">Parent : {{ row.parentName }}</p>
            </td>
            <td class="px-5 py-3.5">
              <p class="text-sm font-bold text-gray-900">{{ row.monthsUnpaid }} mois</p>
              <p class="text-xs text-gray-500">{{ row.monthsDue }} écoulés · {{ row.monthsPaid }} payés</p>
            </td>
            <td class="px-5 py-3.5 text-sm text-gray-700">
              {{ row.monthlyFee > 0 ? row.monthlyFee.toFixed(2) + ' TND' : 'À définir' }}
              <p v-if="row.feeSource === 'default'" class="text-[11px] text-amber-600">tarif par défaut</p>
            </td>
            <td class="px-5 py-3.5">
              <span :class="['text-sm font-bold', row.monthlyFee > 0 ? 'text-gray-900' : 'text-gray-400']">
                {{ row.monthlyFee > 0 ? row.amountDue.toFixed(2) + ' TND' : 'À définir' }}
              </span>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-1.5">
                <component :is="getStatusIcon(row.status)" :class="['w-4 h-4', getStatusColor(row.status)]" />
                <span class="text-xs font-medium" :class="getStatusTextColor(row.status)">
                  {{ getStatusLabel(row.status) }}
                </span>
              </div>
            </td>
            <td class="px-5 py-3.5 text-right">
              <button
                @click="$emit('collectDaycare', row.daycareChildId)"
                class="px-2.5 py-1 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700 transition-colors shadow-xs"
              >
                Encaisser
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="filteredDaycareDues.length === 0" class="text-center py-10">
        <p class="text-gray-500 text-sm">Aucun solde à régler pour la garderie</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Clock, AlertCircle, CheckCircle, FileText } from 'lucide-vue-next';
import type { Dues, Kind } from '../../types/payments';

const props = defineProps<{
  dues: Dues;
  duesKind: Kind;
  duesFilter: 'all' | 'pending' | 'overdue';
  searchTerm: string;
  isDirector?: boolean;
}>();

defineEmits<{
  (e: 'update:duesKind', val: Kind): void;
  (e: 'update:duesFilter', val: 'all' | 'pending' | 'overdue'): void;
  (e: 'collectStudent', val: { studentId: number; teacherId: number }): void;
  (e: 'collectDaycare', val: number): void;
  (e: 'extractStudent', val: number): void;
}>();

const kinds: Array<{ value: Kind; label: string }> = [
  { value: 'student', label: 'Élèves' },
  { value: 'daycare', label: 'Garderie' }
];

const duesFilters: Array<{ value: 'all' | 'pending' | 'overdue'; label: string }> = [
  { value: 'all', label: 'Tous' },
  { value: 'pending', label: 'En attente' },
  { value: 'overdue', label: 'En retard' }
];

const rulesText = computed(() => {
  const t = props.dues.thresholds;
  if (props.duesKind === 'student') {
    return `En attente : de ${t.studentPendingFrom} à ${t.studentOverdueAfter} séances non payées avec un même enseignant. En retard : plus de ${t.studentOverdueAfter} séances.`;
  }
  return `En attente : de ${t.daycarePendingFrom} à ${t.daycareOverdueAfter} mois non payés. En retard : plus de ${t.daycareOverdueAfter} mois.`;
});

const term = computed(() => props.searchTerm.trim().toLowerCase());

const filteredStudentDues = computed(() =>
  props.dues.students.filter(r => {
    const matchesSearch =
      !term.value ||
      r.studentName.toLowerCase().includes(term.value) ||
      r.teacherName.toLowerCase().includes(term.value);
    const matchesStatus = props.duesFilter === 'all' || r.status === props.duesFilter;
    return matchesSearch && matchesStatus;
  })
);

const filteredDaycareDues = computed(() =>
  props.dues.daycare.filter(r => {
    const matchesSearch =
      !term.value ||
      r.name.toLowerCase().includes(term.value) ||
      r.parentName.toLowerCase().includes(term.value);
    const matchesStatus = props.duesFilter === 'all' || r.status === props.duesFilter;
    return matchesSearch && matchesStatus;
  })
);

function getStatusLabel(status: string) {
  switch (status) {
    case 'paid': return 'Payé';
    case 'pending': return 'En attente';
    case 'overdue': return 'En retard';
    case 'ok': return 'En cours';
    default: return status;
  }
}

function getStatusIcon(status: string) {
  switch (status) {
    case 'paid': return CheckCircle;
    case 'overdue': return AlertCircle;
    default: return Clock;
  }
}

function getStatusColor(status: string) {
  switch (status) {
    case 'paid': return 'text-green-600';
    case 'pending': return 'text-yellow-600';
    case 'overdue': return 'text-red-600';
    default: return 'text-gray-400';
  }
}

function getStatusTextColor(status: string) {
  switch (status) {
    case 'paid': return 'text-green-700';
    case 'pending': return 'text-yellow-700';
    case 'overdue': return 'text-red-700';
    default: return 'text-gray-500';
  }
}
</script>
