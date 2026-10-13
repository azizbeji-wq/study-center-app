<template>
  <div class="space-y-6">
    <!-- En-tête -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold mb-1">
          <UserCheck class="w-3.5 h-3.5" />
          <span>Feuille de Présence & Appel</span>
        </div>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Faire l'Appel</h2>
        <p class="text-xs text-gray-500 mt-0.5">Pointez les présences, absences et retards de vos séances en temps réel</p>
      </div>

      <!-- Sélecteur d'onglet & Export PDF -->
      <div class="flex items-center gap-2.5 flex-wrap">
        <button
          type="button"
          @click="openPdfModal"
          :disabled="!selectedGroup || studentsList.length === 0"
          class="px-3.5 py-1.5 bg-violet-600 hover:bg-violet-700 disabled:opacity-40 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm shadow-violet-600/20 cursor-pointer disabled:cursor-not-allowed shrink-0"
          title="Exporter la feuille de présence officielle en PDF"
        >
          <Printer class="w-3.5 h-3.5" />
          <span>Exporter Fiche d'Appel (PDF)</span>
        </button>

        <div class="flex items-center gap-2 bg-gray-100 p-1 rounded-xl shrink-0">
          <button
            @click="activeTab = 'take'"
            :class="[
              'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5',
              activeTab === 'take'
                ? 'bg-white text-gray-900 shadow-xs'
                : 'text-gray-500 hover:text-gray-900'
            ]"
          >
            <CheckSquare class="w-3.5 h-3.5" />
            <span>Saisie de Séance</span>
          </button>
          <button
            @click="activeTab = 'history'"
            :class="[
              'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5',
              activeTab === 'history'
                ? 'bg-white text-gray-900 shadow-xs'
                : 'text-gray-500 hover:text-gray-900'
            ]"
          >
            <History class="w-3.5 h-3.5" />
            <span>Historique Passé</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Notification de succès / erreur -->
    <div
      v-if="toastMessage"
      :class="[
        'p-4 rounded-2xl text-xs font-bold flex items-center gap-2.5 shadow-md animate-in fade-in',
        toastType === 'success' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white'
      ]"
    >
      <CheckCircle2 v-if="toastType === 'success'" class="w-5 h-5 shrink-0" />
      <AlertCircle v-else class="w-5 h-5 shrink-0" />
      <span class="flex-1">{{ toastMessage }}</span>
      <button @click="toastMessage = ''" class="text-white/80 hover:text-white p-1">
        <X class="w-4 h-4" />
      </button>
    </div>

    <!-- ONGLET 1 : SAISIE DE LA SÉANCE -->
    <div v-if="activeTab === 'take'" class="space-y-6">
      <!-- Sélecteurs de Séance (Groupe, Matière, Date) -->
      <div class="bg-white rounded-2xl p-5 border border-gray-200/90 shadow-xs">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <!-- Sélecteur de Groupe -->
          <div>
            <label class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center gap-1.5">
              <Users class="w-3.5 h-3.5 text-violet-600" />
              <span>Groupe d'Élèves</span>
            </label>
            <select
              v-model="selectedGroup"
              @change="onGroupChange"
              class="w-full text-xs font-semibold px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-violet-500 focus:outline-none"
            >
              <option value="" disabled>-- Choisir un groupe --</option>
              <option v-for="g in groupsList" :key="g.group_name" :value="g.group_name">
                {{ g.group_name }} ({{ g.student_count }} élèves - {{ g.level || 'Général' }})
              </option>
            </select>
          </div>

          <!-- Sélecteur de Matière -->
          <div>
            <label class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center gap-1.5">
              <BookOpen class="w-3.5 h-3.5 text-violet-600" />
              <span>Matière Enseignée</span>
            </label>
            <input
              v-model="selectedSubject"
              type="text"
              placeholder="Ex: Mathématiques, Physique..."
              class="w-full text-xs font-semibold px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-violet-500 focus:outline-none"
            />
          </div>

          <!-- Sélecteur de Date -->
          <div>
            <label class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center gap-1.5">
              <Calendar class="w-3.5 h-3.5 text-violet-600" />
              <span>Date de la Séance</span>
            </label>
            <input
              v-model="sessionDate"
              type="date"
              @change="fetchStudents"
              class="w-full text-xs font-semibold px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-violet-500 focus:outline-none"
            />
          </div>
        </div>
      </div>

      <!-- Barre de Statistiques & Actions Rapides -->
      <div
        v-if="selectedGroup && studentsList.length > 0"
        class="bg-white rounded-2xl p-4 border border-gray-200/90 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4"
      >
        <!-- Compteurs -->
        <div class="flex flex-wrap items-center gap-3 text-xs">
          <div class="flex items-center gap-1.5 bg-gray-100 text-gray-700 px-3 py-1.5 rounded-xl font-bold">
            <Users class="w-3.5 h-3.5" />
            <span>Total : {{ studentsList.length }}</span>
          </div>
          <div class="flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-xl font-bold border border-emerald-200/60">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>Présents : {{ countStatus('present') }}</span>
          </div>
          <div class="flex items-center gap-1.5 bg-red-50 text-red-700 px-3 py-1.5 rounded-xl font-bold border border-red-200/60">
            <span class="w-2 h-2 rounded-full bg-red-500"></span>
            <span>Absents : {{ countStatus('absent') }}</span>
          </div>
          <div class="flex items-center gap-1.5 bg-amber-50 text-amber-700 px-3 py-1.5 rounded-xl font-bold border border-amber-200/60">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            <span>Retards : {{ countStatus('late') }}</span>
          </div>
          <div class="flex items-center gap-1.5 bg-blue-50 text-blue-700 px-3 py-1.5 rounded-xl font-bold border border-blue-200/60">
            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            <span>Excusés : {{ countStatus('excused') }}</span>
          </div>
          <div class="text-[11px] font-bold text-gray-500">
            Taux : {{ attendanceRate }}%
          </div>
        </div>

        <!-- Raccourcis d'appel & Export PDF -->
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="openPdfModal"
            class="px-3 py-1.5 bg-violet-50 hover:bg-violet-100 text-violet-700 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5 border border-violet-200/70 cursor-pointer shadow-2xs"
            title="Aperçu et impression de la fiche d'appel en PDF"
          >
            <Printer class="w-3.5 h-3.5 text-violet-600" />
            <span>Imprimer / PDF</span>
          </button>
          <button
            @click="markAll('present')"
            class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-xl text-xs font-bold transition-colors flex items-center gap-1 cursor-pointer"
          >
            <CheckCheck class="w-3.5 h-3.5" />
            <span>Tous Présents</span>
          </button>
          <button
            @click="markAll('absent')"
            class="px-3 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-xs font-bold transition-colors flex items-center gap-1 cursor-pointer"
          >
            <RotateCcw class="w-3.5 h-3.5" />
            <span>Réinitialiser</span>
          </button>
        </div>
      </div>

      <!-- Chargement des élèves -->
      <div v-if="loadingStudents" class="flex flex-col items-center justify-center py-16 bg-white rounded-2xl border border-gray-200">
        <div class="w-10 h-10 border-4 border-violet-600 border-t-transparent rounded-full animate-spin mb-3"></div>
        <p class="text-xs font-semibold text-gray-500">Chargement de la liste des élèves...</p>
      </div>

      <!-- Aucun groupe sélectionné -->
      <div
        v-else-if="!selectedGroup"
        class="bg-white rounded-2xl border border-gray-200 p-12 text-center max-w-lg mx-auto space-y-3"
      >
        <div class="w-14 h-14 bg-violet-50 text-violet-600 rounded-2xl flex items-center justify-center mx-auto">
          <Users class="w-7 h-7" />
        </div>
        <h3 class="text-base font-bold text-gray-900">Sélectionnez un groupe</h3>
        <p class="text-xs text-gray-500 leading-relaxed">
          Veuillez choisir un groupe dans le menu déroulant ci-dessus pour charger la feuille d'appel correspondante.
        </p>
      </div>

      <!-- Aucun élève dans ce groupe -->
      <div
        v-else-if="studentsList.length === 0"
        class="bg-white rounded-2xl border border-gray-200 p-12 text-center max-w-lg mx-auto space-y-3"
      >
        <div class="w-14 h-14 bg-gray-100 text-gray-400 rounded-2xl flex items-center justify-center mx-auto">
          <AlertCircle class="w-7 h-7" />
        </div>
        <h3 class="text-base font-bold text-gray-900">Aucun élève trouvé</h3>
        <p class="text-xs text-gray-500 leading-relaxed">
          Aucun élève n'est inscrit dans le groupe « {{ selectedGroup }} ». Vérifiez auprès de l'administration si des inscriptions sont en attente.
        </p>
      </div>

      <!-- Liste des élèves & Pointage -->
      <div v-else class="space-y-4">
        <div class="bg-white rounded-2xl border border-gray-200/90 shadow-xs overflow-hidden">
          <div class="divide-y divide-gray-100">
            <div
              v-for="(student, index) in studentsList"
              :key="student.id"
              class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-gray-50/50 transition-colors"
            >
              <!-- Info Élève -->
              <div class="flex items-center gap-3.5 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center font-black text-sm shrink-0">
                  {{ student.name.charAt(0).toUpperCase() }}
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400 font-bold">#{{ index + 1 }}</span>
                    <h4 class="text-sm font-bold text-gray-900 truncate">{{ student.name }}</h4>
                    <span v-if="student.level" class="text-[10px] font-semibold bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md shrink-0">
                      {{ student.level }}
                    </span>
                  </div>
                  <div class="flex items-center gap-3 text-xs text-gray-500 mt-0.5">
                    <span v-if="student.phone" class="flex items-center gap-1">
                      <Phone class="w-3 h-3 text-gray-400" />
                      {{ student.phone }}
                    </span>
                    <span v-if="student.parent_phone" class="flex items-center gap-1 text-[11px] text-gray-400">
                      Parent : {{ student.parent_phone }}
                    </span>
                    <span class="text-[11px] text-emerald-600 font-semibold">
                      Assiduité : {{ student.stats?.attendance_rate ?? 100 }}%
                    </span>
                  </div>
                </div>
              </div>

              <!-- Statut de présence (Boutons radio ergonomiques) & Notes -->
              <div class="flex flex-col sm:flex-row sm:items-center gap-3 shrink-0">
                <!-- Boutons de Statut -->
                <div class="inline-flex rounded-xl p-1 bg-gray-100 border border-gray-200/80 gap-1">
                  <!-- Présent -->
                  <button
                    type="button"
                    @click="student.current_status = 'present'"
                    :class="[
                      'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
                      student.current_status === 'present'
                        ? 'bg-emerald-600 text-white shadow-xs'
                        : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'
                    ]"
                  >
                    <Check class="w-3.5 h-3.5" />
                    <span>Présent</span>
                  </button>

                  <!-- Absent -->
                  <button
                    type="button"
                    @click="student.current_status = 'absent'"
                    :class="[
                      'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
                      student.current_status === 'absent'
                        ? 'bg-red-600 text-white shadow-xs'
                        : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'
                    ]"
                  >
                    <X class="w-3.5 h-3.5" />
                    <span>Absent</span>
                  </button>

                  <!-- En Retard -->
                  <button
                    type="button"
                    @click="student.current_status = 'late'"
                    :class="[
                      'px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1 cursor-pointer',
                      student.current_status === 'late'
                        ? 'bg-amber-500 text-white shadow-xs'
                        : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'
                    ]"
                  >
                    <Clock class="w-3.5 h-3.5" />
                    <span>Retard</span>
                  </button>

                  <!-- Excusé -->
                  <button
                    type="button"
                    @click="student.current_status = 'excused'"
                    :class="[
                      'px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1 cursor-pointer',
                      student.current_status === 'excused'
                        ? 'bg-blue-600 text-white shadow-xs'
                        : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'
                    ]"
                  >
                    <ShieldAlert class="w-3.5 h-3.5" />
                    <span>Excusé</span>
                  </button>
                </div>

                <!-- Champ Note / Observation -->
                <div class="relative w-full sm:w-44">
                  <input
                    v-model="student.current_notes"
                    type="text"
                    placeholder="Motif / observation..."
                    class="w-full text-[11px] px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-violet-500 focus:outline-none"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bouton de Sauvegarde Flottant / Bas de page -->
        <div class="flex items-center justify-between p-4 bg-white rounded-2xl border border-gray-200/90 shadow-md">
          <div class="text-xs text-gray-500">
            Séance du <span class="font-bold text-gray-900">{{ sessionDate }}</span> pour <span class="font-bold text-violet-700">{{ selectedGroup }}</span> ({{ selectedSubject }})
          </div>
          <button
            @click="submitAttendance"
            :disabled="saving"
            class="px-6 py-3 bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-extrabold rounded-xl text-xs transition-all flex items-center gap-2 shadow-lg shadow-violet-600/25 cursor-pointer"
          >
            <span v-if="saving" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <Save v-else class="w-4 h-4" />
            <span>{{ saving ? 'Validation en cours...' : 'Enregistrer et valider l\'appel' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ONGLET 2 : HISTORIQUE DES SÉANCES -->
    <div v-else class="space-y-4">
      <div class="bg-white rounded-2xl p-5 border border-gray-200/90 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div>
          <h3 class="text-sm font-bold text-gray-900">Historique des Présences Enregistrées</h3>
          <p class="text-xs text-gray-500 mt-0.5">Consultez les pointages réalisés lors de vos précédentes séances</p>
        </div>
        <button
          @click="fetchHistory"
          :disabled="loadingHistory"
          class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5"
        >
          <RotateCw :class="['w-3.5 h-3.5', loadingHistory ? 'animate-spin' : '']" />
          <span>Actualiser</span>
        </button>
      </div>

      <div v-if="loadingHistory" class="flex justify-center py-12">
        <div class="w-8 h-8 border-4 border-violet-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div
        v-else-if="historyList.length === 0"
        class="bg-white rounded-2xl border border-gray-200 p-12 text-center max-w-lg mx-auto space-y-3"
      >
        <div class="w-14 h-14 bg-gray-50 text-gray-400 rounded-2xl flex items-center justify-center mx-auto">
          <History class="w-7 h-7" />
        </div>
        <h3 class="text-base font-bold text-gray-900">Aucun historique disponible</h3>
        <p class="text-xs text-gray-500 leading-relaxed">
          Vous n'avez pas encore validé d'appel de présence depuis ce compte. Vos pointages apparaîtront ici.
        </p>
      </div>

      <div v-else class="bg-white rounded-2xl border border-gray-200/90 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
              <tr>
                <th class="py-3 px-4">Date Séance</th>
                <th class="py-3 px-4">Élève</th>
                <th class="py-3 px-4">Groupe</th>
                <th class="py-3 px-4">Matière</th>
                <th class="py-3 px-4">Statut</th>
                <th class="py-3 px-4">Observations</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="att in historyList" :key="att.id" class="hover:bg-gray-50/50">
                <td class="py-3 px-4 font-bold text-gray-900">{{ att.session_date }}</td>
                <td class="py-3 px-4 font-semibold text-gray-800">{{ att.student?.name || 'Élève #' + att.student_id }}</td>
                <td class="py-3 px-4">
                  <span class="inline-block bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded font-bold text-[10px]">
                    {{ att.group_name || 'N/A' }}
                  </span>
                </td>
                <td class="py-3 px-4 text-gray-600">{{ att.subject_name }}</td>
                <td class="py-3 px-4">
                  <span
                    :class="[
                      'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold',
                      att.status === 'present' ? 'bg-emerald-100 text-emerald-800' : '',
                      att.status === 'absent' ? 'bg-red-100 text-red-800' : '',
                      att.status === 'late' ? 'bg-amber-100 text-amber-800' : '',
                      att.status === 'excused' ? 'bg-blue-100 text-blue-800' : ''
                    ]"
                  >
                    {{ formatStatusLabel(att.status) }}
                  </span>
                </td>
                <td class="py-3 px-4 text-gray-500 italic">{{ att.notes || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modale d'Aperçu & Impression de la Fiche d'Appel en PDF -->
    <div
      v-if="showPdfModal"
      class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-3 sm:p-6 overflow-y-auto"
      @click.self="showPdfModal = false"
    >
      <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl my-6 flex flex-col border border-gray-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200 max-h-[92vh]"
      >
        <!-- Header de la modale -->
        <div class="px-6 py-4 bg-gradient-to-r from-violet-700 via-purple-700 to-violet-800 text-white flex items-center justify-between shrink-0 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-md flex items-center justify-center font-bold text-lg border border-white/20 shadow-inner">
              <Printer class="w-5 h-5 text-white" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-base font-black tracking-tight text-white">Export PDF - Fiche d'Appel Pédagogique</h3>
                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-white/20 text-white border border-white/30">
                  Format A4
                </span>
              </div>
              <p class="text-xs text-violet-100/90 mt-0.5">
                {{ selectedGroup }} • {{ selectedSubject }} • Séance du {{ sessionDate }}
              </p>
            </div>
          </div>

          <button
            @click="showPdfModal = false"
            class="p-1.5 rounded-xl text-white/80 hover:text-white hover:bg-white/15 transition-colors cursor-pointer"
            title="Fermer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Barre d'outils de la modale : Choix du format & Bouton Imprimer -->
        <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shrink-0">
          <!-- Toggle Format : Rempli vs Vierge -->
          <div class="inline-flex rounded-xl p-1 bg-gray-200/80 gap-1 text-xs font-bold">
            <button
              type="button"
              @click="pdfFormat = 'filled'"
              :class="[
                'px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5',
                pdfFormat === 'filled' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              <CheckSquare class="w-3.5 h-3.5 text-emerald-600" />
              <span>Avec Pointage Actuel</span>
            </button>
            <button
              type="button"
              @click="pdfFormat = 'blank'"
              :class="[
                'px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5',
                pdfFormat === 'blank' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              <FileText class="w-3.5 h-3.5 text-violet-600" />
              <span>Fiche Vierge (Émargement en classe)</span>
            </button>
          </div>

          <!-- Actions Imprimer / Enregistrer PDF -->
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="showPdfModal = false"
              class="px-4 py-2 border border-gray-200 bg-white hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-xl transition-all cursor-pointer"
            >
              Fermer
            </button>
            <button
              type="button"
              @click="triggerPrintAttendance"
              class="px-5 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-xl transition-all flex items-center gap-2 shadow-md shadow-violet-600/25 cursor-pointer"
            >
              <Printer class="w-4 h-4" />
              <span>Imprimer / Télécharger en PDF (A4)</span>
            </button>
          </div>
        </div>

        <!-- Corps de la modale avec aperçu fidèle de la feuille A4 -->
        <div class="p-6 overflow-y-auto bg-gray-100/60 flex-1 flex justify-center">
          <div
            id="attendance-sheet-printable"
            class="bg-white rounded-xl shadow-md border border-gray-300 p-8 sm:p-10 w-full max-w-3xl space-y-6 text-gray-900"
          >
            <!-- En-tête officiel du document -->
            <div class="border-b-2 border-violet-800 pb-5">
              <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div>
                  <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-violet-800 text-white flex items-center justify-center font-black text-xl shadow-xs">
                      SC
                    </div>
                    <div>
                      <h1 class="text-lg font-black text-violet-950 uppercase tracking-tight">
                        Centre d'Études & de Formation
                      </h1>
                      <p class="text-[11px] font-semibold text-gray-600">
                        Établissement de Soutien Scolaire Pédagogique • Cursus Tunisien
                      </p>
                    </div>
                  </div>
                  <p class="text-[10px] text-gray-400 mt-1">
                    Année Scolaire : 2025 - 2026 • République Tunisienne
                  </p>
                </div>

                <div class="text-right sm:text-right border-t sm:border-t-0 pt-2 sm:pt-0 border-gray-100">
                  <span class="inline-block px-3 py-1 rounded-md text-[11px] font-black uppercase tracking-wider bg-violet-50 text-violet-900 border border-violet-200">
                    FICHE OFFICIELLE D'APPEL & D'ÉMARGEMENT
                  </span>
                  <p class="text-[11px] font-semibold text-gray-500 mt-1">
                    Date : <strong class="text-gray-900">{{ sessionDate }}</strong>
                  </p>
                </div>
              </div>
            </div>

            <!-- Fiche d'identification de la séance -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-gray-50 p-4 rounded-xl border border-gray-200 text-xs">
              <div>
                <span class="text-[10px] font-bold text-gray-500 uppercase block">Enseignant(e)</span>
                <strong class="text-gray-900 text-xs">{{ currentTeacherName }}</strong>
              </div>
              <div>
                <span class="text-[10px] font-bold text-gray-500 uppercase block">Groupe</span>
                <strong class="text-violet-700 text-xs">{{ selectedGroup }}</strong>
              </div>
              <div>
                <span class="text-[10px] font-bold text-gray-500 uppercase block">Niveau Scolaire</span>
                <strong class="text-gray-900 text-xs">{{ currentGroupDetails?.level || 'Secondaire' }}</strong>
              </div>
              <div>
                <span class="text-[10px] font-bold text-gray-500 uppercase block">Matière Enseignée</span>
                <strong class="text-gray-900 text-xs">{{ selectedSubject }}</strong>
              </div>
            </div>

            <!-- Synthèse statistique si format rempli -->
            <div v-if="pdfFormat === 'filled'" class="flex flex-wrap items-center justify-between gap-3 bg-violet-50/60 p-3 rounded-xl border border-violet-100 text-xs">
              <span class="font-bold text-violet-950">Statistiques de Séance :</span>
              <div class="flex items-center gap-4 text-[11px]">
                <span>Inscrits : <strong>{{ studentsList.length }}</strong></span>
                <span class="text-emerald-700">Présents : <strong>{{ countStatus('present') }}</strong></span>
                <span class="text-red-700">Absents : <strong>{{ countStatus('absent') }}</strong></span>
                <span class="text-amber-700">Retards : <strong>{{ countStatus('late') }}</strong></span>
                <span class="text-violet-800 font-bold">Assiduité : <strong>{{ attendanceRate }}%</strong></span>
              </div>
            </div>

            <!-- Tableau d'émargement et d'appel des élèves -->
            <div class="border border-gray-300 rounded-xl overflow-hidden">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="bg-gray-100 text-gray-700 font-black text-[11px] border-b border-gray-300 uppercase">
                    <th class="py-2.5 px-3 w-10 text-center border-r border-gray-300">N°</th>
                    <th class="py-2.5 px-3 border-r border-gray-300">Nom & Prénom de l'Élève</th>
                    <th class="py-2.5 px-3 border-r border-gray-300 w-32">Niveau</th>
                    <th class="py-2.5 px-3 border-r border-gray-300 w-28">Téléphone</th>
                    <th class="py-2.5 px-3 border-r border-gray-300 w-36 text-center">
                      {{ pdfFormat === 'filled' ? 'Statut Pointé' : 'Pointage (P / A / R)' }}
                    </th>
                    <th class="py-2.5 px-3 border-r border-gray-300 w-32 text-center">Émargement Élève</th>
                    <th class="py-2.5 px-3">Observations</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr
                    v-for="(student, idx) in studentsList"
                    :key="student.id"
                    class="hover:bg-gray-50/50"
                  >
                    <td class="py-2 px-3 text-center font-bold text-gray-500 border-r border-gray-200 text-[11px]">
                      {{ idx + 1 }}
                    </td>
                    <td class="py-2 px-3 font-bold text-gray-900 border-r border-gray-200">
                      {{ student.name }}
                    </td>
                    <td class="py-2 px-3 text-gray-600 border-r border-gray-200 text-[11px]">
                      {{ student.level || '—' }}
                    </td>
                    <td class="py-2 px-3 text-gray-600 border-r border-gray-200 text-[11px]">
                      {{ student.phone || student.parent_phone || '—' }}
                    </td>

                    <!-- Colonne Statut : Soit Badge rempli, soit Cases à cocher vierges -->
                    <td class="py-2 px-3 border-r border-gray-200 text-center">
                      <div v-if="pdfFormat === 'filled'">
                        <span
                          :class="[
                            'inline-block px-2 py-0.5 rounded text-[10px] font-extrabold',
                            student.current_status === 'present' ? 'bg-emerald-100 text-emerald-800' : '',
                            student.current_status === 'absent' ? 'bg-red-100 text-red-800' : '',
                            student.current_status === 'late' ? 'bg-amber-100 text-amber-800' : '',
                            student.current_status === 'excused' ? 'bg-blue-100 text-blue-800' : ''
                          ]"
                        >
                          {{ formatStatusLabel(student.current_status || 'present') }}
                        </span>
                      </div>
                      <div v-else class="flex items-center justify-center gap-2 text-[10px] text-gray-600 font-mono">
                        <span>[ ] P</span>
                        <span>[ ] A</span>
                        <span>[ ] R</span>
                      </div>
                    </td>

                    <!-- Émargement / Signature de l'élève -->
                    <td class="py-2 px-3 border-r border-gray-200 text-center">
                      <span class="inline-block w-24 h-5 border-b border-dashed border-gray-300"></span>
                    </td>

                    <!-- Observations -->
                    <td class="py-2 px-3 text-[11px] text-gray-600 italic">
                      {{ (pdfFormat === 'filled' ? student.current_notes : '') || '—' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Bloc officiel de validation & Signatures -->
            <div class="pt-6 border-t border-gray-200 grid grid-cols-2 gap-8 text-xs">
              <div class="border border-gray-200 rounded-xl p-4 bg-gray-50/50 min-h-28 flex flex-col justify-between">
                <div>
                  <span class="font-bold text-gray-900 block">Visa & Signature de l'Enseignant(e)</span>
                  <p class="text-[10px] text-gray-500 mt-0.5">Certifie l'exactitude des présences et des émargements</p>
                </div>
                <div class="text-[10px] text-gray-400 italic pt-6">Signature :</div>
              </div>

              <div class="border border-gray-200 rounded-xl p-4 bg-gray-50/50 min-h-28 flex flex-col justify-between">
                <div>
                  <span class="font-bold text-gray-900 block">Cachet & Visa de la Direction du Centre</span>
                  <p class="text-[10px] text-gray-500 mt-0.5">Enregistré et archivé à l'administration</p>
                </div>
                <div class="text-[10px] text-gray-400 italic pt-6">Tampon / Signature :</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import {
  UserCheck, Users, BookOpen, Calendar, CheckSquare, History, CheckCheck,
  RotateCcw, Check, X, Clock, ShieldAlert, Phone, Save, CheckCircle2,
  AlertCircle, RotateCw, Printer, FileText, Download
} from 'lucide-vue-next';
import api from '../utils/api';
import { printIsolatedElement } from '../utils/printHelper';

const props = defineProps<{
  initialGroup?: string;
  initialSubject?: string;
  teacherName?: string;
}>();

const activeTab = ref<'take' | 'history'>('take');
const selectedGroup = ref(props.initialGroup || '');
const selectedSubject = ref(props.initialSubject || '');
const sessionDate = ref(new Date().toISOString().split('T')[0]);

const groupsList = ref<any[]>([]);
const studentsList = ref<any[]>([]);
const historyList = ref<any[]>([]);

const loadingStudents = ref(false);
const loadingHistory = ref(false);
const saving = ref(false);

const toastMessage = ref('');
const toastType = ref<'success' | 'error'>('success');

// État pour la modale d'aperçu et d'impression PDF
const showPdfModal = ref(false);
const pdfFormat = ref<'filled' | 'blank'>('filled');

const currentGroupDetails = computed(() => {
  return groupsList.value.find(g => g.group_name === selectedGroup.value) || null;
});

const currentTeacherName = computed(() => {
  if (props.teacherName) return props.teacherName;
  try {
    const u = localStorage.getItem('user');
    if (u) return JSON.parse(u).name || 'Professeur';
  } catch {}
  return 'Professeur';
});

function openPdfModal() {
  if (!selectedGroup.value || studentsList.value.length === 0) {
    showToast('Veuillez sélectionner un groupe avec des élèves pour exporter la fiche.', 'error');
    return;
  }
  showPdfModal.value = true;
}

function triggerPrintAttendance() {
  const docTitle = `Fiche_Appel_${selectedGroup.value}_${sessionDate.value}`;
  printIsolatedElement('attendance-sheet-printable', docTitle);
}

const attendanceRate = computed(() => {
  if (studentsList.value.length === 0) return 100;
  const presents = studentsList.value.filter(s => s.current_status === 'present').length;
  return Math.round((presents / studentsList.value.length) * 100);
});

function countStatus(status: string): number {
  return studentsList.value.filter(s => s.current_status === status).length;
}

function markAll(status: string) {
  studentsList.value.forEach(s => {
    s.current_status = status;
  });
}

function formatStatusLabel(status: string): string {
  const map: Record<string, string> = {
    present: 'Présent',
    absent: 'Absent',
    late: 'En retard',
    excused: 'Excusé',
  };
  return map[status] || status;
}

async function fetchGroups() {
  try {
    const res = await api.get('/api/teacher/groups');
    groupsList.value = res.data || [];

    // Si un groupe par défaut est passé ou premier groupe disponible
    if (!selectedGroup.value && groupsList.value.length > 0) {
      selectedGroup.value = groupsList.value[0].group_name;
      if (!selectedSubject.value && groupsList.value[0].subjects?.length > 0) {
        selectedSubject.value = groupsList.value[0].subjects[0];
      }
      fetchStudents();
    } else if (selectedGroup.value) {
      const match = groupsList.value.find(g => g.group_name === selectedGroup.value);
      if (match && !selectedSubject.value && match.subjects?.length > 0) {
        selectedSubject.value = match.subjects[0];
      }
      fetchStudents();
    }
  } catch (err) {
    console.error('Erreur chargement groupes enseignant:', err);
  }
}

function onGroupChange() {
  const match = groupsList.value.find(g => g.group_name === selectedGroup.value);
  if (match && match.subjects?.length > 0) {
    selectedSubject.value = match.subjects[0];
  }
  fetchStudents();
}

async function fetchStudents() {
  if (!selectedGroup.value) return;
  loadingStudents.value = true;
  try {
    const res = await api.get(`/api/teacher/groups/${encodeURIComponent(selectedGroup.value)}/students`, {
      params: {
        session_date: sessionDate.value,
        subject_name: selectedSubject.value,
      },
    });
    studentsList.value = res.data || [];
  } catch (err) {
    console.error('Erreur chargement élèves du groupe:', err);
  } finally {
    loadingStudents.value = false;
  }
}

async function submitAttendance() {
  if (!selectedGroup.value || !selectedSubject.value || studentsList.value.length === 0) {
    showToast('Veuillez sélectionner un groupe et une matière valide.', 'error');
    return;
  }

  saving.value = true;
  try {
    const payload = {
      session_date: sessionDate.value,
      subject_name: selectedSubject.value,
      group_name: selectedGroup.value,
      records: studentsList.value.map(s => ({
        student_id: s.id,
        status: s.current_status || 'present',
        notes: s.current_notes || null,
      })),
    };

    const res = await api.post('/api/teacher/attendance/batch', payload);
    showToast(`Appel enregistré avec succès (${res.data.count} élèves pointés).`, 'success');
  } catch (err: any) {
    console.error('Erreur enregistrement appel:', err);
    showToast(err.response?.data?.message || 'Erreur lors de l\'enregistrement de l\'appel.', 'error');
  } finally {
    saving.value = false;
  }
}

async function fetchHistory() {
  loadingHistory.value = true;
  try {
    const res = await api.get('/api/teacher/attendances');
    historyList.value = res.data || [];
  } catch (err) {
    console.error('Erreur historique:', err);
  } finally {
    loadingHistory.value = false;
  }
}

function showToast(msg: string, type: 'success' | 'error' = 'success') {
  toastMessage.value = msg;
  toastType.value = type;
  setTimeout(() => {
    toastMessage.value = '';
  }, 4000);
}

onMounted(() => {
  fetchGroups();
  fetchHistory();
});
</script>
