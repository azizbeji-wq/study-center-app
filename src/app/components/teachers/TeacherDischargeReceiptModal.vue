<template>
  <div
    v-if="isOpen && teacher"
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
    @click="$emit('close')"
  >
    <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8 max-w-xl w-full max-h-[90vh] overflow-y-auto mx-4 md:mx-auto" @click.stop>
      <!-- Printable Area -->
      <div id="printable-teacher-discharge" class="border-2 border-dashed border-emerald-300 rounded-2xl p-6 bg-slate-50/60">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-200 pb-4 mb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg shadow-xs">
              🎓
            </div>
            <div>
              <h2 class="text-base font-black text-gray-900 tracking-tight uppercase">Centre d'Études & de Formation</h2>
              <p class="text-xs text-gray-500 font-medium">Direction Générale • Service Comptabilité</p>
            </div>
          </div>
          <div class="text-right">
            <span class="inline-block px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold text-xs rounded-full uppercase tracking-wider border border-emerald-200">
              Décharge N° DCH-{{ teacher.id.toString().padStart(4, '0') }}
            </span>
            <p class="text-[11px] text-gray-500 mt-1">Date : {{ formatDate(teacher.payout_date) }}</p>
          </div>
        </div>

        <!-- Title -->
        <div class="text-center py-2 mb-4 bg-emerald-50 rounded-xl border border-emerald-200">
          <h3 class="text-xs font-black text-emerald-950 uppercase tracking-wider">
            Reçu de Décharge de Paiement — Honoraires Enseignant
          </h3>
          <p class="text-[11px] text-emerald-700">Attestation officielle de versement de la part professeur</p>
        </div>

        <!-- Body -->
        <div class="space-y-2.5 text-xs text-gray-700">
          <div class="flex justify-between py-1.5 border-b border-gray-200/60">
            <span class="text-gray-500 font-medium">Nom de l'enseignant :</span>
            <span class="font-bold text-gray-900 text-sm">{{ teacher.name }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-gray-200/60">
            <span class="text-gray-500 font-medium">Matière(s) enseignée(s) :</span>
            <span class="font-semibold text-gray-800">{{ getTeacherSubjects(teacher).join(', ') }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-gray-200/60">
            <span class="text-gray-500 font-medium">Téléphone :</span>
            <span class="font-medium text-gray-800">{{ teacher.phone || '-' }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-gray-200/60">
            <span class="text-gray-500 font-medium">Période concernée :</span>
            <span class="font-semibold text-indigo-700">{{ teacher.payout_month || 'Mois en cours' }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-gray-200/60">
            <span class="text-gray-500 font-medium">Formule appliquée :</span>
            <span class="font-medium text-gray-800">
              {{ teacher.remuneration_type === 'percentage' || (teacher.rate_percentage && teacher.rate_percentage > 0)
                ? `Formule C : ${teacher.rate_percentage || 70}% sur ${getGrossRevenue(teacher)} TND brut`
                : (teacher.remuneration_type === 'fixed_center'
                  ? `Formule A : Idara ${teacher.center_fixed_per_student || 20} TND / élève`
                  : `Formule B : ${teacher.fixed_student_rate || 15} TND × ${teacher.studentCount || 0} élève(s)`) }}
            </span>
          </div>

          <!-- Amount Highlight Box -->
          <div class="mt-4 p-4 rounded-xl bg-linear-to-r from-emerald-600 to-teal-700 text-white shadow-xs flex items-center justify-between">
            <div>
              <p class="text-[10px] uppercase font-bold text-emerald-100 tracking-wider">Montant Net Perçu :</p>
              <p class="text-xs text-emerald-200">Reçu en totalité en espèces / virement</p>
            </div>
            <p class="text-2xl font-black">{{ getTeacherShare(teacher) }} TND</p>
          </div>

          <!-- Legal Discharge Clause -->
          <p class="text-[11px] text-gray-500 italic pt-2 leading-relaxed text-justify">
            « Je soussigné(e), {{ teacher.name }}, atteste par la présente avoir reçu l'intégralité de mes honoraires et de ma part d'enseignement relative à la période susmentionnée de la part de la direction du Centre d'Études. »
          </p>

          <!-- Signature Boxes -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-6 text-center text-xs">
            <div class="p-3 bg-white rounded-xl border border-gray-200">
              <p class="font-bold text-gray-700 mb-1">Signature de l'Enseignant</p>
              <p class="text-[10px] text-gray-400 italic">(Mention manuscrite "Bon pour accord et reçu")</p>
              <div class="h-14 border-b border-dashed border-gray-300 mt-2"></div>
            </div>
            <div class="p-3 bg-white rounded-xl border border-gray-200">
              <p class="font-bold text-gray-700 mb-1">Cachet & Signature Direction</p>
              <div class="h-14 border-b border-dashed border-gray-300 mt-2 flex items-center justify-center">
                <span class="text-[10px] text-emerald-700 font-extrabold border-2 border-emerald-500 px-2 py-0.5 rounded uppercase rotate-[-5deg]">
                  ✓ RÈGLEMENT EFFECTUÉ
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions (Hidden on Print) -->
      <div class="flex items-center justify-end gap-3 mt-6 print:hidden">
        <button
          type="button"
          @click="$emit('close')"
          class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800 transition-colors cursor-pointer"
        >
          Fermer
        </button>
        <button
          type="button"
          @click="printDischargeReceipt"
          class="flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition-colors cursor-pointer"
        >
          <Printer class="w-4 h-4" />
          <span>Imprimer la Décharge (A4/A5)</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Printer } from 'lucide-vue-next';
import type { Teacher } from '../../types/teachers';
import { useTeachers } from '../../composables/useTeachers';

defineProps<{
  isOpen: boolean;
  teacher: Teacher | null;
}>();

defineEmits<{
  (e: 'close'): void;
}>();

const {
  getTeacherSubjects,
  getGrossRevenue,
  getTeacherShare,
  formatDate
} = useTeachers();

function printDischargeReceipt() {
  window.print();
}
</script>
