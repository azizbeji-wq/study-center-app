<template>
  <div class="space-y-4">
    <!-- Barre de filtres et de recherche -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-xs p-4 space-y-3">
      <div class="flex flex-col sm:flex-row gap-3">
        <!-- Recherche textuelle -->
        <div class="relative flex-1">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            :value="searchQuery"
            @input="$emit('update:searchQuery', ($event.target as HTMLInputElement).value)"
            type="text"
            placeholder="Rechercher par école ou fichier..."
            class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
          <button
            v-if="searchQuery"
            @click="$emit('update:searchQuery', '')"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer"
          >
            <X class="w-3.5 h-3.5" />
          </button>
        </div>

        <!-- Filtre École -->
        <select
          :value="filterSchoolId"
          @change="$emit('update:filterSchoolId', ($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : null)"
          class="px-3 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none min-w-[200px]"
        >
          <option :value="null">Toutes les écoles ({{ schools.length }})</option>
          <option v-for="school in schools" :key="school.id" :value="school.id">
            {{ school.name }}
          </option>
        </select>

        <!-- Filtre Niveau -->
        <select
          :value="filterLevel"
          @change="$emit('update:filterLevel', ($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : null)"
          class="px-3 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none min-w-[150px]"
        >
          <option :value="null">Tous les niveaux</option>
          <option v-for="lvl in levels" :key="lvl" :value="lvl">
            {{ formatLevel(lvl) }}
          </option>
        </select>

        <!-- Filtre Section -->
        <select
          :value="filterSection"
          @change="$emit('update:filterSection', ($event.target as HTMLSelectElement).value || null)"
          class="px-3 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none min-w-[150px]"
        >
          <option :value="null">Toutes les classes</option>
          <option v-for="sec in sectionOptions" :key="sec.value" :value="sec.value">
            {{ sec.label }}
          </option>
        </select>

        <!-- Bouton Réinitialiser -->
        <button
          v-if="hasActiveFilters"
          @click="$emit('resetFilters')"
          class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors whitespace-nowrap cursor-pointer"
          title="Effacer tous les filtres"
        >
          <X class="w-3.5 h-3.5" />
          <span>Réinitialiser</span>
        </button>
      </div>
    </div>

    <!-- Chargement -->
    <div v-if="loadingList" class="flex justify-center py-16">
      <div class="flex flex-col items-center gap-3">
        <div class="w-9 h-9 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
        <span class="text-xs font-medium text-gray-500">Chargement des emplois du temps...</span>
      </div>
    </div>

    <!-- Aucun résultat -->
    <div
      v-else-if="imports.length === 0"
      class="bg-white border border-dashed border-gray-300 rounded-2xl p-12 text-center"
    >
      <div class="w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 mx-auto flex items-center justify-center mb-3">
        <FileText class="w-7 h-7" />
      </div>
      <h3 class="text-base font-bold text-gray-900">Aucun emploi du temps trouvé</h3>
      <p class="text-sm text-gray-500 max-w-md mx-auto mt-1">
        {{ hasActiveFilters
          ? "Aucun fichier ne correspond à vos filtres actuels. Modifiez la recherche ou réinitialisez les filtres."
          : "Vous n'avez pas encore importé d'emploi du temps. Cliquez ci-dessous pour ajouter le premier." }}
      </p>
      <div class="mt-4 flex items-center justify-center gap-2">
        <button
          v-if="hasActiveFilters"
          @click="$emit('resetFilters')"
          class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 cursor-pointer"
        >
          Réinitialiser les filtres
        </button>
        <button
          @click="$emit('openImport')"
          class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-xs flex items-center gap-2 cursor-pointer"
        >
          <Upload class="w-4 h-4" />
          <span>Importer maintenant</span>
        </button>
      </div>
    </div>

    <!-- Grille des cartes d'emplois du temps -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <article
        v-for="item in imports"
        :key="item.id"
        class="bg-white rounded-xl border border-gray-200 hover:border-indigo-200 hover:shadow-md transition-all p-5 flex flex-col justify-between group"
      >
        <div>
          <!-- Ligne supérieure avec icône type de fichier et badges -->
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
              <div
                :class="[
                  'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 shadow-2xs',
                  isPdf(item)
                    ? 'bg-rose-50 text-rose-600 border border-rose-100'
                    : 'bg-emerald-50 text-emerald-600 border border-emerald-100'
                ]"
              >
                <FileText v-if="isPdf(item)" class="w-5 h-5" />
                <ImageIcon v-else class="w-5 h-5" />
              </div>

              <div class="min-w-0">
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                  <span>{{ formatLevel(item.level) }}</span>
                  <span class="text-gray-300">•</span>
                  <span class="text-indigo-600 font-extrabold">{{ formatSectionDisplay(item.section) }}</span>
                </h3>
                <p class="text-xs text-gray-600 flex items-center gap-1 mt-0.5 truncate">
                  <Building2 class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                  <span class="truncate font-medium">{{ item.school_name }}</span>
                </p>
              </div>
            </div>

            <!-- Badge format -->
            <span
              :class="[
                'text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md shrink-0',
                isPdf(item) ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'
              ]"
            >
              {{ isPdf(item) ? 'PDF' : 'IMAGE' }}
            </span>
          </div>

          <!-- Détails fichier -->
          <div class="mt-4 p-3 bg-gray-50/80 rounded-lg border border-gray-100 space-y-1 text-xs">
            <div class="flex items-center justify-between text-gray-500">
              <span class="font-medium truncate pr-2 text-gray-700" :title="item.file_name">
                {{ item.file_name }}
              </span>
            </div>
            <p class="text-[11px] text-gray-400">
              Importé le {{ formatDate(item.created_at) }}
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center gap-2">
          <button
            @click="$emit('openViewer', item)"
            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-xs rounded-lg border border-indigo-200 transition-colors cursor-pointer"
            title="Prévisualiser le document"
          >
            <Eye class="w-3.5 h-3.5" />
            <span>Voir</span>
          </button>

          <button
            @click="$emit('openEdit', item)"
            class="p-2 border border-gray-200 text-gray-600 hover:text-indigo-600 hover:bg-gray-50 rounded-lg transition-colors cursor-pointer"
            title="Modifier l'affectation ou le fichier"
            aria-label="Modifier"
          >
            <Pencil class="w-3.5 h-3.5" />
          </button>

          <button
            @click="$emit('askDelete', item)"
            class="p-2 border border-gray-200 text-gray-500 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 rounded-lg transition-colors cursor-pointer"
            title="Supprimer cet emploi du temps"
            aria-label="Supprimer"
          >
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Search, X, FileText, Image as ImageIcon, Building2, Upload, Eye, Pencil, Trash2 } from 'lucide-vue-next';
import type { School, CalendarImport } from '../../types/calendar';
import { levels, sectionOptions, useSchoolCalendar } from '../../composables/useSchoolCalendar';

defineProps<{
  imports: CalendarImport[];
  schools: School[];
  searchQuery: string;
  filterSchoolId: number | null;
  filterLevel: number | null;
  filterSection: string | null;
  loadingList: boolean;
  hasActiveFilters: boolean;
}>();

defineEmits<{
  (e: 'update:searchQuery', val: string): void;
  (e: 'update:filterSchoolId', val: number | null): void;
  (e: 'update:filterLevel', val: number | null): void;
  (e: 'update:filterSection', val: string | null): void;
  (e: 'resetFilters'): void;
  (e: 'openImport'): void;
  (e: 'openViewer', item: CalendarImport): void;
  (e: 'openEdit', item: CalendarImport): void;
  (e: 'askDelete', item: CalendarImport): void;
}>();

const { formatLevel, formatSectionDisplay, formatDate, isPdf } = useSchoolCalendar();
</script>
