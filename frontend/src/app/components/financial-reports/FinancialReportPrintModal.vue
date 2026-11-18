<script setup lang="ts">
import { BarChart3, Printer, X } from 'lucide-vue-next';
import { printIsolatedElement } from '../../utils/printHelper';

const props = defineProps<{
  isOpen: boolean;
  summaryData: any;
  kpis: any;
  selectedMonth: string;
  selectedPeriod: string;
  selectedSchoolYear: string;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
}>();

function handlePrint() {
  const docTitle = `Bilan Comptable & Financier - ${props.selectedMonth || 'Annuel'}`;
  printIsolatedElement('financial-report-printable', docTitle);
}
</script>

<template>
  <div
    v-if="isOpen"
    @click.self="emit('close')"
    class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs p-3 sm:p-6 flex justify-center items-start"
  >
    <div class="bg-white rounded-2xl border border-gray-200 shadow-2xl w-full max-w-4xl my-2 sm:my-6 overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95">
      <!-- Actions barre (Sticky Top) -->
      <div class="p-4 bg-gray-900 text-white flex items-center justify-between no-print print:hidden shrink-0 shadow-sm z-20">
        <div class="flex items-center gap-2">
          <BarChart3 class="w-4 h-4 text-indigo-400" />
          <span class="text-xs sm:text-sm font-bold">Bilan Comptable & Financier Officiel</span>
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="handlePrint"
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer"
          >
            <Printer class="w-3.5 h-3.5" />
            <span>Imprimer / PDF</span>
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
        <div id="financial-report-printable" class="p-6 sm:p-8 space-y-6 text-gray-900 bg-white rounded-xl border border-gray-200 print:border-none print:shadow-none print:p-0">
          <!-- En-tête Centre d'études -->
          <div class="avoid-break flex items-start justify-between border-b-2 border-gray-900 pb-5">
            <div>
              <h1 class="text-xl font-black tracking-tight text-gray-900">CENTRE D'ÉTUDES & SOUTIEN SCOLAIRE</h1>
              <p class="text-xs text-gray-500 mt-0.5">Direction Financière & Comptabilité Générale</p>
              <p class="text-xs text-gray-500">Tunis, Tunisie • Document Certifié d'Exploitation</p>
            </div>
            <div class="text-right">
              <span class="text-xs font-mono px-2.5 py-1 bg-gray-100 rounded-lg font-bold border border-gray-300">
                RAPPORT-{{ selectedMonth || 'ANNUEL' }}
              </span>
              <p class="text-xs text-gray-500 mt-2">Édité le: {{ new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }) }}</p>
              <p class="text-xs text-gray-500">Période: <span class="font-bold text-gray-900">{{ selectedPeriod === 'school_year' ? `Année Scolaire ${selectedSchoolYear}` : (selectedPeriod === 'current_month' ? `Mois ${selectedMonth}` : '12 derniers mois') }}</span></p>
            </div>
          </div>

          <!-- Titre du document -->
          <div class="avoid-break text-center py-2.5 bg-gray-50 rounded-xl border border-gray-200">
            <h2 class="text-sm font-black uppercase tracking-wider text-gray-900">SYNTHÈSE COMPTABLE & BILAN DE GESTION</h2>
            <p class="text-xs text-gray-600 mt-0.5">
              État réel des encaissements, dépenses d'exploitation, honoraires et marge nette
            </p>
          </div>

          <!-- Grille des 4 Grands Indicateurs -->
          <div class="avoid-break grid grid-cols-4 gap-3 text-xs">
            <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
              <span class="text-gray-500 uppercase text-[10px] tracking-wider font-semibold">Recettes Totales</span>
              <p class="text-lg font-black text-gray-900 mt-0.5">{{ (kpis?.totalRevenue || 0).toLocaleString('fr-FR') }} DT</p>
              <p class="text-[10px] text-gray-500 mt-0.5">Élèves & Garderie</p>
            </div>

            <div class="p-3 bg-red-50/70 rounded-xl border border-red-200">
              <span class="text-red-700 uppercase text-[10px] tracking-wider font-semibold">Total Dépenses</span>
              <p class="text-lg font-black text-red-600 mt-0.5">{{ (kpis?.totalExpenses || 0).toLocaleString('fr-FR') }} DT</p>
              <p class="text-[10px] text-red-600 mt-0.5">Charges d'exploitation</p>
            </div>

            <div class="p-3 bg-indigo-50/70 rounded-xl border border-indigo-200">
              <span class="text-indigo-700 uppercase text-[10px] tracking-wider font-semibold">Parts Enseignants</span>
              <p class="text-lg font-black text-indigo-900 mt-0.5">{{ (kpis?.totalTeacherPayouts || 0).toLocaleString('fr-FR') }} DT</p>
              <p class="text-[10px] text-indigo-600 mt-0.5">Honoraires sur séances</p>
            </div>

            <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-300">
              <span class="text-emerald-800 uppercase text-[10px] tracking-wider font-semibold">Bénéfice Net</span>
              <p class="text-lg font-black text-emerald-950 mt-0.5">{{ (kpis?.netProfit || 0).toLocaleString('fr-FR') }} DT</p>
              <p class="text-[10px] text-emerald-700 mt-0.5">Marge nette dégagée</p>
            </div>
          </div>

          <!-- Évolution Mensuelle Récapitulative -->
          <div class="avoid-break">
            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">1. Évolution Mensuelle Réelle</h3>
            <table class="w-full text-left text-xs border border-gray-200 rounded-xl overflow-hidden whitespace-nowrap">
              <thead class="bg-gray-100 font-semibold text-gray-700 border-b border-gray-200">
                <tr>
                  <th class="py-2 px-3">Mois</th>
                  <th class="py-2 px-3 text-right">Élèves</th>
                  <th class="py-2 px-3 text-right">Garderie</th>
                  <th class="py-2 px-3 text-right font-bold">Total Recettes</th>
                  <th class="py-2 px-3 text-right text-red-600">Dépenses</th>
                  <th class="py-2 px-3 text-right text-indigo-700">Parts Profs</th>
                  <th class="py-2 px-3 text-right font-black text-emerald-700">Bénéfice Net</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="m in summaryData?.monthsTrend || []" :key="m.month" class="avoid-break">
                  <td class="py-2 px-3 font-semibold text-gray-900">{{ m.label }}</td>
                  <td class="py-2 px-3 text-right text-gray-600">{{ Number(m.students).toFixed(2) }} DT</td>
                  <td class="py-2 px-3 text-right text-gray-600">{{ Number(m.daycare).toFixed(2) }} DT</td>
                  <td class="py-2 px-3 text-right font-bold text-gray-900">{{ Number(m.totalRevenue).toFixed(2) }} DT</td>
                  <td class="py-2 px-3 text-right text-red-600 font-medium">{{ Number(m.expenses).toFixed(2) }} DT</td>
                  <td class="py-2 px-3 text-right text-indigo-700 font-medium">{{ Number(m.teacherPayouts).toFixed(2) }} DT</td>
                  <td class="py-2 px-3 text-right font-black" :class="m.netProfit >= 0 ? 'text-emerald-700' : 'text-red-600'">
                    {{ Number(m.netProfit).toFixed(2) }} DT
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Ventilation des Dépenses par Catégorie -->
          <div class="avoid-break">
            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">2. Répartition des Dépenses par Poste</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
              <div
                v-for="cat in summaryData?.expenseDonut || []"
                :key="cat.category"
                class="flex items-center justify-between p-2 bg-gray-50 rounded-lg border border-gray-100 avoid-break"
              >
                <span class="text-gray-700 font-medium">{{ cat.label }} ({{ cat.count }})</span>
                <span class="font-bold text-gray-900">{{ Number(cat.amount).toFixed(2) }} DT ({{ cat.percentage }}%)</span>
              </div>
            </div>
          </div>

          <!-- Synthèse Honoraires Enseignants -->
          <div class="avoid-break">
            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">3. Honoraires & Parts des Enseignants</h3>
            <table class="w-full text-left text-xs border border-gray-200 rounded-xl overflow-hidden whitespace-nowrap">
              <thead class="bg-gray-100 font-semibold text-gray-700 border-b border-gray-200">
                <tr>
                  <th class="py-2 px-3">Enseignant</th>
                  <th class="py-2 px-3">Matière</th>
                  <th class="py-2 px-2 text-center">Séances</th>
                  <th class="py-2 px-3 text-right">C.A. Encaissé</th>
                  <th class="py-2 px-3 text-right font-bold">Part Due</th>
                  <th class="py-2 px-3 text-right text-emerald-700">Déjà Payé</th>
                  <th class="py-2 px-3 text-right text-red-600 font-bold">Reste à Régler</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="t in summaryData?.byTeacher || []" :key="t.id" class="avoid-break">
                  <td class="py-2 px-3 font-semibold text-gray-900">{{ t.name }}</td>
                  <td class="py-2 px-3 text-gray-600">{{ t.subject || 'Général' }}</td>
                  <td class="py-2 px-2 text-center font-bold">{{ t.sessionsCount }}</td>
                  <td class="py-2 px-3 text-right text-gray-600">{{ Number(t.revenueCollected).toFixed(2) }} DT</td>
                  <td class="py-2 px-3 text-right font-bold text-indigo-900">{{ Number(t.teacherShare).toFixed(2) }} DT</td>
                  <td class="py-2 px-3 text-right text-emerald-700">{{ Number(t.paidAmount).toFixed(2) }} DT</td>
                  <td class="py-2 px-3 text-right font-black" :class="t.remainingToPay > 0 ? 'text-red-600' : 'text-gray-400'">
                    {{ Number(t.remainingToPay).toFixed(2) }} DT
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Visa et Certification -->
          <div class="avoid-break grid grid-cols-1 md:grid-cols-2 gap-8 pt-6 border-t-2 border-gray-900 text-xs">
            <div>
              <p class="font-bold text-gray-900">Visa & Rapprochement Comptable :</p>
              <p class="text-[10px] text-gray-500 mt-1">Conforme au grand livre et aux journaux de caisse.</p>
              <div class="h-14 border-b border-dashed border-gray-400 mt-4"></div>
            </div>
            <div class="text-right">
              <p class="font-bold text-gray-900">Cachet & Signature de la Direction :</p>
              <p class="text-[10px] text-gray-500 mt-1">Document certifié pour bilan d'exploitation.</p>
              <div class="h-14 border-b border-dashed border-gray-400 mt-4"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
