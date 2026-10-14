<script setup lang="ts">
import { ShieldAlert, RefreshCw } from 'lucide-vue-next';
import type { AccountingAuditLog } from '../../types/financial';
import { formatAuditAction, getAuditActionBadge, formatLogDate } from '../../composables/useFinancialFormatters';

defineProps<{
  summaryData: any;
  auditLogs: AccountingAuditLog[];
  journalDate: string;
}>();

const emit = defineEmits<{
  (e: 'update:journalDate', value: string): void;
  (e: 'fetchSummary'): void;
  (e: 'fetchAuditLogs'): void;
}>();

function onDateChange(e: Event) {
  const target = e.target as HTMLInputElement;
  emit('update:journalDate', target.value);
  emit('fetchSummary');
}
</script>

<template>
  <div class="space-y-6">
    <!-- Journal de Caisse Quotidien -->
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs space-y-5">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
        <div>
          <h3 class="text-base font-bold text-gray-900">Journal de Caisse Quotidien</h3>
          <p class="text-xs text-gray-500 mt-0.5">Flux d'espèces et rapprochement des encaissements / décaissements du jour</p>
        </div>

        <!-- Date Picker pour le Journal -->
        <div class="flex items-center gap-2">
          <span class="text-xs font-medium text-gray-500">Date :</span>
          <input
            :value="journalDate"
            type="date"
            @change="onDateChange"
            class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>
      </div>

      <!-- Totaux de caisse du jour -->
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <!-- Caisse Espèces Nette -->
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
          <span class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">Solde Espèces du Jour</span>
          <p class="text-2xl font-black text-emerald-950 mt-1">{{ (summaryData?.dailyCashJournal?.netCashOnly || 0).toLocaleString('fr-FR') }} DT</p>
          <p class="text-[11px] text-emerald-700 mt-0.5">Espèces reçues − Dépenses espèces</p>
        </div>

        <!-- Total Entrées -->
        <div class="bg-white border border-gray-200 rounded-xl p-4">
          <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Entrées (Tous modes)</span>
          <p class="text-2xl font-black text-gray-900 mt-1">{{ (summaryData?.dailyCashJournal?.totalInflows || 0).toLocaleString('fr-FR') }} DT</p>
          <p class="text-[11px] text-gray-500 mt-0.5">{{ summaryData?.dailyCashJournal?.inflowsCount || 0 }} encaissement(s)</p>
        </div>

        <!-- Total Sorties -->
        <div class="bg-white border border-gray-200 rounded-xl p-4">
          <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Sorties (Dépenses)</span>
          <p class="text-2xl font-black text-red-600 mt-1">{{ (summaryData?.dailyCashJournal?.totalOutflows || 0).toLocaleString('fr-FR') }} DT</p>
          <p class="text-[11px] text-gray-500 mt-0.5">{{ summaryData?.dailyCashJournal?.outflowsCount || 0 }} dépense(s)</p>
        </div>

        <!-- Solde Net Global -->
        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4">
          <span class="text-xs font-semibold text-indigo-800 uppercase tracking-wider">Flux Net de la Journée</span>
          <p class="text-2xl font-black text-indigo-950 mt-1">{{ (summaryData?.dailyCashJournal?.netTotal || 0).toLocaleString('fr-FR') }} DT</p>
          <p class="text-[11px] text-indigo-700 mt-0.5">Encaissements nets générés</p>
        </div>
      </div>

      <!-- Ventilation par Mode de Règlement Aujourd'hui -->
      <div class="pt-3 border-t border-gray-100">
        <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Ventilation par Mode de Règlement</h4>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
          <div class="p-2.5 rounded-xl border border-gray-200 bg-gray-50">
            <span class="text-gray-500">Espèces :</span>
            <span class="font-bold text-gray-900 ml-1">{{ (summaryData?.dailyCashJournal?.inflowsByMethod?.especes || 0).toLocaleString('fr-FR') }} DT</span>
          </div>
          <div class="p-2.5 rounded-xl border border-gray-200 bg-gray-50">
            <span class="text-gray-500">Chèque :</span>
            <span class="font-bold text-gray-900 ml-1">{{ (summaryData?.dailyCashJournal?.inflowsByMethod?.cheque || 0).toLocaleString('fr-FR') }} DT</span>
          </div>
          <div class="p-2.5 rounded-xl border border-gray-200 bg-gray-50">
            <span class="text-gray-500">Virement :</span>
            <span class="font-bold text-gray-900 ml-1">{{ (summaryData?.dailyCashJournal?.inflowsByMethod?.virement || 0).toLocaleString('fr-FR') }} DT</span>
          </div>
          <div class="p-2.5 rounded-xl border border-gray-200 bg-gray-50">
            <span class="text-gray-500">Carte :</span>
            <span class="font-bold text-gray-900 ml-1">{{ (summaryData?.dailyCashJournal?.inflowsByMethod?.carte || 0).toLocaleString('fr-FR') }} DT</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Traçabilité & Journal d'Audit des Paiements -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-xs overflow-hidden">
      <div class="p-4 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
            <ShieldAlert class="w-4 h-4 text-indigo-600" />
            <span>Traçabilité & Journal d'Audit des Paiements</span>
          </h3>
          <p class="text-xs text-gray-500 mt-0.5">Historique certifié : qui a créé, modifié, validé ou supprimé un paiement</p>
        </div>

        <button
          @click="emit('fetchAuditLogs')"
          class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition-colors cursor-pointer"
        >
          <RefreshCw class="w-3.5 h-3.5" />
          <span>Actualiser</span>
        </button>
      </div>

      <div class="overflow-x-auto max-h-[400px]">
        <table class="w-full text-left text-xs whitespace-nowrap">
          <thead class="bg-gray-50 text-gray-600 uppercase font-semibold border-b border-gray-200 sticky top-0 z-10">
            <tr>
              <th class="py-3 px-4">Date & Heure</th>
              <th class="py-3 px-4">Utilisateur</th>
              <th class="py-3 px-3">Rôle</th>
              <th class="py-3 px-3">Action</th>
              <th class="py-3 px-4">Élève Concerné</th>
              <th class="py-3 px-3">Mode</th>
              <th class="py-3 px-4 text-right">Montant</th>
              <th class="py-3 px-3">IP</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr v-for="log in auditLogs" :key="log.id" class="hover:bg-gray-50/80 transition-colors">
              <td class="py-3 px-4 text-gray-500 whitespace-nowrap font-mono">
                {{ formatLogDate(log.created_at) }}
              </td>
              <td class="py-3 px-4 font-bold text-gray-900">
                {{ log.user_name || 'Système' }}
              </td>
              <td class="py-3 px-3">
                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold', log.user_role === 'director' ? 'bg-indigo-50 text-indigo-700' : 'bg-gray-100 text-gray-700']">
                  {{ log.user_role === 'director' ? 'Directeur' : 'Personnel' }}
                </span>
              </td>
              <td class="py-3 px-3">
                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold border', getAuditActionBadge(log.action)]">
                  {{ formatAuditAction(log.action) }}
                </span>
              </td>
              <td class="py-3 px-4 font-medium text-gray-800">
                {{ log.student_name || '—' }}
              </td>
              <td class="py-3 px-3 text-gray-600 capitalize">
                {{ log.payment_method || 'especes' }}
              </td>
              <td class="py-3 px-4 text-right font-black text-gray-900">
                {{ log.amount ? `${Number(log.amount).toLocaleString('fr-FR')} DT` : '—' }}
              </td>
              <td class="py-3 px-3 text-gray-400 font-mono text-[10px]">
                {{ log.ip_address || '—' }}
              </td>
            </tr>

            <tr v-if="auditLogs.length === 0">
              <td colspan="8" class="py-8 text-center text-gray-400">
                Aucune trace d'audit enregistrée pour l'instant.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
