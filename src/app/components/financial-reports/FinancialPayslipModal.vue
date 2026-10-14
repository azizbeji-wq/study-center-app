<script setup lang="ts">
import { FileText, Printer, X } from 'lucide-vue-next';
import { printIsolatedElement } from '../../utils/printHelper';

const props = defineProps<{
  isOpen: boolean;
  payslip: any;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
}>();

function handlePrint() {
  const docTitle = `Fiche de Paie - ${props.payslip?.teacher?.name || 'Enseignant'}`;
  printIsolatedElement('payslip-printable', docTitle);
}
</script>

<template>
  <div
    v-if="isOpen && payslip"
    @click.self="emit('close')"
    class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs p-3 sm:p-6 flex justify-center items-start"
  >
    <div class="bg-white rounded-2xl border border-gray-200 shadow-2xl w-full max-w-2xl my-2 sm:my-6 overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95">
      <!-- Actions barre (Sticky Top) -->
      <div class="p-4 bg-gray-900 text-white flex items-center justify-between no-print print:hidden shrink-0 shadow-sm z-20">
        <div class="flex items-center gap-2">
          <FileText class="w-4 h-4 text-indigo-400" />
          <span class="text-xs sm:text-sm font-bold">Fiche de Paie & Décharge d'Honoraires</span>
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="handlePrint"
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer"
          >
            <Printer class="w-3.5 h-3.5" />
            <span>Imprimer</span>
          </button>
          <button
            @click="emit('close')"
            class="text-gray-400 hover:text-white p-1 rounded-lg hover:bg-gray-800 transition-colors cursor-pointer"
            title="Fermer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Corps Déroulant Imprimable -->
      <div class="flex-1 overflow-y-auto p-4 sm:p-8 bg-gray-50/50 print:p-0 print:bg-white print:overflow-visible">
        <div id="payslip-printable" class="p-6 sm:p-8 space-y-6 text-gray-900 bg-white rounded-xl border border-gray-200 print:border-none print:shadow-none print:p-0">
          <!-- En-tête Centre d'études -->
          <div class="avoid-break flex items-start justify-between border-b-2 border-gray-900 pb-5">
            <div>
              <h1 class="text-xl font-black tracking-tight text-gray-900">CENTRE D'ÉTUDES & SOUTIEN SCOLAIRE</h1>
              <p class="text-xs text-gray-500 mt-0.5">Administration Générale & Direction Financière</p>
              <p class="text-xs text-gray-500">Tunis, Tunisie • Tél: +216 71 000 000</p>
            </div>
            <div class="text-right">
              <span class="text-xs font-mono px-2.5 py-1 bg-gray-100 rounded-lg font-bold border border-gray-300">
                {{ payslip.payslipNumber }}
              </span>
              <p class="text-xs text-gray-500 mt-2">Date: {{ payslip.generatedAt }}</p>
            </div>
          </div>

          <!-- Titre du document -->
          <div class="avoid-break text-center py-2 bg-gray-50 rounded-xl border border-gray-200">
            <h2 class="text-base font-black uppercase tracking-wider text-gray-900">FICHE DE DÉCHARGE D'HONORAIRES</h2>
            <p class="text-xs text-gray-600 font-medium">Période : {{ payslip.monthLabel }}</p>
          </div>

          <!-- Informations Enseignant -->
          <div class="avoid-break grid grid-cols-1 md:grid-cols-2 gap-4 text-xs bg-gray-50/70 p-4 rounded-xl border border-gray-200">
            <div>
              <p class="text-gray-500 uppercase text-[10px] tracking-wider font-semibold">Bénéficiaire</p>
              <p class="text-sm font-black text-gray-900 mt-0.5">{{ payslip.teacher.name }}</p>
              <p class="text-gray-600 mt-0.5">Matière : <span class="font-bold text-gray-900">{{ payslip.teacher.subject || 'Général' }}</span></p>
              <p class="text-gray-600">Tél : {{ payslip.teacher.phone || '—' }}</p>
            </div>
            <div class="text-right">
              <p class="text-gray-500 uppercase text-[10px] tracking-wider font-semibold">Formule Contractuelle</p>
              <p class="font-bold text-gray-900 mt-0.5">
                <template v-if="payslip.teacher.remunerationType === 'percentage'">
                  Formule C : {{ payslip.teacher.ratePercentage || 70 }}% sur tarif brut
                </template>
                <template v-else-if="payslip.teacher.remunerationType === 'fixed_center'">
                  Formule A : Part Idara {{ payslip.teacher.centerFixedPerStudent || 20 }} DT/élève
                </template>
                <template v-else>
                  Formule B : {{ payslip.teacher.fixedStudentRate || 15 }} DT fixe par élève
                </template>
              </p>
              <p class="text-gray-600 mt-1">Séances validées ce mois : <span class="font-bold text-gray-900">{{ payslip.summary.totalSessions }}</span></p>
            </div>
          </div>

          <!-- Détail des séances & élèves -->
          <div class="avoid-break">
            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Détail des séances encaissées</h3>
            <table class="w-full text-left text-xs border border-gray-200 rounded-xl overflow-hidden whitespace-nowrap">
              <thead class="bg-gray-100 font-semibold text-gray-700 border-b border-gray-200">
                <tr>
                  <th class="py-2 px-3">Date</th>
                  <th class="py-2 px-3">Élève</th>
                  <th class="py-2 px-2 text-center">Séances</th>
                  <th class="py-2 px-3 text-right">C.A. Élève</th>
                  <th class="py-2 px-3 text-right">Part Professeur</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in payslip.items" :key="item.paymentId" class="avoid-break">
                  <td class="py-2 px-3 text-gray-600 font-mono">{{ item.date }}</td>
                  <td class="py-2 px-3 font-semibold text-gray-900">{{ item.studentName }}</td>
                  <td class="py-2 px-2 text-center font-bold">{{ item.sessions }}</td>
                  <td class="py-2 px-3 text-right text-gray-700">{{ Number(item.amountPaid).toFixed(2) }} DT</td>
                  <td class="py-2 px-3 text-right font-black text-gray-900">{{ Number(item.teacherShare).toFixed(2) }} DT</td>
                </tr>
                <tr v-if="payslip.items.length === 0">
                  <td colspan="5" class="py-4 text-center text-gray-400">Aucune séance encaissée pour ce mois.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Totalisation Financière -->
          <div class="avoid-break border-t-2 border-gray-900 pt-4 flex flex-col items-end text-xs space-y-1">
            <div class="w-64 flex justify-between py-1 border-b border-gray-200">
              <span class="text-gray-600">Total Brut Dû :</span>
              <span class="font-bold text-gray-900">{{ Number(payslip.summary.grossDue || 0).toFixed(2) }} DT</span>
            </div>
            <div class="w-64 flex justify-between py-1 border-b border-gray-200">
              <span class="text-gray-600">Acomptes Déjà Versés :</span>
              <span class="font-bold text-emerald-700">− {{ Number(payslip.summary.advancesPaid || 0).toFixed(2) }} DT</span>
            </div>
            <div class="w-64 flex justify-between py-2 text-sm font-black bg-gray-100 px-3 rounded-xl border border-gray-300">
              <span class="text-gray-900">NET À RÉGLER :</span>
              <span class="text-indigo-950 font-black text-base">{{ Number(payslip.summary.netPayable || 0).toFixed(2) }} DT</span>
            </div>
          </div>

          <!-- Signatures et Décharge -->
          <div class="avoid-break grid grid-cols-1 md:grid-cols-2 gap-8 pt-8 border-t border-gray-200 text-xs">
            <div class="space-y-12">
              <p class="font-bold text-gray-900">Visa & Cachet de la Direction :</p>
              <div class="h-14 border-b border-dashed border-gray-400"></div>
            </div>
            <div class="space-y-12 text-right">
              <p class="font-bold text-gray-900">Signature de l'Enseignant (Pour décharge) :</p>
              <div class="h-14 border-b border-dashed border-gray-400"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
