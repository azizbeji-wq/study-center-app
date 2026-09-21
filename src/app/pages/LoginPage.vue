<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-50 dark:bg-gray-900">
    <form @submit.prevent="login" class="bg-white dark:bg-gray-800 p-8 rounded shadow-md w-96">
      <h2 class="text-2xl font-bold mb-4 text-gray-800 dark:text-gray-100">Login</h2>
      <div class="mb-4">
        <label class="block text-gray-700 dark:text-gray-200 mb-1" for="email">Email</label>
        <input v-model="email" type="email" id="email" required class="w-full p-2 border rounded" />
      </div>
      <div class="mb-4">
        <label class="block text-gray-700 dark:text-gray-200 mb-1" for="password">Password</label>
        <input v-model="password" type="password" id="password" required class="w-full p-2 border rounded" />
      </div>
      <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded transition">Login</button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const email = ref('');
const password = ref('');
const router = useRouter();

async function login() {
  try {
    await axios.post('/login', { email: email.value, password: password.value }, { withCredentials: true });
    router.push('/dashboard');
  } catch (e) {
    alert('Login failed');
  }
}
</script>
