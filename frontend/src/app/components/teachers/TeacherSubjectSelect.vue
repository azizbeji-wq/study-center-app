<template>
  <div>
    <div class="flex items-center justify-between mb-1.5">
      <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
        Matière(s) enseignée(s) *
      </label>
      <span class="text-[11px] text-indigo-600 font-medium">Un prof peut enseigner plusieurs matières</span>
    </div>

    <!-- Selected Subjects Chips -->
    <div class="min-h-[44px] p-2 bg-gray-50 border border-gray-300 rounded-lg flex flex-wrap items-center gap-1.5 mb-2">
      <span
        v-for="sub in modelValue"
        :key="sub"
        class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-600 text-white text-xs font-medium rounded-md shadow-2xs"
      >
        <span>{{ sub }}</span>
        <button
          type="button"
          @click="removeSubject(sub)"
          class="text-indigo-200 hover:text-white ml-0.5 p-0.5 cursor-pointer"
          title="Retirer cette matière"
        >
          <X class="w-3 h-3" />
        </button>
      </span>

      <span v-if="modelValue.length === 0" class="text-xs text-rose-500 italic pl-1">
        Veuillez sélectionner au moins une matière ci-dessous.
      </span>
    </div>

    <!-- Subject Dropdown to Add -->
    <div class="flex gap-2">
      <select
        v-model="newSubjectSelect"
        @change="onAddSubjectFromSelect"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
      >
        <option value="">+ Ajouter une matière du programme...</option>
        <option
          v-for="sub in tunisianSubjects"
          :key="sub"
          :value="sub"
          :disabled="modelValue.includes(sub)"
        >
          {{ sub }} {{ modelValue.includes(sub) ? '(déjà ajoutée)' : '' }}
        </option>
      </select>
    </div>

    <!-- Quick Suggestions Tags -->
    <div class="flex flex-wrap gap-1 mt-2">
      <button
        v-for="quick in commonQuickSubjects"
        :key="quick"
        type="button"
        @click="toggleSubject(quick)"
        :class="[
          'text-[11px] px-2 py-0.5 rounded-full border transition-colors cursor-pointer',
          modelValue.includes(quick)
            ? 'bg-indigo-100 text-indigo-800 border-indigo-300 font-semibold'
            : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
        ]"
      >
        {{ modelValue.includes(quick) ? '✓ ' : '+ ' }}{{ quick }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { X } from 'lucide-vue-next';
import { ALL_TUNISIAN_SUBJECTS } from '../../utils/tunisianEducation';

const props = defineProps<{
  modelValue: string[];
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', val: string[]): void;
}>();

const tunisianSubjects = ALL_TUNISIAN_SUBJECTS;
const commonQuickSubjects = [
  'Mathématiques',
  'Sciences Physiques',
  'SVT',
  'Informatique',
  'Français',
  'Anglais',
  'Arabe',
  'Philosophie',
  'Économie',
  'Gestion',
  'Histoire-Géo'
];

const newSubjectSelect = ref('');

function addSubject(sub: string) {
  if (!sub) return;
  if (!props.modelValue.includes(sub)) {
    emit('update:modelValue', [...props.modelValue, sub]);
  }
}

function removeSubject(sub: string) {
  emit('update:modelValue', props.modelValue.filter(s => s !== sub));
}

function toggleSubject(sub: string) {
  if (props.modelValue.includes(sub)) {
    removeSubject(sub);
  } else {
    addSubject(sub);
  }
}

function onAddSubjectFromSelect() {
  if (newSubjectSelect.value) {
    addSubject(newSubjectSelect.value);
    newSubjectSelect.value = '';
  }
}
</script>
