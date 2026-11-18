<template>
  <div class="size-full min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 p-4">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl border border-gray-100 p-8 space-y-6 animate-in fade-in zoom-in-95">
      <!-- En-tête avec Logo -->
      <div class="flex flex-col items-center text-center">
        <div class="w-16 h-16 bg-white rounded-2xl p-2.5 flex items-center justify-center mb-3 shadow-md border border-gray-100">
          <img src="/logo.png" alt="Logo Centre d'Études" class="w-full h-full object-contain" />
        </div>
        <h1 class="text-2xl font-black text-gray-900 tracking-tight">Centre d'Études</h1>
        <p class="text-xs text-gray-500 mt-0.5">Direction Financière, Pédagogie & Administration</p>
      </div>

      <!-- Message d'information de session expirée -->
      <div
        v-if="activeNotice"
        class="p-3.5 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-xs font-semibold flex items-center gap-2.5 animate-in fade-in"
      >
        <Clock class="w-4 h-4 shrink-0 text-amber-600" />
        <span class="leading-relaxed">{{ activeNotice }}</span>
      </div>

      <!-- Message d'erreur -->
      <div
        v-if="errorMessage"
        class="p-3.5 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-semibold flex items-center gap-2 animate-in fade-in"
      >
        <AlertCircle class="w-4 h-4 shrink-0 text-red-600" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Formulaire de Connexion -->
      <form @submit.prevent="handleLoginSubmit" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1.5">
            Adresse Email
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
              <Mail class="w-4 h-4" />
            </div>
            <input
              v-model="email"
              type="email"
              required
              placeholder="nom@exemple.com"
              class="w-full pl-10 pr-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-base text-gray-900 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium"
            />
          </div>
        </div>

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="block text-xs font-bold text-gray-700">
              Mot de passe
            </label>
          </div>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
              <Lock class="w-4 h-4" />
            </div>
            <input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              required
              placeholder="••••••••"
              class="w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-base text-gray-900 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
              title="Afficher/masquer le mot de passe"
            >
              <Eye v-if="!showPassword" class="w-4 h-4" />
              <EyeOff v-else class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Bouton Se Connecter -->
        <button
          type="submit"
          :disabled="loading"
          class="w-full min-h-[44px] py-3 px-4 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold rounded-xl transition-all shadow-md shadow-indigo-600/20 flex items-center justify-center gap-2 cursor-pointer focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        >
          <span v-if="loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
          <LogIn v-else class="w-4 h-4" />
          <span>{{ loading ? 'Vérification...' : 'Se connecter' }}</span>
        </button>
      </form>

      <!-- Séparateur Comptes Démo -->
      <div class="relative flex py-1 items-center">
        <div class="flex-grow border-t border-gray-200"></div>
        <span class="flex-shrink mx-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
          Connexion Démo Rapide (1 clic)
        </span>
        <div class="flex-grow border-t border-gray-200"></div>
      </div>

      <!-- Boutons 1-clic Démo -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <button
          type="button"
          @click="fillAndLogin('director@studycenter.com', 'director123')"
          :disabled="loading"
          class="min-h-[44px] px-3 py-2 flex items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50/80 hover:bg-indigo-100 text-indigo-900 transition-all text-xs font-bold cursor-pointer disabled:opacity-50 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          title="Se connecter avec le compte Directeur (director@studycenter.com)"
        >
          <Crown class="w-4 h-4 text-indigo-700 shrink-0" />
          <div class="text-left">
            <span class="block leading-tight">Directeur</span>
            <span class="text-[10px] font-normal text-indigo-700/80">Gestion totale</span>
          </div>
        </button>

        <button
          type="button"
          @click="fillAndLogin('secretary@studycenter.com', 'secretary123')"
          :disabled="loading"
          class="min-h-[44px] px-3 py-2 flex items-center justify-center gap-2 rounded-xl border border-purple-200 bg-purple-50/80 hover:bg-purple-100 text-purple-900 transition-all text-xs font-bold cursor-pointer disabled:opacity-50 focus:ring-2 focus:ring-purple-500 focus:outline-none"
          title="Se connecter avec le compte Secrétaire (secretary@studycenter.com)"
        >
          <ClipboardList class="w-4 h-4 text-purple-700 shrink-0" />
          <div class="text-left">
            <span class="block leading-tight">Secrétaire</span>
            <span class="text-[10px] font-normal text-purple-700/80">Accueil & Frais</span>
          </div>
        </button>

        <button
          type="button"
          @click="fillAndLogin('teacher@studycenter.com', 'teacher123')"
          :disabled="loading"
          class="min-h-[44px] px-3 py-2 flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50/80 hover:bg-emerald-100 text-emerald-900 transition-all text-xs font-bold cursor-pointer disabled:opacity-50 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
          title="Se connecter avec le compte Enseignant (teacher@studycenter.com)"
        >
          <GraduationCap class="w-4 h-4 text-emerald-700 shrink-0" />
          <div class="text-left">
            <span class="block leading-tight">Enseignant</span>
            <span class="text-[10px] font-normal text-emerald-700/80">Appel & Cours</span>
          </div>
        </button>
      </div>


      <!-- Badge de Sécurité & Lien Guide d'Utilisation -->
      <div class="space-y-2 pt-1 text-center">
        <div>
          <a
            href="/guide_utilisation.html"
            target="_blank"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors hover:underline"
          >
            <BookOpen class="w-3.5 h-3.5" />
            <span>Consulter le Guide d'Utilisation Officiel</span>
          </a>
        </div>
        <div class="text-[11px] text-gray-400 flex items-center justify-center gap-1.5">
          <ShieldCheck class="w-3.5 h-3.5 text-emerald-600" />
          <span>Authentification chiffrée Laravel Sanctum</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Mail, Lock, Eye, EyeOff, LogIn, AlertCircle, ShieldCheck, Clock, Crown, ClipboardList, GraduationCap, BookOpen } from 'lucide-vue-next';
import api from '../utils/api';
import { setSession, consumeNotice } from '../utils/session';

const props = defineProps<{
  sessionNotice?: string;
}>();

const emit = defineEmits<{
  (e: 'login', role: 'director' | 'secretary' | 'teacher', name: string, permissions?: string[]): void
}>();

const email = ref('');
const password = ref('');
const showPassword = ref(false);
const loading = ref(false);
const errorMessage = ref('');
const activeNotice = ref('');

onMounted(() => {
  activeNotice.value = props.sessionNotice || consumeNotice() || '';
});

watch(() => props.sessionNotice, (newVal) => {
  if (newVal) {
    activeNotice.value = newVal;
  }
});

function fillAndLogin(demoEmail: string, demoPass: string) {
  email.value = demoEmail;
  password.value = demoPass;
  handleLoginSubmit();
}

async function handleLoginSubmit() {
  loading.value = true;
  errorMessage.value = '';
  activeNotice.value = '';

  try {
    const res = await api.post('/api/login', {
      email: email.value.trim(),
      password: password.value,
    });

    if (res.data && res.data.token && res.data.user) {
      const loginTime = res.data.login_time ? new Date(res.data.login_time).getTime() : Date.now();
      setSession(res.data.token, res.data.user, loginTime);
      emit('login', res.data.user.role, res.data.user.name, res.data.user.permissions);
    } else {
      errorMessage.value = 'Réponse serveur inattendue.';
    }
  } catch (err: any) {
    console.error('Erreur login:', err);
    if (err.response?.status === 403) {
      errorMessage.value = err.response.data?.message || 'Ce compte est inactif.';
    } else if (err.response?.status === 401) {
      errorMessage.value = 'Adresse email ou mot de passe incorrect.';
    } else if (err.response?.status === 429) {
      errorMessage.value = 'Trop de tentatives de connexion. Veuillez patienter 1 minute.';
    } else {
      errorMessage.value = err.response?.data?.message || 'Impossible de joindre le serveur. Vérifiez la connexion.';
    }
  } finally {
    loading.value = false;
  }
}
</script>
