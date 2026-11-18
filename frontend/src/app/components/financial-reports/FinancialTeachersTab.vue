<script setup lang="ts">
import { FileText } from 'lucide-vue-next';

defineProps<{
  summaryData: any;
  kpis: any;
  totalTeachersGross: number;
  totalCenterTeacherMargin: number;
}>();

const emit = defineEmits<{
  (e: 'openPayslip', teacherId: number): void;
  (e: 'togglePayout', teacher: any): void;
}>();
</script>

<template>
  <div class="space-y-6">
    <!-- KPI Enseignants -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">C.A. Brut Enseignants</span>
        <p class="text-2xl font-black text-gray-900 mt-1">{{ totalTeachersGross.toLocaleString('fr-FR') }} DT</p>
        <p class="text-[11px] text-gray-500 mt-0.5">Revenus encaissés générés par les cours</p>
      </div>

      <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 shadow-xs">
        <span class="text-xs font-semibold text-indigo-800 uppercase tracking-wider">Total Parts Professeurs</span>
        <p class="text-2xl font-black text-indigo-950 mt-1">{{ (kpis?.totalTeacherPayouts || 0).toLocaleString('fr-FR') }} DT</p>
        <p class="text-[11px] text-indigo-700 mt-0.5">Calculé selon la formule exacte de chaque enseignant</p>
      </div>

      <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 shadow-xs">
        <span class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">Marge Centre Nette</span>
        <p class="text-2xl font-black text-emerald-950 mt-1">{{ totalCenterTeacherMargin.toLocaleString('fr-FR') }} DT</p>
        <p class="text-[11px] text-emerald-700 mt-0.5">Différence brute conservée par l'établissement</p>
      </div>
    </div>

    <!-- Tableau de Suivi « Dû, Payé, Reste à Payer » -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-xs overflow-hidden">
      <div class="p-4 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-bold text-gray-900">Suivi des Honoraires & Fiches de Paie</h3>
          <p class="text-xs text-gray-500 mt-0.5">Séances réelles dispensées, montants dus, acomptes versés et reste à payer</p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-700 rounded-full">
          {{ (summaryData?.byTeacher || []).length }} Enseignants
        </span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs whitespace-nowrap">
          <thead class="bg-gray-50 text-gray-600 uppercase font-semibold border-b border-gray-200">
            <tr>
              <th class="py-3 px-4">Enseignant</th>
              <th class="py-3 px-3">Matière</th>
              <th class="py-3 px-3 text-center">Formule Rémunération</th>
              <th class="py-3 px-3 text-center">Séances Payées</th>
              <th class="py-3 px-3 text-right">C.A. Encaissé</th>
              <th class="py-3 px-4 text-right bg-indigo-50 text-indigo-900 font-bold">Part Due</th>
              <th class="py-3 px-4 text-right text-emerald-700 font-bold">Déjà Versé</th>
              <th class="py-3 px-4 text-right text-red-600 font-bold">Reste à Payer</th>
              <th class="py-3 px-3 text-center">Statut Versement</th>
              <th class="py-3 px-4 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr v-for="t in summaryData?.byTeacher || []" :key="t.id" class="hover:bg-gray-50/80 transition-colors">
              <td class="py-3 px-4 font-bold text-gray-900">
                {{ t.name }}
                <span class="block text-[11px] text-gray-400 font-normal">{{ t.phone || 'Sans tel' }}</span>
              </td>
              <td class="py-3 px-3 font-medium text-gray-700">
                {{ t.subject || 'Général' }}
              </td>
              <td class="py-3 px-3 text-center">
                <span
                  :class="[
                    'px-2 py-0.5 rounded-full text-[10px] font-bold border',
                    t.remunerationType === 'percentage'
                      ? 'bg-purple-50 text-purple-700 border-purple-200'
                      : t.remunerationType === 'fixed_center'
                        ? 'bg-indigo-50 text-indigo-700 border-indigo-200'
                        : 'bg-blue-50 text-blue-700 border-blue-200'
                  ]"
                >
                  {{ t.remunerationType === 'percentage'
                    ? `Formule C : ${t.ratePercentage || 70}% du C.A.`
                    : t.remunerationType === 'fixed_center'
                      ? `Formule A : Idara ${t.centerFixedPerStudent || 20} DT/él`
                      : `Formule B : ${t.fixedStudentRate || 15} DT/él` }}
                </span>
              </td>
              <td class="py-3 px-3 text-center font-bold text-gray-800">
                {{ t.sessionsCount }}
              </td>
              <td class="py-3 px-3 text-right font-medium text-gray-700">
                {{ Number(t.revenueCollected || 0).toLocaleString('fr-FR') }} DT
              </td>
              <td class="py-3 px-4 text-right font-black text-indigo-900 bg-indigo-50/50 text-sm">
                {{ Number(t.teacherShare || 0).toLocaleString('fr-FR') }} DT
              </td>
              <td class="py-3 px-4 text-right font-bold text-emerald-700">
                {{ Number(t.paidAmount || 0).toLocaleString('fr-FR') }} DT
              </td>
              <td class="py-3 px-4 text-right font-black" :class="t.remainingToPay > 0 ? 'text-red-600' : 'text-gray-400'">
                {{ Number(t.remainingToPay || 0).toLocaleString('fr-FR') }} DT
              </td>
              <td class="py-3 px-3 text-center">
                <span
                  :class="[
                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold inline-block border',
                    (t.payoutStatus === 'paid' || t.payoutStatus === 'taken')
                      ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                      : 'bg-amber-50 text-amber-700 border-amber-200'
                  ]"
                >
                  {{ (t.payoutStatus === 'paid' || t.payoutStatus === 'taken') ? 'Payé' : 'En attente' }}
                </span>
              </td>
              <td class="py-3 px-4 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <button
                    @click="emit('openPayslip', t.id)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-xs font-semibold transition-colors cursor-pointer"
                    title="Fiche de paie imprimable"
                  >
                    <FileText class="w-3.5 h-3.5" />
                    <span>Fiche Paie</span>
                  </button>

                  <button
                    @click="emit('togglePayout', t)"
                    :class="[
                      'px-2 py-1 rounded-lg text-xs font-semibold transition-colors border cursor-pointer',
                      (t.payoutStatus === 'paid' || t.payoutStatus === 'taken')
                        ? 'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200'
                        : 'bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700'
                    ]"
                    :title="(t.payoutStatus === 'paid' || t.payoutStatus === 'taken') ? 'Remettre en attente' : 'Marquer comme payé'"
                  >
                    {{ (t.payoutStatus === 'paid' || t.payoutStatus === 'taken') ? 'Annuler' : 'Régler' }}
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!summaryData?.byTeacher?.length">
              <td colspan="10" class="py-8 text-center text-gray-400">
                Aucun enseignant répertorié.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
