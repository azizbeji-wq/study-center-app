<script setup lang="ts">
import { MessageCircle } from 'lucide-vue-next';

defineProps<{
  summaryData: any;
  kpis: any;
}>();
</script>

<template>
  <div class="space-y-6">
    <!-- Soldes dus élèves & garderie -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-red-50/80 border border-red-200 rounded-2xl p-4 shadow-xs">
        <span class="text-xs font-semibold text-red-800 uppercase tracking-wider">Total Reste à Encaisser</span>
        <p class="text-2xl font-black text-red-950 mt-1">{{ (kpis?.totalUnpaid || 0).toLocaleString('fr-FR') }} DT</p>
        <p class="text-[11px] text-red-700 mt-0.5">Soldes non réglés cumulés</p>
      </div>

      <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Impayés Élèves (Cours)</span>
        <p class="text-2xl font-black text-gray-900 mt-1">{{ (kpis?.unpaidStudents || 0).toLocaleString('fr-FR') }} DT</p>
        <p class="text-[11px] text-gray-500 mt-0.5">Séances suivies non payées</p>
      </div>

      <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Impayés Garderie</span>
        <p class="text-2xl font-black text-gray-900 mt-1">{{ (kpis?.unpaidDaycare || 0).toLocaleString('fr-FR') }} DT</p>
        <p class="text-[11px] text-gray-500 mt-0.5">Mois de garde en retard</p>
      </div>
    </div>

    <!-- Tableau des Plus Gros Retards avec bouton WhatsApp -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-xs overflow-hidden">
      <div class="p-4 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-bold text-gray-900">Les Plus Gros Retards & Relances Immédiates</h3>
          <p class="text-xs text-gray-500 mt-0.5">Un clic sur l'icône WhatsApp ouvre un message personnalisé et pré-rempli vers le parent</p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 bg-amber-50 text-amber-800 rounded-full border border-amber-200">
          Action Recouvrement
        </span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs whitespace-nowrap">
          <thead class="bg-gray-50 text-gray-600 uppercase font-semibold border-b border-gray-200">
            <tr>
              <th class="py-3 px-4">Élève / Enfant</th>
              <th class="py-3 px-3">Service</th>
              <th class="py-3 px-3">Parent / Contact</th>
              <th class="py-3 px-3 text-center">Retard</th>
              <th class="py-3 px-4 text-right">Montant Dû</th>
              <th class="py-3 px-3 text-center">Statut</th>
              <th class="py-3 px-4 text-center">Relance WhatsApp</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr v-for="debtor in summaryData?.topDebtors || []" :key="debtor.key" class="hover:bg-gray-50/80 transition-colors">
              <td class="py-3 px-4 font-bold text-gray-900">
                {{ debtor.name }}
              </td>
              <td class="py-3 px-3 text-gray-600">
                {{ debtor.category }}
              </td>
              <td class="py-3 px-3 text-gray-700">
                <span v-if="debtor.parentName" class="font-medium text-gray-900">{{ debtor.parentName }} - </span>
                <span class="font-mono text-gray-600">{{ debtor.phone || 'Non renseigné' }}</span>
              </td>
              <td class="py-3 px-3 text-center font-bold text-gray-700">
                {{ debtor.unpaidUnits }}
              </td>
              <td class="py-3 px-4 text-right font-black text-red-600 text-sm">
                {{ debtor.amountDue.toLocaleString('fr-FR') }} DT
              </td>
              <td class="py-3 px-3 text-center">
                <span
                  :class="[
                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold border',
                    debtor.status === 'overdue'
                      ? 'bg-red-50 text-red-700 border-red-200'
                      : 'bg-amber-50 text-amber-700 border-amber-200'
                  ]"
                >
                  {{ debtor.status === 'overdue' ? 'En retard critique' : 'En attente' }}
                </span>
              </td>
              <td class="py-3 px-4 text-center">
                <a
                  v-if="debtor.whatsappUrl"
                  :href="debtor.whatsappUrl"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
                >
                  <MessageCircle class="w-3.5 h-3.5" />
                  <span>Relancer</span>
                </a>
                <span v-else class="text-gray-400 text-[11px]">Pas de tél</span>
              </td>
            </tr>

            <tr v-if="!summaryData?.topDebtors?.length">
              <td colspan="7" class="py-8 text-center text-emerald-600 font-semibold">
                🎉 Aucun retard de paiement détecté ! Tous les comptes sont à jour.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
