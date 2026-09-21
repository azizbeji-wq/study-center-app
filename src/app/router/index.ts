// src/app/router/index.ts
import { createRouter, createWebHistory } from 'vue-router';
import LoginPage from '../pages/LoginPage.vue';
import DashboardOverview from '../pages/DashboardOverview.vue';
import TeachersManagement from '../components/TeachersManagement.vue';

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/login', name: 'Login', component: LoginPage },
  { path: '/dashboard', name: 'Dashboard', component: DashboardOverview, meta: { requiresAuth: true } },
  { path: '/teachers', name: 'Teachers', component: TeachersManagement, meta: { requiresAuth: true } },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
