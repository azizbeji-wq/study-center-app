<template>
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
    <div class="bg-white rounded-xl p-4 border border-gray-200/80 shadow-xs flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
        <FileText class="w-5 h-5" />
      </div>
      <div>
        <p class="text-xs font-medium text-gray-500">Emplois du temps</p>
        <p class="text-xl font-bold text-gray-900 mt-0.5">{{ importsCount }}</p>
      </div>
    </div>

    <div class="bg-white rounded-xl p-4 border border-gray-200/80 shadow-xs flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
        <Building2 class="w-5 h-5" />
      </div>
      <div>
        <p class="text-xs font-medium text-gray-500">Écoles enregistrées</p>
        <p class="text-xl font-bold text-gray-900 mt-0.5">{{ schoolsCount }}</p>
      </div>
    </div>

    <div class="bg-white rounded-xl p-4 border border-gray-200/80 shadow-xs flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-100 flex items-center justify-center text-purple-600 shrink-0">
        <Calendar class="w-5 h-5" />
      </div>
      <div>
        <p class="text-xs font-medium text-gray-500">Niveaux couverts</p>
        <p class="text-xl font-bold text-gray-900 mt-0.5">{{ distinctLevelsCount }} / 6</p>
      </div>
    </div>

    <div class="bg-white rounded-xl p-4 border border-gray-200/80 shadow-xs flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
        <Upload class="w-5 h-5" />
      </div>
      <div class="min-w-0">
        <p class="text-xs font-medium text-gray-500">Dernier import</p>
        <p class="text-xs font-bold text-gray-900 mt-1 truncate">{{ latestImportText }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { FileText, Building2, Calendar, Upload } from 'lucide-vue-next';

defineProps<{
  importsCount: number;
  schoolsCount: number;
  distinctLevelsCount: number;
  latestImportText: string;
}>();
</script>
