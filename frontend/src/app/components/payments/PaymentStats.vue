<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-green-50/80 border border-green-200 rounded-xl p-4 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
          <DollarSign class="w-5 h-5 text-green-600" />
        </div>
        <div>
          <p class="text-xs font-semibold text-green-700 uppercase">Total Payé</p>
          <p class="text-2xl font-bold text-green-900">{{ totalPaid.toFixed(2) }} TND</p>
        </div>
      </div>
    </div>
    <div class="bg-yellow-50/80 border border-yellow-200 rounded-xl p-4 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
          <Clock class="w-5 h-5 text-yellow-600" />
        </div>
        <div>
          <p class="text-xs font-semibold text-yellow-700 uppercase">En Attente</p>
          <p class="text-2xl font-bold text-yellow-900">{{ pendingTotal.toFixed(2) }} TND</p>
          <p class="text-[11px] text-yellow-700">{{ pendingCount }} solde(s)</p>
        </div>
      </div>
    </div>
    <div class="bg-red-50/80 border border-red-200 rounded-xl p-4 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
          <AlertCircle class="w-5 h-5 text-red-600" />
        </div>
        <div>
          <p class="text-xs font-semibold text-red-700 uppercase">En Retard</p>
          <p class="text-2xl font-bold text-red-900">{{ overdueTotal.toFixed(2) }} TND</p>
          <p class="text-[11px] text-red-700">{{ overdueCount }} solde(s)</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { DollarSign, Clock, AlertCircle } from 'lucide-vue-next';

defineProps<{
  totalPaid: number;
  pendingTotal: number;
  pendingCount: number;
  overdueTotal: number;
  overdueCount: number;
}>();
</script>
