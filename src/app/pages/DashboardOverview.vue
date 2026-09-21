<template>
  <div class="p-6">
    <h1 class="text-3xl font-bold mb-4 text-gray-800 dark:text-gray-100">Dashboard</h1>
    <p class="text-gray-600 dark:text-gray-300">Welcome to the Study Center admin dashboard. Here you can view summaries, recent activity, and quick links.</p>
    <!-- Simple cards example -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
      <div class="bg-white dark:bg-gray-800 p-4 rounded shadow">
        <h2 class="text-xl font-semibold mb-2 text-gray-700 dark:text-gray-200">Teachers</h2>
        <p class="text-2xl font-bold text-indigo-600">{{ teachersCount }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 p-4 rounded shadow">
        <h2 class="text-xl font-semibold mb-2 text-gray-700 dark:text-gray-200">Students</h2>
        <p class="text-2xl font-bold text-indigo-600">{{ studentsCount }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 p-4 rounded shadow">
        <h2 class="text-xl font-semibold mb-2 text-gray-700 dark:text-gray-200">Payments</h2>
        <p class="text-2xl font-bold text-indigo-600">{{ paymentsCount }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import axios from 'axios';

const teachersCount = ref(0);
const studentsCount = ref(0);
const paymentsCount = ref(0);

onMounted(async () => {
  try {
    const [t, s, p] = await Promise.all([
      axios.get('/api/teachers'),
      axios.get('/api/students'),
      axios.get('/api/payments'),
    ]);
    teachersCount.value = t.data.length;
    studentsCount.value = s.data.length;
    paymentsCount.value = p.data.length;
  } catch (e) {
    console.error('Failed to load dashboard data', e);
  }
});
</script>
