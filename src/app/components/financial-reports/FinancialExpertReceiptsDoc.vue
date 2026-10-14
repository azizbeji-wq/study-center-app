<script setup lang="ts">
defineProps<{
  paymentsList: any[];
  totalAmount: number;
  totalStudents: number;
  totalDaycare: number;
  totalCash: number;
}>();
</script>

<template>
  <div class="space-y-6">
    <div class="avoid-break text-center py-2.5 bg-gray-50 rounded-xl border border-gray-200">
      <h2 class="text-sm font-black uppercase tracking-wider text-gray-900">GRAND LIVRE GÉNÉRAL DES RECETTES & ENCAISSEMENTS</h2>
      <p class="text-xs text-gray-600 mt-0.5">
        Relevé exhaustif des règlements perçus (cours de soutien, élèves et garderie scolaire)
      </p>
    </div>

    <!-- KPIs Recettes -->
    <div class="avoid-break grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
      <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
        <span class="text-gray-500 uppercase text-[10px] tracking-wider font-semibold">Total Encaissé</span>
        <p class="text-lg font-black text-emerald-700 mt-0.5">{{ totalAmount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
        <p class="text-[10px] text-gray-500 mt-0.5">{{ paymentsList.length }} transactions</p>
      </div>
      <div class="p-3 bg-blue-50/70 rounded-xl border border-blue-200">
        <span class="text-blue-700 uppercase text-[10px] tracking-wider font-semibold">Part Élèves (Cours)</span>
        <p class="text-lg font-black text-blue-900 mt-0.5">{{ totalStudents.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
        <p class="text-[10px] text-blue-600 mt-0.5">Soutien scolaire</p>
      </div>
      <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-200">
        <span class="text-amber-700 uppercase text-[10px] tracking-wider font-semibold">Part Garderie</span>
        <p class="text-lg font-black text-amber-900 mt-0.5">{{ totalDaycare.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
        <p class="text-[10px] text-amber-600 mt-0.5">Services de garde</p>
      </div>
      <div class="p-3 bg-indigo-50/70 rounded-xl border border-indigo-200">
        <span class="text-indigo-700 uppercase text-[10px] tracking-wider font-semibold">Règlements Espèces</span>
        <p class="text-lg font-black text-indigo-900 mt-0.5">{{ totalCash.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
        <p class="text-[10px] text-indigo-600 mt-0.5">Caisse directe</p>
      </div>
    </div>

    <!-- Tableau Grand Livre Recettes -->
    <div class="avoid-break">
      <table class="w-full text-left text-xs border border-gray-200 rounded-xl overflow-hidden whitespace-nowrap">
        <thead class="bg-gray-100 font-semibold text-gray-700 border-b border-gray-200">
          <tr>
            <th class="py-2.5 px-3">Date</th>
            <th class="py-2.5 px-3">Réf / Reçu</th>
            <th class="py-2.5 px-3">Élève / Enfant</th>
            <th class="py-2.5 px-3">Mois</th>
            <th class="py-2.5 px-3">Type</th>
            <th class="py-2.5 px-3">Mode Règlement</th>
            <th class="py-2.5 px-3 text-right font-bold">Montant (DT)</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="p in paymentsList" :key="p.id" class="avoid-break hover:bg-gray-50/50">
            <td class="py-2 px-3 text-gray-700">{{ p.date || p.created_at?.substring(0, 10) || '—' }}</td>
            <td class="py-2 px-3 font-mono text-[11px] text-gray-500">#REC-{{ String(p.id).padStart(5, '0') }}</td>
            <td class="py-2 px-3 font-bold text-gray-900">{{ p.student_name || 'Élève inconnu' }}</td>
            <td class="py-2 px-3 text-gray-600">{{ p.month || '—' }}</td>
            <td class="py-2 px-3">
              <span
                class="px-2 py-0.5 rounded text-[10px] font-bold"
                :class="p.daycare_child_id || p.is_garderie ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'"
              >
                {{ p.daycare_child_id || p.is_garderie ? 'Garderie' : (p.is_primaire ? 'Primaire' : (p.is_secondaire ? 'Secondaire' : 'Cours')) }}
              </span>
            </td>
            <td class="py-2 px-3 text-gray-600 capitalize">{{ p.payment_method || 'Espèces' }}</td>
            <td class="py-2 px-3 text-right font-black text-gray-900">{{ Number(p.amount || 0).toFixed(2) }} DT</td>
          </tr>
          <tr v-if="paymentsList.length === 0">
            <td colspan="7" class="py-8 text-center text-gray-400">Aucun encaissement enregistré sur cette période</td>
          </tr>
        </tbody>
        <tfoot v-if="paymentsList.length > 0" class="bg-gray-100 font-bold border-t-2 border-gray-300">
          <tr>
            <td colspan="6" class="py-2.5 px-3 text-right uppercase text-[11px] text-gray-700">Total Général des Recettes :</td>
            <td class="py-2.5 px-3 text-right font-black text-emerald-700 text-sm">{{ totalAmount.toFixed(2) }} DT</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>
