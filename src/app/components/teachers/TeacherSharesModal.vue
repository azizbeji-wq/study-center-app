<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
    @click="$emit('close')"
  >
    <div
      class="bg-white rounded-2xl shadow-2xl p-6 max-w-4xl w-full space-y-4 max-h-[92vh] overflow-y-auto mx-4 md:mx-auto"
      @click.stop
    >
      <div class="flex items-center justify-between border-b pb-3 border-gray-100">
        <div>
          <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <span>📊 Bilan Financier & Parts des Professeurs</span>
          </h3>
          <p class="text-xs text-gray-500 mt-0.5">
            Récapitulatif mensuel des gains de chaque enseignant et de la marge du centre d'études.
          </p>
        </div>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 cursor-pointer"
          aria-label="Fermer"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <div class="overflow-x-auto border border-gray-200 rounded-xl">
        <table class="w-full text-left text-sm whitespace-nowrap">
          <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase tracking-wider border-b border-gray-200">
            <tr>
              <th class="py-3 px-4">Enseignant</th>
              <th class="py-3 px-3">Matière(s)</th>
              <th class="py-3 px-3 text-center">Élèves</th>
              <th class="py-3 px-3 text-center">Séances/Mois</th>
              <th class="py-3 px-3">Formule Rémunération</th>
              <th class="py-3 px-3 text-right">C.A. Brut</th>
              <th class="py-3 px-3 text-right text-emerald-700 font-bold">Part Enseignant</th>
              <th class="py-3 px-4 text-right text-indigo-700 font-bold">Part Centre</th>
              <th class="py-3 px-3 text-center">Statut Versement</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="t in teachers" :key="t.id" class="hover:bg-gray-50/80 transition-colors">
              <td class="py-3 px-4 font-bold text-gray-900">{{ t.name }}</td>
              <td class="py-3 px-3 text-xs text-gray-600">
                <span
                  v-for="s in getTeacherSubjects(t)"
                  :key="s"
                  class="inline-block bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded text-[10px] font-medium mr-1 mb-0.5"
                >
                  {{ s }}
                </span>
              </td>
              <td class="py-3 px-3 text-center font-semibold text-gray-800">{{ t.studentCount || 0 }}</td>
              <td class="py-3 px-3 text-center font-medium text-gray-700">{{ (t.weeklyLessons || 4) * 4 }}</td>
              <td class="py-3 px-3 text-xs">
                <span
                  v-if="t.remuneration_type === 'percentage' || (t.rate_percentage && t.rate_percentage > 0)"
                  class="px-2 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 rounded font-semibold text-[11px]"
                >
                  Formule C : {{ t.rate_percentage || 70 }}% du Brut
                </span>
                <span
                  v-else-if="t.remuneration_type === 'fixed_center'"
                  class="px-2 py-0.5 bg-indigo-50 text-indigo-800 border border-indigo-200 rounded font-semibold text-[11px]"
                >
                  Formule A : Idara {{ t.center_fixed_per_student || 20 }} TND / élève
                </span>
                <span
                  v-else
                  class="px-2 py-0.5 bg-blue-50 text-blue-800 border border-blue-200 rounded font-semibold text-[11px]"
                >
                  Formule B : {{ t.fixed_student_rate || 15 }} TND / élève
                </span>
              </td>
              <td class="py-3 px-3 text-right font-medium text-gray-800">{{ getGrossRevenue(t) }} TND</td>
              <td class="py-3 px-3 text-right font-bold text-emerald-700">{{ getTeacherShare(t) }} TND</td>
              <td class="py-3 px-4 text-right font-bold text-indigo-700">+{{ getCenterShare(t) }} TND</td>
              <td class="py-3 px-3 text-center">
                <button
                  type="button"
                  @click="$emit('togglePayout', t)"
                  :disabled="togglingPayoutId === t.id"
                  :class="[
                    'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border transition-colors cursor-pointer',
                    t.payout_status === 'paid'
                      ? 'bg-emerald-100 text-emerald-800 border-emerald-300 hover:bg-emerald-200'
                      : 'bg-amber-100 text-amber-900 border-amber-300 hover:bg-amber-200'
                  ]"
                  :title="t.payout_status === 'paid' ? 'Cliquer pour annuler le versement' : 'Cliquer pour marquer que l\'enseignant a pris son argent'"
                >
                  <Check v-if="t.payout_status === 'paid'" class="w-3 h-3" />
                  <Clock v-else class="w-3 h-3" />
                  <span>{{ t.payout_status === 'paid' ? 'A pris son argent' : 'En attente' }}</span>
                </button>
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-50/90 font-bold border-t-2 border-gray-200 text-gray-900">
            <tr>
              <td class="py-3 px-4" colspan="2">TOTAL GÉNÉRAL</td>
              <td class="py-3 px-3 text-center">{{ totalStudentsCount }}</td>
              <td class="py-3 px-3 text-center">{{ totalMonthlySessions }}</td>
              <td></td>
              <td class="py-3 px-3 text-right font-bold text-gray-900">{{ totalGrossRevenue.toLocaleString('fr-FR') }} TND</td>
              <td class="py-3 px-3 text-right font-black text-emerald-700 text-base">{{ totalTeachersShare.toLocaleString('fr-FR') }} TND</td>
              <td class="py-3 px-4 text-right font-black text-indigo-700 text-base">+{{ totalCenterShare.toLocaleString('fr-FR') }} TND</td>
              <td class="py-3 px-3 text-center text-xs text-gray-600 font-semibold">{{ paidTeachersCount }}/{{ teachers.length }} payé(s)</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
        <button
          @click="$emit('close')"
          class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-xs cursor-pointer"
        >
          Fermer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { X, Check, Clock } from 'lucide-vue-next';
import type { Teacher } from '../../types/teachers';
import { useTeachers } from '../../composables/useTeachers';

defineProps<{
  isOpen: boolean;
  teachers: Teacher[];
  togglingPayoutId: number | null;
  totalStudentsCount: number;
  totalMonthlySessions: number;
  totalGrossRevenue: number;
  totalTeachersShare: number;
  totalCenterShare: number;
  paidTeachersCount: number;
}>();

defineEmits<{
  (e: 'close'): void;
  (e: 'togglePayout', teacher: Teacher): void;
}>();

const {
  getTeacherSubjects,
  getGrossRevenue,
  getTeacherShare,
  getCenterShare
} = useTeachers();
</script>
