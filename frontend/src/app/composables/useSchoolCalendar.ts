import { ref, computed } from 'vue';
import api from '../utils/api';
import type { School, CalendarImport, SectionOption } from '../types/calendar';

export const levels = [1, 2, 3, 4, 5, 6];

export const sectionOptions: SectionOption[] = [
  { value: 'A', label: 'Classe A (أ)' },
  { value: 'B', label: 'Classe B (ب)' },
  { value: 'C', label: 'Classe C (ج)' },
  { value: 'D', label: 'Classe D (د)' },
  { value: 'E', label: 'Classe E (هـ)' }
];

export function useSchoolCalendar() {
  const schools = ref<School[]>([]);
  const imports = ref<CalendarImport[]>([]);
  const loadingList = ref(false);
  const errorMessage = ref('');
  const successMessage = ref('');

  const searchQuery = ref('');
  const filterSchoolId = ref<number | null>(null);
  const filterLevel = ref<number | null>(null);
  const filterSection = ref<string | null>(null);
  const schoolSearchQuery = ref('');

  function formatLevel(level: number): string {
    return level === 1 ? '1ère année' : `${level}ème année`;
  }

  function normalizeSection(sec: string): string {
    if (!sec) return 'A';
    const s = sec.trim().toUpperCase();
    if (s === 'أ' || s === 'A') return 'A';
    if (s === 'ب' || s === 'B') return 'B';
    if (s === 'ج' || s === 'C') return 'C';
    if (s === 'د' || s === 'D') return 'D';
    if (s === 'هـ' || s === 'E') return 'E';
    return s;
  }

  function formatSectionDisplay(sec: string): string {
    const normalized = normalizeSection(sec);
    const found = sectionOptions.find(o => o.value === normalized);
    return found ? found.label : `Classe ${sec}`;
  }

  function formatDate(value: string): string {
    try {
      return new Date(value).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      });
    } catch {
      return value;
    }
  }

  function isPdf(item: CalendarImport): boolean {
    return (item.file_type ?? '').includes('pdf') || item.file_name.toLowerCase().endsWith('.pdf');
  }

  function getSchoolImportCount(schoolId: number): number {
    return imports.value.filter(i => i.school_id === schoolId).length;
  }

  function extractError(e: any, fallback: string): string {
    const errors = e?.response?.data?.errors;
    if (errors) {
      const first = Object.values(errors)[0] as string[];
      if (first?.length) return first[0];
    }
    return e?.response?.data?.message || fallback;
  }

  function showSuccess(message: string) {
    successMessage.value = message;
    setTimeout(() => {
      if (successMessage.value === message) successMessage.value = '';
    }, 5000);
  }

  function resetFilters() {
    searchQuery.value = '';
    filterSchoolId.value = null;
    filterLevel.value = null;
    filterSection.value = null;
  }

  const hasActiveFilters = computed(
    () =>
      Boolean(searchQuery.value.trim()) ||
      filterSchoolId.value !== null ||
      filterLevel.value !== null ||
      filterSection.value !== null
  );

  const filteredImports = computed(() => {
    return imports.value.filter(item => {
      if (filterSchoolId.value && item.school_id !== filterSchoolId.value) return false;
      if (filterLevel.value && item.level !== filterLevel.value) return false;
      if (filterSection.value && normalizeSection(item.section) !== normalizeSection(filterSection.value)) return false;

      if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        const matchSchool = (item.school_name || '').toLowerCase().includes(q);
        const matchFile = (item.file_name || '').toLowerCase().includes(q);
        const matchLevel = formatLevel(item.level).toLowerCase().includes(q);
        const matchSection = (item.section || '').toLowerCase().includes(q);
        if (!matchSchool && !matchFile && !matchLevel && !matchSection) {
          return false;
        }
      }

      return true;
    });
  });

  const filteredSchools = computed(() => {
    if (!schoolSearchQuery.value.trim()) return schools.value;
    const q = schoolSearchQuery.value.toLowerCase().trim();
    return schools.value.filter(
      s =>
        s.name.toLowerCase().includes(q) ||
        (s.city && s.city.toLowerCase().includes(q)) ||
        (s.address && s.address.toLowerCase().includes(q))
    );
  });

  const distinctLevelsCount = computed(() => {
    const set = new Set(imports.value.map(i => i.level));
    return set.size;
  });

  const latestImportText = computed(() => {
    if (imports.value.length === 0) return 'Aucun';
    const first = imports.value[0];
    return `${first.school_name} (${formatLevel(first.level)})`;
  });

  async function loadSchools() {
    try {
      const res = await api.get('/api/schools');
      schools.value = res.data;
    } catch (e) {
      errorMessage.value = extractError(e, 'Impossible de charger la liste des écoles.');
    }
  }

  async function loadImports() {
    loadingList.value = true;
    errorMessage.value = '';

    try {
      const res = await api.get('/api/school-calendar-imports', {
        params: {
          school_id: filterSchoolId.value ?? undefined,
          level: filterLevel.value ?? undefined,
          section: filterSection.value ?? undefined
        }
      });
      imports.value = res.data;
    } catch (e) {
      imports.value = [];
      errorMessage.value = extractError(e, 'Impossible de charger les emplois du temps.');
    } finally {
      loadingList.value = false;
    }
  }

  return {
    schools,
    imports,
    loadingList,
    errorMessage,
    successMessage,
    searchQuery,
    filterSchoolId,
    filterLevel,
    filterSection,
    schoolSearchQuery,
    hasActiveFilters,
    filteredImports,
    filteredSchools,
    distinctLevelsCount,
    latestImportText,
    formatLevel,
    normalizeSection,
    formatSectionDisplay,
    formatDate,
    isPdf,
    getSchoolImportCount,
    extractError,
    showSuccess,
    resetFilters,
    loadSchools,
    loadImports
  };
}
