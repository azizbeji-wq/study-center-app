<template>
  <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-all space-y-4">
    <div class="flex items-start justify-between">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-indigo-50 border border-indigo-100 rounded-full flex items-center justify-center">
          <span class="text-base font-bold text-indigo-600">{{ getInitial(teacher.name) }}</span>
        </div>
        <div class="min-w-0">
          <div class="flex items-center gap-1.5 flex-wrap">
            <h3 class="text-sm font-semibold text-gray-900">{{ teacher.name }}</h3>
            <span
              v-if="teacher.user_role === 'director'"
              class="px-1.5 py-0.5 bg-indigo-100 text-indigo-800 text-[10px] font-bold rounded shadow-2xs"
              title="Directeur assurant des cours"
            >
              Directeur & Prof
            </span>
            <span
              v-else-if="teacher.user_role === 'secretary'"
              class="px-1.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded shadow-2xs"
              title="Personnel assurant des cours"
            >
              Personnel & Prof
            </span>
          </div>
          <div class="flex flex-wrap gap-1 mt-1">
            <span
              v-for="sub in getTeacherSubjects(teacher)"
              :key="sub"
              class="px-2 py-0.5 bg-indigo-50 text-indigo-700 font-semibold text-[11px] rounded-md border border-indigo-100"
            >
              {{ sub }}
            </span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-1">
        <button
          v-if="isDirector"
          @click="$emit('extract', teacher.id)"
          class="p-1.5 hover:bg-emerald-50 text-gray-600 hover:text-emerald-600 rounded-lg transition-colors cursor-pointer"
          title="Exporter l'extrait de paiement de cet enseignant (PDF / Excel)"
        >
          <FileText class="w-4 h-4" />
        </button>
        <button
          @click="$emit('edit', teacher)"
          class="p-1.5 hover:bg-gray-100 text-gray-600 hover:text-blue-600 rounded-lg transition-colors cursor-pointer"
          title="Modifier"
        >
          <Edit2 class="w-4 h-4" />
        </button>
        <button
          @click="$emit('delete', teacher.id)"
          class="p-1.5 hover:bg-red-50 text-gray-600 hover:text-red-600 rounded-lg transition-colors cursor-pointer"
          title="Supprimer"
        >
          <Trash2 class="w-4 h-4" />
        </button>
      </div>
    </div>

    <div class="space-y-1.5 text-xs text-gray-600 bg-gray-50/75 p-3 rounded-lg border border-gray-100">
      <p class="flex items-center gap-2"><span>📱</span> <span>{{ teacher.phone }}</span></p>
      <p class="flex items-center gap-2"><span>✉️</span> <span>{{ teacher.email }}</span></p>
    </div>

    <div class="pt-2 border-t border-gray-100">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-center">
        <div class="bg-blue-50/70 p-2 rounded-lg border border-blue-100">
          <p class="text-[10px] uppercase font-semibold text-gray-500">Élèves</p>
          <p class="text-sm font-bold text-gray-900">{{ teacher.studentCount }}</p>
        </div>
        <div class="bg-purple-50/70 p-2 rounded-lg border border-purple-100">
          <p class="text-[10px] uppercase font-semibold text-gray-500">Leçons/sem</p>
          <p class="text-sm font-bold text-gray-900">{{ teacher.weeklyLessons }}</p>
        </div>
        <div class="bg-emerald-50/70 p-2 rounded-lg border border-emerald-100">
          <p class="text-[10px] uppercase font-semibold text-emerald-700">Formule Gain</p>
          <p class="text-xs font-bold text-emerald-800 mt-0.5">
            <template v-if="teacher.remuneration_type === 'percentage' || (teacher.rate_percentage && teacher.rate_percentage > 0)">
              C: {{ teacher.rate_percentage || 70 }}%
            </template>
            <template v-else-if="teacher.remuneration_type === 'fixed_center'">
              A: Idara {{ teacher.center_fixed_per_student || 20 }} DT/él
            </template>
            <template v-else>
              B: {{ teacher.fixed_student_rate || 15 }} DT/él
            </template>
          </p>
        </div>
      </div>
    </div>

    <!-- Director Remuneration & Teacher Share Section -->
    <div v-if="isDirector" class="p-3 bg-linear-to-r from-emerald-50/90 to-teal-50/90 rounded-xl border border-emerald-200/90 space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-bold text-emerald-950 uppercase tracking-wider flex items-center gap-1.5">
          <span>💰 Part du Professeur :</span>
        </span>
        <span class="text-sm font-black text-emerald-700 bg-white px-2.5 py-0.5 rounded-lg border border-emerald-200 shadow-2xs">
          {{ getTeacherShare(teacher) }} TND <span class="text-[10px] font-medium text-emerald-600">/ mois</span>
        </span>
      </div>

      <div class="flex items-center justify-between text-[11px] text-emerald-800 pt-1.5 border-t border-emerald-200/60">
        <span>
          <span class="text-gray-500">Calcul :</span>
          <strong class="text-emerald-950 font-bold ml-1">
            <template v-if="teacher.remuneration_type === 'percentage' || (teacher.rate_percentage && teacher.rate_percentage > 0)">
              Formule C : {{ teacher.rate_percentage || 70 }}% du brut ({{ getGrossRevenue(teacher) }} TND)
            </template>
            <template v-else-if="teacher.remuneration_type === 'fixed_center'">
              Formule A : Brut ({{ getGrossRevenue(teacher) }} DT) - Idara ({{ (teacher.center_fixed_per_student || 20) * (teacher.studentCount || 0) }} DT)
            </template>
            <template v-else>
              Formule B : {{ teacher.fixed_student_rate || 15 }} TND × {{ teacher.studentCount || 0 }} élève(s)
            </template>
          </strong>
        </span>
        <span class="text-indigo-700 font-bold bg-white/80 px-2 py-0.5 rounded text-[11px] border border-indigo-100" title="Part conservée par le centre d'études">
          Centre : +{{ getCenterShare(teacher) }} TND
        </span>
      </div>

      <!-- Payout Status and Action -->
      <div class="pt-2 border-t border-emerald-200/80 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-1.5">
          <span
            :class="[
              'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border shadow-2xs',
              teacher.payout_status === 'paid'
                ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
                : 'bg-amber-100 text-amber-900 border-amber-300'
            ]"
          >
            <span>{{ teacher.payout_status === 'paid' ? '✓ A pris son argent' : '⏳ Non encore payé' }}</span>
            <span v-if="teacher.payout_status === 'paid' && teacher.payout_date" class="text-[10px] font-normal opacity-85">
              ({{ formatDate(teacher.payout_date) }})
            </span>
          </span>
        </div>

        <div class="flex items-center gap-1.5">
          <button
            type="button"
            @click="$emit('togglePayout', teacher)"
            :disabled="isTogglingPayout"
            :class="[
              'px-2.5 py-1 rounded-lg text-xs font-bold transition-all shadow-2xs cursor-pointer flex items-center gap-1',
              teacher.payout_status === 'paid'
                ? 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-100'
                : 'bg-emerald-600 hover:bg-emerald-700 text-white'
            ]"
            :title="teacher.payout_status === 'paid' ? 'Remettre le versement en attente' : 'Confirmer que l\'enseignant a bien pris son argent'"
          >
            <Check v-if="teacher.payout_status !== 'paid'" class="w-3.5 h-3.5" />
            <RotateCcw v-else class="w-3 h-3 text-gray-500" />
            <span>{{ teacher.payout_status === 'paid' ? 'Annuler' : 'Marquer payé' }}</span>
          </button>

          <button
            v-if="teacher.payout_status === 'paid'"
            type="button"
            @click="$emit('openDischarge', teacher)"
            class="p-1 hover:bg-white text-emerald-800 rounded border border-emerald-200 cursor-pointer shadow-2xs"
            title="Imprimer le reçu de décharge de versement enseignant"
          >
            <Printer class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { FileText, Edit2, Trash2, Check, RotateCcw, Printer } from 'lucide-vue-next';
import type { Teacher } from '../../types/teachers';
import { useTeachers } from '../../composables/useTeachers';

defineProps<{
  teacher: Teacher;
  isDirector: boolean;
  isTogglingPayout: boolean;
}>();

defineEmits<{
  (e: 'extract', id: number): void;
  (e: 'edit', teacher: Teacher): void;
  (e: 'delete', id: number): void;
  (e: 'togglePayout', teacher: Teacher): void;
  (e: 'openDischarge', teacher: Teacher): void;
}>();

const {
  getInitial,
  getTeacherSubjects,
  getTeacherShare,
  getGrossRevenue,
  getCenterShare,
  formatDate
} = useTeachers();
</script>
