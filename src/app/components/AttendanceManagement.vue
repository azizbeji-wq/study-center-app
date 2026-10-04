<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Suivi des Présences & Tarification</h2>
        <p class="text-sm text-gray-500 mt-1">
          Appel par groupe, suivi des absences et calcul automatique du montant dû par élève
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="openBatchModal"
          class="flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
        >
          <UserCheck class="w-4 h-4" />
          Faire l'appel de groupe
        </button>
      </div>
    </div>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-xs">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase">Total Séances</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ attendances.length }}</p>
          </div>
          <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
            <Calendar class="w-5 h-5" />
          </div>
        </div>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-xs">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-green-600 uppercase">Présences</p>
            <p class="text-2xl font-bold text-green-700 mt-1">{{ presentCount }}</p>
          </div>
          <div class="w-10 h-10 bg-green-50 text-green-600 rounded-lg flex items-center justify-center">
            <CheckCircle2 class="w-5 h-5" />
          </div>
        </div>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-xs">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-red-600 uppercase">Absences</p>
            <p class="text-2xl font-bold text-red-700 mt-1">{{ absentCount }}</p>
          </div>
          <div class="w-10 h-10 bg-red-50 text-red-600 rounded-lg flex items-center justify-center">
            <XCircle class="w-5 h-5" />
          </div>
        </div>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-xs">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-purple-600 uppercase">Groupes Actifs</p>
            <p class="text-2xl font-bold text-purple-700 mt-1">{{ uniqueGroups.length }}</p>
          </div>
          <div class="w-10 h-10 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center">
            <Users class="w-5 h-5" />
          </div>
        </div>
      </div>
    </div>

    <!-- Filters & Group Selector -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-xs space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
        <!-- Filter Group -->
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Filtrer par Groupe</label>
          <select
            v-model="filterGroup"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          >
            <option value="">Tous les groupes</option>
            <option v-for="grp in allAvailableGroups" :key="grp" :value="grp">{{ grp }}</option>
          </select>
        </div>

        <!-- Filter Date -->
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Date de la séance</label>
          <input
            type="date"
            v-model="filterDate"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
        </div>

        <!-- Filter Teacher -->
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Enseignant</label>
          <select
            v-model="filterTeacherId"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          >
            <option value="">Tous les enseignants</option>
            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }} ({{ t.subject }})</option>
          </select>
        </div>

        <!-- Search Student -->
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Recherche élève</label>
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              type="text"
              placeholder="Nom de l'élève..."
              v-model="searchTerm"
              class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between pt-2 border-t border-gray-100 text-xs text-gray-500">
        <span>{{ filteredAttendances.length }} fiche(s) de présence trouvée(s)</span>
        <button
          v-if="filterGroup || filterDate || filterTeacherId || searchTerm"
          @click="resetFilters"
          class="text-indigo-600 hover:text-indigo-800 font-medium"
        >
          Réinitialiser les filtres
        </button>
      </div>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-xs">
      <table class="w-full">
        <thead class="bg-gray-50/75 border-b border-gray-200">
          <tr>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Élève</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Groupe / Niveau</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Matière & Professeur</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut Présence</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Calcul Règlement</th>
            <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="att in filteredAttendances" :key="att.id" class="hover:bg-gray-50/80 transition-colors">
            <!-- Student -->
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">
                  {{ att.student?.name?.charAt(0) || '?' }}
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ att.student?.name || 'Inconnu' }}</p>
                  <p class="text-xs text-gray-500">{{ att.student?.phone }}</p>
                </div>
              </div>
            </td>

            <!-- Group & Level -->
            <td class="px-6 py-4">
              <div class="space-y-0.5">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                  {{ att.group_name || att.student?.group_name || 'Sans groupe' }}
                </span>
                <p class="text-xs text-gray-500">{{ att.student?.level }}</p>
              </div>
            </td>

            <!-- Subject & Teacher -->
            <td class="px-6 py-4">
              <p class="text-sm font-medium text-gray-900">{{ att.subject_name }}</p>
              <p class="text-xs text-gray-500">{{ att.teacher?.name ? 'Prof: ' + att.teacher.name : '-' }}</p>
            </td>

            <!-- Date -->
            <td class="px-6 py-4 text-sm text-gray-600">
              {{ formatDate(att.session_date) }}
            </td>

            <!-- Status -->
            <td class="px-6 py-4">
              <span
                :class="[
                  'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                  att.status === 'present' ? 'bg-green-100 text-green-800' :
                  att.status === 'absent' ? 'bg-red-100 text-red-800' :
                  att.status === 'excused' ? 'bg-yellow-100 text-yellow-800' : 'bg-orange-100 text-orange-800'
                ]"
              >
                <component :is="getStatusIcon(att.status)" class="w-3.5 h-3.5" />
                {{ getStatusLabel(att.status) }}
              </span>
            </td>

            <!-- Due calculation info -->
            <td class="px-6 py-4">
              <div v-if="att.student" class="text-xs space-y-1">
                <div class="flex items-center gap-1 font-medium text-gray-900">
                  <span>Dû: {{ getStudentPayable(att.student) }} TND</span>
                  <span
                    v-if="att.student.calculated_fee?.isDefaultPrice"
                    class="text-[10px] text-gray-400 font-normal"
                    title="Aucun prix n'est défini pour cet élève : tarif par défaut par séance"
                  >
                    (tarif par défaut)
                  </span>
                  <span
                    v-if="att.student.is_remise"
                    class="px-1.5 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-semibold rounded"
                  >
                    -{{ att.student.remise }}{{ att.student.discount_type === 'percentage' ? '%' : ' TND' }}
                  </span>
                </div>
                <p class="text-[11px] text-gray-500">
                  Présent {{ getStudentPresenceRate(att.student.id) }} séances
                </p>
              </div>
            </td>

            <!-- Actions -->
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-1.5">
                <button
                  @click="openStudentSummary(att.student)"
                  class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                  title="Fiche financière & présence"
                >
                  <FileText class="w-4 h-4" />
                </button>
                <button
                  @click="deleteAttendance(att.id)"
                  class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                  title="Supprimer"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="filteredAttendances.length === 0" class="text-center py-12">
        <Users class="w-10 h-10 text-gray-300 mx-auto mb-2" />
        <p class="text-gray-500 font-medium text-sm">Aucune présence trouvée</p>
        <p class="text-gray-400 text-xs mt-1">Cliquez sur "Faire l'appel de groupe" pour enregistrer les séances.</p>
      </div>
    </div>

    <!-- Batch Attendance Modal -->
    <div v-if="showBatchModal" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click="closeBatchModal">
      <div class="bg-white rounded-2xl p-6 max-w-2xl w-full shadow-2xl space-y-4 max-h-[90vh] flex flex-col" @click.stop>
        <div class="flex items-center justify-between border-b pb-3 border-gray-100">
          <div>
            <h3 class="text-lg font-bold text-gray-900">Appel de Présence par Groupe</h3>
            <p class="text-xs text-gray-500">Sélectionnez le groupe ou niveau pour marquer qui est présent ou absent</p>
          </div>
          <button @click="closeBatchModal" class="text-gray-400 hover:text-gray-600 text-lg">×</button>
        </div>

        <form @submit.prevent="submitBatchAttendance" class="space-y-4 flex-1 overflow-y-auto pr-1">
          <!-- Session config -->
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 bg-gray-50 p-3.5 rounded-xl border border-gray-200">
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Date *</label>
              <input
                type="date"
                v-model="batchForm.session_date"
                required
                class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Groupe *</label>
              <select
                v-model="batchForm.group_name"
                @change="onBatchGroupChange"
                required
                class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500"
              >
                <option value="" disabled>Choisir un groupe</option>
                <option v-for="grp in allAvailableGroups" :key="grp" :value="grp">{{ grp }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Matière *</label>
              <select
                v-model="batchForm.subject_name"
                @change="onBatchSubjectChange"
                required
                class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500"
              >
                <option value="" disabled>Choisir la matière</option>
                <option v-for="sub in activeGroupSubjects" :key="sub" :value="sub">{{ sub }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Enseignant</label>
              <select
                v-model="batchForm.teacher_id"
                class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500"
              >
                <option :value="undefined">Sélectionner prof</option>
                <option v-for="t in batchTeacherOptions.list" :key="t.id" :value="t.id">{{ t.name }}</option>
              </select>
              <p v-if="batchForm.subject_name && !batchTeacherOptions.matched" class="text-[11px] text-amber-700 mt-1">
                Aucun enseignant déclaré pour « {{ batchForm.subject_name }} » : liste complète affichée.
              </p>
            </div>
          </div>

          <!-- Students List for Attendance -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-semibold text-gray-700 uppercase tracking-wider">
                Liste des Élèves du Groupe ({{ studentsInSelectedGroup.length }})
              </label>
              <div class="flex gap-2">
                <button
                  type="button"
                  @click="markAllStudents('present')"
                  class="text-xs px-2.5 py-1 bg-green-50 text-green-700 hover:bg-green-100 rounded font-medium border border-green-200"
                >
                  Tous Présents
                </button>
                <button
                  type="button"
                  @click="markAllStudents('absent')"
                  class="text-xs px-2.5 py-1 bg-red-50 text-red-700 hover:bg-red-100 rounded font-medium border border-red-200"
                >
                  Tous Absents
                </button>
              </div>
            </div>

            <div v-if="studentsInSelectedGroup.length === 0" class="p-6 text-center bg-gray-50 border rounded-xl text-gray-500 text-sm">
              Aucun élève trouvé pour le groupe "{{ batchForm.group_name }}".<br>
              Vérifiez les groupes attribués aux élèves dans l'onglet "Élèves".
            </div>

            <div v-else class="space-y-2 max-h-64 overflow-y-auto pr-1">
              <div
                v-for="st in studentsInSelectedGroup"
                :key="st.id"
                class="flex items-center justify-between p-3 border rounded-xl hover:bg-gray-50 transition-colors"
              >
                <div>
                  <p class="text-sm font-semibold text-gray-900">{{ st.name }}</p>
                  <p class="text-xs text-gray-500">
                    {{ st.level }}
                    <span v-if="st.is_remise" class="text-amber-600 font-medium ml-1">
                      (Remise: {{ st.remise }}{{ st.discount_type === 'percentage' ? '%' : ' TND' }})
                    </span>
                  </p>
                </div>

                <div class="flex items-center gap-1.5">
                  <button
                    type="button"
                    @click="batchRecords[st.id] = 'present'"
                    :class="[
                      'px-3 py-1 text-xs font-semibold rounded-lg border transition-colors',
                      batchRecords[st.id] === 'present'
                        ? 'bg-green-600 text-white border-green-600 shadow-xs'
                        : 'bg-white text-gray-600 border-gray-300 hover:bg-green-50'
                    ]"
                  >
                    Présent
                  </button>
                  <button
                    type="button"
                    @click="batchRecords[st.id] = 'absent'"
                    :class="[
                      'px-3 py-1 text-xs font-semibold rounded-lg border transition-colors',
                      batchRecords[st.id] === 'absent'
                        ? 'bg-red-600 text-white border-red-600 shadow-xs'
                        : 'bg-white text-gray-600 border-gray-300 hover:bg-red-50'
                    ]"
                  >
                    Absent
                  </button>
                  <button
                    type="button"
                    @click="batchRecords[st.id] = 'excused'"
                    :class="[
                      'px-3 py-1 text-xs font-semibold rounded-lg border transition-colors',
                      batchRecords[st.id] === 'excused'
                        ? 'bg-yellow-500 text-white border-yellow-500 shadow-xs'
                        : 'bg-white text-gray-600 border-gray-300 hover:bg-yellow-50'
                    ]"
                  >
                    Excusé
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
            <button
              type="button"
              @click="closeBatchModal"
              class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="submitting || studentsInSelectedGroup.length === 0"
              class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-sm"
            >
              {{ submitting ? 'Enregistrement...' : 'Enregistrer la séance' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Student Summary Modal -->
    <div v-if="selectedStudentSummary" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click="selectedStudentSummary = null">
      <div class="bg-white rounded-2xl p-6 max-w-lg w-full shadow-2xl space-y-4" @click.stop>
        <div class="flex items-center justify-between border-b pb-3 border-gray-100">
          <div>
            <h3 class="text-xl font-bold text-gray-900">{{ selectedStudentSummary.name }}</h3>
            <p class="text-xs text-gray-500">{{ selectedStudentSummary.level }} • Groupe {{ selectedStudentSummary.group_name || 'N/A' }}</p>
          </div>
          <button @click="selectedStudentSummary = null" class="text-gray-400 hover:text-gray-600 text-lg">×</button>
        </div>

        <!-- Financial & Attendance Card -->
        <div class="bg-linear-to-br from-indigo-50 to-blue-50 border border-indigo-100 rounded-xl p-4 space-y-3">
          <div class="flex justify-between items-center text-sm">
            <span class="text-gray-600">Tarif Brut de base:</span>
            <span class="font-bold text-gray-900">{{ studentFeeDetails.gross }} TND</span>
          </div>

          <div v-if="selectedStudentSummary.is_remise" class="flex justify-between items-center text-sm text-amber-700">
            <span>Remise accordée ({{ selectedStudentSummary.remise }}{{ selectedStudentSummary.discount_type === 'percentage' ? '%' : ' TND' }}):</span>
            <span class="font-bold">-{{ studentFeeDetails.discount }} TND</span>
          </div>

          <div class="pt-2 border-t border-indigo-200/60 flex justify-between items-center">
            <span class="text-base font-bold text-gray-900">Total Net à Payer:</span>
            <span class="text-xl font-black text-indigo-700">{{ studentFeeDetails.net }} TND</span>
          </div>
        </div>

        <!-- Attendance Stats -->
        <div class="grid grid-cols-3 gap-2 text-center">
          <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-100">
            <p class="text-xs text-gray-500">Séances</p>
            <p class="text-lg font-bold text-gray-800">{{ getStudentAttendanceStats(selectedStudentSummary.id).total }}</p>
          </div>
          <div class="bg-green-50 p-2.5 rounded-lg border border-green-100">
            <p class="text-xs text-green-700">Présences</p>
            <p class="text-lg font-bold text-green-800">{{ getStudentAttendanceStats(selectedStudentSummary.id).present }}</p>
          </div>
          <div class="bg-red-50 p-2.5 rounded-lg border border-red-100">
            <p class="text-xs text-red-700">Absences</p>
            <p class="text-lg font-bold text-red-800">{{ getStudentAttendanceStats(selectedStudentSummary.id).absent }}</p>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button
            @click="selectedStudentSummary = null"
            class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import {
  Calendar,
  CheckCircle2,
  XCircle,
  Clock,
  Users,
  UserCheck,
  Search,
  Trash2,
  FileText
} from 'lucide-vue-next';
import api from '../utils/api';
import { TUNISIAN_LEVELS, COMMON_TUNISIAN_GROUPS, getSubjectsForLevel } from '../utils/tunisianEducation';
import { filterTeachersBySubject } from '../utils/teacherSubjects';

interface StudentSubject {
  subject: string;
  teacher?: string;
  teacher_id?: number;
  group_name?: string;
  price_per_month?: number;
  price_per_session?: number;
}

interface Student {
  id: number;
  name: string;
  level: string;
  group_name?: string;
  phone: string;
  is_remise?: boolean;
  remise?: number;
  discount_type?: 'percentage' | 'fixed';
  base_fee?: number;
  calculated_fee?: {
    gross: number;
    discount: number;
    netPayable: number;
    isDefaultPrice?: boolean;
  };
  subjects?: StudentSubject[];
}

interface Teacher {
  id: number;
  name: string;
  subject: string;
  subjects?: string[];
}

interface AttendanceRecord {
  id: number;
  student_id: number;
  teacher_id?: number;
  group_name?: string;
  subject_name: string;
  session_date: string;
  status: 'present' | 'absent' | 'excused' | 'late';
  notes?: string;
  student?: Student;
  teacher?: Teacher;
}

const attendances = ref<AttendanceRecord[]>([]);
const students = ref<Student[]>([]);
const teachers = ref<Teacher[]>([]);

const loading = ref(false);
const submitting = ref(false);

// Filters
const filterGroup = ref('');
const filterDate = ref('');
const filterTeacherId = ref<number | ''>('');
const searchTerm = ref('');

// Modals
const showBatchModal = ref(false);
const selectedStudentSummary = ref<Student | null>(null);

const batchForm = ref({
  session_date: new Date().toISOString().split('T')[0],
  group_name: 'M1',
  subject_name: 'Mathématiques',
  teacher_id: undefined as number | undefined,
});

const batchRecords = ref<Record<number, 'present' | 'absent' | 'excused' | 'late'>>({});

const allAvailableGroups = computed(() => {
  const fromStudents = students.value.map(s => s.group_name).filter(Boolean) as string[];
  const combined = Array.from(new Set([...COMMON_TUNISIAN_GROUPS, ...fromStudents]));
  return combined.sort();
});

const uniqueGroups = computed(() => {
  const grps = attendances.value.map(a => a.group_name || a.student?.group_name).filter(Boolean);
  return Array.from(new Set(grps));
});

const presentCount = computed(() => attendances.value.filter(a => a.status === 'present').length);
const absentCount = computed(() => attendances.value.filter(a => a.status === 'absent').length);

const activeGroupSubjects = computed(() => {
  // Find sample student or matching curriculum
  const firstStudent = students.value.find(s => s.group_name === batchForm.value.group_name);
  if (firstStudent && firstStudent.level) {
    return getSubjectsForLevel(firstStudent.level);
  }
  return [
    'Mathématiques',
    'Sciences Physiques',
    'SVT',
    'Informatique',
    'Français',
    'Anglais',
    'Arabe',
    'Économie',
    'Gestion'
  ];
});

const studentsInSelectedGroup = computed(() => {
  if (!batchForm.value.group_name) return [];
  return students.value.filter(s => s.group_name === batchForm.value.group_name);
});

// Enseignants de la matière choisie (un enseignant peut donner plusieurs matières)
const batchTeacherOptions = computed(() =>
  filterTeachersBySubject(teachers.value, batchForm.value.subject_name)
);

const filteredAttendances = computed(() => {
  return attendances.value.filter(a => {
    const studentName = a.student?.name?.toLowerCase() || '';
    const matchesSearch = !searchTerm.value || studentName.includes(searchTerm.value.toLowerCase());
    const matchesGroup = !filterGroup.value || a.group_name === filterGroup.value || a.student?.group_name === filterGroup.value;
    const matchesDate = !filterDate.value || a.session_date === filterDate.value;
    const matchesTeacher = !filterTeacherId.value || a.teacher_id === Number(filterTeacherId.value);

    return matchesSearch && matchesGroup && matchesDate && matchesTeacher;
  });
});

async function loadData() {
  loading.value = true;
  try {
    const [attRes, stRes, teaRes] = await Promise.all([
      api.get('/api/attendances').catch(() => ({ data: [] })),
      api.get('/api/students').catch(() => ({ data: [] })),
      api.get('/api/teachers').catch(() => ({ data: [] })),
    ]);
    attendances.value = attRes.data || [];
    students.value = stRes.data || [];
    teachers.value = teaRes.data || [];
  } catch (err) {
    console.error('Error fetching data', err);
  } finally {
    loading.value = false;
  }
}

onMounted(loadData);

function resetFilters() {
  filterGroup.value = '';
  filterDate.value = '';
  filterTeacherId.value = '';
  searchTerm.value = '';
}

function openBatchModal() {
  showBatchModal.value = true;
  onBatchGroupChange();
}

function closeBatchModal() {
  showBatchModal.value = false;
}

function onBatchGroupChange() {
  batchRecords.value = {};
  studentsInSelectedGroup.value.forEach(s => {
    batchRecords.value[s.id] = 'present';
  });
  onBatchSubjectChange();
}

// Quand la matière change : on ne garde que les enseignants de cette matière
function onBatchSubjectChange() {
  const { list, matched } = batchTeacherOptions.value;
  const current = batchForm.value.teacher_id;

  if (current && !list.some(t => t.id === current)) {
    batchForm.value.teacher_id = undefined;
  }
  if (!batchForm.value.teacher_id && matched && list.length === 1) {
    batchForm.value.teacher_id = list[0].id;
  }
}

function markAllStudents(status: 'present' | 'absent') {
  studentsInSelectedGroup.value.forEach(s => {
    batchRecords.value[s.id] = status;
  });
}

async function submitBatchAttendance() {
  if (studentsInSelectedGroup.value.length === 0) return;
  submitting.value = true;

  try {
    const payload = {
      session_date: batchForm.value.session_date,
      group_name: batchForm.value.group_name,
      subject_name: batchForm.value.subject_name,
      teacher_id: batchForm.value.teacher_id || null,
      records: studentsInSelectedGroup.value.map(s => ({
        student_id: s.id,
        status: batchRecords.value[s.id] || 'present',
      }))
    };

    await api.post('/api/attendances/batch', payload);
    await loadData();
    showBatchModal.value = false;
  } catch (err) {
    console.error('Failed to submit batch attendance', err);
    alert('Erreur lors de l\'enregistrement des présences');
  } finally {
    submitting.value = false;
  }
}

async function deleteAttendance(id: number) {
  if (!confirm('Supprimer cet enregistrement de présence ?')) return;
  try {
    await api.delete(`/api/attendances/${id}`);
    attendances.value = attendances.value.filter(a => a.id !== id);
  } catch (err) {
    console.error('Delete attendance error', err);
  }
}

function openStudentSummary(student?: Student) {
  if (!student) return;
  selectedStudentSummary.value = student;
}

const studentFeeDetails = computed(() => {
  if (!selectedStudentSummary.value) return { gross: 0, discount: 0, net: 0 };
  const s = selectedStudentSummary.value;

  if (s.calculated_fee) {
    return {
      gross: s.calculated_fee.gross,
      discount: s.calculated_fee.discount,
      net: s.calculated_fee.netPayable
    };
  }

  // Fallback
  const gross = s.base_fee || 120;
  let discount = 0;
  if (s.is_remise && s.remise) {
    discount = s.discount_type === 'percentage' ? (gross * s.remise) / 100 : s.remise;
  }
  return {
    gross,
    discount,
    net: Math.max(0, gross - discount)
  };
});

function getStudentPayable(student: Student): number {
  if (student.calculated_fee?.netPayable !== undefined) {
    return student.calculated_fee.netPayable;
  }
  const gross = student.base_fee || 120;
  const rem = student.remise || 0;
  const disc = student.discount_type === 'percentage' ? (gross * rem) / 100 : rem;
  return Math.max(0, gross - disc);
}

function getStudentPresenceRate(studentId: number): number {
  return attendances.value.filter(a => a.student_id === studentId && a.status === 'present').length;
}

function getStudentAttendanceStats(studentId: number) {
  const records = attendances.value.filter(a => a.student_id === studentId);
  return {
    total: records.length,
    present: records.filter(r => r.status === 'present').length,
    absent: records.filter(r => r.status === 'absent').length,
  };
}

function getStatusLabel(status: string) {
  switch (status) {
    case 'present': return 'Présent';
    case 'absent': return 'Absent';
    case 'excused': return 'Excusé';
    case 'late': return 'En Retard';
    default: return status;
  }
}

function getStatusIcon(status: string) {
  switch (status) {
    case 'present': return CheckCircle2;
    case 'absent': return XCircle;
    case 'excused': return Clock;
    default: return Clock;
  }
}

function formatDate(dateStr: string) {
  if (!dateStr) return '-';
  try {
    return new Date(dateStr).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    });
  } catch {
    return dateStr;
  }
}
</script>