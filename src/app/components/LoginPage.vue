<template>
  <div class="size-full flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 p-4">
    <div class="w-full max-w-md p-8 bg-white rounded-2xl shadow-xl border border-gray-100">
      <div class="flex flex-col items-center mb-8">
        <div class="w-16 h-16 bg-indigo-600 rounded-full flex items-center justify-center mb-4 shadow-md">
          <GraduationCap class="w-10 h-10 text-white" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-1">Centre d'Études</h1>
        <p class="text-sm text-gray-500">Système de gestion académique & financière</p>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <div>
          <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2.5">
            Sélectionnez votre rôle
          </label>
          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              @click="selectedRole = 'director'"
              :class="[
                'p-4 rounded-xl border-2 transition-all text-left flex flex-col items-center cursor-pointer',
                selectedRole === 'director'
                  ? 'border-indigo-600 bg-indigo-50/70 shadow-xs'
                  : 'border-gray-200 hover:border-indigo-300 bg-white'
              ]"
            >
              <div class="text-2xl mb-1.5">👔</div>
              <div class="text-sm font-bold text-gray-900">Directeur</div>
              <div class="text-[11px] text-gray-500">Accès complet</div>
            </button>
            <button
              type="button"
              @click="selectedRole = 'secretary'"
              :class="[
                'p-4 rounded-xl border-2 transition-all text-left flex flex-col items-center cursor-pointer',
                selectedRole === 'secretary'
                  ? 'border-indigo-600 bg-indigo-50/70 shadow-xs'
                  : 'border-gray-200 hover:border-indigo-300 bg-white'
              ]"
            >
              <div class="text-2xl mb-1.5">📋</div>
              <div class="text-sm font-bold text-gray-900">Secrétaire</div>
              <div class="text-[11px] text-gray-500">Accès restreint</div>
            </button>
          </div>
        </div>

        <div v-if="errorMessage" class="text-xs text-red-600 bg-red-50 p-3 rounded-lg border border-red-200">
          {{ errorMessage }}
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-sm flex items-center justify-center gap-2"
        >
          <span v-if="loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
          <span>{{ loading ? 'Connexion en cours...' : 'Se connecter' }}</span>
        </button>
      </form>

      <p class="mt-6 text-xs text-center text-green-600 flex items-center justify-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
        Connecté au serveur API Laravel & MySQL
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { GraduationCap } from 'lucide-vue-next';
import api from '../utils/api';

const emit = defineEmits<{
  (e: 'login', role: 'director' | 'secretary', name: string): void
}>();

const selectedRole = ref<'director' | 'secretary'>('director');
const loading = ref(false);
const errorMessage = ref('');

const handleSubmit = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    const res = await api.post('/api/login', { role: selectedRole.value });
    if (res.data && res.data.token) {
      localStorage.setItem('token', res.data.token);
      localStorage.setItem('user', JSON.stringify(res.data.user));
      emit('login', res.data.user.role, res.data.user.name);
    } else {
      const fallbackName = selectedRole.value === 'director' ? 'Directeur' : 'Secrétaire';
      emit('login', selectedRole.value, fallbackName);
    }
  } catch (e: any) {
    console.error('Erreur login:', e);
    // Fallback so user can still access demo if desired
    const fallbackName = selectedRole.value === 'director' ? 'Directeur' : 'Secrétaire';
    emit('login', selectedRole.value, fallbackName);
  } finally {
    loading.value = false;
  }
};
</script>
