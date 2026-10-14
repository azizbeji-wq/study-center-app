import { ref, computed } from 'vue';
import api from '../utils/api';
import { parseSubjects } from '../utils/teacherSubjects';
import type { Teacher, PayoutFilter } from '../types/teachers';

export function useTeachers() {
  const teachers = ref<Teacher[]>([]);
  const searchTerm = ref('');
  const payoutFilter = ref<PayoutFilter>('all');
  const loading = ref(false);
  const submitting = ref(false);
  const errorMessage = ref('');
  const togglingPayoutId = ref<number | null>(null);

  const currentUser = ref<{ role?: string; name?: string } | null>(null);
  try {
    const saved = localStorage.getItem('user');
    if (saved) currentUser.value = JSON.parse(saved);
  } catch {}

  const isDirector = computed(() => {
    return currentUser.value?.role === 'director' || teachers.value.some(t => t.teacher_share !== undefined || t.rate_percentage !== undefined);
  });

  function getTeacherSubjects(teacher: Teacher): string[] {
    if (teacher.subjects && teacher.subjects.length > 0) {
      return teacher.subjects;
    }
    return parseSubjects(teacher.subject);
  }

  function getInitial(name: string): string {
    const clean = name.replace(/^prof\.?\s*/i, '').trim();
    return (clean.charAt(0) || name.charAt(0) || 'P').toUpperCase();
  }

  function formatDate(d: string | null | undefined): string {
    if (!d) return '-';
    try {
      const parts = d.split('-');
      if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
      return d;
    } catch {
      return d;
    }
  }

  function getGrossRevenue(teacher: Teacher): number {
    if (teacher.monthly_gross_revenue !== undefined) return Number(teacher.monthly_gross_revenue);
    const count = Number(teacher.studentCount) || 0;
    return count * 60;
  }

  function getTeacherShare(teacher: Teacher): number {
    if (teacher.teacher_share !== undefined) return Number(teacher.teacher_share);
    const gross = getGrossRevenue(teacher);
    const count = Number(teacher.studentCount) || 0;
    if (teacher.remuneration_type === 'fixed_center') {
      const centerFixed = Number(teacher.center_fixed_per_student) || 20;
      return Math.max(0, gross - (count * centerFixed));
    }
    const isPct = teacher.remuneration_type === 'percentage' || (teacher.rate_percentage && teacher.rate_percentage > 0);
    if (isPct) {
      const pct = Number(teacher.rate_percentage) || 70;
      return Math.round((gross * pct) / 100);
    }
    const fixedRate = Number(teacher.fixed_student_rate) || 15;
    return Math.round(fixedRate * count);
  }

  function getCenterShare(teacher: Teacher): number {
    if (teacher.center_share !== undefined) return Number(teacher.center_share);
    const gross = getGrossRevenue(teacher);
    const tShare = getTeacherShare(teacher);
    return Math.max(0, gross - tShare);
  }

  const totalGrossRevenue = computed(() =>
    teachers.value.reduce((sum, t) => sum + getGrossRevenue(t), 0)
  );

  const totalTeachersShare = computed(() =>
    teachers.value.reduce((sum, t) => sum + getTeacherShare(t), 0)
  );

  const totalCenterShare = computed(() =>
    teachers.value.reduce((sum, t) => sum + getCenterShare(t), 0)
  );

  const avgTeacherShare = computed(() => {
    if (teachers.value.length === 0) return 0;
    return Math.round(totalTeachersShare.value / teachers.value.length);
  });

  const totalStudentsCount = computed(() =>
    teachers.value.reduce((sum, t) => sum + (Number(t.studentCount) || 0), 0)
  );

  const totalMonthlySessions = computed(() =>
    teachers.value.reduce((sum, t) => sum + ((Number(t.weeklyLessons) || 4) * 4), 0)
  );

  const paidTeachersCount = computed(() => teachers.value.filter(t => t.payout_status === 'paid').length);
  const pendingTeachersCount = computed(() => teachers.value.filter(t => t.payout_status !== 'paid').length);

  const filteredTeachers = computed(() => {
    let list = teachers.value;
    if (isDirector.value && payoutFilter.value !== 'all') {
      if (payoutFilter.value === 'paid') {
        list = list.filter(t => t.payout_status === 'paid');
      } else {
        list = list.filter(t => t.payout_status !== 'paid');
      }
    }

    if (!searchTerm.value.trim()) return list;
    const q = searchTerm.value.toLowerCase().trim();
    return list.filter((teacher) => {
      const matchName = teacher.name.toLowerCase().includes(q);
      const matchSub = (teacher.subject || '').toLowerCase().includes(q);
      const matchSubs = getTeacherSubjects(teacher).some(s => s.toLowerCase().includes(q));
      return matchName || matchSub || matchSubs;
    });
  });

  async function loadTeachers() {
    loading.value = true;
    try {
      const res = await api.get('/api/teachers');
      teachers.value = res.data;
    } catch (e: any) {
      console.error('Erreur chargement enseignants:', e);
    } finally {
      loading.value = false;
    }
  }

  async function togglePayoutStatus(teacher: Teacher) {
    if (!isDirector.value) return;
    const isCurrentlyPaid = teacher.payout_status === 'paid';
    const targetStatus = isCurrentlyPaid ? 'pending' : 'paid';
    const amount = getTeacherShare(teacher);
    const confirmMsg = targetStatus === 'paid'
      ? `Confirmer que l'enseignant "${teacher.name}" a bien pris son argent (${amount} TND) ?`
      : `Remettre le versement de l'enseignant "${teacher.name}" en attente ?`;

    if (!confirm(confirmMsg)) return;

    togglingPayoutId.value = teacher.id;
    try {
      const todayStr = new Date().toISOString().split('T')[0];
      const monthStr = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' }).format(new Date());

      const res = await api.post(`/api/teachers/${teacher.id}/toggle-payout`, {
        status: targetStatus,
        amount: amount,
        date: todayStr,
        month: monthStr,
      });

      if (res.data?.teacher) {
        const idx = teachers.value.findIndex(t => t.id === teacher.id);
        if (idx !== -1) {
          teachers.value[idx] = { ...teachers.value[idx], ...res.data.teacher };
        }
      } else {
        teacher.payout_status = targetStatus;
        teacher.payout_date = targetStatus === 'paid' ? todayStr : null;
        teacher.payout_amount = targetStatus === 'paid' ? amount : null;
        teacher.payout_month = targetStatus === 'paid' ? monthStr : null;
      }
    } catch (err: any) {
      console.error('Erreur changement statut versement:', err);
      alert(err?.response?.data?.message || 'Erreur lors de la mise à jour du statut.');
    } finally {
      togglingPayoutId.value = null;
    }
  }

  async function deleteTeacher(id: number) {
    if (!confirm('Voulez-vous vraiment supprimer cet enseignant ?')) return;
    try {
      await api.delete(`/api/teachers/${id}`);
      teachers.value = teachers.value.filter(t => t.id !== id);
    } catch (e: any) {
      console.error('Erreur suppression enseignant:', e);
      alert('Impossible de supprimer cet enseignant.');
    }
  }

  return {
    teachers,
    searchTerm,
    payoutFilter,
    loading,
    submitting,
    errorMessage,
    togglingPayoutId,
    currentUser,
    isDirector,
    getTeacherSubjects,
    getInitial,
    formatDate,
    getGrossRevenue,
    getTeacherShare,
    getCenterShare,
    totalGrossRevenue,
    totalTeachersShare,
    totalCenterShare,
    avgTeacherShare,
    totalStudentsCount,
    totalMonthlySessions,
    paidTeachersCount,
    pendingTeachersCount,
    filteredTeachers,
    loadTeachers,
    togglePayoutStatus,
    deleteTeacher
  };
}
