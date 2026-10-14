<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4"
    @click="$emit('close')"
  >
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-lg w-full space-y-4 max-h-[90vh] overflow-y-auto mx-4 md:mx-auto" @click.stop>
      <div class="flex items-center justify-between border-b pb-3 border-gray-100">
        <div>
          <h3 class="text-xl font-bold text-gray-900">
            {{ isEditing ? "Modifier l'enseignant" : 'Ajouter un enseignant' }}
          </h3>
          <p class="text-xs text-gray-500">Programme tunisien & Mode de rémunération par séance</p>
        </div>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">×</button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Nom complet *</label>
            <button
              v-if="!isEditing && currentUser?.name"
              type="button"
              @click="fillWithMyAccount"
              class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 underline cursor-pointer"
              title="Créer un profil enseignant pour vous-même"
            >
              + Remplir avec mon compte ({{ currentUser.name }})
            </button>
          </div>
          <input
            v-model="formData.name"
            type="text"
            required
            placeholder="Ex: Prof. Karim Belhadj"
            class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
        </div>

        <!-- Tunisian Multiple Subjects Select -->
        <TeacherSubjectSelect v-model="formData.subjects" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Téléphone *</label>
            <input
              v-model="formData.phone"
              type="text"
              required
              placeholder="Ex: 21 345 678"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Leçons / sem</label>
            <input
              v-model.number="formData.weeklyLessons"
              type="number"
              min="0"
              placeholder="Ex: 4"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Email *</label>
          <input
            v-model="formData.email"
            type="email"
            required
            placeholder="Ex: karim.belhadj@email.tn"
            class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
        </div>

        <!-- Rémunération Enseignant : Formule B & Formule C -->
        <div class="bg-emerald-50/70 p-4 rounded-xl border border-emerald-200/80 space-y-3.5">
          <div class="flex items-center justify-between">
            <label class="block text-xs font-bold text-emerald-950 uppercase tracking-wider">
              Paramètres Financiers & Formule de Rémunération
            </label>
            <span class="text-[11px] font-semibold text-emerald-700 bg-white px-2 py-0.5 rounded-full border border-emerald-200">
              Idara & Enseignant
            </span>
          </div>

          <!-- 1. Paramètres de Base (Part Administration & Prix Séance) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">
                Part Fixe de l'Administration par Élève *
              </label>
              <div class="relative">
                <input
                  v-model.number="formData.center_fixed_per_student"
                  type="number"
                  min="0"
                  step="1"
                  placeholder="Ex: 20 TND"
                  required
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-white font-bold text-indigo-700 focus:ring-2 focus:ring-emerald-500 pr-14"
                />
                <span class="absolute right-3 top-2.5 text-xs text-gray-400 font-bold">DT / élève</span>
              </div>
              <p class="text-[11px] text-gray-500 mt-1">
                Montant fixe prélevé par l'administration sur chaque élève inscrit.
              </p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">
                Prix de la Séance pour l'Élève *
              </label>
              <div class="relative">
                <input
                  v-model.number="formData.salary_per_lesson"
                  type="number"
                  min="0"
                  step="1"
                  placeholder="Ex: 25 TND"
                  required
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-white font-bold text-gray-900 focus:ring-2 focus:ring-emerald-500 pr-14"
                />
                <span class="absolute right-3 top-2.5 text-xs text-gray-400 font-bold">DT / séance</span>
              </div>
              <p class="text-[11px] text-gray-500 mt-1">
                Appliqué lors de l'inscription de l'élève (Frais = séances × ce prix).
              </p>
            </div>
          </div>

          <!-- 2. Formule de rémunération (A, B ou C) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Formule de rémunération</label>
              <select
                v-model="formData.remuneration_type"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white font-semibold text-gray-900 focus:ring-2 focus:ring-emerald-500"
              >
                <option value="percentage">Formule C : Pourcentage (%) du cours</option>
                <option value="fixed">Formule B : Part Fixe Enseignant par Élève</option>
                <option value="fixed_center">Formule A : Part Fixe Administration par Élève</option>
              </select>
            </div>

            <div>
              <template v-if="formData.remuneration_type === 'percentage'">
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                  Part Enseignant (%) *
                </label>
                <div class="relative">
                  <input
                    v-model.number="formData.rate_percentage"
                    type="number"
                    min="1"
                    max="100"
                    step="1"
                    placeholder="Ex: 70%"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500 pr-8"
                  />
                  <span class="absolute right-3 top-2 text-xs text-gray-400 font-bold">%</span>
                </div>
              </template>
              <template v-else-if="formData.remuneration_type === 'fixed_center'">
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                  Part Fixe Administration (TND / élève) *
                </label>
                <div class="relative">
                  <input
                    v-model.number="formData.center_fixed_per_student"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="Ex: 20 TND"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white font-bold text-indigo-700 focus:ring-2 focus:ring-emerald-500 pr-12"
                  />
                  <span class="absolute right-3 top-2 text-xs text-gray-400 font-bold">DT / élève</span>
                </div>
              </template>
              <template v-else>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                  Part Fixe Enseignant par Élève (TND / élève) *
                </label>
                <div class="relative">
                  <input
                    v-model.number="formData.fixed_student_rate"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="Ex: 15 TND"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500 pr-12"
                  />
                  <span class="absolute right-3 top-2 text-xs text-gray-400 font-bold">DT / élève</span>
                </div>
              </template>
            </div>
          </div>

          <!-- Simulation & Règle Remise Absorbée par l'Administration -->
          <div class="text-[11px] text-emerald-900 bg-white/90 p-3 rounded-lg border border-emerald-200 space-y-1">
            <div v-if="formData.remuneration_type === 'percentage'">
              💡 <strong>Formule C (Pourcentage) :</strong> Pour un cours de 4 séances à {{ formData.salary_per_lesson || 25 }} TND (100 TND brut par élève), l'enseignant touche <strong>{{ formData.rate_percentage || 70 }}%</strong> ({{ ((formData.salary_per_lesson || 25) * 4 * (formData.rate_percentage || 70) / 100).toFixed(0) }} TND/élève) et le centre conserve le reste.
            </div>
            <div v-else-if="formData.remuneration_type === 'fixed_center'">
              💡 <strong>Formule A (Part Fixe Administration) :</strong> L'administration prélève <strong>{{ formData.center_fixed_per_student || 20 }} TND par élève</strong>. L'enseignant touche tout le reste du cours brut (ex: 100 TND - {{ formData.center_fixed_per_student || 20 }} TND = {{ Math.max(0, ((formData.salary_per_lesson || 25) * 4) - (formData.center_fixed_per_student || 20)) }} TND/élève).
            </div>
            <div v-else>
              💡 <strong>Formule B (Fixe par élève) :</strong> L'enseignant touche un montant fixe de <strong>{{ formData.fixed_student_rate || 15 }} TND par élève</strong> inscrit chaque mois. Le centre conserve la marge restante.
            </div>
            <div class="text-amber-800 font-medium pt-1 border-t border-emerald-100 flex items-start gap-1">
              <span>🛡️</span>
              <span><strong>Protection Enseignant :</strong> Toute remise (discount) accordée à un élève est absorbée à 100% par l'administration. La rémunération du professeur reste calculée sur le plein tarif brut.</span>
            </div>
          </div>
        </div>

        <div v-if="errorMessage" class="text-xs text-red-600 bg-red-50 p-2.5 rounded-lg border border-red-200">
          {{ errorMessage }}
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 cursor-pointer"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-sm cursor-pointer"
          >
            {{ submitting ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import api from '../../utils/api';
import { parseSubjects } from '../../utils/teacherSubjects';
import TeacherSubjectSelect from './TeacherSubjectSelect.vue';
import type { Teacher, TeacherFormData } from '../../types/teachers';

const props = defineProps<{
  isOpen: boolean;
  isEditing: boolean;
  editingTeacher: Teacher | null;
  currentUser?: { name?: string; email?: string; phone?: string } | null;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'saved', teacher: Teacher): void;
}>();

function defaultFormData(): TeacherFormData {
  return {
    name: '',
    subjects: ['Mathématiques'],
    phone: '',
    email: '',
    weeklyLessons: 4,
    remuneration_type: 'percentage',
    rate_percentage: 70,
    salary_per_lesson: 25,
    fixed_student_rate: 15,
    center_fixed_per_student: 20
  };
}

const formData = ref<TeacherFormData>(defaultFormData());
const submitting = ref(false);
const errorMessage = ref('');

function fillWithMyAccount() {
  if (!props.currentUser) return;
  if (props.currentUser.name) formData.value.name = props.currentUser.name;
  if (props.currentUser.email) formData.value.email = props.currentUser.email;
  if (props.currentUser.phone) formData.value.phone = props.currentUser.phone;
}

watch(
  () => props.isOpen,
  isOpen => {
    if (!isOpen) return;
    errorMessage.value = '';
    if (props.isEditing && props.editingTeacher) {
      const t = props.editingTeacher;
      const subs = t.subjects && t.subjects.length > 0 ? t.subjects : parseSubjects(t.subject);
      formData.value = {
        name: t.name,
        subjects: subs.length > 0 ? [...subs] : ['Mathématiques'],
        phone: t.phone,
        email: t.email,
        weeklyLessons: t.weeklyLessons || 0,
        remuneration_type: t.remuneration_type || 'percentage',
        rate_percentage: t.rate_percentage || 70,
        salary_per_lesson: t.salary_per_lesson || 25,
        fixed_student_rate: t.fixed_student_rate || 15,
        center_fixed_per_student: t.center_fixed_per_student || 20
      };
    } else {
      formData.value = defaultFormData();
    }
  },
  { immediate: true }
);

async function handleSubmit() {
  if (formData.value.subjects.length === 0) {
    errorMessage.value = 'Veuillez sélectionner au moins une matière pour cet enseignant.';
    return;
  }

  submitting.value = true;
  errorMessage.value = '';

  const payload = {
    ...formData.value,
    rate_percentage: Number(formData.value.rate_percentage) || 70,
    salary_per_lesson: Number(formData.value.salary_per_lesson) || 25,
    fixed_student_rate: Number(formData.value.fixed_student_rate) || 15,
    center_fixed_per_student: Number(formData.value.center_fixed_per_student) || 20,
    subject: formData.value.subjects.join(', '),
    subjects: formData.value.subjects
  };

  try {
    let saved: Teacher;
    if (props.isEditing && props.editingTeacher) {
      const res = await api.put(`/api/teachers/${props.editingTeacher.id}`, payload);
      saved = res.data;
    } else {
      const res = await api.post('/api/teachers', payload);
      saved = res.data;
    }
    emit('saved', saved);
    emit('close');
  } catch (e: any) {
    console.error('Erreur enregistrement enseignant:', e);
    errorMessage.value = e.response?.data?.message || 'Erreur lors de l\'enregistrement';
  } finally {
    submitting.value = false;
  }
}
</script>
