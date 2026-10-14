<template>
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
    <div class="px-5 py-4 border-b border-gray-200">
      <h3 class="text-base font-bold text-gray-900">Historique des paiements</h3>
      <p class="text-xs text-gray-500 mt-0.5">Paiements encaissés uniquement (avec indicateur de ponctualité)</p>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead class="bg-gray-50/75 border-b border-gray-200">
          <tr>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Payeur</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Détail</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mois</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Encaissé par</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut & Échéance</th>
            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="payment in filteredPayments" :key="payment.id" class="hover:bg-gray-50/80 transition-colors">
            <td class="px-5 py-3.5">
              <p class="text-sm font-medium text-gray-900">{{ payment.studentName }}</p>
              <span
                :class="[
                  'inline-flex px-2 py-0.5 rounded text-[11px] font-semibold',
                  payment.daycareChildId ? 'bg-orange-100 text-orange-800' : 'bg-indigo-50 text-indigo-700'
                ]"
              >
                {{ payment.daycareChildId ? 'Garderie' : 'Élève' }}
              </span>
            </td>
            <td class="px-5 py-3.5 text-sm text-gray-600">{{ paymentDetail(payment) }}</td>
            <td class="px-5 py-3.5 text-sm text-gray-600">{{ payment.month }}</td>
            <td class="px-5 py-3.5 text-sm font-semibold text-gray-900">{{ Number(payment.amount).toFixed(2) }} TND</td>
            <td class="px-5 py-3.5 text-sm text-gray-600">{{ payment.date || '-' }}</td>
            <td class="px-5 py-3.5">
              <span
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold"
                :class="payment.createdByRole === 'secretary' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200'"
              >
                <span>{{ payment.createdByName || 'Directeur Fondateur' }}</span>
                <span class="text-[9px] font-medium opacity-75">({{ payment.createdByRole === 'secretary' ? 'Personnel' : 'Directeur' }})</span>
              </span>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex flex-col items-start gap-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                  <CheckCircle class="w-3.5 h-3.5 text-emerald-600" />
                  <span>Payé</span>
                </span>
                <span
                  :class="[
                    'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border',
                    getPaymentPunctuality(payment).badgeClass
                  ]"
                >
                  {{ getPaymentPunctuality(payment).label }}
                </span>
              </div>
            </td>
            <td class="px-5 py-3.5 text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  v-if="isDirector && (payment.studentId || payment.teacherId)"
                  @click="payment.teacherId ? $emit('extractTeacher', payment.teacherId) : $emit('extractStudent', payment.studentId!)"
                  class="p-1.5 hover:bg-emerald-50 text-gray-600 hover:text-emerald-600 rounded-lg transition-colors"
                  :title="payment.teacherId ? 'Extrait de paiement de cet enseignant' : 'Extrait de paiement de cet élève'"
                >
                  <FileText class="w-4 h-4" />
                </button>
                <button
                  @click="$emit('printReceipt', payment)"
                  class="p-1.5 hover:bg-indigo-50 text-gray-600 hover:text-indigo-600 rounded-lg transition-colors"
                  title="Imprimer le reçu"
                >
                  <Printer class="w-4 h-4" />
                </button>
                <button
                  @click="$emit('edit', payment)"
                  class="p-1.5 hover:bg-gray-100 text-gray-600 hover:text-blue-600 rounded-lg transition-colors"
                  title="Modifier"
                >
                  <Edit2 class="w-4 h-4" />
                </button>
                <button
                  @click="$emit('delete', payment.id)"
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
      <div v-if="filteredPayments.length === 0" class="text-center py-10">
        <p class="text-gray-500 text-sm">Aucun paiement trouvé</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { CheckCircle, FileText, Printer, Edit2, Trash2 } from 'lucide-vue-next';
import type { Payment } from '../../types/payments';
import { usePayments } from '../../composables/usePayments';

const props = defineProps<{
  payments: Payment[];
  searchTerm: string;
  isDirector?: boolean;
}>();

defineEmits<{
  (e: 'extractTeacher', teacherId: number): void;
  (e: 'extractStudent', studentId: number): void;
  (e: 'printReceipt', payment: Payment): void;
  (e: 'edit', payment: Payment): void;
  (e: 'delete', paymentId: number): void;
}>();

const { paymentDetail, getPaymentPunctuality } = usePayments();

const term = computed(() => props.searchTerm.trim().toLowerCase());

const filteredPayments = computed(() =>
  props.payments.filter(p => {
    if (p.status !== 'paid') return false;
    if (!term.value) return true;
    return (
      p.studentName.toLowerCase().includes(term.value) ||
      (p.month || '').toLowerCase().includes(term.value) ||
      (p.teacherName || '').toLowerCase().includes(term.value)
    );
  })
);
</script>
