<script setup lang="ts">
defineProps<{
  teachersList: any[];
  kpis: any;
  totalGross: number;
  totalPaid: number;
  totalRemaining: number;
}>();
</script>

<template>
  <div class="space-y-6">
    <div class="avoid-break text-center py-2.5 bg-gray-50 rounded-xl border border-gray-200">
      <h2 class="text-sm font-black uppercase tracking-wider text-gray-900">ÉTATS CERTIFIÉS DES HONORAIRES & RÉMUNÉRATIONS DES ENSEIGNANTS</h2>
      <p class="text-xs text-gray-600 mt-0.5">
        Récapitulatif des cours, quotas de séances, montants versés et soldes d'honoraires par professeur
      </p>
    </div>

    <!-- KPIs Enseignants -->
    <div class="avoid-break grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
      <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
        <span class="text-gray-500 uppercase text-[10px] tracking-wider font-semibold">CA Cours Encaissé</span>
        <p class="text-lg font-black text-gray-900 mt-0.5">{{ totalGross.toFixed(2) }} DT</p>
        <p class="text-[10px] text-gray-500 mt-0.5">{{ teachersList.length }} enseignants</p>
      </div>
      <div class="p-3 bg-indigo-50/70 rounded-xl border border-indigo-200">
        <span class="text-indigo-700 uppercase text-[10px] tracking-wider font-semibold">Total Honoraires Dûs</span>
        <p class="text-lg font-black text-indigo-900 mt-0.5">{{ Number(kpis?.totalTeacherPayouts || 0).toFixed(2) }} DT</p>
        <p class="text-[10px] text-indigo-600 mt-0.5">Rémunération totale</p>
      </div>
      <div class="p-3 bg-emerald-50/70 rounded-xl border border-emerald-200">
        <span class="text-emerald-700 uppercase text-[10px] tracking-wider font-semibold">Déjà Versé</span>
        <p class="text-lg font-black text-emerald-700 mt-0.5">{{ totalPaid.toFixed(2) }} DT</p>
        <p class="text-[10px] text-emerald-600 mt-0.5">Règlements effectués</p>
      </div>
      <div class="p-3 bg-red-50/70 rounded-xl border border-red-200">
        <span class="text-red-700 uppercase text-[10px] tracking-wider font-semibold">Reste à Régler</span>
        <p class="text-lg font-black text-red-600 mt-0.5">{{ totalRemaining.toFixed(2) }} DT</p>
        <p class="text-[10px] text-red-600 mt-0.5">Soldes en attente</p>
      </div>
    </div>

    <!-- Tableau Honoraires Enseignants -->
    <div class="avoid-break">
      <table class="w-full text-left text-xs border border-gray-200 rounded-xl overflow-hidden whitespace-nowrap">
        <thead class="bg-gray-100 font-semibold text-gray-700 border-b border-gray-200">
          <tr>
            <th class="py-2.5 px-3">Enseignant</th>
            <th class="py-2.5 px-3">Matière</th>
            <th class="py-2.5 px-3">Formule Appliquée</th>
            <th class="py-2.5 px-2 text-center">Séances</th>
            <th class="py-2.5 px-3 text-right">CA Encaissé</th>
            <th class="py-2.5 px-3 text-right font-bold text-indigo-900">Part Due (DT)</th>
            <th class="py-2.5 px-3 text-right text-emerald-700">Déjà Versé</th>
            <th class="py-2.5 px-3 text-right font-bold">Reste à Payer</th>
            <th class="py-2.5 px-3 text-center">Statut</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="t in teachersList" :key="t.id" class="avoid-break hover:bg-gray-50/50">
            <td class="py-2 px-3 font-bold text-gray-900">{{ t.name }}</td>
            <td class="py-2 px-3 text-gray-600">{{ t.subject || 'Général' }}</td>
            <td class="py-2 px-3 text-gray-600">
              <span v-if="t.remunerationType === 'percentage'" class="text-indigo-600 font-medium">Formule C ({{ t.ratePercentage || 70 }}%)</span>
              <span v-else-if="t.remunerationType === 'fixed_center'" class="text-emerald-700 font-medium">Formule A (Idara {{ t.centerFixedPerStudent || 20 }} DT/él)</span>
              <span v-else class="text-purple-700 font-medium">Formule B (Fixe {{ t.fixedStudentRate || 15 }} DT/él)</span>
            </td>
            <td class="py-2 px-2 text-center font-bold text-gray-800">{{ t.sessionsCount }}</td>
            <td class="py-2 px-3 text-right text-gray-600">{{ Number(t.revenueCollected || 0).toFixed(2) }} DT</td>
            <td class="py-2 px-3 text-right font-black text-indigo-900">{{ Number(t.teacherShare || 0).toFixed(2) }} DT</td>
            <td class="py-2 px-3 text-right text-emerald-700 font-semibold">{{ Number(t.paidAmount || 0).toFixed(2) }} DT</td>
            <td class="py-2 px-3 text-right font-black" :class="t.remainingToPay > 0 ? 'text-red-600' : 'text-gray-400'">
              {{ Number(t.remainingToPay || 0).toFixed(2) }} DT
            </td>
            <td class="py-2 px-3 text-center">
              <span
                class="px-2 py-0.5 rounded text-[10px] font-bold"
                :class="t.payoutStatus === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
              >
                {{ t.payoutStatus === 'paid' ? 'Réglé' : 'En attente' }}
              </span>
            </td>
          </tr>
          <tr v-if="teachersList.length === 0">
            <td colspan="9" class="py-8 text-center text-gray-400">Aucun enseignant enregistré sur cette période</td>
          </tr>
        </tbody>
        <tfoot v-if="teachersList.length > 0" class="bg-gray-100 font-bold border-t-2 border-gray-300">
          <tr>
            <td colspan="5" class="py-2.5 px-3 text-right uppercase text-[11px] text-gray-700">Totaux des Honoraires :</td>
            <td class="py-2.5 px-3 text-right font-black text-indigo-900 text-sm">{{ Number(kpis?.totalTeacherPayouts || 0).toFixed(2) }} DT</td>
            <td class="py-2.5 px-3 text-right text-emerald-700 text-sm">{{ totalPaid.toFixed(2) }} DT</td>
            <td class="py-2.5 px-3 text-right font-black text-red-600 text-sm">{{ totalRemaining.toFixed(2) }} DT</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>
