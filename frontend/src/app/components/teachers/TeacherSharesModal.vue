<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="teacher-shares-title"
    @click.self="$emit('close')"
    @keydown.esc="$emit('close')"
  >
    <div
      class="bg-white rounded-2xl shadow-xl w-full max-w-5xl my-6 flex flex-col max-h-[92vh] border border-gray-200 overflow-hidden"
      @click.stop
    >
      <!-- En-tête sobre -->
      <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between shrink-0 bg-gray-50/50">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center border border-gray-200">
            <BarChart3 class="w-5 h-5 text-gray-700" />
          </div>
          <div>
            <h2 id="teacher-shares-title" class="text-base font-semibold text-gray-900">
              Répartition des honoraires enseignants
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">
              Synthèse mensuelle des parts professeurs et de la marge conservée par le centre
            </p>
          </div>
        </div>

        <button
          type="button"
          @click="$emit('close')"
          class="min-h-[44px] min-w-[44px] p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-colors flex items-center justify-center cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500"
          aria-label="Fermer la fenêtre"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Métriques de synthèse en 4 blocs calmes -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-5 bg-white border-b border-gray-200 shrink-0">
        <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-200">
          <span class="text-[11px] font-medium text-gray-500 uppercase tracking-wider block">Chiffre d'affaires brut</span>
          <p class="text-lg font-semibold text-gray-900 mt-1 tabular-nums">{{ totalGrossRevenue.toLocaleString('fr-FR') }} TND</p>
          <p class="text-[11px] text-gray-400 mt-0.5">{{ totalStudentsCount }} élèves · {{ totalMonthlySessions }} séances</p>
        </div>

        <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200/80">
          <span class="text-[11px] font-medium text-emerald-800 uppercase tracking-wider block">Part totale enseignants</span>
          <p class="text-lg font-semibold text-emerald-950 mt-1 tabular-nums">{{ totalTeachersShare.toLocaleString('fr-FR') }} TND</p>
          <p class="text-[11px] text-emerald-700 mt-0.5">Honoraires dus ce mois</p>
        </div>

        <div class="p-3.5 rounded-xl bg-indigo-50/70 border border-indigo-200/80">
          <span class="text-[11px] font-medium text-indigo-800 uppercase tracking-wider block">Marge nette centre</span>
          <p class="text-lg font-semibold text-indigo-950 mt-1 tabular-nums">+{{ totalCenterShare.toLocaleString('fr-FR') }} TND</p>
          <p class="text-[11px] text-indigo-700 mt-0.5">Part conservée par l'administration</p>
        </div>

        <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-200">
          <span class="text-[11px] font-medium text-gray-500 uppercase tracking-wider block">Régularisation</span>
          <p class="text-lg font-semibold text-gray-900 mt-1 tabular-nums">{{ paidTeachersCount }} / {{ teachers.length }}</p>
          <p class="text-[11px] text-gray-500 mt-0.5">Enseignants rémunérés</p>
        </div>
      </div>

      <!-- Tableau détaillé -->
      <div class="flex-1 overflow-auto p-5">
        <div class="border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
          <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50/80 text-gray-600 text-xs font-semibold border-b border-gray-200">
              <tr>
                <th scope="col" class="py-3 px-4">Enseignant</th>
                <th scope="col" class="py-3 px-3">Matières</th>
                <th scope="col" class="py-3 px-3 text-right">Élèves</th>
                <th scope="col" class="py-3 px-3 text-right">Séances/mois</th>
                <th scope="col" class="py-3 px-3">Formule</th>
                <th scope="col" class="py-3 px-3 text-right">C.A. brut</th>
                <th scope="col" class="py-3 px-3 text-right text-emerald-800">Part prof</th>
                <th scope="col" class="py-3 px-4 text-right text-indigo-800">Part centre</th>
                <th scope="col" class="py-3 px-4 text-center">Statut</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="t in teachers" :key="t.id" class="hover:bg-gray-50/60 transition-colors">
                <td class="py-3.5 px-4 font-medium text-gray-900">
                  {{ t.name }}
                </td>
                <td class="py-3.5 px-3">
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="s in getTeacherSubjects(t)"
                      :key="s"
                      class="inline-flex items-center px-2 py-0.5 rounded text-[11px] bg-gray-100 text-gray-700 font-medium border border-gray-200/60"
                    >
                      {{ s }}
                    </span>
                  </div>
                </td>
                <td class="py-3.5 px-3 text-right text-gray-700 tabular-nums">
                  {{ t.studentCount || 0 }}
                </td>
                <td class="py-3.5 px-3 text-right text-gray-700 tabular-nums">
                  {{ (t.weeklyLessons || 4) * 4 }}
                </td>
                <td class="py-3.5 px-3 text-xs">
                  <span
                    v-if="t.remuneration_type === 'percentage' || (t.rate_percentage && t.rate_percentage > 0)"
                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-800 border border-amber-200/70"
                  >
                    Formule C · {{ t.rate_percentage || 70 }}%
                  </span>
                  <span
                    v-else-if="t.remuneration_type === 'fixed_center'"
                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-indigo-50 text-indigo-800 border border-indigo-200/70"
                  >
                    Formule A · Fixe {{ t.center_fixed_per_student || 20 }} TND
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-800 border border-slate-200"
                  >
                    Formule B · {{ t.fixed_student_rate || 15 }} TND/él.
                  </span>
                </td>
                <td class="py-3.5 px-3 text-right text-gray-800 tabular-nums font-medium">
                  {{ getGrossRevenue(t).toLocaleString('fr-FR') }} TND
                </td>
                <td class="py-3.5 px-3 text-right font-semibold text-emerald-800 tabular-nums">
                  {{ getTeacherShare(t).toLocaleString('fr-FR') }} TND
                </td>
                <td class="py-3.5 px-4 text-right font-semibold text-indigo-800 tabular-nums">
                  +{{ getCenterShare(t).toLocaleString('fr-FR') }} TND
                </td>
                <td class="py-3.5 px-4 text-center">
                  <button
                    type="button"
                    @click="$emit('togglePayout', t)"
                    :disabled="togglingPayoutId === t.id"
                    :class="[
                      'min-h-[36px] inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium border transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-indigo-500 disabled:opacity-50',
                      t.payout_status === 'paid'
                        ? 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100'
                        : 'bg-amber-50 text-amber-900 border-amber-200 hover:bg-amber-100'
                    ]"
                    :title="t.payout_status === 'paid' ? 'Cliquer pour repasser en attente' : 'Cliquer pour marquer comme régularisé'"
                  >
                    <Check v-if="t.payout_status === 'paid'" class="w-3.5 h-3.5 text-emerald-700" />
                    <Clock v-else class="w-3.5 h-3.5 text-amber-700" />
                    <span>{{ t.payout_status === 'paid' ? 'Rémunéré' : 'En attente' }}</span>
                  </button>
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50 border-t border-gray-200 font-semibold text-gray-900 text-xs">
              <tr>
                <td class="py-3.5 px-4" colspan="2">Total général</td>
                <td class="py-3.5 px-3 text-right tabular-nums">{{ totalStudentsCount }}</td>
                <td class="py-3.5 px-3 text-right tabular-nums">{{ totalMonthlySessions }}</td>
                <td></td>
                <td class="py-3.5 px-3 text-right tabular-nums font-semibold">{{ totalGrossRevenue.toLocaleString('fr-FR') }} TND</td>
                <td class="py-3.5 px-3 text-right text-emerald-800 tabular-nums font-bold text-sm">{{ totalTeachersShare.toLocaleString('fr-FR') }} TND</td>
                <td class="py-3.5 px-4 text-right text-indigo-800 tabular-nums font-bold text-sm">+{{ totalCenterShare.toLocaleString('fr-FR') }} TND</td>
                <td class="py-3.5 px-4 text-center text-gray-600 font-medium">
                  {{ paidTeachersCount }}/{{ teachers.length }} réglé(s)
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Pied de modale calme -->
      <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-200 flex justify-end shrink-0">
        <button
          type="button"
          @click="$emit('close')"
          class="min-h-[44px] px-5 py-2 bg-white text-gray-700 text-xs font-semibold rounded-xl border border-gray-300 hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-2xs cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          Fermer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { X, Check, Clock, BarChart3 } from 'lucide-vue-next';
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
