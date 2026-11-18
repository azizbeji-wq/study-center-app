// src/app/guide/useGuide.ts
// Composable central du guide : progression, chargement, synchronisation

import { ref, computed, readonly } from 'vue';
import api from '../utils/api';
import type { UserRole, TabKey, ProgressKey, ChecklistItem } from './types';
import { getGuideContent } from './content';

// ─── Clé localStorage de secours ───────────────────────────
const LOCAL_KEY = 'guide_progress_v1';

function readLocalProgress(): Set<string> {
  try {
    const raw = localStorage.getItem(LOCAL_KEY);
    if (!raw) return new Set();
    const arr = JSON.parse(raw);
    return Array.isArray(arr) ? new Set(arr) : new Set();
  } catch {
    return new Set();
  }
}

function writeLocalProgress(keys: Set<string>) {
  try {
    localStorage.setItem(LOCAL_KEY, JSON.stringify([...keys]));
  } catch {
    // Silencieux : stockage plein ou indisponible
  }
}

// ─── État global (singleton par session) ───────────────────
const completedKeys = ref<Set<string>>(new Set());
const loading = ref(false);
const apiAvailable = ref(true);

// ─── Chargement initial ─────────────────────────────────────
let loaded = false;

export async function loadProgress() {
  if (loaded) return;
  loaded = true;
  loading.value = true;
  try {
    const res = await api.get('/api/guide/progress');
    const keys: string[] = res.data?.completed_keys ?? [];
    const combined = new Set(keys);
    const checks = res.data?.starting_checks;
    if (checks) {
      if (checks.has_subjects) combined.add('checklist.subjects');
      if (checks.has_teachers) combined.add('checklist.teachers');
      if (checks.has_pricing) combined.add('checklist.pricing');
      if (checks.has_students) combined.add('checklist.first_student');
      if (checks.has_attendances) combined.add('checklist.first_attendance');
    }
    completedKeys.value = combined;
    apiAvailable.value = true;
    writeLocalProgress(completedKeys.value);
  } catch {
    // API indisponible : repli sur localStorage
    apiAvailable.value = false;
    completedKeys.value = readLocalProgress();
  } finally {
    loading.value = false;
  }
}

// ─── Marquer une clé comme faite (optimiste) ───────────────
export async function markDone(key: string) {
  if (completedKeys.value.has(key)) return;
  // Mise à jour optimiste immédiate
  completedKeys.value = new Set([...completedKeys.value, key]);
  writeLocalProgress(completedKeys.value);

  if (apiAvailable.value) {
    try {
      await api.post(`/api/guide/progress/${encodeURIComponent(key)}`);
    } catch {
      // Silencieux : la progression locale est déjà sauvegardée
    }
  }
}

// ─── Annuler une clé ───────────────────────────────────────
export async function markUndone(key: string) {
  if (!completedKeys.value.has(key)) return;
  const next = new Set(completedKeys.value);
  next.delete(key);
  completedKeys.value = next;
  writeLocalProgress(completedKeys.value);

  if (apiAvailable.value) {
    try {
      await api.delete(`/api/guide/progress/${encodeURIComponent(key)}`);
    } catch {
      // Silencieux
    }
  }
}

// ─── Composable pour les composants ───────────────────────
export function useGuide(role: UserRole, permissions?: string[]) {
  const content = computed(() => getGuideContent(role, permissions));

  // Checklist filtrée (retire les doublons de clé pour l'enseignant)
  const checklist = computed<ChecklistItem[]>(() => {
    const seen = new Set<string>();
    return content.value.checklist.filter(item => {
      if (seen.has(item.key)) return false;
      seen.add(item.key);
      return true;
    });
  });

  const checklistDoneCount = computed(() =>
    checklist.value.filter(item => completedKeys.value.has(item.key)).length
  );

  const checklistTotal = computed(() => checklist.value.length);

  const checklistProgress = computed(() =>
    checklistTotal.value > 0
      ? Math.round((checklistDoneCount.value / checklistTotal.value) * 100)
      : 0
  );

  function getPageContent(tab: TabKey) {
    return content.value.pages.find(p => p.tab === tab) ?? null;
  }

  function getTourSteps(tab: TabKey) {
    return content.value.tours.find(t => t.tab === tab)?.steps ?? [];
  }

  function isDone(key: string) {
    return completedKeys.value.has(key);
  }

  function isBannerDismissed(tab: TabKey) {
    return completedKeys.value.has(`banner.${tab}`);
  }

  async function dismissBanner(tab: TabKey) {
    await markDone(`banner.${tab}`);
  }

  async function completeTour(tab: TabKey) {
    await markDone(`tour.${tab}`);
  }

  function isTourDone(tab: TabKey) {
    return completedKeys.value.has(`tour.${tab}`);
  }

  return {
    content: readonly(content),
    checklist: readonly(checklist),
    checklistDoneCount: readonly(checklistDoneCount),
    checklistTotal: readonly(checklistTotal),
    checklistProgress: readonly(checklistProgress),
    loading: readonly(loading),
    getPageContent,
    getTourSteps,
    isDone,
    isBannerDismissed,
    dismissBanner,
    completeTour,
    isTourDone,
    markDone,
    markUndone,
  };
}

