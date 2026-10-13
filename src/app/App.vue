<template>
  <div class="size-full bg-gray-50">
    <LoginPage
      v-if="!user"
      :session-notice="sessionNotice"
      @login="handleLogin"
    />
    <template v-else>
      <DirectorDashboard
        v-if="activePortal === 'director'"
        :user-name="user.name"
        @switch-portal="switchPortal"
        @logout="handleLogout"
      />
      <SecretaryDashboard
        v-else-if="activePortal === 'secretary'"
        :user-name="user.name"
        :permissions="user.permissions"
        @switch-portal="switchPortal"
        @logout="handleLogout"
      />
      <TeacherDashboard
        v-else-if="activePortal === 'teacher'"
        :user-name="user.name"
        :primary-role="user.role"
        @switch-back="switchPortal(user.role)"
        @logout="handleLogout"
      />

      <!-- Widget Flottant Global Assistant IA Gemini -->
      <AiTutorialWidget
        :user-role="activePortal || user.role"
        :user-name="user.name"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import LoginPage from './components/LoginPage.vue';
import DirectorDashboard from './components/DirectorDashboard.vue';
import SecretaryDashboard from './components/SecretaryDashboard.vue';
import TeacherDashboard from './components/TeacherDashboard.vue';
import AiTutorialWidget from './components/AiTutorialWidget.vue';
import api from './utils/api';
import {
  checkSessionValidity,
  clearSession,
  getStoredUser,
  consumeNotice,
  startSessionMonitor,
} from './utils/session';

interface User {
  role: 'director' | 'secretary' | 'teacher';
  name: string;
  permissions?: string[];
}

const user = ref<User | null>(null);
const activePortal = ref<'director' | 'secretary' | 'teacher'>('director');
const sessionNotice = ref<string>('');
let stopMonitor: (() => void) | null = null;

const switchPortal = (portal: 'director' | 'secretary' | 'teacher') => {
  activePortal.value = portal;
  sessionStorage.setItem('active_portal', portal);
};

const onSessionExpired = (message: string) => {
  handleLogout(message);
};

onMounted(() => {
  // 1. Récupération d'un éventuel message d'expiration
  const pendingNotice = consumeNotice();
  if (pendingNotice) {
    sessionNotice.value = pendingNotice;
  }

  // 2. Vérification des règles d'expiration (1h fermé / 2h ouvert)
  const status = checkSessionValidity();
  if (!status.isValid) {
    if (status.message) {
      sessionNotice.value = status.message;
    }
    clearSession(sessionNotice.value);
    user.value = null;
    return;
  }

  // 3. Restauration de l'utilisateur actif
  const savedUser = getStoredUser();
  if (savedUser) {
    user.value = savedUser;
    const savedPortal = sessionStorage.getItem('active_portal') as 'director' | 'secretary' | 'teacher' | null;
    if (savedPortal && ['director', 'secretary', 'teacher'].includes(savedPortal)) {
      activePortal.value = savedPortal;
    } else {
      activePortal.value = savedUser.role;
    }
    stopMonitor = startSessionMonitor(onSessionExpired);
  } else {
    user.value = null;
  }
});

onUnmounted(() => {
  if (stopMonitor) {
    stopMonitor();
    stopMonitor = null;
  }
});

const handleLogin = (role: 'director' | 'secretary' | 'teacher', name: string, permissions?: string[]) => {
  user.value = { role, name, permissions };
  activePortal.value = role;
  sessionStorage.setItem('active_portal', role);
  sessionNotice.value = '';

  if (stopMonitor) {
    stopMonitor();
  }
  stopMonitor = startSessionMonitor(onSessionExpired);
};

const handleLogout = (reason?: string) => {
  if (stopMonitor) {
    stopMonitor();
    stopMonitor = null;
  }

  // Tentative de déconnexion côté serveur sans bloquer
  api.post('/api/logout').catch(() => {});

  sessionStorage.removeItem('active_portal');
  clearSession(reason);
  user.value = null;
  sessionNotice.value = reason || '';
};
</script>
