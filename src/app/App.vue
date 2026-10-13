<template>
  <div class="size-full bg-gray-50">
    <LoginPage
      v-if="!user"
      :session-notice="sessionNotice"
      @login="handleLogin"
    />
    <template v-else>
      <DirectorDashboard
        v-if="user.role === 'director'"
        :user-name="user.name"
        @logout="handleLogout"
      />
      <SecretaryDashboard
        v-else-if="user.role === 'secretary'"
        :user-name="user.name"
        :permissions="user.permissions"
        @logout="handleLogout"
      />
      <TeacherDashboard
        v-else-if="user.role === 'teacher'"
        :user-name="user.name"
        @logout="handleLogout"
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
const sessionNotice = ref<string>('');
let stopMonitor: (() => void) | null = null;

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

  clearSession(reason);
  user.value = null;
  sessionNotice.value = reason || '';
};
</script>
