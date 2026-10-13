<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-3 sm:p-4 overflow-y-auto"
    @click.self="handleClose"
  >
    <div
      class="bg-white rounded-2xl shadow-2xl w-full max-w-lg my-6 flex flex-col border border-gray-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200"
    >
      <!-- Modal Header -->
      <div class="px-6 py-4.5 bg-gradient-to-r from-violet-700 via-purple-700 to-violet-800 text-white flex items-center justify-between shrink-0 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-md flex items-center justify-center font-bold text-lg border border-white/20 shadow-inner">
            <Pencil class="w-5 h-5 text-white" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-base font-black tracking-tight text-white">Modifier le Groupe</h2>
              <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-white/20 text-white border border-white/30">
                Pédagogie
              </span>
            </div>
            <p class="text-xs text-violet-100/90 mt-0.5">
              Mettez à jour le nom, le niveau ou la matière de votre groupe
            </p>
          </div>
        </div>

        <button
          @click="handleClose"
          class="p-1.5 rounded-xl text-white/80 hover:text-white hover:bg-white/15 transition-colors cursor-pointer"
          title="Fermer"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Modal Body / Form -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-4 text-left">
        <!-- Message d'erreur -->
        <div
          v-if="errorMessage"
          class="p-3 bg-red-50 border border-red-200 rounded-xl flex items-start gap-2.5 text-xs text-red-700 animate-in fade-in"
        >
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5 text-red-500" />
          <div class="flex-1 font-medium">{{ errorMessage }}</div>
        </div>

        <!-- Champ 1 : Nom du groupe -->
        <div class="space-y-1.5">
          <label class="block text-xs font-bold text-gray-700">
            Nom du groupe <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="ex: Bac M1, Groupe 3ème Math B..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all"
            />
          </div>
        </div>

        <!-- Champ 2 : Niveau scolaire -->
        <div class="space-y-1.5">
          <label class="block text-xs font-bold text-gray-700">
            Niveau scolaire (Cursus Tunisien) <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <select
              v-model="form.level"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all cursor-pointer font-medium"
            >
              <optgroup label="🎓 Baccalauréat (4ème Année)">
                <option v-for="lvl in bacLevels" :key="lvl.id" :value="lvl.name">{{ lvl.name }}</option>
              </optgroup>
              <optgroup label="📚 3ème Année Secondaire">
                <option v-for="lvl in lycee3Levels" :key="lvl.id" :value="lvl.name">{{ lvl.name }}</option>
              </optgroup>
              <optgroup label="📐 2ème Année Secondaire">
                <option v-for="lvl in lycee2Levels" :key="lvl.id" :value="lvl.name">{{ lvl.name }}</option>
              </optgroup>
              <optgroup label="🌱 1ère Année Secondaire">
                <option v-for="lvl in lyceeCommonLevels" :key="lvl.id" :value="lvl.name">{{ lvl.name }}</option>
              </optgroup>
              <optgroup label="🏫 Collège (Enseignement de Base)">
                <option v-for="lvl in collegeLevels" :key="lvl.id" :value="lvl.name">{{ lvl.name }}</option>
              </optgroup>
              <option value="Autre niveau">Autre niveau</option>
            </select>
          </div>
        </div>

        <!-- Champ 3 : Matière enseignée -->
        <div class="space-y-1.5">
          <label class="block text-xs font-bold text-gray-700">
            Matière enseignée <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model="form.subject"
              type="text"
              required
              placeholder="Mathématiques, Physique..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all"
            />
          </div>
        </div>

        <!-- Champ 4 : Capacité & Notes -->
        <div class="grid grid-cols-2 gap-3 pt-1">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-gray-700">Capacité max</label>
            <input
              v-model.number="form.max_capacity"
              type="number"
              min="1"
              max="50"
              class="w-full px-3.5 py-2 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all"
            />
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-gray-700">Remarques (opt.)</label>
            <input
              v-model="form.notes"
              type="text"
              placeholder="ex: Salle 2, Samedi"
              class="w-full px-3.5 py-2 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all"
            />
          </div>
        </div>

        <!-- Footer -->
        <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
          <button
            type="button"
            @click="handleClose"
            :disabled="loading"
            class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-100 text-xs font-bold transition-all cursor-pointer disabled:opacity-50"
          >
            Annuler
          </button>

          <button
            type="submit"
            :disabled="loading"
            class="px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold shadow-md shadow-violet-600/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
            <CheckCircle2 v-else class="w-4 h-4" />
            <span>{{ loading ? 'Mise à jour...' : 'Enregistrer les modifications' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue';
import { Pencil, X, CheckCircle2, AlertCircle, Loader2 } from 'lucide-vue-next';
import api from '../utils/api';
import { TUNISIAN_LEVELS } from '../utils/tunisianEducation';

const props = defineProps<{
  isOpen: boolean;
  group: any;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'groupUpdated'): void;
}>();

const loading = ref(false);
const errorMessage = ref('');

const collegeLevels = computed(() => TUNISIAN_LEVELS.filter(l => l.cycle === 'college'));
const lyceeCommonLevels = computed(() => TUNISIAN_LEVELS.filter(l => l.id.startsWith('1_')));
const lycee2Levels = computed(() => TUNISIAN_LEVELS.filter(l => l.id.startsWith('2_')));
const lycee3Levels = computed(() => TUNISIAN_LEVELS.filter(l => l.id.startsWith('3_')));
const bacLevels = computed(() => TUNISIAN_LEVELS.filter(l => l.id.startsWith('4_bac')));

const form = reactive({
  name: '',
  level: '',
  subject: '',
  max_capacity: 15,
  notes: '',
});

watch(
  () => props.isOpen,
  (open) => {
    if (open && props.group) {
      errorMessage.value = '';
      form.name = props.group.group_name || props.group.name || '';
      form.level = props.group.level || '4ème Année Bac Mathématiques';
      form.subject = props.group.subjects?.[0] || props.group.subject || 'Mathématiques';
      form.max_capacity = props.group.max_capacity || 15;
      form.notes = props.group.notes || '';
    }
  },
  { immediate: true }
);

function handleClose() {
  if (loading.value) return;
  emit('close');
}

async function handleSubmit() {
  if (!form.name.trim()) {
    errorMessage.value = 'Le nom du groupe est obligatoire.';
    return;
  }

  loading.value = true;
  errorMessage.value = '';

  const identifier = props.group?.id || props.group?.group_name || props.group?.name;

  try {
    await api.put(`/api/groups/${identifier}`, {
      name: form.name.trim(),
      level: form.level,
      subject: form.subject.trim(),
      max_capacity: form.max_capacity || null,
      notes: form.notes || null,
    });

    emit('groupUpdated');
    emit('close');
  } catch (err: any) {
    console.error('Erreur mise à jour groupe:', err);
    errorMessage.value = err.response?.data?.message || 'Erreur lors de la mise à jour du groupe.';
  } finally {
    loading.value = false;
  }
}
</script>
