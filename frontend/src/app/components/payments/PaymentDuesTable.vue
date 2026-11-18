<template>
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
    <div class="px-5 py-4 border-b border-gray-200 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
      <div>
        <h3 class="text-base font-bold text-gray-900">Soldes à régler</h3>
        <p class="text-xs text-gray-500 mt-0.5">{{ rulesText }}</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="k in kinds"
          :key="k.value"
          @click="$emit('update:duesKind', k.value)"
          :class="[
            'px-3.5 py-2 min-h-[44px] rounded-lg text-sm font-medium transition-colors focus:ring-2 focus:ring-indigo-500 focus:outline-none',
            duesKind === k.value
              ? 'bg-indigo-600 text-white'
              : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
          ]"
        >
          {{ k.label }} ({{ k.value === 'student' ? dues.students.length : dues.daycare.length }})
        </button>
        <span class="w-px bg-gray-200 mx-1"></span>
        <button
          v-for="f in duesFilters"
          :key="f.value"
          @click="$emit('update:duesFilter', f.value)"
          :class="[
            'px-3.5 py-2 min-h-[44px] rounded-lg text-sm font-medium transition-colors focus:ring-2 focus:ring-indigo-500 focus:outline-none',
            duesFilter === f.value
              ? 'bg-gray-800 text-white'
              : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
          ]"
        >
          {{ f.label }}
        </button>
      </div>
    </div>

    <!-- Élèves -->
    <div v-if="duesKind === 'student'" class="overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead class="bg-gray-50/75 border-b border-gray-200">
          <tr>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Élève</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Enseignant</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
              <div class="flex items-center gap-1.5">
                <span>Séances non payées</span>
                <HintButton
                  label="Séances dues"
                  text="Nombre de séances suivies par l'élève qui n'ont pas encore fait l'objet d'un règlement."
                />
              </div>
            </th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Prix / séance</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant dû</th>
            <th data-tour="payments-status" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
              <div class="flex items-center gap-1.5">
                <span>Statut</span>
                <HintButton
                  label="Statuts de paiement"
                  text="À jour : moins de 4 séances dues. En attente : dès 4 séances dues. En retard : au-delà de 7 séances dues."
                />
              </div>
            </th>
            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in filteredStudentDues" :key="row.key" class="hover:bg-gray-50/80 transition-colors">
            <td class="px-5 py-3.5">
              <p class="text-sm font-semibold text-gray-900">{{ row.studentName }}</p>
              <p v-if="row.groupName" class="text-xs text-gray-500">{{ row.groupName }}</p>
            </td>
            <td class="px-5 py-3.5">
              <p class="text-sm text-gray-900">{{ row.teacherName }}</p>
              <p class="text-xs text-gray-500">{{ row.subjects.join(', ') }}</p>
            </td>
            <td class="px-5 py-3.5">
              <p class="text-sm font-bold text-gray-900">{{ row.sessionsUnpaid }} séance(s)</p>
              <p class="text-xs text-gray-500">{{ row.sessionsAttended }} suivies · {{ row.sessionsPaid }} payées</p>
            </td>
            <td class="px-5 py-3.5 text-sm text-gray-700">
              {{ row.unitAmount > 0 ? row.unitAmount.toFixed(2) + ' TND' : 'À définir' }}
              <p v-if="row.priceSource === 'monthly'" class="text-[11px] text-gray-400">
                prix mensuel ÷ {{ dues.pricing.sessionsPerMonth }}
              </p>
              <p v-else-if="row.priceSource === 'default'" class="text-[11px] text-amber-600">
                tarif par défaut
              </p>
            </td>
            <td class="px-5 py-3.5">
              <span :class="['text-sm font-bold', row.unitAmount > 0 ? 'text-gray-900' : 'text-gray-400']">
                {{ row.unitAmount > 0 ? row.amountDue.toFixed(2) + ' TND' : 'À définir' }}
              </span>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-1.5">
                <component :is="getStatusIcon(row.status)" :class="['w-4 h-4', getStatusColor(row.status)]" />
                <span class="text-xs font-medium" :class="getStatusTextColor(row.status)">
                  {{ getStatusLabel(row.status) }}
                </span>
              </div>
            </td>
            <td class="px-5 py-3.5 text-right">
              <div class="flex items-center justify-end gap-2 flex-wrap">
                <button
                  v-if="isDirector"
                  type="button"
                  @click="$emit('extractStudent', row.studentId)"
                  class="p-2 min-h-[44px] min-w-[44px] inline-flex items-center justify-center hover:bg-indigo-50 text-indigo-600 rounded-lg transition-colors border border-indigo-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                  title="Générer l'extrait de compte de cet élève"
                  aria-label="Générer l'extrait de compte de cet élève"
                >
                  <FileText class="w-4 h-4" />
                </button>

                <template v-if="row.status === 'pending' || row.status === 'overdue'">
                  <a
                    v-if="getWhatsAppLink(row)"
                    :href="getWhatsAppLink(row) || undefined"
                    target="_blank"
                    rel="noopener noreferrer"
                    data-tour="payments-whatsapp"
                    class="inline-flex items-center gap-1.5 px-3 py-2 min-h-[44px] bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-300 text-xs font-semibold rounded-lg transition-colors focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                    title="Envoyer un rappel par WhatsApp"
                  >
                    <MessageCircle class="w-4 h-4 text-emerald-600" />
                    <span>Envoyer le rappel</span>
                  </a>
                  <button
                    v-else
                    type="button"
                    disabled
                    data-tour="payments-whatsapp"
                    class="inline-flex items-center gap-1.5 px-3 py-2 min-h-[44px] bg-gray-100 text-gray-400 border border-gray-200 text-xs font-medium rounded-lg cursor-not-allowed"
                    title="Numéro du parent manquant"
                    aria-label="Numéro du parent manquant"
                  >
                    <MessageCircle class="w-4 h-4 text-gray-400" />
                    <span>Numéro du parent manquant</span>
                  </button>
                </template>

                <button
                  type="button"
                  @click="$emit('collectStudent', { studentId: row.studentId, teacherId: row.teacherId })"
                  data-tour="payments-collect-btn"
                  class="inline-flex items-center justify-center px-3.5 py-2 min-h-[44px] bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                >
                  Encaisser
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="filteredStudentDues.length === 0" class="text-center py-10">
        <p class="text-gray-500 text-sm">Aucun solde à régler pour les élèves</p>
      </div>
    </div>

    <!-- Garderie -->
    <div v-else class="overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead class="bg-gray-50/75 border-b border-gray-200">
          <tr>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Enfant</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mois non payés</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tarif mensuel</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant dû</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in filteredDaycareDues" :key="row.key" class="hover:bg-gray-50/80 transition-colors">
            <td class="px-5 py-3.5">
              <p class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                <Baby class="w-4 h-4 text-orange-600 shrink-0" />
                <span>{{ row.name }}</span>
              </p>
              <p class="text-xs text-gray-500">Parent : {{ row.parentName }}</p>
            </td>
            <td class="px-5 py-3.5">
              <p class="text-sm font-bold text-gray-900">{{ row.monthsUnpaid }} mois</p>
              <p class="text-xs text-gray-500">{{ row.monthsDue }} écoulés · {{ row.monthsPaid }} payés</p>
            </td>
            <td class="px-5 py-3.5 text-sm text-gray-700">
              {{ row.monthlyFee > 0 ? row.monthlyFee.toFixed(2) + ' TND' : 'À définir' }}
              <p v-if="row.feeSource === 'default'" class="text-[11px] text-amber-600">tarif par défaut</p>
            </td>
            <td class="px-5 py-3.5">
              <span :class="['text-sm font-bold', row.monthlyFee > 0 ? 'text-gray-900' : 'text-gray-400']">
                {{ row.monthlyFee > 0 ? row.amountDue.toFixed(2) + ' TND' : 'À définir' }}
              </span>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-1.5">
                <component :is="getStatusIcon(row.status)" :class="['w-4 h-4', getStatusColor(row.status)]" />
                <span class="text-xs font-medium" :class="getStatusTextColor(row.status)">
                  {{ getStatusLabel(row.status) }}
                </span>
              </div>
            </td>
            <td class="px-5 py-3.5 text-right">
              <div class="flex items-center justify-end gap-2 flex-wrap">
                <template v-if="row.status === 'pending' || row.status === 'overdue'">
                  <a
                    v-if="getWhatsAppLinkDaycare(row)"
                    :href="getWhatsAppLinkDaycare(row) || undefined"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1.5 px-3 py-2 min-h-[44px] bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-300 text-xs font-semibold rounded-lg transition-colors focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                    title="Envoyer un rappel par WhatsApp"
                  >
                    <MessageCircle class="w-4 h-4 text-emerald-600" />
                    <span>Envoyer le rappel</span>
                  </a>
                  <button
                    v-else
                    type="button"
                    disabled
                    class="inline-flex items-center gap-1.5 px-3 py-2 min-h-[44px] bg-gray-100 text-gray-400 border border-gray-200 text-xs font-medium rounded-lg cursor-not-allowed"
                    title="Numéro du parent manquant"
                    aria-label="Numéro du parent manquant"
                  >
                    <MessageCircle class="w-4 h-4 text-gray-400" />
                    <span>Numéro du parent manquant</span>
                  </button>
                </template>

                <button
                  type="button"
                  @click="$emit('collectDaycare', row.daycareChildId)"
                  class="inline-flex items-center justify-center px-3.5 py-2 min-h-[44px] bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                >
                  Encaisser
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="filteredDaycareDues.length === 0" class="text-center py-10">
        <p class="text-gray-500 text-sm">Aucun solde à régler pour la garderie</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Clock, AlertCircle, CheckCircle, FileText, MessageCircle, Baby } from 'lucide-vue-next';
import HintButton from '../HintButton.vue';
import type { Dues, Kind, DueStudent, DueDaycare } from '../../types/payments';

const props = defineProps<{
  dues: Dues;
  duesKind: Kind;
  duesFilter: 'all' | 'pending' | 'overdue';
  searchTerm: string;
  isDirector?: boolean;
}>();

defineEmits<{
  (e: 'update:duesKind', val: Kind): void;
  (e: 'update:duesFilter', val: 'all' | 'pending' | 'overdue'): void;
  (e: 'collectStudent', val: { studentId: number; teacherId: number }): void;
  (e: 'collectDaycare', val: number): void;
  (e: 'extractStudent', val: number): void;
}>();

const kinds: Array<{ value: Kind; label: string }> = [
  { value: 'student', label: 'Élèves' },
  { value: 'daycare', label: 'Garderie' }
];

const duesFilters: Array<{ value: 'all' | 'pending' | 'overdue'; label: string }> = [
  { value: 'all', label: 'Tous' },
  { value: 'pending', label: 'En attente' },
  { value: 'overdue', label: 'En retard' }
];

const rulesText = computed(() => {
  const t = props.dues.thresholds;
  if (props.duesKind === 'student') {
    return `En attente : de ${t.studentPendingFrom} à ${t.studentOverdueAfter} séances non payées avec un même enseignant. En retard : plus de ${t.studentOverdueAfter} séances.`;
  }
  return `En attente : de ${t.daycarePendingFrom} à ${t.daycareOverdueAfter} mois non payés. En retard : plus de ${t.daycareOverdueAfter} mois.`;
});

const term = computed(() => props.searchTerm.trim().toLowerCase());

const filteredStudentDues = computed(() =>
  props.dues.students.filter(r => {
    const matchesSearch =
      !term.value ||
      r.studentName.toLowerCase().includes(term.value) ||
      r.teacherName.toLowerCase().includes(term.value);
    const matchesStatus = props.duesFilter === 'all' || r.status === props.duesFilter;
    return matchesSearch && matchesStatus;
  })
);

const filteredDaycareDues = computed(() =>
  props.dues.daycare.filter(r => {
    const matchesSearch =
      !term.value ||
      r.name.toLowerCase().includes(term.value) ||
      r.parentName.toLowerCase().includes(term.value);
    const matchesStatus = props.duesFilter === 'all' || r.status === props.duesFilter;
    return matchesSearch && matchesStatus;
  })
);

function getStatusLabel(status: string) {
  switch (status) {
    case 'paid': return 'Payé';
    case 'pending': return 'En attente';
    case 'overdue': return 'En retard';
    case 'ok': return 'En cours';
    default: return status;
  }
}

function getStatusIcon(status: string) {
  switch (status) {
    case 'paid': return CheckCircle;
    case 'overdue': return AlertCircle;
    default: return Clock;
  }
}

function getStatusColor(status: string) {
  switch (status) {
    case 'paid': return 'text-green-600';
    case 'pending': return 'text-yellow-600';
    case 'overdue': return 'text-red-600';
    default: return 'text-gray-400';
  }
}

function getStatusTextColor(status: string) {
  switch (status) {
    case 'paid': return 'text-green-700';
    case 'pending': return 'text-yellow-700';
    case 'overdue': return 'text-red-700';
    default: return 'text-gray-500';
  }
}

function normalizeTunisianPhone(raw?: string | null): string | null {
  if (!raw) return null;
  let cleaned = String(raw).replace(/[^\d+]/g, '');
  if (cleaned.startsWith('+')) {
    cleaned = cleaned.slice(1);
  } else if (cleaned.startsWith('00')) {
    cleaned = cleaned.slice(2);
  }
  // Retirer le 0 initial si suivi de 8 chiffres (ex: 098123456)
  if (cleaned.length === 9 && cleaned.startsWith('0')) {
    cleaned = cleaned.slice(1);
  }
  // Ajouter l'indicatif 216 s'il y a exactement 8 chiffres
  if (cleaned.length === 8) {
    cleaned = '216' + cleaned;
  }
  // Format tunisien valide : exactement 11 chiffres débutant par 216
  if (/^216\d{8}$/.test(cleaned)) {
    return cleaned;
  }
  return null;
}

function getCurrentMonthName(): string {
  try {
    return new Intl.DateTimeFormat('fr-FR', { month: 'long' }).format(new Date());
  } catch {
    return 'ce mois';
  }
}

function getStudentFirstName(fullName: string): string {
  if (!fullName) return '';
  return fullName.trim().split(/\s+/)[0] || fullName.trim();
}

function getWhatsAppLink(row: DueStudent): string | null {
  const phone = normalizeTunisianPhone(row.parentPhone || row.phone);
  if (!phone) return null;
  const firstName = getStudentFirstName(row.studentName);
  const month = getCurrentMonthName();
  const text = `Bonjour Madame, Monsieur, la mensualité de ${month} pour ${firstName} n’a pas encore été réglée. Merci de passer au centre. Cordialement.`;
  return `https://wa.me/${phone}?text=${encodeURIComponent(text)}`;
}

function getWhatsAppLinkDaycare(row: DueDaycare): string | null {
  const phone = normalizeTunisianPhone(row.phone);
  if (!phone) return null;
  const firstName = getStudentFirstName(row.name);
  const month = getCurrentMonthName();
  const text = `Bonjour Madame, Monsieur, la mensualité de ${month} pour ${firstName} n’a pas encore été réglée. Merci de passer au centre. Cordialement.`;
  return `https://wa.me/${phone}?text=${encodeURIComponent(text)}`;
}
</script>
