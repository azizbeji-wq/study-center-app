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
            <Users class="w-5 h-5 text-white" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-base font-black tracking-tight text-white">Nouveau Groupe Pédagogique</h2>
              <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-white/20 text-white border border-white/30">
                Espace Enseignant
              </span>
            </div>
            <p class="text-xs text-violet-100/90 mt-0.5">
              Créez une nouvelle classe pour organiser vos séances et vos élèves
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

        <!-- Message de succès -->
        <div
          v-if="successMessage"
          class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-2.5 text-xs text-emerald-700 animate-in fade-in"
        >
          <CheckCircle2 class="w-4 h-4 shrink-0 mt-0.5 text-emerald-500" />
          <div class="flex-1 font-medium">{{ successMessage }}</div>
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
              placeholder="ex: Groupe Bac Sciences A, Groupe 3ème Math B..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all placeholder:text-gray-400"
            />
          </div>
          <p class="text-[11px] text-gray-400">Identifiant visuel qui sera affiché sur les feuilles d'appel et le planning.</p>
        </div>

        <!-- Champ 2 : Niveau scolaire -->
        <div class="space-y-1.5">
          <label class="block text-xs font-bold text-gray-700">
            Niveau scolaire <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <select
              v-model="form.level"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all cursor-pointer"
            >
              <option disabled value="">Sélectionnez un niveau scolaire</option>
              <option v-for="lvl in availableLevels" :key="lvl" :value="lvl">
                {{ lvl }}
              </option>
            </select>
          </div>
        </div>

        <!-- Champ 3 : Matière enseignée -->
        <div class="space-y-1.5">
          <label class="block text-xs font-bold text-gray-700 flex items-center justify-between">
            <span>Matière enseignée <span class="text-red-500">*</span></span>
            <span class="text-[11px] font-normal text-violet-600">Vos matières affiliées</span>
          </label>
          <div class="relative">
            <select
              v-model="form.subject"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all cursor-pointer"
            >
              <option disabled value="">Sélectionnez la matière</option>
              <option v-for="subj in normalizedSubjects" :key="subj" :value="subj">
                {{ subj }}
              </option>
            </select>
          </div>
          <p class="text-[11px] text-gray-400">Cette matière déterminera les feuilles de présence et le décompte de vos cours.</p>
        </div>

        <!-- Champ 4 : Capacité max (optionnel) -->
        <div class="grid grid-cols-2 gap-3 pt-1">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-gray-700">
              Capacité maximale
            </label>
            <input
              v-model.number="form.max_capacity"
              type="number"
              min="1"
              max="50"
              placeholder="15 élèves"
              class="w-full px-3.5 py-2 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all"
            />
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-gray-700">
              Remarque / Salle (opt.)
            </label>
            <input
              v-model="form.notes"
              type="text"
              placeholder="ex: Salle 2, créneau samedi"
              class="w-full px-3.5 py-2 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-600 focus:bg-white transition-all"
            />
          </div>
        </div>

        <!-- Modal Footer Actions -->
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
            <Plus v-else class="w-4 h-4" />
            <span>{{ loading ? 'Création en cours...' : 'Créer le groupe' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue';
import { Users, X, Plus, AlertCircle, CheckCircle2, Loader2 } from 'lucide-vue-next';
import api from '../utils/api';

const props = defineProps<{
  isOpen: boolean;
  subjects?: string[];
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'groupCreated', group: any): void;
}>();

const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const availableLevels = [
  'Baccalauréat',
  '4ème Année Secondaire',
  '3ème Année Secondaire',
  '2ème Année Secondaire',
  '1ère Année Secondaire',
  '9ème Année de Base',
  '8ème Année de Base',
  '7ème Année de Base',
  '6ème Année Primaire',
  '5ème Année Primaire',
  'Autre niveau',
];

const normalizedSubjects = computed(() => {
  if (props.subjects && props.subjects.length > 0) {
    return props.subjects;
  }
  return [
    'Mathématiques',
    'Physique-Chimie',
    'Sciences de la Vie et de la Terre (SVT)',
    'Français',
    'Anglais',
    'Informatique',
    'Économie & Gestion',
    'Philosophie',
    'Arabe',
  ];
});

const form = reactive({
  name: '',
  level: 'Baccalauréat',
  subject: '',
  max_capacity: 15,
  notes: '',
});

// Initialiser la matière par défaut à l'ouverture du modal
watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      errorMessage.value = '';
      successMessage.value = '';
      form.name = '';
      form.level = 'Baccalauréat';
      form.max_capacity = 15;
      form.notes = '';
      form.subject = normalizedSubjects.value[0] || 'Mathématiques';
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

  if (!form.level) {
    errorMessage.value = 'Veuillez sélectionner un niveau scolaire.';
    return;
  }

  if (!form.subject) {
    errorMessage.value = 'Veuillez sélectionner une matière.';
    return;
  }

  loading.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const payload = {
      name: form.name.trim(),
      level: form.level,
      subject: form.subject,
      max_capacity: form.max_capacity || 15,
      notes: form.notes?.trim() || null,
    };

    const res = await api.post('/api/groups', payload);

    successMessage.value = res.data?.message || 'Groupe créé avec succès !';
    
    // Informer le composant parent du nouveau groupe pour actualiser la liste
    emit('groupCreated', res.data?.group || res.data);

    // Fermer le modal après un bref instant pour confirmer la réussite
    setTimeout(() => {
      emit('close');
    }, 400);
  } catch (err: any) {
    console.error('Erreur lors de la création du groupe:', err);
    if (err.response?.data?.message) {
      errorMessage.value = err.response.data.message;
    } else if (err.response?.data?.errors) {
      const firstError = Object.values(err.response.data.errors)[0] as string[];
      errorMessage.value = Array.isArray(firstError) ? firstError[0] : String(firstError);
    } else {
      errorMessage.value = 'Impossible de créer le groupe. Veuillez réessayer.';
    }
  } finally {
    loading.value = false;
  }
}
</script>
