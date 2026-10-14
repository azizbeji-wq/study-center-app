<script setup lang="ts">
import { Lock, Unlock, Printer, Download } from 'lucide-vue-next';
import { formatDateTime } from '../../composables/useFinancialFormatters';

defineProps<{
  summaryData: any;
  kpis: any;
  selectedMonth: string;
}>();

const emit = defineEmits<{
  (e: 'closeMonth'): void;
  (e: 'reopenMonth'): void;
  (e: 'openExpertPdf', type: 'receipts' | 'expenses' | 'teachers'): void;
  (e: 'exportCsv', type: 'payments' | 'expenses' | 'teachers'): void;
}>();
</script>

<template>
  <div class="space-y-6">
    <!-- Carte Clôture du Mois Actuel -->
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100">
        <div>
          <div class="flex items-center gap-2">
            <h3 class="text-base font-bold text-gray-900">Clôture Comptable : Mois {{ selectedMonth }}</h3>
            <span
              :class="[
                'px-3 py-0.5 rounded-full text-xs font-bold border',
                summaryData?.monthlyClosing?.isClosed
                  ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                  : 'bg-amber-50 text-amber-800 border-amber-200'
              ]"
            >
              {{ summaryData?.monthlyClosing?.isClosed ? '✓ Mois Clôturé & Figé' : 'Mois Actuellement Ouvert' }}
            </span>
          </div>
          <p class="text-xs text-gray-500 mt-1">
            La clôture mensuelle valide et fige définitivement les chiffres du mois pour la comptabilité générale.
          </p>
        </div>

        <!-- Bouton Clôturer / Réouvrir -->
        <div>
          <button
            v-if="!summaryData?.monthlyClosing?.isClosed"
            @click="emit('closeMonth')"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
          >
            <Lock class="w-4 h-4" />
            <span>Clôturer et Figer ce Mois</span>
          </button>

          <button
            v-else
            @click="emit('reopenMonth')"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-xl text-xs font-bold transition-colors cursor-pointer"
          >
            <Unlock class="w-4 h-4" />
            <span>Réouvrir pour Régularisation</span>
          </button>
        </div>
      </div>

      <!-- Détail de la clôture active -->
      <div v-if="summaryData?.monthlyClosing?.isClosed" class="grid grid-cols-1 sm:grid-cols-4 gap-4 mt-4 pt-2 text-xs">
        <div class="p-3 bg-gray-50 rounded-xl">
          <span class="text-gray-500">Date de clôture :</span>
          <p class="font-bold text-gray-900 mt-0.5">{{ formatDateTime(summaryData.monthlyClosing.closedAt) }}</p>
        </div>
        <div class="p-3 bg-gray-50 rounded-xl">
          <span class="text-gray-500">Recettes figées :</span>
          <p class="font-bold text-gray-900 mt-0.5">{{ (kpis?.totalRevenue || 0).toLocaleString('fr-FR') }} DT</p>
        </div>
        <div class="p-3 bg-gray-50 rounded-xl">
          <span class="text-gray-500">Dépenses figées :</span>
          <p class="font-bold text-red-600 mt-0.5">{{ (kpis?.totalExpenses || 0).toLocaleString('fr-FR') }} DT</p>
        </div>
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
          <span class="text-emerald-800 font-semibold">Bénéfice Net Figé :</span>
          <p class="font-black text-emerald-950 text-sm mt-0.5">{{ (kpis?.netProfit || 0).toLocaleString('fr-FR') }} DT</p>
        </div>
      </div>
    </div>

    <!-- Section Téléchargement & Exports Comptables -->
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs space-y-4">
      <h3 class="text-base font-bold text-gray-900">Exports pour l'Expert-Comptable</h3>
      <p class="text-xs text-gray-500">Générez les fichiers certifiés aux formats compatibles Excel, CSV et PDF :</p>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
        <!-- Carte 1 : Grand Livre des Recettes -->
        <div class="p-4 border border-gray-200 rounded-xl hover:border-indigo-300 transition-colors bg-gray-50/50 flex flex-col justify-between">
          <div>
            <h4 class="font-bold text-gray-900 text-sm">Grand Livre des Recettes</h4>
            <p class="text-xs text-gray-500 mt-1">Détail exhaustif de chaque paiement élève et garderie avec reçu et mode de règlement.</p>
          </div>
          <div class="mt-4 flex flex-col sm:flex-row items-stretch gap-2">
            <button
              @click="emit('openExpertPdf', 'receipts')"
              class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
              title="Générer et exporter le Grand Livre des Recettes au format PDF officiel"
            >
              <Printer class="w-3.5 h-3.5" />
              <span>Exporter PDF (A4)</span>
            </button>
            <button
              @click="emit('exportCsv', 'payments')"
              class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
              title="Exporter au format Excel / CSV"
            >
              <Download class="w-3.5 h-3.5" />
              <span>CSV</span>
            </button>
          </div>
        </div>

        <!-- Carte 2 : Journal des Dépenses -->
        <div class="p-4 border border-gray-200 rounded-xl hover:border-indigo-300 transition-colors bg-gray-50/50 flex flex-col justify-between">
          <div>
            <h4 class="font-bold text-gray-900 text-sm">Journal des Dépenses</h4>
            <p class="text-xs text-gray-500 mt-1">Liste complète des factures, loyers, salaires annexes et justificatifs comptables.</p>
          </div>
          <div class="mt-4 flex flex-col sm:flex-row items-stretch gap-2">
            <button
              @click="emit('openExpertPdf', 'expenses')"
              class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
              title="Générer et exporter le Journal des Dépenses au format PDF officiel"
            >
              <Printer class="w-3.5 h-3.5" />
              <span>Exporter PDF (A4)</span>
            </button>
            <button
              @click="emit('exportCsv', 'expenses')"
              class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
              title="Exporter au format Excel / CSV"
            >
              <Download class="w-3.5 h-3.5" />
              <span>CSV</span>
            </button>
          </div>
        </div>

        <!-- Carte 3 : États des Honoraires Professeurs -->
        <div class="p-4 border border-gray-200 rounded-xl hover:border-indigo-300 transition-colors bg-gray-50/50 flex flex-col justify-between">
          <div>
            <h4 class="font-bold text-gray-900 text-sm">États des Honoraires Professeurs</h4>
            <p class="text-xs text-gray-500 mt-1">Récapitulatif des cours, quotas de séances, montants versés et soldes d'honoraires.</p>
          </div>
          <div class="mt-4 flex flex-col sm:flex-row items-stretch gap-2">
            <button
              @click="emit('openExpertPdf', 'teachers')"
              class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
              title="Générer et exporter les Honoraires des Enseignants au format PDF officiel"
            >
              <Printer class="w-3.5 h-3.5" />
              <span>Exporter PDF (A4)</span>
            </button>
            <button
              @click="emit('exportCsv', 'teachers')"
              class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
              title="Exporter au format Excel / CSV"
            >
              <Download class="w-3.5 h-3.5" />
              <span>CSV</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
