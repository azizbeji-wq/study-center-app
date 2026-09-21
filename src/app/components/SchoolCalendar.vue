<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">
          Emplois du temps des écoles primaires
        </h2>

        <p class="text-sm text-gray-500 mt-1">
          Consultez les emplois du temps des écoles primaires importées
        </p>
      </div>
    </div>

    <!-- School Selection -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
      <label
        for="school"
        class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2"
      >
        École primaire
      </label>

      <select
        id="school"
        v-model="selectedSchoolId"
        @change="onSchoolChange"
        class="w-full md:w-96 px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
      >
        <option :value="null">
          Sélectionner une école
        </option>

        <option
          v-for="school in schools"
          :key="school.id"
          :value="school.id"
        >
          {{ school.name }}
        </option>
      </select>

      <!-- Loading schools -->
      <p
        v-if="loadingSchools"
        class="text-sm text-gray-500 mt-3"
      >
        Chargement des écoles...
      </p>

      <!-- Error -->
      <div
        v-if="errorMessage"
        class="text-xs text-red-600 bg-red-50 p-2.5 rounded-lg border border-red-200 mt-3"
      >
        {{ errorMessage }}
      </div>

      <!-- No school -->
      <p
        v-if="!loadingSchools && !errorMessage && schools.length === 0"
        class="text-sm text-orange-600 mt-3"
      >
        Aucune école enregistrée.
      </p>
    </div>

    <!-- No School Selected -->
    <div
      v-if="!selectedSchool"
      class="bg-gray-50 border border-gray-200 rounded-xl p-10 text-center"
    >
      <div class="text-4xl mb-3">
        🏫
      </div>

      <h3 class="font-semibold text-gray-800">
        Aucune école sélectionnée
      </h3>

      <p class="text-sm text-gray-500 mt-1">
        Sélectionnez une école primaire pour afficher son emploi du temps.
      </p>
    </div>

    <!-- School Selected -->
    <template v-if="selectedSchool">

      <!-- School Info -->
      <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5">
        <p class="text-xs text-indigo-500 uppercase font-semibold">
          École sélectionnée
        </p>

        <h3 class="text-lg font-bold text-indigo-900 mt-1">
          {{ selectedSchool.name }}
        </h3>

        <p
          v-if="selectedSchool.address"
          class="text-sm text-indigo-700 mt-1"
        >
          {{ selectedSchool.address }}
        </p>
      </div>

      <!-- Level Filter -->
      <div>
        <h3 class="text-sm font-semibold text-gray-700 mb-3">
          Niveau scolaire
        </h3>

        <div class="flex flex-wrap gap-2">
          <button
            v-for="level in levels"
            :key="level"
            @click="selectLevel(level)"
            :class="[
              'px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-xs',
              selectedLevel === level
                ? 'bg-indigo-600 text-white'
                : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
            ]"
          >
            {{ formatLevel(level) }}
          </button>
        </div>
      </div>

      <!-- Loading Classes -->
      <div
        v-if="loadingClasses"
        class="flex justify-center py-8"
      >
        <div
          class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"
        ></div>
      </div>

      <!-- Sections -->
      <div
        v-if="selectedLevel !== null && !loadingClasses"
        class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
      >
        <h3 class="text-sm font-semibold text-gray-700 mb-3">
          Classe
        </h3>

        <div
          v-if="availableSections.length > 0"
          class="flex flex-wrap gap-2"
        >
          <button
            v-for="section in availableSections"
            :key="section"
            @click="selectSection(section)"
            :class="[
              'px-6 py-2 rounded-lg text-lg font-semibold transition-colors shadow-xs',
              selectedSection === section
                ? 'bg-indigo-600 text-white'
                : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
            ]"
          >
            {{ section }}
          </button>
        </div>

        <div
          v-else
          class="text-sm text-gray-400 text-center py-6"
        >
          Aucune classe disponible pour ce niveau
        </div>
      </div>

      <!-- Calendar Loading -->
      <div
        v-if="loadingCalendar"
        class="flex justify-center py-12"
      >
        <div
          class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"
        ></div>
      </div>

      <!-- Schedule -->
      <template v-if="selectedClassId && !loadingCalendar">

        <!-- Schedule Header -->
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-gray-900">
              Emploi du temps
            </h3>

            <p class="text-sm text-gray-500 mt-1">
              {{ selectedSchool.name }}
              •
              {{ formatLevel(selectedLevel!) }}
              •
              Classe {{ selectedSection }}
            </p>
          </div>

          <span
            class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-full"
          >
            {{ formatLevel(selectedLevel!) }} - {{ selectedSection }}
          </span>
        </div>

        <!-- No Schedule -->
        <div
          v-if="courses.length === 0"
          class="bg-white border border-gray-200 rounded-xl p-10 text-center shadow-sm"
        >
          <div class="text-3xl mb-3">
            📅
          </div>

          <h3 class="text-sm font-semibold text-gray-700">
            Aucun emploi du temps
          </h3>

          <p class="text-xs text-gray-400 mt-1">
            Aucun emploi du temps importé pour cette classe.
          </p>
        </div>

        <!-- Schedule Grid -->
        <div
          v-else
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
        >
          <div
            v-for="daySchedule in scheduleByDay"
            :key="daySchedule.day"
            class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm flex flex-col"
          >
            <!-- Day Header -->
            <div
              class="bg-indigo-600 px-4 py-3 flex items-center justify-between"
            >
              <h3 class="text-sm font-bold text-white tracking-wide">
                {{ daySchedule.day }}
              </h3>

              <span class="text-xs text-indigo-200 font-medium">
                {{ daySchedule.items.length }} cours
              </span>
            </div>

            <!-- Courses -->
            <div class="p-4 space-y-3 flex-1 bg-gray-50/50">

              <template v-if="daySchedule.items.length > 0">
                <div
                  v-for="item in daySchedule.items"
                  :key="item.id"
                  class="border-l-4 border-indigo-500 pl-3.5 pr-2 py-2.5 bg-white rounded-lg border border-gray-200/80 shadow-xs"
                >
                  <!-- Time -->
                  <div class="text-xs font-semibold text-indigo-600">
                    {{ formatTime(item.start_time) }}
                    -
                    {{ formatTime(item.end_time) }}
                  </div>

                  <!-- Subject -->
                  <div class="text-sm font-bold text-gray-900 mt-1">
                    {{ item.subject }}
                  </div>

                  <!-- Teacher -->
                  <div
                    v-if="item.teacher_name"
                    class="text-xs font-medium text-gray-600 mt-1"
                  >
                    👨‍🏫 {{ item.teacher_name }}
                  </div>

                  <!-- Room -->
                  <div
                    v-if="item.room"
                    class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100 text-xs text-gray-500"
                  >
                    <span class="bg-gray-100 px-2 py-0.5 rounded font-medium">
                      Salle
                    </span>

                    <span class="font-medium text-gray-600">
                      {{ item.room }}
                    </span>
                  </div>
                </div>
              </template>

              <div
                v-else
                class="text-xs text-gray-400 text-center py-8"
              >
                Aucun cours ce jour
              </div>

            </div>
          </div>
        </div>

        <!-- Daycare Children -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

          <!-- Header -->
          <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-gray-900">
                Élèves de garde concernés
              </h3>

              <p class="text-xs text-gray-500 mt-1">
                {{ formatLevel(selectedLevel!) }}
                -
                Classe {{ selectedSection }}
              </p>
            </div>

            <span
              class="px-3 py-1 text-xs font-semibold bg-orange-100 text-orange-700 rounded-full"
            >
              {{ daycareChildren.length }} élève(s)
            </span>
          </div>

          <!-- Children -->
          <div
            v-if="daycareChildren.length > 0"
            class="divide-y divide-gray-100"
          >
            <div
              v-for="child in daycareChildren"
              :key="child.id"
              class="flex items-center gap-3 px-5 py-4 hover:bg-gray-50 transition-colors"
            >
              <div
                class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center"
              >
                <span class="text-sm font-bold text-orange-600">
                  {{ getInitial(child.name) }}
                </span>
              </div>

              <div>
                <p class="text-sm font-semibold text-gray-900">
                  {{ child.name }}
                </p>

                <p class="text-xs text-gray-500">
                  Élève de garde
                </p>
              </div>
            </div>
          </div>

          <!-- No Children -->
          <div
            v-else
            class="text-xs text-gray-400 text-center py-10"
          >
            Aucun élève de garde concerné par cette classe
          </div>

        </div>
      </template>

      <!-- Select Section Message -->
      <div
        v-if="
          selectedLevel !== null &&
          !selectedClassId &&
          !loadingClasses
        "
        class="bg-gray-50 border border-gray-200 rounded-xl p-8 text-center"
      >
        <p class="text-sm text-gray-500">
          Sélectionnez une classe pour consulter son emploi du temps.
        </p>
      </div>

    </template>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../utils/api';

interface School {
  id: number;
  name: string;
  address?: string;
}

interface SchoolClass {
  id: number;
  school_id: number;
  level: number;
  section: string;
}

interface Course {
  id: number;
  school_class_id: number;
  day: string;
  start_time: string;
  end_time: string;
  subject: string;
  teacher_name?: string;
  room?: string;
}

interface DaycareChild {
  id: number;
  name: string;
}

const schools = ref<School[]>([]);
const schoolClasses = ref<SchoolClass[]>([]);
const courses = ref<Course[]>([]);
const daycareChildren = ref<DaycareChild[]>([]);

const loadingSchools = ref(false);
const loadingClasses = ref(false);
const loadingCalendar = ref(false);
const errorMessage = ref('');

const selectedSchoolId = ref<number | null>(null);
const selectedLevel = ref<number | null>(null);
const selectedSection = ref<string | null>(null);
const selectedClassId = ref<number | null>(null);

const levels = [1, 2, 3, 4, 5, 6];

const days = [
  'Lundi',
  'Mardi',
  'Mercredi',
  'Jeudi',
  'Vendredi',
  'Samedi'
];

const selectedSchool = computed(() => {
  if (selectedSchoolId.value === null) {
    return null;
  }

  return (
    schools.value.find(
      school =>
        Number(school.id) === Number(selectedSchoolId.value)
    ) ?? null
  );
});

const availableSections = computed(() => {
  if (selectedLevel.value === null) {
    return [];
  }

  const sections = schoolClasses.value
    .filter(
      schoolClass =>
        Number(schoolClass.level) ===
        Number(selectedLevel.value)
    )
    .map(
      schoolClass => schoolClass.section
    );

  return [...new Set(sections)];
});

const scheduleByDay = computed(() => {
  return days.map((day) => ({
    day,
    items: courses.value
      .filter((course) => course.day === day)
      .sort((a, b) =>
        a.start_time.localeCompare(b.start_time)
      )
  }));
});

function formatLevel(level: number) {
  if (level === 1) {
    return '1ère année';
  }

  return `${level}ème année`;
}

function formatTime(time?: string) {
  if (!time) {
    return '';
  }

  return time.substring(0, 5);
}

function getInitial(name: string) {
  if (!name) {
    return '?';
  }

  return name.charAt(0).toUpperCase();
}

async function loadSchools() {
  loadingSchools.value = true;
  errorMessage.value = '';

  try {
    const res = await api.get('/api/schools');

    schools.value = res.data;

    console.log('Écoles chargées:', res.data);
  } catch (e: any) {
    console.error(
      'Erreur chargement écoles:',
      e
    );

    errorMessage.value =
      e.response?.data?.message ||
      'Impossible de charger les écoles.';

    schools.value = [];
  } finally {
    loadingSchools.value = false;
  }
}

async function onSchoolChange() {
  selectedLevel.value = null;
  selectedSection.value = null;
  selectedClassId.value = null;

  schoolClasses.value = [];
  courses.value = [];
  daycareChildren.value = [];

  errorMessage.value = '';

  if (!selectedSchoolId.value) {
    return;
  }

  loadingClasses.value = true;

  try {
    const res = await api.get(
      `/api/schools/${selectedSchoolId.value}/classes`
    );

    schoolClasses.value = res.data;

    console.log(
      'Classes chargées:',
      res.data
    );
  } catch (e: any) {
    console.error(
      'Erreur chargement classes:',
      e
    );

    errorMessage.value =
      e.response?.data?.message ||
      'Impossible de charger les classes.';

    schoolClasses.value = [];
  } finally {
    loadingClasses.value = false;
  }
}

function selectLevel(level: number) {
  selectedLevel.value = level;

  selectedSection.value = null;
  selectedClassId.value = null;

  courses.value = [];
  daycareChildren.value = [];
}

async function selectSection(section: string) {
  selectedSection.value = section;

  const schoolClass = schoolClasses.value.find(
    item =>
      Number(item.level) === Number(selectedLevel.value) &&
      item.section === section
  );

  if (!schoolClass) {
    errorMessage.value =
      'Classe introuvable.';

    selectedClassId.value = null;

    return;
  }

  selectedClassId.value = schoolClass.id;

  await loadClassInformation(
    schoolClass.id
  );
}

async function loadClassInformation(
  classId: number
) {
  loadingCalendar.value = true;
  errorMessage.value = '';

  courses.value = [];
  daycareChildren.value = [];

  try {
    const res = await api.get(
      `/api/school-classes/${classId}/calendar`
    );

    courses.value =
      res.data.calendar ?? [];

    daycareChildren.value =
      res.data.daycare_children ?? [];

    console.log(
      'Emploi du temps:',
      courses.value
    );

    console.log(
      'Élèves de garde:',
      daycareChildren.value
    );
  } catch (e: any) {
    console.error(
      'Erreur chargement emploi du temps:',
      e
    );

    errorMessage.value =
      e.response?.data?.message ||
      "Impossible de charger l'emploi du temps.";

    courses.value = [];
    daycareChildren.value = [];
  } finally {
    loadingCalendar.value = false;
  }
}

onMounted(loadSchools);
</script>