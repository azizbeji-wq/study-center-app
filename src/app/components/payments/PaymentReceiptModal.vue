<template>
  <div v-if="isOpen && payment" class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click="$emit('close')">
    <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8 max-w-lg w-full max-h-[90vh] overflow-y-auto mx-4 md:mx-auto" @click.stop>
      <!-- Cadre Reçu -->
      <div id="printable-receipt" class="border-2 border-dashed border-indigo-200 rounded-xl p-6 bg-slate-50/50">
        <!-- En-tête -->
        <div class="flex items-center justify-between border-b border-gray-200 pb-4 mb-4">
          <div class="flex items-center gap-3">
            <img src="/logo.png" alt="Logo" class="w-12 h-12 object-contain" />
            <div>
              <h2 class="text-xl font-black text-indigo-900 tracking-tight">CENTRE D'ÉTUDES</h2>
              <p class="text-xs text-gray-500 font-medium">Soutien Scolaire & Garderie</p>
            </div>
          </div>
          <div class="text-right">
            <span class="inline-block px-2.5 py-1 bg-green-100 text-green-800 font-bold text-xs rounded-full uppercase tracking-wider">
              Reçu N° {{ payment.id.toString().padStart(5, '0') }}
            </span>
            <p class="text-[11px] text-gray-500 mt-1">Date: {{ payment.date || today() }}</p>
          </div>
        </div>

        <!-- Corps du reçu -->
        <div class="space-y-3 text-sm">
          <div class="flex justify-between py-1 border-b border-gray-100">
            <span class="text-gray-500">Bénéficiaire :</span>
            <span class="font-bold text-gray-900">{{ payment.studentName }}</span>
          </div>
          <div class="flex justify-between py-1 border-b border-gray-100">
            <span class="text-gray-500">Prestation / Détail :</span>
            <span class="font-semibold text-gray-800">{{ paymentDetail(payment) }}</span>
          </div>
          <div class="flex justify-between py-1 border-b border-gray-100">
            <span class="text-gray-500">Période concernée :</span>
            <span class="text-gray-800">{{ payment.month }}</span>
          </div>
          <div class="flex justify-between py-1 border-b border-gray-100">
            <span class="text-gray-500">Quantité réglée :</span>
            <span class="font-semibold text-indigo-700">
              {{ payment.daycareChildId ? payment.monthsPaid + ' mois de garderie' : payment.sessionsPaid + ' séance(s)' }}
            </span>
          </div>

          <!-- Cadre Montant -->
          <div class="mt-4 p-4 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-between">
            <span class="text-sm font-bold text-indigo-950 uppercase">Montant Réglé :</span>
            <span class="text-2xl font-black text-indigo-700">{{ Number(payment.amount).toFixed(2) }} TND</span>
          </div>

          <!-- Signatures -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-6 text-xs text-center">
            <div>
              <p class="text-gray-400 italic">Signature du parent</p>
              <div class="h-10 border-b border-gray-300 mt-2"></div>
            </div>
            <div>
              <p class="text-gray-400 italic">Cachet & Direction</p>
              <div class="h-10 border-b border-gray-300 mt-2 flex items-center justify-center">
                <span class="text-[10px] text-green-700 font-bold border border-green-500 px-2 py-0.5 rounded uppercase">
                  ✓ RÉGLÉ
                </span>
              </div>
              <p class="text-[10px] text-gray-500 font-medium mt-1">
                Encaissé par : {{ payment.createdByName || 'Direction' }} ({{ payment.createdByRole === 'secretary' ? 'Personnel' : 'Direction' }})
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Boutons d'action -->
      <div class="flex items-center justify-end gap-3 mt-6 print:hidden">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors"
        >
          Fermer
        </button>
        <button
          @click="printReceipt"
          class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 shadow-md transition-colors"
        >
          <Printer class="w-4 h-4" />
          Imprimer le reçu
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Printer } from 'lucide-vue-next';
import type { Payment } from '../../types/payments';
import { usePayments } from '../../composables/usePayments';

defineProps<{
  isOpen: boolean;
  payment: Payment | null;
}>();

defineEmits<{
  (e: 'close'): void;
}>();

const { paymentDetail } = usePayments();
const today = () => new Date().toISOString().split('T')[0];

function printReceipt() {
  window.print();
}
</script>
