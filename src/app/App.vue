<template>
  <div class="size-full bg-gray-50">
    <LoginPage v-if="!user" @login="handleLogin" />
    <template v-else>
      <DirectorDashboard
        v-if="user.role === 'director'"
        :user-name="user.name"
        @logout="handleLogout"
      />
      <SecretaryDashboard
        v-else-if="user.role === 'secretary'"
        :user-name="user.name"
        @logout="handleLogout"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import LoginPage from './components/LoginPage.vue';
import DirectorDashboard from './components/DirectorDashboard.vue';
import SecretaryDashboard from './components/SecretaryDashboard.vue';

interface User {
  role: 'director' | 'secretary';
  name: string;
}

const user = ref<User | null>(null);

onMounted(() => {
  const savedUser = localStorage.getItem('user');
  if (savedUser) {
    try {
      user.value = JSON.parse(savedUser);
    } catch (e) {
      localStorage.removeItem('user');
    }
  }
});

const handleLogin = (role: 'director' | 'secretary', name: string) => {
  user.value = { role, name };
};

const handleLogout = () => {
  user.value = null;
  localStorage.removeItem('user');
  localStorage.removeItem('token');
};
</script>
