// src/app/router/index.ts
import { createRouter, createWebHistory } from 'vue-router';
import LoginPage from '../components/LoginPage.vue';
import DashboardOverview from '../components/DashboardOverview.vue';
import StudentsManagement from '../components/StudentsManagement.vue';
import TeachersManagement from '../components/TeachersManagement.vue';
import PaymentsManagement from '../components/PaymentsManagement.vue';
import AttendanceManagement from '../components/AttendanceManagement.vue';
import TeacherDashboard from '../components/TeacherDashboard.vue';

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/login', name: 'Login', component: LoginPage },
  { path: '/dashboard', name: 'Dashboard', component: DashboardOverview, meta: { requiresAuth: true } },
  { path: '/students', name: 'Students', component: StudentsManagement, meta: { requiresAuth: true } },
  { path: '/teachers', name: 'Teachers', component: TeachersManagement, meta: { requiresAuth: true } },
  { path: '/payments', name: 'Payments', component: PaymentsManagement, meta: { requiresAuth: true } },
  { path: '/attendance', name: 'Attendance', component: AttendanceManagement, meta: { requiresAuth: true } },
  { path: '/teacher', name: 'Teacher', component: TeacherDashboard, meta: { requiresAuth: true } },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
