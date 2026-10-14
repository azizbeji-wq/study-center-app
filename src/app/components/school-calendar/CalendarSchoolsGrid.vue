<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white border border-gray-200 rounded-xl p-4 shadow-xs">
      <div class="relative flex-1 max-w-md">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input
          :value="searchQuery"
          @input="$emit('update:searchQuery', ($event.target as HTMLInputElement).value)"
          type="text"
          placeholder="Rechercher une école par nom ou ville..."
          class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        />
      </div>

      <button
        @click="$emit('openSchoolModal', null)"
        class="flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-xs text-sm cursor-pointer"
      >
        <Plus class="w-4 h-4" />
        <span>Nouvelle école</span>
      </button>
    </div>

    <!-- Liste des écoles -->
    <div v-if="schools.length === 0" class="bg-white border border-dashed border-gray-300 rounded-2xl p-12 text-center">
      <Building2 class="w-12 h-12 text-gray-300 mx-auto mb-2" />
      <h3 class="text-sm font-bold text-gray-800">Aucune école trouvée</h3>
      <p class="text-xs text-gray-500 mt-1">Ajoutez une école pour pouvoir y rattacher des emplois du temps.</p>
      <button
        @click="$emit('openSchoolModal', null)"
        class="mt-4 px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 cursor-pointer"
      >
        Ajouter une école
      </button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="school in schools"
        :key="school.id"
        class="bg-white rounded-xl border border-gray-200 p-5 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between"
      >
        <div>
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                <Building2 class="w-5 h-5" />
              </div>
              <div>
                <h3 class="font-bold text-gray-900 text-sm leading-tight">{{ school.name }}</h3>
                <p class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                  <MapPin class="w-3 h-3 text-gray-400 shrink-0" />
                  <span>{{ school.city || 'Ville non spécifiée' }}</span>
                </p>
              </div>
            </div>

            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
              {{ getSchoolImportCount(school.id) }} emploi(s)
            </span>
          </div>

          <p v-if="school.address" class="text-xs text-gray-600 mt-3 bg-gray-50 p-2.5 rounded-lg border border-gray-100">
            📍 {{ school.address }}
          </p>
        </div>

        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
          <button
            @click="$emit('viewSchoolImports', school.id)"
            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 hover:underline cursor-pointer"
          >
            <span>Voir les emplois du temps</span>
            <ChevronRight class="w-3.5 h-3.5" />
          </button>

          <div class="flex items-center gap-1">
            <button
              @click="$emit('openSchoolModal', school)"
              class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer"
              title="Modifier l'école"
              aria-label="Modifier"
            >
              <Pencil class="w-3.5 h-3.5" />
            </button>
            <button
              @click="$emit('askDeleteSchool', school)"
              class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
              title="Supprimer l'école"
              aria-label="Supprimer"
            >
              <Trash2 class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Search, Plus, Building2, MapPin, ChevronRight, Pencil, Trash2 } from 'lucide-vue-next';
import type { School } from '../../types/calendar';

defineProps<{
  schools: School[];
  searchQuery: string;
  getSchoolImportCount: (id: number) => number;
}>();

defineEmits<{
  (e: 'update:searchQuery', val: string): void;
  (e: 'openSchoolModal', school?: School | null): void;
  (e: 'viewSchoolImports', schoolId: number): void;
  (e: 'askDeleteSchool', school: School): void;
}>();
</script>
