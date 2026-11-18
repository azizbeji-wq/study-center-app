<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
    @click="$emit('close')"
  >
    <div
      class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full space-y-4 max-h-[90vh] overflow-y-auto mx-4 md:mx-auto"
      @click.stop
    >
      <div class="flex items-start justify-between border-b border-gray-100 pb-3">
        <div>
          <h3 class="text-base font-bold text-gray-900">
            {{ editingSchool ? "Modifier l'école" : "Ajouter une école primaire" }}
          </h3>
          <p class="text-xs text-gray-500 mt-0.5">
            Les écoles partenaires permettent de classer les emplois du temps.
          </p>
        </div>
        <button @click="$emit('close')" aria-label="Fermer" class="text-gray-400 hover:text-gray-600 p-1 cursor-pointer">
          <X class="w-5 h-5" />
        </button>
      </div>

      <div class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Nom de l'école *</label>
          <input
            v-model="schoolForm.name"
            type="text"
            placeholder="ex: École Primaire Habib Bourguiba"
            class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Ville / Gouvernorat</label>
          <input
            v-model="schoolForm.city"
            type="text"
            placeholder="ex: Tunis, Ariana, Ben Arous..."
            class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Adresse complète</label>
          <input
            v-model="schoolForm.address"
            type="text"
            placeholder="ex: Avenue Habib Bourguiba, Centre Ville"
            class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
        </div>
      </div>

      <div
        v-if="schoolError"
        class="text-xs text-rose-700 bg-rose-50 p-2.5 rounded-lg border border-rose-200"
      >
        {{ schoolError }}
      </div>

      <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
        <button
          type="button"
          @click="$emit('close')"
          class="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50 cursor-pointer"
        >
          Annuler
        </button>
        <button
          type="button"
          :disabled="!schoolForm.name.trim() || isSavingSchool"
          @click="saveSchool"
          class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-xs cursor-pointer"
        >
          {{ isSavingSchool ? 'Enregistrement...' : (editingSchool ? 'Mettre à jour' : 'Ajouter') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { X } from 'lucide-vue-next';
import api from '../../utils/api';
import type { School } from '../../types/calendar';
import { useSchoolCalendar } from '../../composables/useSchoolCalendar';

const props = defineProps<{
  isOpen: boolean;
  editingSchool: School | null;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'saved', payload: { id: number; name: string; isEdit: boolean }): void;
}>();

const { extractError } = useSchoolCalendar();

const schoolForm = ref({
  name: '',
  city: '',
  address: ''
});
const schoolError = ref('');
const isSavingSchool = ref(false);

watch(
  () => props.isOpen,
  isOpen => {
    if (!isOpen) return;
    schoolError.value = '';
    if (props.editingSchool) {
      schoolForm.value = {
        name: props.editingSchool.name,
        city: props.editingSchool.city || '',
        address: props.editingSchool.address || ''
      };
    } else {
      schoolForm.value = {
        name: '',
        city: '',
        address: ''
      };
    }
  },
  { immediate: true }
);

async function saveSchool() {
  if (!schoolForm.value.name.trim()) return;

  isSavingSchool.value = true;
  schoolError.value = '';

  try {
    let savedId: number;
    const isEdit = !!props.editingSchool;
    if (props.editingSchool) {
      const res = await api.put(`/api/schools/${props.editingSchool.id}`, schoolForm.value);
      savedId = res.data.id;
    } else {
      const res = await api.post('/api/schools', schoolForm.value);
      savedId = res.data.id;
    }

    emit('saved', { id: savedId, name: schoolForm.value.name, isEdit });
    emit('close');
  } catch (e) {
    schoolError.value = extractError(e, "L'enregistrement de l'école a échoué.");
  } finally {
    isSavingSchool.value = false;
  }
}
</script>
