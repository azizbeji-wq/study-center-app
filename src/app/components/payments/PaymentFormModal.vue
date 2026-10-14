<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click="$emit('close')">
    <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full max-h-[92vh] overflow-y-auto mx-4 md:mx-auto" @click.stop>
      <h3 class="text-xl font-bold text-gray-900 mb-4">
        {{ isEditing ? 'Modifier le paiement' : 'Enregistrer un paiement' }}
      </h3>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- ===== Création ===== -->
        <template v-if="!isEditing">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <button
              v-for="k in kinds"
              :key="k.value"
              type="button"
              @click="setKind(k.value)"
              :class="[
                'px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                form.kind === k.value
                  ? 'bg-indigo-600 text-white'
                  : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
              ]"
            >
              {{ k.label }}
            </button>
          </div>

          <!-- Élève + enseignant -->
          <template v-if="form.kind === 'student'">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Élève *</label>
              <select
                v-model="form.studentId"
                @change="onStudentChange"
                required
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              >
                <option :value="null" disabled>Sélectionner un élève...</option>
                <option v-for="s in students" :key="s.id" :value="s.id">
                  {{ s.name }} ({{ s.group_name || 'Sans groupe' }} - {{ s.level }})
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Enseignant *</label>
              <select
                v-model="form.teacherId"
                @change="onTeacherChange"
                required
                :disabled="!form.studentId"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none disabled:bg-gray-100"
              >
                <option :value="null" disabled>Sélectionner un enseignant...</option>
                <option v-for="t in teacherOptions" :key="t.id" :value="t.id">{{ t.name }}</option>
              </select>
              <p v-if="form.studentId && teacherOptions.length === 0" class="text-xs text-red-600 mt-1">
                Cet élève n'a aucune matière assignée.
              </p>
            </div>

            <div v-if="form.studentId && form.teacherId" class="text-xs p-2.5 bg-indigo-50 rounded-lg border border-indigo-100 text-indigo-900 space-y-0.5">
              <p>
                Séances non payées :
                <span class="font-bold">{{ currentStudentDue ? currentStudentDue.sessionsUnpaid : 0 }}</span>
                <template v-if="currentStudentDue"> (sur {{ currentStudentDue.sessionsAttended }} suivies)</template>
              </p>
              <p>
                Prix par séance :
                <span class="font-bold">
                  {{ currentUnitAmount > 0 ? currentUnitAmount.toFixed(2) + ' TND' : 'à définir (modifiez l\'élève)' }}
                </span>
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Séances payées *</label>
                <input
                  v-model.number="form.sessionsPaid"
                  @input="recomputeAmount"
                  type="number"
                  min="1"
                  step="1"
                  required
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Montant (TND) *</label>
                <input
                  v-model.number="form.amount"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                />
              </div>
            </div>
          </template>

          <!-- Garderie -->
          <template v-else>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Enfant de garde *</label>
              <select
                v-model="form.daycareChildId"
                @change="onChildChange"
                required
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              >
                <option :value="null" disabled>Sélectionner un enfant...</option>
                <option v-for="c in children" :key="c.id" :value="c.id">{{ c.name }} (parent : {{ c.parentName }})</option>
              </select>
            </div>

            <div v-if="form.daycareChildId" class="text-xs p-2.5 bg-orange-50 rounded-lg border border-orange-100 text-orange-900 space-y-0.5">
              <p>
                Mois non payés :
                <span class="font-bold">{{ currentChildDue ? currentChildDue.monthsUnpaid : 0 }}</span>
              </p>
              <p>
                Tarif mensuel :
                <span class="font-bold">
                  {{ currentChildFee > 0 ? currentChildFee.toFixed(2) + ' TND' : 'à définir (page Élèves de garde)' }}
                </span>
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Mois payés *</label>
                <input
                  v-model.number="form.monthsPaid"
                  @input="recomputeAmount"
                  type="number"
                  min="1"
                  step="1"
                  required
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Montant (TND) *</label>
                <input
                  v-model.number="form.amount"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                />
              </div>
            </div>
          </template>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Date de paiement</label>
            <input
              v-model="form.date"
              type="date"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>
        </template>

        <!-- ===== Modification ===== -->
        <template v-else>
          <div class="text-sm p-2.5 bg-gray-50 rounded-lg border border-gray-200">
            <p class="font-semibold text-gray-900">{{ form.studentName }}</p>
            <p class="text-xs text-gray-500">{{ editingPayment ? paymentDetail(editingPayment) : '' }}</p>
          </div>

          <div v-if="editingPayment && editingPayment.type === 'student' && editingPayment.teacherId">
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Séances payées</label>
            <input
              v-model.number="form.sessionsPaid"
              type="number"
              min="0"
              step="1"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <div v-if="editingPayment && editingPayment.type === 'daycare'">
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Mois payés</label>
            <input
              v-model.number="form.monthsPaid"
              type="number"
              min="0"
              step="1"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <div v-if="isLegacyEdit" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Mois</label>
              <input
                v-model="form.month"
                type="text"
                required
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Statut</label>
              <select
                v-model="form.status"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              >
                <option value="paid">Payé</option>
                <option value="pending">En attente</option>
                <option value="overdue">En retard</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Montant (TND) *</label>
              <input
                v-model.number="form.amount"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Date de paiement</label>
              <input
                v-model="form.date"
                type="date"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
          </div>
        </template>

        <div v-if="errorMessage" class="text-xs text-red-600 bg-red-50 p-2.5 rounded-lg border border-red-200">
          {{ errorMessage }}
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-sm"
          >
            {{ submitting ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import api from '../../utils/api';
import type {
  Payment,
  Dues,
  StudentLite,
  ChildLite,
  Kind
} from '../../types/payments';
import { usePayments } from '../../composables/usePayments';
import { usePaymentForm } from '../../composables/usePaymentForm';

const props = defineProps<{
  isOpen: boolean;
  isEditing: boolean;
  editingPayment: Payment | null;
  students: StudentLite[];
  children: ChildLite[];
  dues: Dues;
  prefill?: { kind?: Kind; studentId?: number; teacherId?: number; daycareChildId?: number };
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'saved'): void;
}>();

const { firstError, paymentDetail } = usePayments();
const {
  form,
  teacherOptions,
  currentStudentDue,
  currentChildDue,
  currentUnitAmount,
  currentChildFee,
  recomputeAmount,
  onStudentChange,
  onTeacherChange,
  onChildChange,
  setKind,
  initFromPayment,
  initFromPrefill
} = usePaymentForm(props);

const kinds: Array<{ value: Kind; label: string }> = [
  { value: 'student', label: 'Élèves' },
  { value: 'daycare', label: 'Garderie' }
];

const submitting = ref(false);
const errorMessage = ref('');

const isLegacyEdit = computed(
  () => !!props.editingPayment && !props.editingPayment.studentId && !props.editingPayment.daycareChildId
);

watch(
  () => props.isOpen,
  isOpen => {
    if (!isOpen) return;
    errorMessage.value = '';
    if (props.isEditing && props.editingPayment) {
      initFromPayment(props.editingPayment);
    } else {
      initFromPrefill(props.prefill || {});
    }
  },
  { immediate: true }
);

async function handleSubmit() {
  submitting.value = true;
  errorMessage.value = '';

  try {
    if (props.isEditing && props.editingPayment) {
      const p = props.editingPayment;
      const payload: Record<string, any> = {
        amount: form.value.amount,
        date: form.value.date || null
      };
      if (p.type === 'student' && p.teacherId) payload.sessionsPaid = form.value.sessionsPaid;
      if (p.type === 'daycare') payload.monthsPaid = form.value.monthsPaid;
      if (!p.studentId && !p.daycareChildId) {
        payload.month = form.value.month;
        payload.status = form.value.status;
        payload.studentName = form.value.studentName;
      }
      await api.put(`/api/payments/${p.id}`, payload);
    } else if (form.value.kind === 'student') {
      await api.post('/api/payments', {
        studentId: form.value.studentId,
        teacherId: form.value.teacherId,
        sessionsPaid: form.value.sessionsPaid,
        amount: form.value.amount,
        date: form.value.date || null
      });
    } else {
      await api.post('/api/payments', {
        daycareChildId: form.value.daycareChildId,
        monthsPaid: form.value.monthsPaid,
        amount: form.value.amount,
        date: form.value.date || null
      });
    }

    emit('saved');
    emit('close');
  } catch (e: any) {
    console.error('Erreur sauvegarde paiement:', e);
    errorMessage.value = firstError(e, 'Erreur lors de la sauvegarde');
  } finally {
    submitting.value = false;
  }
}
</script>
