<template>
  <div class="bg-linear-to-r from-emerald-50 via-teal-50 to-indigo-50 border border-emerald-200/80 rounded-2xl p-4 shadow-xs">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3 pb-3 border-b border-emerald-200/50">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-2xs">
          💰
        </div>
        <div>
          <h3 class="text-sm font-bold text-gray-900">Espace Directeur • Répartition Financière des Enseignants</h3>
          <p class="text-xs text-gray-600">Suivi mensuel de la part de chaque enseignant et des marges générées pour le centre.</p>
        </div>
      </div>
      <button
        @click="$emit('openSharesModal')"
        class="text-xs font-bold text-emerald-800 bg-white px-3 py-1.5 rounded-lg border border-emerald-200 hover:bg-emerald-100 transition-colors shadow-2xs self-start sm:self-auto flex items-center gap-1.5 cursor-pointer"
      >
        <span>Voir le tableau détaillé</span>
        <span>→</span>
      </button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
      <div class="bg-white/80 backdrop-blur-xs p-3 rounded-xl border border-emerald-200/60">
        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">C.A. Brut Estimé</p>
        <p class="text-lg font-extrabold text-gray-900 mt-0.5">{{ totalGrossRevenue.toLocaleString('fr-FR') }} TND</p>
        <p class="text-[10px] text-gray-500">Revenus des cours</p>
      </div>

      <div class="bg-emerald-600 text-white p-3 rounded-xl shadow-xs">
        <p class="text-[11px] font-bold text-emerald-100 uppercase tracking-wider">Part Totale Enseignants</p>
        <p class="text-lg font-black mt-0.5">{{ totalTeachersShare.toLocaleString('fr-FR') }} TND</p>
        <p class="text-[10px] text-emerald-200">À reverser aux professeurs</p>
      </div>

      <div class="bg-white/80 backdrop-blur-xs p-3 rounded-xl border border-indigo-200/60">
        <p class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider">Part Nette Centre</p>
        <p class="text-lg font-extrabold text-indigo-900 mt-0.5">{{ totalCenterShare.toLocaleString('fr-FR') }} TND</p>
        <p class="text-[10px] text-indigo-500">Marge conservée</p>
      </div>

      <div class="bg-white/80 backdrop-blur-xs p-3 rounded-xl border border-teal-200/60">
        <p class="text-[11px] font-bold text-teal-700 uppercase tracking-wider">Moyenne / Professeur</p>
        <p class="text-lg font-extrabold text-teal-900 mt-0.5">{{ avgTeacherShare.toLocaleString('fr-FR') }} TND</p>
        <p class="text-[10px] text-teal-600">Sur {{ teachersCount }} enseignant(s)</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  totalGrossRevenue: number;
  totalTeachersShare: number;
  totalCenterShare: number;
  avgTeacherShare: number;
  teachersCount: number;
}>();

defineEmits<{
  (e: 'openSharesModal'): void;
}>();
</script>
