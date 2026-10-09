<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-2 sm:p-4 overflow-y-auto"
    @click.self="close"
  >
    <div
      class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl my-6 flex flex-col max-h-[92vh] border border-gray-200 overflow-hidden"
    >
      <!-- Modal Header (Hidden on Print) -->
      <div class="px-6 py-4 bg-linear-to-r from-indigo-900 via-indigo-800 to-slate-900 text-white flex items-center justify-between no-print shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center font-bold text-lg border border-white/20">
            📑
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-lg font-bold">Extraction des Extraits de Paiement</h2>
              <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                Directeur Uniquement
              </span>
            </div>
            <p class="text-xs text-indigo-200 mt-0.5">
              Générez et exportez des états financiers pour un ou plusieurs professeurs ou élèves (PDF & Excel)
            </p>
          </div>
        </div>

        <button
          @click="close"
          class="p-2 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition-colors"
          title="Fermer"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Controls & Filter Bar (Hidden on Print) -->
      <div class="p-5 bg-gray-50 border-b border-gray-200 space-y-4 no-print shrink-0">
        <!-- Target Toggle Tabs -->
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="inline-flex rounded-xl bg-gray-200/80 p-1 border border-gray-300/60 shadow-2xs">
            <button
              type="button"
              @click="switchExtractType('teachers')"
              :class="[
                'flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all',
                extractType === 'teachers'
                  ? 'bg-white text-indigo-950 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              <span>👨‍🏫 Extraits Enseignants</span>
              <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="extractType === 'teachers' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-300/70 text-gray-700'">
                {{ teachers.length }}
              </span>
            </button>
            <button
              type="button"
              @click="switchExtractType('students')"
              :class="[
                'flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all',
                extractType === 'students'
                  ? 'bg-white text-indigo-950 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              <span>👨‍🎓 Extraits Élèves</span>
              <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="extractType === 'students' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-300/70 text-gray-700'">
                {{ students.length }}
              </span>
            </button>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="triggerPrint"
              :disabled="loading || displayedItemsCount === 0"
              class="flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-colors shadow-xs disabled:opacity-50 cursor-pointer"
            >
              <Printer class="w-4 h-4" />
              <span>Imprimer / PDF (A4)</span>
            </button>
            <button
              type="button"
              @click="exportCsv"
              :disabled="loading || displayedItemsCount === 0"
              class="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-colors shadow-xs disabled:opacity-50 cursor-pointer"
            >
              <FileSpreadsheet class="w-4 h-4" />
              <span>Exporter Excel (CSV)</span>
            </button>
          </div>
        </div>

        <!-- Filter Row -->
        <div class="grid grid-cols-12 gap-3 items-center">
          <!-- Selection Mode (All vs Specific) -->
          <div class="md:col-span-4 flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-300 text-xs">
            <span class="text-gray-500 font-semibold shrink-0">Cible :</span>
            <label class="flex items-center gap-1 cursor-pointer font-medium text-gray-700">
              <input
                type="radio"
                value="all"
                v-model="selectionMode"
                class="text-indigo-600 focus:ring-indigo-500"
              />
              <span>Tous ({{ extractType === 'teachers' ? teachers.length : students.length }})</span>
            </label>
            <label class="flex items-center gap-1 cursor-pointer font-medium text-gray-700 ml-2">
              <input
                type="radio"
                value="custom"
                v-model="selectionMode"
                class="text-indigo-600 focus:ring-indigo-500"
              />
              <span>Sélection spécifique</span>
            </label>
          </div>

          <!-- Date Filters -->
          <div class="md:col-span-5 flex items-center gap-2 text-xs">
            <div class="flex items-center gap-1 bg-white px-2.5 py-1.5 rounded-lg border border-gray-300 flex-1">
              <span class="text-gray-400 font-medium">Du</span>
              <input
                type="date"
                v-model="dateFrom"
                class="w-full bg-transparent focus:outline-none text-xs text-gray-700"
              />
            </div>
            <div class="flex items-center gap-1 bg-white px-2.5 py-1.5 rounded-lg border border-gray-300 flex-1">
              <span class="text-gray-400 font-medium">Au</span>
              <input
                type="date"
                v-model="dateTo"
                class="w-full bg-transparent focus:outline-none text-xs text-gray-700"
              />
            </div>
            <button
              v-if="dateFrom || dateTo"
              type="button"
              @click="clearDates"
              class="text-gray-400 hover:text-gray-600 p-1 text-[11px]"
              title="Réinitialiser les dates"
            >
              ✕
            </button>
          </div>

          <!-- Quick Refresh / Status -->
          <div class="md:col-span-3 flex justify-end">
            <button
              type="button"
              @click="fetchExtractData"
              class="flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-gray-100 text-gray-700 rounded-lg text-xs font-semibold border border-gray-300 transition-colors shadow-2xs"
            >
              <RefreshCw class="w-3.5 h-3.5 text-gray-500" :class="{ 'animate-spin': loading }" />
              <span>Actualiser les données</span>
            </button>
          </div>
        </div>

        <!-- Specific Multi-Selector Drawer (When selectionMode === 'custom') -->
        <div
          v-if="selectionMode === 'custom'"
          class="bg-white p-3 rounded-xl border border-indigo-200/80 shadow-2xs space-y-2"
        >
          <div class="flex items-center justify-between text-xs pb-1 border-b border-gray-100">
            <span class="font-bold text-gray-800">
              Sélectionnez les {{ extractType === 'teachers' ? 'enseignants' : 'élèves' }} à inclure dans l'extrait :
            </span>
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="selectAll"
                class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold"
              >
                Tout sélectionner
              </button>
              <span class="text-gray-300">|</span>
              <button
                type="button"
                @click="deselectAll"
                class="text-[11px] text-gray-500 hover:text-gray-700 font-semibold"
              >
                Tout désélectionner
              </button>
            </div>
          </div>

          <!-- Search filter inside selector -->
          <input
            type="text"
            v-model="entitySearch"
            :placeholder="`Filtrer par nom ou matière...`"
            class="w-full px-3 py-1.5 text-xs bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500"
          />

          <!-- Checkboxes Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-1.5 max-h-36 overflow-y-auto pt-1">
            <label
              v-for="item in filteredEntitiesList"
              :key="item.id"
              class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-50 text-xs cursor-pointer border border-transparent hover:border-gray-200 transition-colors"
            >
              <input
                type="checkbox"
                :value="item.id"
                v-model="selectedIds"
                class="rounded text-indigo-600 focus:ring-indigo-500"
              />
              <span class="truncate font-medium text-gray-800">{{ item.name }}</span>
              <span v-if="item.sub" class="text-[10px] text-gray-400 truncate">({{ item.sub }})</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Printable & Preview Document Area -->
      <div class="flex-1 overflow-y-auto p-4 sm:p-8 bg-gray-100/60 print:bg-white print:p-0 print:overflow-visible">
        <!-- Loading State -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-gray-500 space-y-3">
          <div class="w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
          <p class="text-sm font-semibold">Génération des extraits financiers en cours...</p>
        </div>

        <!-- Empty State -->
        <div
          v-else-if="displayedItemsCount === 0"
          class="bg-white rounded-2xl p-12 text-center max-w-md mx-auto shadow-xs border border-gray-200 space-y-3 max-h-[90vh] overflow-y-auto"
        >
          <div class="text-4xl">📄</div>
          <h3 class="text-base font-bold text-gray-900">Aucun élément sélectionné</h3>
          <p class="text-xs text-gray-500">
            Veuillez choisir « Tous » ou cocher au moins un {{ extractType === 'teachers' ? 'professeur' : 'élève' }} ci-dessus.
          </p>
        </div>

        <!-- Actual Printable Extract Sheet -->
        <div
          v-else
          id="printable-extract-document"
          class="bg-white rounded-xl shadow-xs border border-gray-200 p-6 sm:p-10 max-w-4xl mx-auto space-y-8 print:shadow-none print:border-none print:p-4 print:max-w-none max-h-[90vh] overflow-y-auto"
        >
          <!-- Document Official Header -->
          <div class="border-b-2 border-indigo-900 pb-5 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div>
              <div class="flex items-center gap-3">
                <img src="/logo.png" alt="Logo" class="w-10 h-10 object-contain" />
                <h1 class="text-xl font-black text-indigo-950 uppercase tracking-tight">
                  Centre d'Études & de Formation
                </h1>
              </div>
              <p class="text-xs text-gray-500 mt-1">
                Plateforme de Gestion Pédagogique et Financière • Direction Générale
              </p>
              <p class="text-[11px] text-gray-400">
                Tunisie • Contacts & Administration
              </p>
            </div>

            <div class="text-right sm:text-right border-t sm:border-t-0 pt-2 sm:pt-0 border-gray-100">
              <span class="inline-block px-3 py-1 rounded-md text-xs font-black uppercase tracking-wider bg-indigo-50 text-indigo-950 border border-indigo-200">
                {{ extractType === 'teachers' ? 'Extrait Rémunérations Enseignants' : 'Extrait Compte Élèves' }}
              </span>
              <p class="text-[11px] text-gray-500 mt-1">
                Date d'édition : <strong>{{ formattedNow }}</strong>
              </p>
              <p v-if="dateFrom || dateTo" class="text-[11px] text-indigo-700 font-semibold">
                Période : {{ dateFrom || 'Début' }} → {{ dateTo || 'Ce jour' }}
              </p>
              <p class="text-[10px] text-gray-400">
                Émis par : {{ currentUserName || 'Direction' }} (Directeur)
              </p>
            </div>
          </div>

          <!-- ================= TEACHERS EXTRACT VIEW ================= -->
          <template v-if="extractType === 'teachers'">
            <!-- Grand Totals Banner -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-linear-to-r from-emerald-50 via-teal-50 to-indigo-50 p-4 rounded-xl border border-emerald-200 text-center">
              <div>
                <p class="text-[10px] font-bold uppercase text-gray-500">C.A. Total Encaissé</p>
                <p class="text-xl font-black text-gray-900 mt-0.5">{{ formatMoney(teachersDataTotals.grand_total_collected) }} TND</p>
                <p class="text-[10px] text-gray-500">{{ teachersDataTotals.teachers_count }} enseignant(s)</p>
              </div>
              <div class="bg-emerald-600 text-white p-2.5 rounded-lg shadow-2xs">
                <p class="text-[10px] font-bold uppercase text-emerald-100">Total Parts Professeurs</p>
                <p class="text-xl font-black mt-0.5">{{ formatMoney(teachersDataTotals.grand_total_teacher_share) }} TND</p>
                <p class="text-[10px] text-emerald-100">À reverser aux enseignants</p>
              </div>
              <div>
                <p class="text-[10px] font-bold uppercase text-indigo-700">Marge Totale Centre</p>
                <p class="text-xl font-black text-indigo-950 mt-0.5">+{{ formatMoney(teachersDataTotals.grand_total_center_margin) }} TND</p>
                <p class="text-[10px] text-indigo-600">Bénéfice net conservé</p>
              </div>
            </div>

            <!-- List of Teachers with Individual Statements -->
            <div class="space-y-6">
              <div
                v-for="item in teachersList"
                :key="item.teacher.id"
                class="rounded-xl border border-gray-200 overflow-hidden break-inside-avoid"
              >
                <!-- Teacher Statement Card Header -->
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div>
                    <div class="flex items-center gap-2">
                      <h3 class="text-sm font-bold text-gray-900">{{ item.teacher.name }}</h3>
                      <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 text-[10px] font-semibold rounded">
                        {{ item.teacher.subject }}
                      </span>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-0.5">
                      Tél: {{ item.teacher.phone || 'Non renseigné' }} • Email: {{ item.teacher.email || 'Non renseigné' }}
                    </p>
                  </div>

                  <div class="flex flex-wrap items-center justify-end gap-2 text-right">
                    <span
                      :class="[
                        'inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-bold border',
                        item.teacher.payout_status === 'paid'
                          ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
                          : 'bg-amber-100 text-amber-900 border-amber-300'
                      ]"
                    >
                      <span>{{ item.teacher.payout_status === 'paid' ? '✓ A pris son argent' : '⏳ Honoraires en attente' }}</span>
                      <span v-if="item.teacher.payout_status === 'paid' && item.teacher.payout_date" class="text-[10px] font-normal opacity-85">
                        ({{ item.teacher.payout_date }})
                      </span>
                    </span>

                    <span class="text-xs font-bold px-2.5 py-1 rounded-md bg-white border border-gray-300 text-gray-800">
                      Formule :
                      <strong class="text-indigo-700">
                        {{ item.teacher.remuneration_type === 'percentage' || (item.teacher.rate_percentage && item.teacher.rate_percentage > 0)
                          ? `${item.teacher.rate_percentage || 70}% du groupe`
                          : `${item.teacher.salary_per_lesson || 35} TND / séance` }}
                      </strong>
                    </span>
                  </div>
                </div>

                <!-- Payments Table for This Teacher -->
                <div class="overflow-x-auto">
                  <table class="w-full text-xs text-left whitespace-nowrap">
                    <thead class="bg-gray-100/70 text-gray-600 uppercase font-semibold text-[10px] border-b border-gray-200">
                      <tr>
                        <th class="py-2 px-3">Date</th>
                        <th class="py-2 px-3">Élève Payeur</th>
                        <th class="py-2 px-2 text-center">Séances</th>
                        <th class="py-2 px-3 text-right">Montant Reçu</th>
                        <th class="py-2 px-3 text-right text-emerald-800 bg-emerald-50/50">Part Professeur</th>
                        <th class="py-2 px-3 text-right text-indigo-800 bg-indigo-50/50">Marge Centre</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                      <tr v-for="pay in item.payments" :key="pay.id" class="hover:bg-gray-50/60">
                        <td class="py-2 px-3 text-gray-600 font-medium">{{ pay.date || '-' }}</td>
                        <td class="py-2 px-3 font-semibold text-gray-900">{{ pay.student_name || 'Élève' }}</td>
                        <td class="py-2 px-2 text-center text-gray-600">{{ pay.sessions_paid }}</td>
                        <td class="py-2 px-3 text-right font-bold text-gray-900">{{ formatMoney(pay.amount) }} TND</td>
                        <td class="py-2 px-3 text-right font-black text-emerald-700 bg-emerald-50/30">{{ formatMoney(pay.teacher_share) }} TND</td>
                        <td class="py-2 px-3 text-right font-bold text-indigo-700 bg-indigo-50/30">+{{ formatMoney(pay.center_margin) }} TND</td>
                      </tr>
                      <tr v-if="item.payments.length === 0">
                        <td colspan="6" class="py-4 text-center text-gray-400 italic">
                          Aucun paiement enregistré pour cette période.
                        </td>
                      </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200 font-bold text-gray-900">
                      <tr>
                        <td colspan="2" class="py-2.5 px-3 uppercase text-[10px] text-gray-500">
                          Total {{ item.teacher.name }} ({{ item.summary.payments_count }} paiement(s))
                        </td>
                        <td class="py-2.5 px-2 text-center">{{ item.summary.total_sessions_paid }}</td>
                        <td class="py-2.5 px-3 text-right text-gray-900">{{ formatMoney(item.summary.total_collected) }} TND</td>
                        <td class="py-2.5 px-3 text-right text-emerald-800 font-black text-sm bg-emerald-50/60">
                          {{ formatMoney(item.summary.total_teacher_share) }} TND
                        </td>
                        <td class="py-2.5 px-3 text-right text-indigo-800 font-black text-sm bg-indigo-50/60">
                          +{{ formatMoney(item.summary.total_center_margin) }} TND
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </template>

          <!-- ================= STUDENTS EXTRACT VIEW ================= -->
          <template v-else>
            <!-- Grand Totals Banner -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-linear-to-r from-blue-50 via-indigo-50 to-purple-50 p-4 rounded-xl border border-indigo-200 text-center">
              <div>
                <p class="text-[10px] font-bold uppercase text-gray-500">Élèves Extraits</p>
                <p class="text-xl font-black text-gray-900 mt-0.5">{{ studentsDataTotals.students_count }}</p>
                <p class="text-[10px] text-gray-500">Fiches de comptes</p>
              </div>
              <div class="bg-indigo-600 text-white p-2.5 rounded-lg shadow-2xs">
                <p class="text-[10px] font-bold uppercase text-indigo-100">Total Encaissé</p>
                <p class="text-xl font-black mt-0.5">{{ formatMoney(studentsDataTotals.grand_total_paid) }} TND</p>
                <p class="text-[10px] text-indigo-100">Paiements validés</p>
              </div>
              <div>
                <p class="text-[10px] font-bold uppercase text-red-600">Total Soldes Impayés</p>
                <p class="text-xl font-black text-red-700 mt-0.5">{{ formatMoney(studentsDataTotals.grand_total_due) }} TND</p>
                <p class="text-[10px] text-red-500">Reste à régler</p>
              </div>
            </div>

            <!-- List of Students Statements -->
            <div class="space-y-6">
              <div
                v-for="item in studentsList"
                :key="item.student.id"
                class="rounded-xl border border-gray-200 overflow-hidden break-inside-avoid"
              >
                <!-- Student Statement Card Header -->
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div>
                    <div class="flex items-center gap-2">
                      <h3 class="text-sm font-bold text-gray-900">{{ item.student.name }}</h3>
                      <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-[10px] font-semibold rounded">
                        {{ item.student.level || 'Non spécifié' }}
                      </span>
                      <span v-if="item.student.group_name" class="px-2 py-0.5 bg-gray-200 text-gray-700 text-[10px] font-semibold rounded">
                        {{ item.student.group_name }}
                      </span>
                      <span v-if="item.student.is_remise" class="px-1.5 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold rounded">
                        Remise {{ item.student.remise }}{{ item.student.discount_type === 'percentage' ? '%' : ' TND' }}
                      </span>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-0.5">
                      Parent : {{ item.student.parent_name || 'Non renseigné' }} (Tél: {{ item.student.parent_phone || item.student.phone || '-' }})
                    </p>
                  </div>

                  <div class="flex items-center gap-2">
                    <span class="text-xs px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-800 font-bold border border-emerald-200">
                      Total Payé : {{ formatMoney(item.summary.total_paid) }} TND
                    </span>
                    <span
                      :class="[
                        'text-xs px-2.5 py-1 rounded-md font-bold border',
                        item.summary.total_due > 0
                          ? 'bg-red-50 text-red-700 border-red-200'
                          : 'bg-gray-100 text-gray-600 border-gray-200'
                      ]"
                    >
                      Reste Dû : {{ formatMoney(item.summary.total_due) }} TND
                    </span>
                  </div>
                </div>

                <!-- Payments Table for This Student -->
                <div class="p-3 bg-white space-y-3">
                  <h4 class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">
                    Historique des Règlements Effectués
                  </h4>
                  <div class="overflow-x-auto rounded-lg border border-gray-100">
                    <table class="w-full text-xs text-left whitespace-nowrap">
                      <thead class="bg-gray-50 text-gray-600 uppercase font-semibold text-[10px] border-b border-gray-200">
                        <tr>
                          <th class="py-2 px-3">Date</th>
                          <th class="py-2 px-3">Enseignant / Prestation</th>
                          <th class="py-2 px-2 text-center">Volume</th>
                          <th class="py-2 px-3">Encaissé par</th>
                          <th class="py-2 px-3 text-right">Montant Réglé</th>
                          <th class="py-2 px-3 text-center">Statut</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100">
                        <tr v-for="pay in item.payments" :key="pay.id">
                          <td class="py-2 px-3 text-gray-600 font-medium">{{ pay.date || '-' }}</td>
                          <td class="py-2 px-3 font-semibold text-gray-900">
                            {{ pay.teacherName || (pay.type === 'daycare' ? 'Garderie Scolaire' : 'Cours Général') }}
                          </td>
                          <td class="py-2 px-2 text-center text-gray-600">
                            {{ pay.type === 'daycare' ? `${pay.monthsPaid} mois` : `${pay.sessionsPaid} séance(s)` }}
                          </td>
                          <td class="py-2 px-3">
                            <span class="text-[11px] font-bold text-gray-800">
                              {{ pay.createdByName || 'Directeur' }}
                            </span>
                            <span class="text-[10px] text-gray-500 block">
                              ({{ pay.createdByRole === 'secretary' ? 'Personnel' : 'Directeur' }})
                            </span>
                          </td>
                          <td class="py-2 px-3 text-right font-black text-gray-900">
                            {{ formatMoney(pay.amount) }} TND
                          </td>
                          <td class="py-2 px-3 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800">
                              {{ pay.status === 'paid' ? 'Payé' : pay.status }}
                            </span>
                          </td>
                        </tr>
                        <tr v-if="item.payments.length === 0">
                          <td colspan="5" class="py-3 text-center text-gray-400 italic">
                            Aucun paiement enregistré pour cette période.
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Outstanding Balances / Dues section if any -->
                  <div v-if="item.dues.length > 0" class="pt-2">
                    <h4 class="text-[11px] font-bold text-red-800 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                      <span>⚠️ Soldes en Attente de Règlement</span>
                    </h4>
                    <div class="overflow-x-auto rounded-lg border border-red-200 bg-red-50/30">
                      <table class="w-full text-xs text-left whitespace-nowrap">
                        <thead class="bg-red-100/60 text-red-900 uppercase font-semibold text-[10px]">
                          <tr>
                            <th class="py-1.5 px-3">Enseignant & Matière</th>
                            <th class="py-1.5 px-2 text-center">Séances Impayées</th>
                            <th class="py-1.5 px-2 text-right">Tarif Unitaire</th>
                            <th class="py-1.5 px-3 text-right font-bold text-red-900">Total Solde Dû</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-red-100">
                          <tr v-for="(due, didx) in item.dues" :key="didx">
                            <td class="py-1.5 px-3 font-semibold text-gray-800">
                              {{ due.teacher_name }} ({{ due.subject }})
                            </td>
                            <td class="py-1.5 px-2 text-center font-bold text-red-700">
                              {{ due.unpaid_sessions }}
                            </td>
                            <td class="py-1.5 px-2 text-right text-gray-600">
                              {{ formatMoney(due.unit_price) }} TND
                            </td>
                            <td class="py-1.5 px-3 text-right font-black text-red-700">
                              {{ formatMoney(due.amount_due) }} TND
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <!-- Official Signature Block (Shown on Print and Screen) -->
          <div class="border-t-2 border-gray-300 pt-6 mt-8 flex flex-col sm:flex-row justify-between items-end gap-6 break-inside-avoid">
            <div class="text-xs text-gray-500 space-y-1">
              <p>Document généré électroniquement par le système de gestion du centre.</p>
              <p>Fait foi pour la comptabilité interne et le versement des honoraires.</p>
            </div>

            <div class="text-center sm:text-right min-w-[200px] border border-gray-200 rounded-xl p-4 bg-gray-50/50">
              <p class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                Visa et Signature de la Direction
              </p>
              <div class="h-14 flex items-center justify-center text-gray-300 italic text-xs">
                (Cachet et signature)
              </div>
              <p class="text-[10px] text-gray-500 border-t border-gray-200 pt-1">
                {{ currentUserName || 'Le Directeur' }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { X, Printer, FileSpreadsheet, RefreshCw } from 'lucide-vue-next';
import api from '../utils/api';
import { printIsolatedElement } from '../utils/printHelper';

const props = defineProps<{
  isOpen: boolean;
  initialType?: 'teachers' | 'students';
  initialTeacherId?: number | null;
  initialStudentId?: number | null;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
}>();

const extractType = ref<'teachers' | 'students'>('teachers');
const selectionMode = ref<'all' | 'custom'>('all');
const selectedIds = ref<number[]>([]);
const entitySearch = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const loading = ref(false);

const teachers = ref<any[]>([]);
const students = ref<any[]>([]);

const teachersList = ref<any[]>([]);
const teachersDataTotals = ref({
  grand_total_collected: 0,
  grand_total_teacher_share: 0,
  grand_total_center_margin: 0,
  teachers_count: 0
});

const studentsList = ref<any[]>([]);
const studentsDataTotals = ref({
  grand_total_paid: 0,
  grand_total_due: 0,
  students_count: 0
});

const currentUser = ref<{ role?: string; name?: string } | null>(null);
try {
  const saved = localStorage.getItem('user');
  if (saved) currentUser.value = JSON.parse(saved);
} catch {}

const currentUserName = computed(() => currentUser.value?.name || 'Directeur');

const formattedNow = computed(() => {
  const d = new Date();
  return d.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
});

const filteredEntitiesList = computed(() => {
  const term = entitySearch.value.trim().toLowerCase();
  if (extractType.value === 'teachers') {
    return teachers.value
      .filter(t => !term || t.name.toLowerCase().includes(term) || (t.subject || '').toLowerCase().includes(term))
      .map(t => ({ id: t.id, name: t.name, sub: t.subject }));
  } else {
    return students.value
      .filter(s => !term || s.name.toLowerCase().includes(term) || (s.level || '').toLowerCase().includes(term))
      .map(s => ({ id: s.id, name: s.name, sub: s.level }));
  }
});

const displayedItemsCount = computed(() => {
  if (selectionMode.value === 'all') {
    return extractType.value === 'teachers' ? teachers.value.length : students.value.length;
  }
  return selectedIds.value.length;
});

function formatMoney(amount: number | string | undefined): string {
  const num = Number(amount) || 0;
  return num.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function clearDates() {
  dateFrom.value = '';
  dateTo.value = '';
  fetchExtractData();
}

function selectAll() {
  const list = extractType.value === 'teachers' ? teachers.value : students.value;
  selectedIds.value = list.map(item => item.id);
}

function deselectAll() {
  selectedIds.value = [];
}

function switchExtractType(type: 'teachers' | 'students') {
  extractType.value = type;
  selectedIds.value = [];
  selectionMode.value = 'all';
  entitySearch.value = '';
  fetchExtractData();
}

async function fetchExtractData() {
  loading.value = true;
  try {
    const params: Record<string, any> = {};
    if (dateFrom.value) params.from = dateFrom.value;
    if (dateTo.value) params.to = dateTo.value;

    if (selectionMode.value === 'custom' && selectedIds.value.length > 0) {
      if (extractType.value === 'teachers') {
        params.teacher_ids = selectedIds.value.join(',');
      } else {
        params.student_ids = selectedIds.value.join(',');
      }
    }

    if (extractType.value === 'teachers') {
      const res = await api.get('/api/payments/extract-teachers', { params });
      teachersList.value = res.data.data || [];
      teachersDataTotals.value = res.data.totals || {
        grand_total_collected: 0,
        grand_total_teacher_share: 0,
        grand_total_center_margin: 0,
        teachers_count: teachersList.value.length
      };
    } else {
      const res = await api.get('/api/payments/extract-students', { params });
      studentsList.value = res.data.data || [];
      studentsDataTotals.value = res.data.totals || {
        grand_total_paid: 0,
        grand_total_due: 0,
        students_count: studentsList.value.length
      };
    }
  } catch (e) {
    console.error('Erreur chargement des extraits:', e);
  } finally {
    loading.value = false;
  }
}

async function loadInitialOptions() {
  try {
    const [tRes, sRes] = await Promise.all([
      api.get('/api/teachers').catch(() => ({ data: [] })),
      api.get('/api/students').catch(() => ({ data: [] }))
    ]);
    teachers.value = Array.isArray(tRes.data) ? tRes.data : [];
    students.value = Array.isArray(sRes.data) ? sRes.data : [];
  } catch (e) {
    console.error('Erreur chargement entites pour extrait:', e);
  }
}

function triggerPrint() {
  const docTitle = `Extrait - ${extractType.value === 'teachers' ? 'Enseignants' : 'Élèves'}`;
  printIsolatedElement('printable-extract-document', docTitle);
}

function exportCsv() {
  const baseUrl = (api.defaults.baseURL || '').replace(/\/$/, '');
  const token = localStorage.getItem('token') || '';
  const queryParts: string[] = ['format=csv'];

  if (dateFrom.value) queryParts.push(`from=${encodeURIComponent(dateFrom.value)}`);
  if (dateTo.value) queryParts.push(`to=${encodeURIComponent(dateTo.value)}`);

  if (selectionMode.value === 'custom' && selectedIds.value.length > 0) {
    if (extractType.value === 'teachers') {
      queryParts.push(`teacher_ids=${encodeURIComponent(selectedIds.value.join(','))}`);
    } else {
      queryParts.push(`student_ids=${encodeURIComponent(selectedIds.value.join(','))}`);
    }
  }

  const endpoint = extractType.value === 'teachers'
    ? '/api/payments/extract-teachers'
    : '/api/payments/extract-students';

  // Client-side direct stream download using axios blob to maintain sanctum authentication
  api.get(`${endpoint}?${queryParts.join('&')}`, { responseType: 'blob' })
    .then((response) => {
      const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      const filename = extractType.value === 'teachers'
        ? `Extrait_Paiements_Enseignants_${new Date().toISOString().slice(0, 10)}.csv`
        : `Extrait_Paiements_Eleves_${new Date().toISOString().slice(0, 10)}.csv`;
      link.setAttribute('download', filename);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    })
    .catch((err) => {
      console.error('Erreur export CSV:', err);
      alert("Erreur lors de l'exportation du fichier CSV.");
    });
}

function close() {
  emit('close');
}

watch(
  () => props.isOpen,
  async (newVal) => {
    if (newVal) {
      await loadInitialOptions();
      if (props.initialType) {
        extractType.value = props.initialType;
      }
      if (props.initialTeacherId) {
        extractType.value = 'teachers';
        selectionMode.value = 'custom';
        selectedIds.value = [props.initialTeacherId];
      } else if (props.initialStudentId) {
        extractType.value = 'students';
        selectionMode.value = 'custom';
        selectedIds.value = [props.initialStudentId];
      } else {
        selectionMode.value = 'all';
        selectedIds.value = [];
      }
      fetchExtractData();
    }
  },
  { immediate: true }
);

watch(selectionMode, () => {
  fetchExtractData();
});

watch(selectedIds, () => {
  if (selectionMode.value === 'custom') {
    fetchExtractData();
  }
});

watch([dateFrom, dateTo], () => {
  fetchExtractData();
});

onMounted(() => {
  if (props.isOpen) {
    loadInitialOptions();
    fetchExtractData();
  }
});
</script>

<style>
@media print {
  /* Hide all non-printable elements */
  body * {
    visibility: hidden;
  }
  .no-print, .no-print * {
    display: none !important;
  }
  #printable-extract-document, #printable-extract-document * {
    visibility: visible;
  }
  #printable-extract-document {
    position: static !important;
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
    box-shadow: none !important;
  }
}
</style>
