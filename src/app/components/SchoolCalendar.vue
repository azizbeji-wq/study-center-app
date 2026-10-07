<template>
  <div class="space-y-6">

    <!-- En-tête principal -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2.5">
          <Calendar class="w-7 h-7 text-indigo-600" />
          Calendrier Scolaire des Écoles Primaires
        </h2>
        <p class="text-sm text-gray-500 mt-1">
          Gestion complète des écoles partenaires et des emplois du temps importés par classe.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <button
          @click="openSchoolModal()"
          class="flex items-center gap-2 px-3.5 py-2 bg-white text-gray-700 font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors shadow-xs text-sm"
        >
          <Building2 class="w-4 h-4 text-indigo-600" />
          <span>Ajouter une école</span>
        </button>

        <button
          @click="openImport()"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm text-sm"
        >
          <Upload class="w-4 h-4" />
          <span>Importer un emploi du temps</span>
        </button>
      </div>
    </div>

    <!-- Messages de succès et d'erreur généraux -->
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform -translate-y-2 opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="successMessage"
        class="flex items-center justify-between text-sm text-emerald-800 bg-emerald-50 px-4 py-3 rounded-xl border border-emerald-200 shadow-xs"
      >
        <div class="flex items-center gap-2.5">
          <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
          <span>{{ successMessage }}</span>
        </div>
        <button @click="successMessage = ''" class="text-emerald-600 hover:text-emerald-800 p-1">
          <X class="w-4 h-4" />
        </button>
      </div>
    </transition>

    <div
      v-if="errorMessage"
      class="flex items-center justify-between text-sm text-red-800 bg-red-50 px-4 py-3 rounded-xl border border-red-200 shadow-xs"
    >
      <div class="flex items-center gap-2.5">
        <AlertTriangle class="w-5 h-5 text-red-600 shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>
      <button @click="errorMessage = ''" class="text-red-600 hover:text-red-800 p-1">
        <X class="w-4 h-4" />
      </button>
    </div>

    <!-- Statistiques rapides -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
      <div class="bg-white rounded-xl p-4 border border-gray-200/80 shadow-xs flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
          <FileText class="w-5 h-5" />
        </div>
        <div>
          <p class="text-xs font-medium text-gray-500">Emplois du temps</p>
          <p class="text-xl font-bold text-gray-900 mt-0.5">{{ imports.length }}</p>
        </div>
      </div>

      <div class="bg-white rounded-xl p-4 border border-gray-200/80 shadow-xs flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
          <Building2 class="w-5 h-5" />
        </div>
        <div>
          <p class="text-xs font-medium text-gray-500">Écoles enregistrées</p>
          <p class="text-xl font-bold text-gray-900 mt-0.5">{{ schools.length }}</p>
        </div>
      </div>

      <div class="bg-white rounded-xl p-4 border border-gray-200/80 shadow-xs flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-100 flex items-center justify-center text-purple-600 shrink-0">
          <Calendar class="w-5 h-5" />
        </div>
        <div>
          <p class="text-xs font-medium text-gray-500">Niveaux couverts</p>
          <p class="text-xl font-bold text-gray-900 mt-0.5">{{ distinctLevelsCount }} / 6</p>
        </div>
      </div>

      <div class="bg-white rounded-xl p-4 border border-gray-200/80 shadow-xs flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
          <Upload class="w-5 h-5" />
        </div>
        <div class="min-w-0">
          <p class="text-xs font-medium text-gray-500">Dernier import</p>
          <p class="text-xs font-bold text-gray-900 mt-1 truncate">{{ latestImportText }}</p>
        </div>
      </div>
    </div>

    <!-- Onglets de navigation : Emplois du temps vs Gestion des écoles -->
    <div class="flex items-center gap-2 border-b border-gray-200 pb-2">
      <button
        @click="activeView = 'imports'"
        :class="[
          'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all',
          activeView === 'imports'
            ? 'bg-indigo-600 text-white shadow-xs'
            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
        ]"
      >
        <FileText class="w-4 h-4" />
        <span>Emplois du temps importés</span>
        <span
          :class="[
            'text-xs px-2 py-0.5 rounded-full font-bold',
            activeView === 'imports' ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-700'
          ]"
        >
          {{ imports.length }}
        </span>
      </button>

      <button
        @click="activeView = 'schools'"
        :class="[
          'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all',
          activeView === 'schools'
            ? 'bg-indigo-600 text-white shadow-xs'
            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
        ]"
      >
        <Building2 class="w-4 h-4" />
        <span>Gestion des Écoles</span>
        <span
          :class="[
            'text-xs px-2 py-0.5 rounded-full font-bold',
            activeView === 'schools' ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-700'
          ]"
        >
          {{ schools.length }}
        </span>
      </button>
    </div>

    <!-- ================================================================= -->
    <!-- VUE 1 : EMPLOIS DU TEMPS IMPORTÉS                                -->
    <!-- ================================================================= -->
    <div v-if="activeView === 'imports'" class="space-y-4">

      <!-- Barre de filtres et de recherche -->
      <div class="bg-white border border-gray-200 rounded-xl shadow-xs p-4 space-y-3">
        <div class="flex flex-col sm:flex-row gap-3">
          <!-- Recherche textuelle -->
          <div class="relative flex-1">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher par école ou fichier..."
              class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
            <button
              v-if="searchQuery"
              @click="searchQuery = ''"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Filtre École -->
          <select
            v-model="filterSchoolId"
            class="px-3 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none min-w-[200px]"
          >
            <option :value="null">Toutes les écoles ({{ schools.length }})</option>
            <option v-for="school in schools" :key="school.id" :value="school.id">
              {{ school.name }}
            </option>
          </select>

          <!-- Filtre Niveau -->
          <select
            v-model="filterLevel"
            class="px-3 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none min-w-[150px]"
          >
            <option :value="null">Tous les niveaux</option>
            <option v-for="lvl in levels" :key="lvl" :value="lvl">
              {{ formatLevel(lvl) }}
            </option>
          </select>

          <!-- Filtre Section -->
          <select
            v-model="filterSection"
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
            @click="resetFilters"
            class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors whitespace-nowrap"
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
        v-else-if="filteredImports.length === 0"
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
            @click="resetFilters"
            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50"
          >
            Réinitialiser les filtres
          </button>
          <button
            @click="openImport()"
            class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-xs flex items-center gap-2"
          >
            <Upload class="w-4 h-4" />
            <span>Importer maintenant</span>
          </button>
        </div>
      </div>

      <!-- Grille des cartes d'emplois du temps -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <article
          v-for="item in filteredImports"
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
              @click="openViewer(item)"
              class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-xs rounded-lg border border-indigo-200 transition-colors"
              title="Prévisualiser le document"
            >
              <Eye class="w-3.5 h-3.5" />
              <span>Voir</span>
            </button>

            <button
              @click="openEdit(item)"
              class="p-2 border border-gray-200 text-gray-600 hover:text-indigo-600 hover:bg-gray-50 rounded-lg transition-colors"
              title="Modifier l'affectation ou le fichier"
              aria-label="Modifier"
            >
              <Pencil class="w-3.5 h-3.5" />
            </button>

            <button
              @click="askDelete(item)"
              class="p-2 border border-gray-200 text-gray-500 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 rounded-lg transition-colors"
              title="Supprimer cet emploi du temps"
              aria-label="Supprimer"
            >
              <Trash2 class="w-3.5 h-3.5" />
            </button>
          </div>
        </article>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- VUE 2 : GESTION DES ÉCOLES PRIMAIRES                             -->
    <!-- ================================================================= -->
    <div v-else-if="activeView === 'schools'" class="space-y-4">

      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white border border-gray-200 rounded-xl p-4 shadow-xs">
        <div class="relative flex-1 max-w-md">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            v-model="schoolSearchQuery"
            type="text"
            placeholder="Rechercher une école par nom ou ville..."
            class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
        </div>

        <button
          @click="openSchoolModal()"
          class="flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-xs text-sm"
        >
          <Plus class="w-4 h-4" />
          <span>Nouvelle école</span>
        </button>
      </div>

      <!-- Liste des écoles -->
      <div v-if="filteredSchools.length === 0" class="bg-white border border-dashed border-gray-300 rounded-2xl p-12 text-center">
        <Building2 class="w-12 h-12 text-gray-300 mx-auto mb-2" />
        <h3 class="text-sm font-bold text-gray-800">Aucune école trouvée</h3>
        <p class="text-xs text-gray-500 mt-1">Ajoutez une école pour pouvoir y rattacher des emplois du temps.</p>
        <button
          @click="openSchoolModal()"
          class="mt-4 px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700"
        >
          Ajouter une école
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="school in filteredSchools"
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
              @click="viewSchoolImports(school.id)"
              class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 hover:underline"
            >
              <span>Voir les emplois du temps</span>
              <ChevronRight class="w-3.5 h-3.5" />
            </button>

            <div class="flex items-center gap-1">
              <button
                @click="openSchoolModal(school)"
                class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-gray-100 rounded-lg transition-colors"
                title="Modifier l'école"
                aria-label="Modifier"
              >
                <Pencil class="w-3.5 h-3.5" />
              </button>
              <button
                @click="askDeleteSchool(school)"
                class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
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

    <!-- ================================================================= -->
    <!-- MODAL : VISUALISATION D'UN EMPLOI DU TEMPS (VIEWER)              -->
    <!-- ================================================================= -->
    <div
      v-if="viewerItem"
      class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
      @click="closeViewer"
    >
      <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[94vh] flex flex-col overflow-hidden mx-4 md:mx-auto"
        @click.stop
      >
        <!-- En-tête -->
        <div class="flex items-center justify-between gap-3 p-4 border-b border-gray-100 bg-gray-50/50">
          <div class="min-w-0">
            <h3 class="text-base sm:text-lg font-bold text-gray-900 flex items-center gap-2">
              <span>{{ formatLevel(viewerItem.level) }}</span>
              <span>•</span>
              <span class="text-indigo-600">{{ formatSectionDisplay(viewerItem.section) }}</span>
            </h3>
            <p class="text-xs text-gray-500 truncate mt-0.5">
              {{ viewerItem.school_name }} — {{ viewerItem.file_name }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <a
              v-if="viewerUrl"
              :href="viewerUrl"
              target="_blank"
              rel="noopener"
              class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-100 transition-colors"
            >
              <ExternalLink class="w-3.5 h-3.5" />
              <span>Ouvrir dans un onglet</span>
            </a>
            <a
              v-if="viewerUrl"
              :href="viewerUrl"
              :download="viewerItem.file_name"
              class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-xs"
            >
              <Download class="w-3.5 h-3.5" />
              <span>Télécharger</span>
            </a>
            <button
              @click="closeViewer"
              aria-label="Fermer"
              class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <X class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Corps d'aperçu -->
        <div class="flex-1 overflow-auto p-4 bg-gray-100 flex items-center justify-center min-h-[420px]">
          <div v-if="viewerLoading" class="flex flex-col items-center gap-2 py-16">
            <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
            <p class="text-xs text-gray-500">Chargement du document...</p>
          </div>

          <p v-else-if="viewerError" class="text-sm text-rose-600 text-center py-16 bg-white p-6 rounded-xl border border-rose-200">
            {{ viewerError }}
          </p>

          <template v-else-if="viewerUrl">
            <iframe
              v-if="isPdf(viewerItem)"
              :src="viewerUrl"
              title="Emploi du temps"
              class="w-full h-[72vh] rounded-lg border border-gray-300 bg-white shadow-sm"
            ></iframe>
            <div v-else class="max-h-[72vh] overflow-auto flex items-center justify-center">
              <img
                :src="viewerUrl"
                alt="Emploi du temps"
                class="max-w-full max-h-[70vh] object-contain rounded-lg border border-gray-300 bg-white shadow-sm"
              />
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODAL : IMPORT / MODIFICATION D'EMPLOI DU TEMPS                 -->
    <!-- ================================================================= -->
    <div
      v-if="showImportModal"
      class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
      @click="closeImport"
    >
      <div
        class="bg-white rounded-2xl shadow-2xl p-6 max-w-lg w-full space-y-4 max-h-[92vh] overflow-y-auto mx-4 md:mx-auto"
        @click.stop
      >
        <div class="flex items-start justify-between border-b border-gray-100 pb-3">
          <div>
            <h3 class="text-lg font-bold text-gray-900">{{ modalTitle }}</h3>
            <p class="text-xs text-gray-500 mt-0.5">{{ modalSubtitle }}</p>
          </div>
          <button @click="closeImport" aria-label="Fermer" class="text-gray-400 hover:text-gray-600 p-1">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Choix de l'école -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-semibold text-gray-700">École primaire *</label>
            <button
              type="button"
              @click="openSchoolModal(null, true)"
              class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 hover:underline"
            >
              <Plus class="w-3 h-3" />
              <span>Nouvelle école</span>
            </button>
          </div>
          <select
            v-model="importSchoolId"
            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          >
            <option :value="null">Sélectionnez une école...</option>
            <option v-for="school in schools" :key="school.id" :value="school.id">
              {{ school.name }} {{ school.city ? `(${school.city})` : '' }}
            </option>
          </select>
        </div>

        <!-- Niveau et Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Niveau scolaire *</label>
            <select
              v-model.number="importLevel"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            >
              <option v-for="lvl in levels" :key="lvl" :value="lvl">
                {{ formatLevel(lvl) }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Classe / Section *</label>
            <select
              v-model="importSection"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            >
              <option v-for="sec in sectionOptions" :key="sec.value" :value="sec.value">
                {{ sec.label }}
              </option>
            </select>
          </div>
        </div>

        <!-- Zone d'upload de fichier -->
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">
            {{ isEditing ? 'Remplacer le fichier (facultatif)' : 'Fichier emploi du temps *' }}
          </label>

          <p v-if="isEditing && editingItem" class="text-xs text-gray-500 mb-2">
            Fichier actuellement associé : <span class="font-semibold text-indigo-700">{{ editingItem.file_name }}</span>.
            Laissez vide pour conserver ce fichier.
          </p>

          <div
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="handleFileDrop"
            @click="triggerFileInput"
            :class="[
              'border-2 border-dashed rounded-xl p-5 text-center cursor-pointer transition-all',
              dragOver ? 'border-indigo-600 bg-indigo-50/60' : 'border-gray-300 hover:border-indigo-400 bg-gray-50/50'
            ]"
          >
            <input
              ref="fileInput"
              type="file"
              accept=".pdf,image/jpeg,image/png"
              class="hidden"
              @change="handleFileChange"
            />

            <div v-if="!selectedFile" class="space-y-1.5">
              <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 mx-auto flex items-center justify-center">
                <Upload class="w-5 h-5" />
              </div>
              <p class="text-xs font-bold text-gray-800">
                Glissez votre fichier ici ou cliquez pour parcourir
              </p>
              <p class="text-[11px] text-gray-400">
                Formats acceptés : PDF, JPG ou PNG (10 Mo max)
              </p>
            </div>

            <div v-else class="flex items-center justify-between p-3 bg-white rounded-lg border border-indigo-200">
              <div class="text-left min-w-0">
                <p class="text-xs font-bold text-gray-900 truncate">{{ selectedFile.name }}</p>
                <p class="text-[11px] text-gray-500">{{ (selectedFile.size / 1024).toFixed(1) }} Ko</p>
              </div>
              <button
                type="button"
                @click.stop="clearFile"
                class="text-xs text-rose-600 hover:underline font-semibold shrink-0 ml-3"
              >
                Retirer
              </button>
            </div>
          </div>
        </div>

        <div
          v-if="importError"
          class="text-xs text-rose-700 bg-rose-50 p-2.5 rounded-lg border border-rose-200 flex items-center gap-2"
        >
          <AlertTriangle class="w-4 h-4 shrink-0 text-rose-600" />
          <span>{{ importError }}</span>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
          <button
            type="button"
            @click="closeImport"
            class="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50"
          >
            Annuler
          </button>
          <button
            type="button"
            :disabled="!canSave || isUploading"
            @click="saveImport"
            class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-xs flex items-center gap-1.5"
          >
            <span v-if="isUploading" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>{{ submitLabel }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODAL : AJOUT / MODIFICATION D'UNE ÉCOLE PRIMAIRE               -->
    <!-- ================================================================= -->
    <div
      v-if="showSchoolModal"
      class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
      @click="closeSchoolModal"
    >
      <div
        class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full space-y-4 max-h-[90vh] overflow-y-auto mx-4 md:mx-auto"
        @click.stop
      >
        <div class="flex items-start justify-between border-b border-gray-100 pb-3">
          <div>
            <h3 class="text-base font-bold text-gray-900">
              {{ editingSchool ? "Modifier l'école" : "Ajouter une école primaire" }}
            </h3>
            <p class="text-xs text-gray-500 mt-0.5">
              Les écoles partenaires permettent de classer les emplois du temps.
            </p>
          </div>
          <button @click="closeSchoolModal" aria-label="Fermer" class="text-gray-400 hover:text-gray-600 p-1">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Nom de l'école *</label>
            <input
              v-model="schoolForm.name"
              type="text"
              placeholder="ex: École Primaire Habib Bourguiba"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Ville / Gouvernorat</label>
            <input
              v-model="schoolForm.city"
              type="text"
              placeholder="ex: Tunis, Ariana, Ben Arous..."
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Adresse complète</label>
            <input
              v-model="schoolForm.address"
              type="text"
              placeholder="ex: Avenue Habib Bourguiba, Centre Ville"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>
        </div>

        <div
          v-if="schoolError"
          class="text-xs text-rose-700 bg-rose-50 p-2.5 rounded-lg border border-rose-200"
        >
          {{ schoolError }}
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
          <button
            type="button"
            @click="closeSchoolModal"
            class="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50"
          >
            Annuler
          </button>
          <button
            type="button"
            :disabled="!schoolForm.name.trim() || isSavingSchool"
            @click="saveSchool"
            class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-xs"
          >
            {{ isSavingSchool ? 'Enregistrement...' : (editingSchool ? 'Mettre à jour' : 'Ajouter') }}
          </button>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODAL : CONFIRMATION SUPPRESSION EMPLOI DU TEMPS                 -->
    <!-- ================================================================= -->
    <div
      v-if="itemToDelete"
      class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
      @click="cancelDelete"
    >
      <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full space-y-4 max-h-[90vh] overflow-y-auto mx-4 md:mx-auto" @click.stop>
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
            <Trash2 class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-gray-900">Supprimer cet emploi du temps ?</h3>
            <p class="text-xs text-gray-600 mt-1">
              Cette action supprimera définitivement le fichier <span class="font-semibold">{{ itemToDelete.file_name }}</span> pour
              <span class="font-semibold text-gray-800">{{ formatLevel(itemToDelete.level) }} (Classe {{ formatSectionDisplay(itemToDelete.section) }})</span>
              à {{ itemToDelete.school_name }}.
            </p>
          </div>
        </div>

        <div
          v-if="deleteError"
          class="text-xs text-rose-700 bg-rose-50 p-2.5 rounded-lg border border-rose-200"
        >
          {{ deleteError }}
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button
            type="button"
            @click="cancelDelete"
            class="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50"
          >
            Annuler
          </button>
          <button
            type="button"
            :disabled="isDeleting"
            @click="confirmDelete"
            class="px-4 py-2 bg-rose-600 text-white text-xs font-semibold rounded-lg hover:bg-rose-700 disabled:opacity-50 transition-colors shadow-xs"
          >
            {{ isDeleting ? 'Suppression...' : 'Supprimer définitivement' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODAL : CONFIRMATION SUPPRESSION ÉCOLE                          -->
    <!-- ================================================================= -->
    <div
      v-if="schoolToDelete"
      class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
      @click="schoolToDelete = null"
    >
      <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full space-y-4 max-h-[90vh] overflow-y-auto mx-4 md:mx-auto" @click.stop>
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
            <AlertTriangle class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-gray-900">Supprimer cette école ?</h3>
            <p class="text-xs text-gray-600 mt-1">
              Êtes-vous sûr de vouloir supprimer l'école <span class="font-semibold text-gray-900">{{ schoolToDelete.name }}</span> ?
              Tous les emplois du temps associés seront également supprimés.
            </p>
          </div>
        </div>

        <div
          v-if="deleteSchoolError"
          class="text-xs text-rose-700 bg-rose-50 p-2.5 rounded-lg border border-rose-200"
        >
          {{ deleteSchoolError }}
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button
            type="button"
            @click="schoolToDelete = null"
            class="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50"
          >
            Annuler
          </button>
          <button
            type="button"
            :disabled="isDeletingSchool"
            @click="confirmDeleteSchool"
            class="px-4 py-2 bg-rose-600 text-white text-xs font-semibold rounded-lg hover:bg-rose-700 disabled:opacity-50 transition-colors shadow-xs"
          >
            {{ isDeletingSchool ? 'Suppression...' : 'Supprimer l\'école' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import api from '../utils/api';
import {
  Upload,
  FileText,
  Image as ImageIcon,
  X,
  Download,
  ExternalLink,
  Pencil,
  Trash2,
  Calendar,
  Building2,
  Plus,
  Search,
  CheckCircle2,
  AlertTriangle,
  Eye,
  MapPin,
  ChevronRight
} from 'lucide-vue-next';

interface School {
  id: number;
  name: string;
  address?: string | null;
  city?: string | null;
  classes_count?: number;
}

interface CalendarImport {
  id: number;
  file_name: string;
  file_type: string | null;
  created_at: string;
  school_id: number;
  school_name: string;
  level: number;
  section: string;
}

const levels = [1, 2, 3, 4, 5, 6];

const sectionOptions = [
  { value: 'A', label: 'Classe A (أ)' },
  { value: 'B', label: 'Classe B (ب)' },
  { value: 'C', label: 'Classe C (ج)' },
  { value: 'D', label: 'Classe D (د)' },
  { value: 'E', label: 'Classe E (هـ)' }
];

const MAX_SIZE = 10 * 1024 * 1024;
const ALLOWED_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

// Vue active : 'imports' ou 'schools'
const activeView = ref<'imports' | 'schools'>('imports');

const schools = ref<School[]>([]);
const imports = ref<CalendarImport[]>([]);
const loadingList = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

// Filtres et recherche
const searchQuery = ref('');
const filterSchoolId = ref<number | null>(null);
const filterLevel = ref<number | null>(null);
const filterSection = ref<string | null>(null);

// Viewer
const viewerItem = ref<CalendarImport | null>(null);
const viewerUrl = ref('');
const viewerLoading = ref(false);
const viewerError = ref('');

// Import / Édition d'emploi du temps
const showImportModal = ref(false);
const editingItem = ref<CalendarImport | null>(null);
const importSchoolId = ref<number | null>(null);
const importLevel = ref(1);
const importSection = ref('A');
const selectedFile = ref<File | null>(null);
const dragOver = ref(false);
const isUploading = ref(false);
const importError = ref('');
const fileInput = ref<HTMLInputElement | null>(null);

// Suppression emploi du temps
const itemToDelete = ref<CalendarImport | null>(null);
const isDeleting = ref(false);
const deleteError = ref('');

// Gestion des écoles (CRUD)
const showSchoolModal = ref(false);
const editingSchool = ref<School | null>(null);
const schoolForm = ref({
  name: '',
  city: '',
  address: ''
});
const schoolError = ref('');
const isSavingSchool = ref(false);
const schoolSearchQuery = ref('');
const schoolToDelete = ref<School | null>(null);
const isDeletingSchool = ref(false);
const deleteSchoolError = ref('');
const returnToImportAfterSchool = ref(false);

const isEditing = computed(() => editingItem.value !== null);

const canSave = computed(
  () => importSchoolId.value !== null && (isEditing.value || selectedFile.value !== null)
);

const modalTitle = computed(() =>
  isEditing.value ? "Modifier l'affectation ou le fichier" : 'Importer un emploi du temps'
);

const modalSubtitle = computed(() =>
  isEditing.value
    ? 'Modifiez l’école, le niveau, la section ou remplacez le document'
    : 'PDF, JPG ou PNG — 10 Mo maximum'
);

const submitLabel = computed(() => {
  if (isUploading.value) {
    return isEditing.value ? 'Enregistrement...' : 'Importation...';
  }
  return isEditing.value ? 'Mettre à jour' : 'Importer';
});

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

function formatLevel(level: number) {
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

function formatDate(value: string) {
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

function isPdf(item: CalendarImport) {
  return (item.file_type ?? '').includes('pdf') || item.file_name.toLowerCase().endsWith('.pdf');
}

function getSchoolImportCount(schoolId: number): number {
  return imports.value.filter(i => i.school_id === schoolId).length;
}

function extractError(e: any, fallback: string) {
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

function viewSchoolImports(schoolId: number) {
  activeView.value = 'imports';
  filterSchoolId.value = schoolId;
  filterLevel.value = null;
  filterSection.value = null;
  searchQuery.value = '';
}

/* ========================================================================= */
/* API : CHARGEMENT DES DONNÉES                                              */
/* ========================================================================= */

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

watch([filterSchoolId, filterLevel, filterSection], () => {
  loadImports();
});

/* ========================================================================= */
/* CONSULTATION (VIEWER)                                                     */
/* ========================================================================= */

async function openViewer(item: CalendarImport) {
  viewerItem.value = item;
  viewerUrl.value = '';
  viewerError.value = '';
  viewerLoading.value = true;

  try {
    const res = await api.get(`/api/school-calendar-imports/${item.id}/file`, {
      responseType: 'blob'
    });
    viewerUrl.value = URL.createObjectURL(res.data);
  } catch (e) {
    viewerError.value = 'Impossible de charger le fichier. Vérifiez votre connexion et réessayez.';
  } finally {
    viewerLoading.value = false;
  }
}

function closeViewer() {
  if (viewerUrl.value) {
    URL.revokeObjectURL(viewerUrl.value);
  }
  viewerUrl.value = '';
  viewerItem.value = null;
  viewerError.value = '';
}

/* ========================================================================= */
/* IMPORT / MODIFICATION D'EMPLOI DU TEMPS                                   */
/* ========================================================================= */

function openImport() {
  editingItem.value = null;
  importSchoolId.value = filterSchoolId.value || (schools.value[0]?.id ?? null);
  importLevel.value = filterLevel.value ?? 1;
  importSection.value = filterSection.value ?? 'A';
  selectedFile.value = null;
  importError.value = '';
  showImportModal.value = true;
}

function openEdit(item: CalendarImport) {
  editingItem.value = item;
  importSchoolId.value = item.school_id;
  importLevel.value = item.level;
  importSection.value = normalizeSection(item.section);
  selectedFile.value = null;
  importError.value = '';
  showImportModal.value = true;
}

function closeImport() {
  showImportModal.value = false;
  editingItem.value = null;
  selectedFile.value = null;
  importError.value = '';
}

function triggerFileInput() {
  fileInput.value?.click();
}

function setFile(file: File) {
  importError.value = '';

  if (!ALLOWED_TYPES.includes(file.type)) {
    importError.value = 'Format non accepté. Veuillez sélectionner un PDF, un JPG ou un PNG.';
    return;
  }
  if (file.size > MAX_SIZE) {
    importError.value = 'Fichier trop volumineux. La taille maximale autorisée est de 10 Mo.';
    return;
  }

  selectedFile.value = file;
}

function clearFile() {
  selectedFile.value = null;
  if (fileInput.value) fileInput.value.value = '';
}

function handleFileChange(e: Event) {
  const input = e.target as HTMLInputElement;
  if (input.files && input.files[0]) {
    setFile(input.files[0]);
  }
}

function handleFileDrop(e: DragEvent) {
  dragOver.value = false;
  if (e.dataTransfer?.files?.[0]) {
    setFile(e.dataTransfer.files[0]);
  }
}

const multipartConfig = {
  transformRequest: [
    (data: any, headers: any) => {
      if (headers) {
        if (typeof headers.delete === 'function') {
          headers.delete('Content-Type');
        } else {
          delete headers['Content-Type'];
          delete headers['content-type'];
        }
      }
      return data;
    }
  ]
};

async function saveImport() {
  if (importSchoolId.value === null) return;
  if (!isEditing.value && !selectedFile.value) return;

  isUploading.value = true;
  importError.value = '';

  const wasEditing = isEditing.value;

  try {
    const formData = new FormData();
    if (selectedFile.value) {
      formData.append('file', selectedFile.value);
    }
    formData.append('school_id', String(importSchoolId.value));
    formData.append('level', String(importLevel.value));
    formData.append('section', importSection.value);

    if (wasEditing && editingItem.value) {
      await api.post(
        `/api/school-calendar-imports/${editingItem.value.id}`,
        formData,
        multipartConfig
      );
    } else {
      await api.post('/api/school-calendar-imports', formData, multipartConfig);
    }

    const schoolName = schools.value.find(s => s.id === importSchoolId.value)?.name ?? '';
    const sectionLabel = formatSectionDisplay(importSection.value);
    const where = `${formatLevel(importLevel.value)}, ${sectionLabel}, ${schoolName}`;

    showSuccess(
      wasEditing
        ? `Emploi du temps mis à jour avec succès : ${where}.`
        : `Nouvel emploi du temps importé avec succès : ${where}.`
    );

    closeImport();
    await loadImports();
  } catch (e) {
    importError.value = extractError(
      e,
      wasEditing
        ? 'La modification a échoué. Veuillez vérifier les champs et réessayer.'
        : "L'import a échoué. Vérifiez le fichier et réessayez."
    );
  } finally {
    isUploading.value = false;
  }
}

/* ========================================================================= */
/* SUPPRESSION D'EMPLOI DU TEMPS                                             */
/* ========================================================================= */

function askDelete(item: CalendarImport) {
  itemToDelete.value = item;
  deleteError.value = '';
}

function cancelDelete() {
  if (isDeleting.value) return;
  itemToDelete.value = null;
  deleteError.value = '';
}

async function confirmDelete() {
  if (!itemToDelete.value) return;

  isDeleting.value = true;
  deleteError.value = '';

  try {
    await api.delete(`/api/school-calendar-imports/${itemToDelete.value.id}`);
    itemToDelete.value = null;
    showSuccess('Emploi du temps supprimé avec succès.');
    await loadImports();
  } catch (e) {
    deleteError.value = extractError(e, 'La suppression a échoué. Réessayez.');
  } finally {
    isDeleting.value = false;
  }
}

/* ========================================================================= */
/* CRUD ÉCOLES PRIMAIRES                                                     */
/* ========================================================================= */

function openSchoolModal(school: School | null = null, fromImport = false) {
  editingSchool.value = school;
  returnToImportAfterSchool.value = fromImport;
  schoolError.value = '';

  if (school) {
    schoolForm.value = {
      name: school.name,
      city: school.city || '',
      address: school.address || ''
    };
  } else {
    schoolForm.value = {
      name: '',
      city: '',
      address: ''
    };
  }

  showSchoolModal.value = true;
}

function closeSchoolModal() {
  showSchoolModal.value = false;
  editingSchool.value = null;
  schoolError.value = '';
  returnToImportAfterSchool.value = false;
}

async function saveSchool() {
  if (!schoolForm.value.name.trim()) return;

  isSavingSchool.value = true;
  schoolError.value = '';

  try {
    let savedId: number | null = null;
    if (editingSchool.value) {
      const res = await api.put(`/api/schools/${editingSchool.value.id}`, schoolForm.value);
      savedId = res.data.id;
      showSuccess(`École "${schoolForm.value.name}" mise à jour.`);
    } else {
      const res = await api.post('/api/schools', schoolForm.value);
      savedId = res.data.id;
      showSuccess(`École "${schoolForm.value.name}" ajoutée.`);
    }

    await loadSchools();

    // Si on a ouvert ce modal depuis le modal d'import, présélectionner cette école
    if (returnToImportAfterSchool.value && savedId) {
      importSchoolId.value = savedId;
    }

    closeSchoolModal();
  } catch (e) {
    schoolError.value = extractError(e, "L'enregistrement de l'école a échoué.");
  } finally {
    isSavingSchool.value = false;
  }
}

function askDeleteSchool(school: School) {
  schoolToDelete.value = school;
  deleteSchoolError.value = '';
}

async function confirmDeleteSchool() {
  if (!schoolToDelete.value) return;

  isDeletingSchool.value = true;
  deleteSchoolError.value = '';

  try {
    await api.delete(`/api/schools/${schoolToDelete.value.id}`);
    const name = schoolToDelete.value.name;
    schoolToDelete.value = null;
    showSuccess(`École "${name}" et ses emplois du temps associés ont été supprimés.`);
    await loadSchools();
    await loadImports();
  } catch (e) {
    deleteSchoolError.value = extractError(e, "Impossible de supprimer l'école.");
  } finally {
    isDeletingSchool.value = false;
  }
}

/* ========================================================================= */
/* CYCLE DE VIE                                                              */
/* ========================================================================= */

onMounted(async () => {
  await Promise.all([loadSchools(), loadImports()]);
});

onBeforeUnmount(() => {
  if (viewerUrl.value) URL.revokeObjectURL(viewerUrl.value);
});
</script>