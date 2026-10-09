<template>
  <div class="space-y-6">
    <!-- Non-Director Warning if accessed by mistake -->
    <div v-if="!isDirector" class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center">
      <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-3">
        <Lock class="w-6 h-6" />
      </div>
      <h3 class="text-lg font-bold text-amber-900">Espace Réservé à la Direction</h3>
      <p class="text-sm text-amber-700 mt-1 max-w-md mx-auto">
        Le module de comptabilité générale, bilans de marge et fiches de paie est réservé au Directeur du centre.
      </p>
    </div>

    <template v-else>
      <!-- En-tête Principal & Sélecteur de Période -->
      <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-2xl font-bold text-gray-900">Comptabilité & Pilotage Financier</h2>
              <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                Direction
              </span>
              <span v-if="summaryData?.monthlyClosing?.isClosed" class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                <CheckCircle2 class="w-3 h-3" /> Mois Clôturé
              </span>
            </div>
            <p class="text-sm text-gray-500 mt-0.5">
              Revenus réels, dépenses réelles, rémunérations des enseignants et clôtures certifiées
            </p>
          </div>

          <!-- Contrôles Période & Actions Rapides -->
          <div class="flex flex-wrap items-center gap-2">
            <!-- Sélecteur Mode Période -->
            <div class="flex items-center bg-gray-100 p-1 rounded-xl text-xs font-medium">
              <button
                @click="changePeriod('12months')"
                :class="['px-3 py-1.5 rounded-lg transition-all', selectedPeriod === '12months' ? 'bg-white text-indigo-700 shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900']"
              >
                12 Mois
              </button>
              <button
                @click="changePeriod('school_year')"
                :class="['px-3 py-1.5 rounded-lg transition-all', selectedPeriod === 'school_year' ? 'bg-white text-indigo-700 shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900']"
              >
                Année Scolaire
              </button>
              <button
                @click="changePeriod('current_month')"
                :class="['px-3 py-1.5 rounded-lg transition-all', selectedPeriod === 'current_month' ? 'bg-white text-indigo-700 shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900']"
              >
                Mois Précis
              </button>
            </div>

            <!-- Sélecteur Année scolaire -->
            <select
              v-if="selectedPeriod === 'school_year'"
              v-model="selectedSchoolYear"
              @change="fetchSummary"
              class="text-xs bg-white border border-gray-200 rounded-xl px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option value="2025-2026">Sept 2025 – Juin 2026</option>
              <option value="2026-2027">Sept 2026 – Juin 2027</option>
            </select>

            <!-- Sélecteur Mois -->
            <input
              v-if="selectedPeriod === 'current_month'"
              v-model="selectedMonth"
              type="month"
              @change="fetchSummary"
              class="text-xs bg-white border border-gray-200 rounded-xl px-3 py-1.5 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />

            <!-- Boutons d'Action -->
            <button
              @click="openExpenseModal()"
              class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-medium transition-colors shadow-xs"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Dépense</span>
            </button>

            <button
              @click="exportCsv()"
              class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 rounded-xl text-xs font-medium transition-colors shadow-xs"
              title="Exporter pour un comptable"
            >
              <Download class="w-3.5 h-3.5" />
              <span>Export CSV</span>
            </button>

            <button
              @click="triggerPrint()"
              class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 rounded-xl text-xs font-medium transition-colors shadow-xs"
              title="Imprimer ou enregistrer en PDF"
            >
              <Printer class="w-3.5 h-3.5" />
              <span>PDF / Print</span>
            </button>
          </div>
        </div>

        <!-- Navigation par Onglets Métiers -->
        <div class="flex items-center gap-1 mt-5 pt-3 border-t border-gray-100 overflow-x-auto text-xs">
          <button
            v-for="tab in accountingTabs"
            :key="tab.id"
            @click="activeSubTab = tab.id"
            :class="[
              'inline-flex items-center gap-2 px-3.5 py-2 rounded-xl font-medium whitespace-nowrap transition-all',
              activeSubTab === tab.id
                ? 'bg-indigo-600 text-white shadow-xs'
                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
            ]"
          >
            <component :is="tab.icon" class="w-4 h-4" />
            <span>{{ tab.label }}</span>
            <span
              v-if="tab.badge"
              :class="[
                'text-[10px] px-1.5 py-0.2 rounded-full font-bold',
                activeSubTab === tab.id ? 'bg-indigo-700 text-white' : 'bg-gray-200 text-gray-700'
              ]"
            >
              {{ tab.badge }}
            </span>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="bg-white border border-gray-200 rounded-2xl p-12 text-center">
        <div class="w-8 h-8 border-3 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
        <p class="text-sm text-gray-600 font-medium">Calcul des soldes et synthèse comptable en cours...</p>
      </div>

      <div v-else class="space-y-6">
        <!-- ============================================================== -->
        <!-- ONGLET 1 : VUE D'ENSEMBLE & INDICATEURS CLÉS (KPIS RÉELS)       -->
        <!-- ============================================================== -->
        <div v-if="activeSubTab === 'overview'" class="space-y-6">
          <!-- 6 Cartes KPIs Réels -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <!-- Bénéfice Net Réel -->
            <div class="bg-gradient-to-br from-emerald-500 to-teal-700 text-white rounded-2xl p-4 shadow-sm relative overflow-hidden">
              <div class="flex items-center justify-between text-emerald-100 mb-1">
                <span class="text-xs font-semibold uppercase tracking-wider">Bénéfice Net</span>
                <TrendingUp class="w-4 h-4" />
              </div>
              <p class="text-2xl font-black">{{ (kpis.netProfit || 0).toLocaleString('fr-FR') }} <span class="text-sm font-normal">DT</span></p>
              <p class="text-[11px] text-emerald-100 mt-1">
                Encaissements − Dépenses − Parts Profs
              </p>
            </div>

            <!-- Recettes Encaissées -->
            <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
              <div class="flex items-center justify-between text-gray-500 mb-1">
                <span class="text-xs font-semibold uppercase tracking-wider">Recettes Totales</span>
                <div class="w-7 h-7 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
                  <DollarSign class="w-4 h-4" />
                </div>
              </div>
              <p class="text-2xl font-black text-gray-900">{{ (kpis.totalRevenue || 0).toLocaleString('fr-FR') }} <span class="text-sm font-normal text-gray-500">DT</span></p>
              <p class="text-[11px] text-gray-500 mt-1">
                Élèves & Garderie encaissés
              </p>
            </div>

            <!-- Dépenses Réelles -->
            <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
              <div class="flex items-center justify-between text-gray-500 mb-1">
                <span class="text-xs font-semibold uppercase tracking-wider">Dépenses Réelles</span>
                <div class="w-7 h-7 bg-red-50 text-red-600 rounded-lg flex items-center justify-center">
                  <TrendingDown class="w-4 h-4" />
                </div>
              </div>
              <p class="text-2xl font-black text-red-600">{{ (kpis.totalExpenses || 0).toLocaleString('fr-FR') }} <span class="text-sm font-normal text-gray-500">DT</span></p>
              <p class="text-[11px] text-gray-500 mt-1">
                Loyer, STEG, Fournitures...
              </p>
            </div>

            <!-- Parts Enseignants Dues -->
            <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
              <div class="flex items-center justify-between text-gray-500 mb-1">
                <span class="text-xs font-semibold uppercase tracking-wider">Parts Enseignants</span>
                <div class="w-7 h-7 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center">
                  <Users class="w-4 h-4" />
                </div>
              </div>
              <p class="text-2xl font-black text-purple-700">{{ (kpis.totalTeacherPayouts || 0).toLocaleString('fr-FR') }} <span class="text-sm font-normal text-gray-500">DT</span></p>
              <p class="text-[11px] text-gray-500 mt-1">
                Honoraires calculés sur séances
              </p>
            </div>

            <!-- Taux de Recouvrement -->
            <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
              <div class="flex items-center justify-between text-gray-500 mb-1">
                <span class="text-xs font-semibold uppercase tracking-wider">Recouvrement</span>
                <div class="w-7 h-7 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                  <CheckCircle2 class="w-4 h-4" />
                </div>
              </div>
              <p class="text-2xl font-black text-blue-700">{{ kpis.recoveryRate || 100 }}%</p>
              <p class="text-[11px] text-amber-600 font-medium mt-1">
                Reste dû : {{ (kpis.totalUnpaid || 0).toLocaleString('fr-FR') }} DT
              </p>
            </div>

            <!-- Recettes Prévues (Mois Prochain) -->
            <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
              <div class="flex items-center justify-between text-gray-500 mb-1">
                <span class="text-xs font-semibold uppercase tracking-wider">Prévisionnel</span>
                <div class="w-7 h-7 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center">
                  <Calendar class="w-4 h-4" />
                </div>
              </div>
              <p class="text-2xl font-black text-gray-900">{{ (kpis.projectedNextMonthRevenue || 0).toLocaleString('fr-FR') }} <span class="text-sm font-normal text-gray-500">DT</span></p>
              <p class="text-[11px] text-gray-500 mt-1">
                ARPU: {{ kpis.averageRevenuePerStudent || 0 }} DT / élève
              </p>
            </div>
          </div>

          <!-- Graphique Évolution Mensuelle Réelle (12 mois ou Année Scolaire) -->
          <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
              <div>
                <h3 class="text-lg font-bold text-gray-900">Évolution Mensuelle Réelle</h3>
                <p class="text-xs text-gray-500 mt-0.5">Recettes encaissées vs Dépenses vs Marge nette mensuelle</p>
              </div>

              <!-- Légende Graphique -->
              <div class="flex items-center gap-4 text-xs font-medium">
                <div class="flex items-center gap-1.5">
                  <span class="w-3 h-3 rounded-full bg-indigo-600"></span>
                  <span class="text-gray-700">Recettes Encaissées</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="w-3 h-3 rounded-full bg-red-500"></span>
                  <span class="text-gray-700">Dépenses Totales</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                  <span class="text-gray-700">Bénéfice Net</span>
                </div>
              </div>
            </div>

            <!-- SVG Multi-Series Bar/Line Chart -->
            <div class="relative w-full h-[280px]">
              <svg class="w-full h-full" viewBox="0 0 600 280" preserveAspectRatio="none">
                <!-- Grid Lines -->
                <line x1="40" y1="40" x2="580" y2="40" stroke="#F3F4F6" stroke-dasharray="3 3" />
                <line x1="40" y1="90" x2="580" y2="90" stroke="#F3F4F6" stroke-dasharray="3 3" />
                <line x1="40" y1="140" x2="580" y2="140" stroke="#F3F4F6" stroke-dasharray="3 3" />
                <line x1="40" y1="190" x2="580" y2="190" stroke="#F3F4F6" stroke-dasharray="3 3" />
                <line x1="40" y1="240" x2="580" y2="240" stroke="#E5E7EB" />

                <!-- Bars (Revenue & Expenses) -->
                <g v-for="(m, idx) in chartPoints" :key="idx" class="cursor-pointer group">
                  <!-- Bar Revenue -->
                  <rect
                    :x="m.x - 12"
                    :y="m.revenueY"
                    width="10"
                    :height="Math.max(2, 240 - m.revenueY)"
                    fill="#4F46E5"
                    rx="2"
                    class="hover:opacity-80 transition-opacity"
                  >
                    <title>{{ m.label }} - Recettes: {{ m.revenue }} DT (Élèves: {{ m.students }} DT, Garde: {{ m.daycare }} DT)</title>
                  </rect>

                  <!-- Bar Expenses -->
                  <rect
                    :x="m.x + 2"
                    :y="m.expenseY"
                    width="10"
                    :height="Math.max(2, 240 - m.expenseY)"
                    fill="#EF4444"
                    rx="2"
                    class="hover:opacity-80 transition-opacity"
                  >
                    <title>{{ m.label }} - Dépenses: {{ m.expenses }} DT (Parts profs: {{ m.teacherPayouts }} DT)</title>
                  </rect>

                  <!-- X Axis Label -->
                  <text :x="m.x" y="260" text-anchor="middle" fill="#6B7280" font-size="10" font-weight="500">
                    {{ m.label }}
                  </text>
                </g>

                <!-- Line Net Profit Path -->
                <path
                  :d="profitLinePath"
                  fill="none"
                  stroke="#10B981"
                  stroke-width="2.5"
                  stroke-linecap="round"
                />

                <!-- Net Profit Dots -->
                <circle
                  v-for="(m, idx) in chartPoints"
                  :key="'dot-' + idx"
                  :cx="m.x"
                  :cy="m.profitY"
                  r="4"
                  fill="#10B981"
                  stroke="#FFFFFF"
                  stroke-width="2"
                >
                  <title>{{ m.label }} - Bénéfice Net: {{ m.netProfit }} DT</title>
                </circle>
              </svg>
            </div>
          </div>

          <!-- 3 Répartitions Analytiques : Matière, Niveau, Enseignant -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Répartition par Matière -->
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-xs">
              <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center justify-between">
                <span>Revenus par Matière</span>
                <BookOpen class="w-4 h-4 text-indigo-500" />
              </h4>
              <div class="space-y-3">
                <div v-for="sub in summaryData?.bySubject || []" :key="sub.subject" class="space-y-1">
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-gray-700">{{ sub.subject }}</span>
                    <span class="font-bold text-gray-900">{{ sub.amount.toLocaleString('fr-FR') }} DT ({{ sub.percentage }}%)</span>
                  </div>
                  <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-600 rounded-full" :style="{ width: `${sub.percentage}%` }"></div>
                  </div>
                </div>
                <div v-if="!summaryData?.bySubject?.length" class="text-xs text-gray-400 py-4 text-center">
                  Aucune recette enregistrée sur la période
                </div>
              </div>
            </div>

            <!-- Répartition par Niveau Scolaire -->
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-xs">
              <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center justify-between">
                <span>Revenus par Niveau</span>
                <GraduationCap class="w-4 h-4 text-emerald-500" />
              </h4>
              <div class="space-y-3">
                <div v-for="lvl in summaryData?.byLevel || []" :key="lvl.level" class="space-y-1">
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-gray-700">{{ lvl.level }}</span>
                    <span class="font-bold text-gray-900">{{ lvl.amount.toLocaleString('fr-FR') }} DT ({{ lvl.percentage }}%)</span>
                  </div>
                  <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full" :style="{ width: `${lvl.percentage}%` }"></div>
                  </div>
                </div>
                <div v-if="!summaryData?.byLevel?.length" class="text-xs text-gray-400 py-4 text-center">
                  Aucune recette enregistrée
                </div>
              </div>
            </div>

            <!-- Synthèse Manque à Gagner & Recouvrement -->
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
              <div>
                <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center justify-between">
                  <span>Santé Financière</span>
                  <ShieldCheck class="w-4 h-4 text-blue-500" />
                </h4>
                <div class="space-y-3 text-xs">
                  <div class="p-3 bg-blue-50/60 rounded-xl border border-blue-100">
                    <p class="text-blue-900 font-semibold">Taux de Recouvrement Actuel</p>
                    <p class="text-xl font-black text-blue-700 mt-0.5">{{ kpis.recoveryRate }}%</p>
                    <p class="text-[11px] text-blue-600 mt-1">Total encaissé sur le total facturable</p>
                  </div>

                  <div class="p-3 bg-amber-50/60 rounded-xl border border-amber-100">
                    <p class="text-amber-900 font-semibold">Manque à Gagner (Remises)</p>
                    <p class="text-xl font-black text-amber-700 mt-0.5">{{ (kpis.monthlyDiscounts || 0).toLocaleString('fr-FR') }} DT</p>
                    <p class="text-[11px] text-amber-600 mt-1">Réductions accordées aux familles ce mois</p>
                  </div>
                </div>
              </div>

              <div class="pt-3 border-t border-gray-100 text-center">
                <button
                  @click="activeSubTab = 'dues'"
                  class="text-xs text-indigo-600 hover:text-indigo-800 font-bold inline-flex items-center gap-1"
                >
                  <span>Voir les retards de paiement ({{ (kpis.totalUnpaid || 0).toLocaleString('fr-FR') }} DT)</span>
                  <ArrowRight class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- ONGLET 2 : DÉPENSES RÉELLES & GRAPHIQUE EN ANNEAU (DONUT)      -->
        <!-- ============================================================== -->
        <div v-else-if="activeSubTab === 'expenses'" class="space-y-6">
          <!-- Graphique en Anneau & Statistiques Dépenses -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Anneau SVG Dynamique -->
            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs flex flex-col items-center justify-center">
              <h3 class="text-sm font-bold text-gray-900 mb-4 self-start">Ventilation par Catégorie</h3>
              
              <div class="relative w-44 h-44 my-2">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                  <circle
                    v-for="(slice, idx) in expenseDonutSlices"
                    :key="idx"
                    cx="50"
                    cy="50"
                    r="38"
                    fill="transparent"
                    :stroke="slice.color"
                    stroke-width="14"
                    :stroke-dasharray="`${slice.strokeLength} ${2 * Math.PI * 38}`"
                    :stroke-dashoffset="slice.strokeOffset"
                    class="transition-all duration-500 hover:opacity-85"
                  >
                    <title>{{ slice.label }} : {{ slice.amount }} DT ({{ slice.percentage }}%)</title>
                  </circle>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                  <span class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Total</span>
                  <span class="text-base font-black text-gray-900">{{ (kpis.totalExpenses || 0).toLocaleString('fr-FR') }} DT</span>
                </div>
              </div>
            </div>

            <!-- Légende détaillée des 8 catégories -->
            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs lg:col-span-2 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-sm font-bold text-gray-900">Catégories de Dépenses</h3>
                  <button
                    @click="generateRecurringExpenses()"
                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-xl text-xs font-semibold transition-colors"
                    title="Génère automatiquement les dépenses récurrentes (loyer, internet...) pour ce mois"
                  >
                    <Zap class="w-3.5 h-3.5" />
                    <span>Générer Récurrentes (Loyer, etc.)</span>
                  </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                  <div
                    v-for="item in expenseCategoriesSummary"
                    :key="item.category"
                    class="p-2.5 rounded-xl border border-gray-100 bg-gray-50/50 flex items-center justify-between"
                  >
                    <div class="flex items-center gap-2">
                      <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: item.color }"></span>
                      <div>
                        <p class="font-medium text-gray-900">{{ item.label }}</p>
                        <p class="text-[10px] text-gray-500">{{ item.count }} écriture(s)</p>
                      </div>
                    </div>
                    <div class="text-right">
                      <p class="font-bold text-gray-900">{{ item.amount.toLocaleString('fr-FR') }} DT</p>
                      <p class="text-[10px] text-gray-500">{{ item.percentage }}%</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Table Complète des Dépenses Réelles avec CRUD -->
          <div class="bg-white border border-gray-200 rounded-2xl shadow-xs overflow-hidden">
            <!-- Barre d'outils et filtres -->
            <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex flex-wrap items-center gap-2">
                <input
                  v-model="expenseSearch"
                  type="text"
                  placeholder="Rechercher une facture ou dépense..."
                  class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />

                <select
                  v-model="expenseCategoryFilter"
                  class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                  <option value="">Toutes catégories</option>
                  <option value="loyer">Loyer</option>
                  <option value="électricité">Électricité / Eau</option>
                  <option value="fournitures">Fournitures & Copies</option>
                  <option value="entretien">Entretien</option>
                  <option value="publicité">Publicité & Marketing</option>
                  <option value="internet_tel">Internet & Tel</option>
                  <option value="salaires">Salaires Annexes</option>
                  <option value="autres">Autres</option>
                </select>

                <select
                  v-model="expensePaymentMethodFilter"
                  class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                  <option value="">Tous règlements</option>
                  <option value="especes">Espèces</option>
                  <option value="cheque">Chèque</option>
                  <option value="virement">Virement</option>
                  <option value="carte">Carte bancaire</option>
                </select>
              </div>

              <button
                @click="openExpenseModal()"
                class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition-colors shadow-xs"
              >
                <Plus class="w-4 h-4" />
                <span>+ Nouvelle Dépense</span>
              </button>
            </div>

            <!-- Tableau des dépenses -->
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-gray-50 text-gray-600 uppercase font-semibold border-b border-gray-200">
                  <tr>
                    <th class="py-3 px-4">Date</th>
                    <th class="py-3 px-4">Libellé / Titre</th>
                    <th class="py-3 px-3">Catégorie</th>
                    <th class="py-3 px-3">Mode Règlement</th>
                    <th class="py-3 px-3">Justificatif / Ref</th>
                    <th class="py-3 px-3">Enregistrée par</th>
                    <th class="py-3 px-3 text-center">Récurrence</th>
                    <th class="py-3 px-4 text-right">Montant</th>
                    <th class="py-3 px-4 text-center">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                  <tr v-for="exp in filteredExpenses" :key="exp.id" class="hover:bg-gray-50/80 transition-colors">
                    <td class="py-3 px-4 text-gray-600 whitespace-nowrap">
                      {{ exp.date }}
                    </td>
                    <td class="py-3 px-4 font-semibold text-gray-900">
                      {{ exp.title }}
                      <span v-if="exp.notes" class="block text-[11px] text-gray-400 font-normal truncate max-w-xs">{{ exp.notes }}</span>
                    </td>
                    <td class="py-3 px-3">
                      <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border', getCategoryBadgeClass(exp.category)]">
                        {{ getCategoryLabel(exp.category) }}
                      </span>
                    </td>
                    <td class="py-3 px-3 text-gray-600">
                      <span class="capitalize">{{ exp.payment_method || 'Espèces' }}</span>
                    </td>
                    <td class="py-3 px-3 font-mono text-gray-500">
                      {{ exp.receipt_reference || '—' }}
                    </td>
                    <td class="py-3 px-3 whitespace-nowrap">
                      <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold"
                        :class="exp.creator?.role === 'secretary' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200'"
                      >
                        <span>{{ exp.creator?.name || 'Directeur Fondateur' }}</span>
                        <span class="text-[9px] font-medium opacity-75">({{ exp.creator?.role === 'secretary' ? 'Personnel' : 'Directeur' }})</span>
                      </span>
                    </td>
                    <td class="py-3 px-3 text-center">
                      <span v-if="exp.is_recurring" class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-[10px] font-semibold border border-blue-200">
                        Mensuel
                      </span>
                      <span v-else class="text-gray-400">—</span>
                    </td>
                    <td class="py-3 px-4 text-right font-black text-gray-900 text-sm">
                      {{ Number(exp.amount).toLocaleString('fr-FR') }} DT
                    </td>
                    <td class="py-3 px-4 text-center">
                      <div class="flex items-center justify-center gap-1">
                        <button
                          @click="openExpenseModal(exp)"
                          class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-gray-100 rounded-lg transition-colors"
                          title="Modifier"
                        >
                          <Pencil class="w-3.5 h-3.5" />
                        </button>
                        <button
                          @click="deleteExpense(exp.id)"
                          class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                          title="Supprimer"
                        >
                          <Trash2 class="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </td>
                  </tr>

                  <tr v-if="filteredExpenses.length === 0">
                    <td colspan="8" class="py-10 text-center text-gray-400">
                      Aucune dépense trouvée pour les critères sélectionnés.
                    </td>
                  </tr>
                </tbody>
                <tfoot v-if="filteredExpenses.length > 0" class="bg-gray-50 font-bold border-t border-gray-200">
                  <tr>
                    <td colspan="6" class="py-3 px-4 text-right uppercase text-[11px] text-gray-600 tracking-wider">Total Dépenses Filtrées</td>
                    <td class="py-3 px-4 text-right text-red-600 text-sm font-black">{{ totalFilteredExpenses.toLocaleString('fr-FR') }} DT</td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- ONGLET 3 : PARTS DES ENSEIGNANTS SUR SÉANCES RÉELLES          -->
        <!-- ============================================================== -->
        <div v-else-if="activeSubTab === 'teachers'" class="space-y-6">
          <!-- KPI Enseignants -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">C.A. Brut Enseignants</span>
              <p class="text-2xl font-black text-gray-900 mt-1">{{ totalTeachersGross.toLocaleString('fr-FR') }} DT</p>
              <p class="text-[11px] text-gray-500 mt-0.5">Revenus encaissés générés par les cours</p>
            </div>

            <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 shadow-xs">
              <span class="text-xs font-semibold text-indigo-800 uppercase tracking-wider">Total Parts Professeurs</span>
              <p class="text-2xl font-black text-indigo-950 mt-1">{{ (kpis.totalTeacherPayouts || 0).toLocaleString('fr-FR') }} DT</p>
              <p class="text-[11px] text-indigo-700 mt-0.5">Calculé selon la formule exacte de chaque enseignant</p>
            </div>

            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 shadow-xs">
              <span class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">Marge Centre Nette</span>
              <p class="text-2xl font-black text-emerald-950 mt-1">{{ totalCenterTeacherMargin.toLocaleString('fr-FR') }} DT</p>
              <p class="text-[11px] text-emerald-700 mt-0.5">Différence brute conservée par l'établissement</p>
            </div>
          </div>

          <!-- Tableau de Suivi « Dû, Payé, Reste à Payer » -->
          <div class="bg-white border border-gray-200 rounded-2xl shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900">Suivi des Honoraires & Fiches de Paie</h3>
                <p class="text-xs text-gray-500 mt-0.5">Séances réelles dispensées, montants dus, acomptes versés et reste à payer</p>
              </div>
              <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-700 rounded-full">
                {{ (summaryData?.byTeacher || []).length }} Enseignants
              </span>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-gray-50 text-gray-600 uppercase font-semibold border-b border-gray-200">
                  <tr>
                    <th class="py-3 px-4">Enseignant</th>
                    <th class="py-3 px-3">Matière</th>
                    <th class="py-3 px-3 text-center">Formule Rémunération</th>
                    <th class="py-3 px-3 text-center">Séances Payées</th>
                    <th class="py-3 px-3 text-right">C.A. Encaissé</th>
                    <th class="py-3 px-4 text-right bg-indigo-50 text-indigo-900 font-bold">Part Due</th>
                    <th class="py-3 px-4 text-right text-emerald-700 font-bold">Déjà Versé</th>
                    <th class="py-3 px-4 text-right text-red-600 font-bold">Reste à Payer</th>
                    <th class="py-3 px-3 text-center">Statut Versement</th>
                    <th class="py-3 px-4 text-center">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                  <tr v-for="t in summaryData?.byTeacher || []" :key="t.id" class="hover:bg-gray-50/80 transition-colors">
                    <td class="py-3 px-4 font-bold text-gray-900">
                      {{ t.name }}
                      <span class="block text-[11px] text-gray-400 font-normal">{{ t.phone || 'Sans tel' }}</span>
                    </td>
                    <td class="py-3 px-3 font-medium text-gray-700">
                      {{ t.subject || 'Général' }}
                    </td>
                    <td class="py-3 px-3 text-center">
                      <span
                        :class="[
                          'px-2 py-0.5 rounded-full text-[10px] font-bold border',
                          t.remunerationType === 'percentage'
                            ? 'bg-purple-50 text-purple-700 border-purple-200'
                            : t.remunerationType === 'fixed_center'
                              ? 'bg-indigo-50 text-indigo-700 border-indigo-200'
                              : 'bg-blue-50 text-blue-700 border-blue-200'
                        ]"
                      >
                        {{ t.remunerationType === 'percentage'
                          ? `Formule C : ${t.ratePercentage || 70}% du C.A.`
                          : t.remunerationType === 'fixed_center'
                            ? `Formule A : Idara ${t.centerFixedPerStudent || 20} DT/él`
                            : `Formule B : ${t.fixedStudentRate || 15} DT/él` }}
                      </span>
                    </td>
                    <td class="py-3 px-3 text-center font-bold text-gray-800">
                      {{ t.sessionsCount }}
                    </td>
                    <td class="py-3 px-3 text-right font-medium text-gray-700">
                      {{ t.revenueCollected.toLocaleString('fr-FR') }} DT
                    </td>
                    <td class="py-3 px-4 text-right font-black text-indigo-900 bg-indigo-50/50 text-sm">
                      {{ t.teacherShare.toLocaleString('fr-FR') }} DT
                    </td>
                    <td class="py-3 px-4 text-right font-bold text-emerald-700">
                      {{ t.paidAmount.toLocaleString('fr-FR') }} DT
                    </td>
                    <td class="py-3 px-4 text-right font-black" :class="t.remainingToPay > 0 ? 'text-red-600' : 'text-gray-400'">
                      {{ t.remainingToPay.toLocaleString('fr-FR') }} DT
                    </td>
                    <td class="py-3 px-3 text-center">
                      <span
                        :class="[
                          'px-2.5 py-0.5 rounded-full text-[10px] font-bold inline-block border',
                          (t.payoutStatus === 'paid' || t.payoutStatus === 'taken')
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            : 'bg-amber-50 text-amber-700 border-amber-200'
                        ]"
                      >
                        {{ (t.payoutStatus === 'paid' || t.payoutStatus === 'taken') ? 'Payé' : 'En attente' }}
                      </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                      <div class="flex items-center justify-center gap-1.5">
                        <button
                          @click="openPayslip(t.id)"
                          class="inline-flex items-center gap-1 px-2.5 py-1 bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-xs font-semibold transition-colors"
                          title="Fiche de paie imprimable"
                        >
                          <FileText class="w-3.5 h-3.5" />
                          <span>Fiche Paie</span>
                        </button>

                        <button
                          @click="toggleTeacherPayoutStatus(t)"
                          :class="[
                            'px-2 py-1 rounded-lg text-xs font-semibold transition-colors border',
                            (t.payoutStatus === 'paid' || t.payoutStatus === 'taken')
                              ? 'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200'
                              : 'bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700'
                          ]"
                          :title="(t.payoutStatus === 'paid' || t.payoutStatus === 'taken') ? 'Remettre en attente' : 'Marquer comme payé'"
                        >
                          {{ (t.payoutStatus === 'paid' || t.payoutStatus === 'taken') ? 'Annuler' : 'Régler' }}
                        </button>
                      </div>
                    </td>
                  </tr>

                  <tr v-if="!summaryData?.byTeacher?.length">
                    <td colspan="10" class="py-8 text-center text-gray-400">
                      Aucun enseignant répertorié.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- ONGLET 4 : RESTE À ENCAISSER & RELANCES WHATSAPP               -->
        <!-- ============================================================== -->
        <div v-else-if="activeSubTab === 'dues'" class="space-y-6">
          <!-- Soldes dus élèves & garderie -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-red-50/80 border border-red-200 rounded-2xl p-4 shadow-xs">
              <span class="text-xs font-semibold text-red-800 uppercase tracking-wider">Total Reste à Encaisser</span>
              <p class="text-2xl font-black text-red-950 mt-1">{{ (kpis.totalUnpaid || 0).toLocaleString('fr-FR') }} DT</p>
              <p class="text-[11px] text-red-700 mt-0.5">Soldes non réglés cumulés</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Impayés Élèves (Cours)</span>
              <p class="text-2xl font-black text-gray-900 mt-1">{{ (kpis.unpaidStudents || 0).toLocaleString('fr-FR') }} DT</p>
              <p class="text-[11px] text-gray-500 mt-0.5">Séances suivies non payées</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-xs">
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Impayés Garderie</span>
              <p class="text-2xl font-black text-gray-900 mt-1">{{ (kpis.unpaidDaycare || 0).toLocaleString('fr-FR') }} DT</p>
              <p class="text-[11px] text-gray-500 mt-0.5">Mois de garde en retard</p>
            </div>
          </div>

          <!-- Tableau des Plus Gros Retards avec bouton WhatsApp -->
          <div class="bg-white border border-gray-200 rounded-2xl shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900">Les Plus Gros Retards & Relances Immédiates</h3>
                <p class="text-xs text-gray-500 mt-0.5">Un clic sur l'icône WhatsApp ouvre un message personnalisé et pré-rempli vers le parent</p>
              </div>
              <span class="text-xs font-semibold px-2.5 py-1 bg-amber-50 text-amber-800 rounded-full border border-amber-200">
                Action Recouvrement
              </span>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-gray-50 text-gray-600 uppercase font-semibold border-b border-gray-200">
                  <tr>
                    <th class="py-3 px-4">Élève / Enfant</th>
                    <th class="py-3 px-3">Service</th>
                    <th class="py-3 px-3">Parent / Contact</th>
                    <th class="py-3 px-3 text-center">Retard</th>
                    <th class="py-3 px-4 text-right">Montant Dû</th>
                    <th class="py-3 px-3 text-center">Statut</th>
                    <th class="py-3 px-4 text-center">Relance WhatsApp</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                  <tr v-for="debtor in summaryData?.topDebtors || []" :key="debtor.key" class="hover:bg-gray-50/80 transition-colors">
                    <td class="py-3 px-4 font-bold text-gray-900">
                      {{ debtor.name }}
                    </td>
                    <td class="py-3 px-3 text-gray-600">
                      {{ debtor.category }}
                    </td>
                    <td class="py-3 px-3 text-gray-700">
                      <span v-if="debtor.parentName" class="font-medium text-gray-900">{{ debtor.parentName }} - </span>
                      <span class="font-mono text-gray-600">{{ debtor.phone || 'Non renseigné' }}</span>
                    </td>
                    <td class="py-3 px-3 text-center font-bold text-gray-700">
                      {{ debtor.unpaidUnits }}
                    </td>
                    <td class="py-3 px-4 text-right font-black text-red-600 text-sm">
                      {{ debtor.amountDue.toLocaleString('fr-FR') }} DT
                    </td>
                    <td class="py-3 px-3 text-center">
                      <span
                        :class="[
                          'px-2.5 py-0.5 rounded-full text-[10px] font-bold border',
                          debtor.status === 'overdue'
                            ? 'bg-red-50 text-red-700 border-red-200'
                            : 'bg-amber-50 text-amber-700 border-amber-200'
                        ]"
                      >
                        {{ debtor.status === 'overdue' ? 'En retard critique' : 'En attente' }}
                      </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                      <a
                        v-if="debtor.whatsappUrl"
                        :href="debtor.whatsappUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
                      >
                        <MessageCircle class="w-3.5 h-3.5" />
                        <span>Relancer</span>
                      </a>
                      <span v-else class="text-gray-400 text-[11px]">Pas de tél</span>
                    </td>
                  </tr>

                  <tr v-if="!summaryData?.topDebtors?.length">
                    <td colspan="7" class="py-8 text-center text-emerald-600 font-semibold">
                      🎉 Aucun retard de paiement détecté ! Tous les comptes sont à jour.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- ONGLET 5 : JOURNAL DE CAISSE & TRAÇABILITÉ (AUDIT LOGS)       -->
        <!-- ============================================================== -->
        <div v-else-if="activeSubTab === 'cash_audit'" class="space-y-6">
          <!-- Journal de Caisse Quotidien -->
          <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
              <div>
                <h3 class="text-base font-bold text-gray-900">Journal de Caisse Quotidien</h3>
                <p class="text-xs text-gray-500 mt-0.5">Flux d'espèces et rapprochement des encaissements / décaissements du jour</p>
              </div>

              <!-- Date Picker pour le Journal -->
              <div class="flex items-center gap-2">
                <span class="text-xs font-medium text-gray-500">Date :</span>
                <input
                  v-model="journalDate"
                  type="date"
                  @change="fetchSummary"
                  class="text-xs bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
              </div>
            </div>

            <!-- Totaux de caisse du jour -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
              <!-- Caisse Espèces Nette -->
              <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                <span class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">Solde Espèces du Jour</span>
                <p class="text-2xl font-black text-emerald-950 mt-1">{{ (summaryData?.dailyCashJournal?.netCashOnly || 0).toLocaleString('fr-FR') }} DT</p>
                <p class="text-[11px] text-emerald-700 mt-0.5">Espèces reçues − Dépenses espèces</p>
              </div>

              <!-- Total Entrées -->
              <div class="bg-white border border-gray-200 rounded-xl p-4">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Entrées (Tous modes)</span>
                <p class="text-2xl font-black text-gray-900 mt-1">{{ (summaryData?.dailyCashJournal?.totalInflows || 0).toLocaleString('fr-FR') }} DT</p>
                <p class="text-[11px] text-gray-500 mt-0.5">{{ summaryData?.dailyCashJournal?.inflowsCount || 0 }} encaissement(s)</p>
              </div>

              <!-- Total Sorties -->
              <div class="bg-white border border-gray-200 rounded-xl p-4">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Sorties (Dépenses)</span>
                <p class="text-2xl font-black text-red-600 mt-1">{{ (summaryData?.dailyCashJournal?.totalOutflows || 0).toLocaleString('fr-FR') }} DT</p>
                <p class="text-[11px] text-gray-500 mt-0.5">{{ summaryData?.dailyCashJournal?.outflowsCount || 0 }} dépense(s)</p>
              </div>

              <!-- Solde Net Global -->
              <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4">
                <span class="text-xs font-semibold text-indigo-800 uppercase tracking-wider">Flux Net de la Journée</span>
                <p class="text-2xl font-black text-indigo-950 mt-1">{{ (summaryData?.dailyCashJournal?.netTotal || 0).toLocaleString('fr-FR') }} DT</p>
                <p class="text-[11px] text-indigo-700 mt-0.5">Encaissements nets générés</p>
              </div>
            </div>

            <!-- Ventilation par Mode de Règlement Aujourd'hui -->
            <div class="pt-3 border-t border-gray-100">
              <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Ventilation par Mode de Règlement</h4>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                <div class="p-2.5 rounded-xl border border-gray-200 bg-gray-50">
                  <span class="text-gray-500">Espèces :</span>
                  <span class="font-bold text-gray-900 ml-1">{{ (summaryData?.dailyCashJournal?.inflowsByMethod?.especes || 0).toLocaleString('fr-FR') }} DT</span>
                </div>
                <div class="p-2.5 rounded-xl border border-gray-200 bg-gray-50">
                  <span class="text-gray-500">Chèque :</span>
                  <span class="font-bold text-gray-900 ml-1">{{ (summaryData?.dailyCashJournal?.inflowsByMethod?.cheque || 0).toLocaleString('fr-FR') }} DT</span>
                </div>
                <div class="p-2.5 rounded-xl border border-gray-200 bg-gray-50">
                  <span class="text-gray-500">Virement :</span>
                  <span class="font-bold text-gray-900 ml-1">{{ (summaryData?.dailyCashJournal?.inflowsByMethod?.virement || 0).toLocaleString('fr-FR') }} DT</span>
                </div>
                <div class="p-2.5 rounded-xl border border-gray-200 bg-gray-50">
                  <span class="text-gray-500">Carte :</span>
                  <span class="font-bold text-gray-900 ml-1">{{ (summaryData?.dailyCashJournal?.inflowsByMethod?.carte || 0).toLocaleString('fr-FR') }} DT</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Traçabilité & Journal d'Audit des Paiements -->
          <div class="bg-white border border-gray-200 rounded-2xl shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                  <ShieldAlert class="w-4 h-4 text-indigo-600" />
                  <span>Traçabilité & Journal d'Audit des Paiements</span>
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">Historique certifié : qui a créé, modifié, validé ou supprimé un paiement</p>
              </div>

              <button
                @click="fetchAuditLogs"
                class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition-colors"
              >
                <RefreshCw class="w-3.5 h-3.5" />
                <span>Actualiser</span>
              </button>
            </div>

            <div class="overflow-x-auto max-h-[400px]">
              <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-gray-50 text-gray-600 uppercase font-semibold border-b border-gray-200 sticky top-0 z-10">
                  <tr>
                    <th class="py-3 px-4">Date & Heure</th>
                    <th class="py-3 px-4">Utilisateur</th>
                    <th class="py-3 px-3">Rôle</th>
                    <th class="py-3 px-3">Action</th>
                    <th class="py-3 px-4">Élève Concerné</th>
                    <th class="py-3 px-3">Mode</th>
                    <th class="py-3 px-4 text-right">Montant</th>
                    <th class="py-3 px-3">IP</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                  <tr v-for="log in auditLogs" :key="log.id" class="hover:bg-gray-50/80 transition-colors">
                    <td class="py-3 px-4 text-gray-500 whitespace-nowrap font-mono">
                      {{ formatLogDate(log.created_at) }}
                    </td>
                    <td class="py-3 px-4 font-bold text-gray-900">
                      {{ log.user_name || 'Système' }}
                    </td>
                    <td class="py-3 px-3">
                      <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold', log.user_role === 'director' ? 'bg-indigo-50 text-indigo-700' : 'bg-gray-100 text-gray-700']">
                        {{ log.user_role === 'director' ? 'Directeur' : 'Personnel' }}
                      </span>
                    </td>
                    <td class="py-3 px-3">
                      <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold border', getAuditActionBadge(log.action)]">
                        {{ formatAuditAction(log.action) }}
                      </span>
                    </td>
                    <td class="py-3 px-4 font-medium text-gray-800">
                      {{ log.student_name || '—' }}
                    </td>
                    <td class="py-3 px-3 text-gray-600 capitalize">
                      {{ log.payment_method || 'especes' }}
                    </td>
                    <td class="py-3 px-4 text-right font-black text-gray-900">
                      {{ log.amount ? `${Number(log.amount).toLocaleString('fr-FR')} DT` : '—' }}
                    </td>
                    <td class="py-3 px-3 text-gray-400 font-mono text-[10px]">
                      {{ log.ip_address || '—' }}
                    </td>
                  </tr>

                  <tr v-if="auditLogs.length === 0">
                    <td colspan="8" class="py-8 text-center text-gray-400">
                      Aucune trace d'audit enregistrée pour l'instant.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- ONGLET 6 : CLÔTURE MENSUELLE & EXPORTS COMPTABLES              -->
        <!-- ============================================================== -->
        <div v-else-if="activeSubTab === 'closing'" class="space-y-6">
          <!-- Carte Clôture du Mois Actuel -->
          <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100">
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-base font-bold text-gray-900">Clôture Comptable : Mois {{ selectedMonth }}</h3>
                  <span
                    :class="[
                      'px-3 py-0.5 rounded-full text-xs font-bold border',
                      summaryData?.monthlyClosing?.isClosed
                        ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                        : 'bg-amber-50 text-amber-800 border-amber-200'
                    ]"
                  >
                    {{ summaryData?.monthlyClosing?.isClosed ? '✓ Mois Clôturé & Figé' : 'Mois Actuellement Ouvert' }}
                  </span>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                  La clôture mensuelle valide et fige définitivement les chiffres du mois pour la comptabilité générale.
                </p>
              </div>

              <!-- Bouton Clôturer / Réouvrir -->
              <div>
                <button
                  v-if="!summaryData?.monthlyClosing?.isClosed"
                  @click="closeCurrentMonth()"
                  class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs"
                >
                  <Lock class="w-4 h-4" />
                  <span>Clôturer et Figer ce Mois</span>
                </button>

                <button
                  v-else
                  @click="reopenCurrentMonth()"
                  class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-xl text-xs font-bold transition-colors"
                >
                  <Unlock class="w-4 h-4" />
                  <span>Réouvrir pour Régularisation</span>
                </button>
              </div>
            </div>

            <!-- Détail de la clôture active -->
            <div v-if="summaryData?.monthlyClosing?.isClosed" class="grid grid-cols-1 sm:grid-cols-4 gap-4 mt-4 pt-2 text-xs">
              <div class="p-3 bg-gray-50 rounded-xl">
                <span class="text-gray-500">Date de clôture :</span>
                <p class="font-bold text-gray-900 mt-0.5">{{ formatDateTime(summaryData.monthlyClosing.closedAt) }}</p>
              </div>
              <div class="p-3 bg-gray-50 rounded-xl">
                <span class="text-gray-500">Recettes figées :</span>
                <p class="font-bold text-gray-900 mt-0.5">{{ (kpis.totalRevenue || 0).toLocaleString('fr-FR') }} DT</p>
              </div>
              <div class="p-3 bg-gray-50 rounded-xl">
                <span class="text-gray-500">Dépenses figées :</span>
                <p class="font-bold text-red-600 mt-0.5">{{ (kpis.totalExpenses || 0).toLocaleString('fr-FR') }} DT</p>
              </div>
              <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                <span class="text-emerald-800 font-semibold">Bénéfice Net Figé :</span>
                <p class="font-black text-emerald-950 text-sm mt-0.5">{{ (kpis.netProfit || 0).toLocaleString('fr-FR') }} DT</p>
              </div>
            </div>
          </div>

          <!-- Section Téléchargement & Exports Comptables -->
          <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs space-y-4">
            <h3 class="text-base font-bold text-gray-900">Exports pour l'Expert-Comptable</h3>
            <p class="text-xs text-gray-500">Générez les fichiers certifiés aux formats compatibles Excel, CSV et PDF :</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
              <!-- Carte 1 : Grand Livre des Recettes -->
              <div class="p-4 border border-gray-200 rounded-xl hover:border-indigo-300 transition-colors bg-gray-50/50 flex flex-col justify-between">
                <div>
                  <h4 class="font-bold text-gray-900 text-sm">Grand Livre des Recettes</h4>
                  <p class="text-xs text-gray-500 mt-1">Détail exhaustif de chaque paiement élève et garderie avec reçu et mode de règlement.</p>
                </div>
                <div class="mt-4 flex flex-col sm:flex-row items-stretch gap-2">
                  <button
                    @click="openExpertPdfModal('receipts')"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
                    title="Générer et exporter le Grand Livre des Recettes au format PDF officiel"
                  >
                    <Printer class="w-3.5 h-3.5" />
                    <span>Exporter PDF (A4)</span>
                  </button>
                  <button
                    @click="exportCsv('payments')"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
                    title="Exporter au format Excel / CSV"
                  >
                    <Download class="w-3.5 h-3.5" />
                    <span>CSV</span>
                  </button>
                </div>
              </div>

              <!-- Carte 2 : Journal des Dépenses -->
              <div class="p-4 border border-gray-200 rounded-xl hover:border-indigo-300 transition-colors bg-gray-50/50 flex flex-col justify-between">
                <div>
                  <h4 class="font-bold text-gray-900 text-sm">Journal des Dépenses</h4>
                  <p class="text-xs text-gray-500 mt-1">Liste complète des factures, loyers, salaires annexes et justificatifs comptables.</p>
                </div>
                <div class="mt-4 flex flex-col sm:flex-row items-stretch gap-2">
                  <button
                    @click="openExpertPdfModal('expenses')"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
                    title="Générer et exporter le Journal des Dépenses au format PDF officiel"
                  >
                    <Printer class="w-3.5 h-3.5" />
                    <span>Exporter PDF (A4)</span>
                  </button>
                  <button
                    @click="exportCsv('expenses')"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
                    title="Exporter au format Excel / CSV"
                  >
                    <Download class="w-3.5 h-3.5" />
                    <span>CSV</span>
                  </button>
                </div>
              </div>

              <!-- Carte 3 : États des Honoraires Professeurs -->
              <div class="p-4 border border-gray-200 rounded-xl hover:border-indigo-300 transition-colors bg-gray-50/50 flex flex-col justify-between">
                <div>
                  <h4 class="font-bold text-gray-900 text-sm">États des Honoraires Professeurs</h4>
                  <p class="text-xs text-gray-500 mt-1">Récapitulatif des cours, quotas de séances, montants versés et soldes d'honoraires.</p>
                </div>
                <div class="mt-4 flex flex-col sm:flex-row items-stretch gap-2">
                  <button
                    @click="openExpertPdfModal('teachers')"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
                    title="Générer et exporter les Honoraires des Enseignants au format PDF officiel"
                  >
                    <Printer class="w-3.5 h-3.5" />
                    <span>Exporter PDF (A4)</span>
                  </button>
                  <button
                    @click="exportCsv('teachers')"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
                    title="Exporter au format Excel / CSV"
                  >
                    <Download class="w-3.5 h-3.5" />
                    <span>CSV</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ============================================================== -->
    <!-- MODAL 1 : AJOUT / MODIFICATION D'UNE DÉPENSE                   -->
    <!-- ============================================================== -->
    <div v-if="showExpenseModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
      <div class="bg-white rounded-2xl border border-gray-200 shadow-xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">
              <TrendingDown class="w-4 h-4" />
            </div>
            <h3 class="text-base font-bold text-gray-900">
              {{ editingExpenseId ? 'Modifier la Dépense' : 'Enregistrer une Nouvelle Dépense' }}
            </h3>
          </div>
          <button @click="showExpenseModal = false" class="text-gray-400 hover:text-gray-600 p-1">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="saveExpense" class="p-5 space-y-4 text-xs">
          <!-- Titre / Libellé -->
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Libellé / Titre de la dépense *</label>
            <input
              v-model="expenseForm.title"
              type="text"
              required
              placeholder="ex: Loyer local, Facture STEG, Rames de papier..."
              class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium"
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- Catégorie -->
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Catégorie *</label>
              <select
                v-model="expenseForm.category"
                required
                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="loyer">Loyer</option>
                <option value="électricité">Électricité / Eau</option>
                <option value="fournitures">Fournitures & Copies</option>
                <option value="entretien">Entretien & Nettoyage</option>
                <option value="publicité">Publicité & Marketing</option>
                <option value="internet_tel">Internet & Télécoms</option>
                <option value="salaires">Salaires & Primes</option>
                <option value="autres">Autres</option>
              </select>
            </div>

            <!-- Montant -->
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Montant (DT) *</label>
              <input
                v-model.number="expenseForm.amount"
                type="number"
                step="0.01"
                min="0.01"
                required
                placeholder="0.00"
                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-black text-base"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- Date -->
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Date *</label>
              <input
                v-model="expenseForm.date"
                type="date"
                required
                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium"
              />
            </div>

            <!-- Mode de paiement -->
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Mode de règlement *</label>
              <select
                v-model="expenseForm.payment_method"
                required
                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="especes">Espèces</option>
                <option value="cheque">Chèque</option>
                <option value="virement">Virement bancaire</option>
                <option value="carte">Carte bancaire</option>
                <option value="autre">Autre</option>
              </select>
            </div>
          </div>

          <!-- Réf Justificatif -->
          <div>
            <label class="block font-semibold text-gray-700 mb-1">N° Justificatif / Facture (optionnel)</label>
            <input
              v-model="expenseForm.receipt_reference"
              type="text"
              placeholder="ex: FACT-2026-089, Quittance n°45..."
              class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"
            />
          </div>

          <!-- Dépense récurrente -->
          <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 flex items-center justify-between">
            <div>
              <p class="font-bold text-gray-900">Dépense Récurrente Mensuelle</p>
              <p class="text-[11px] text-gray-500">Ajoutée chaque mois (ex: loyer, abonnement internet fibre)</p>
            </div>
            <input
              v-model="expenseForm.is_recurring"
              type="checkbox"
              class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"
            />
          </div>

          <!-- Notes -->
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Notes / Commentaires</label>
            <textarea
              v-model="expenseForm.notes"
              rows="2"
              placeholder="Détails complémentaires..."
              class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
            <button
              type="button"
              @click="showExpenseModal = false"
              class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-colors"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="isSubmittingExpense"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition-colors shadow-xs disabled:opacity-50"
            >
              {{ isSubmittingExpense ? 'Enregistrement...' : (editingExpenseId ? 'Mettre à jour' : 'Enregistrer la Dépense') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 2 : FICHE DE PAIE / DÉCHARGE IMPRIMABLE ENSEIGNANT       -->
    <!-- ============================================================== -->
    <div
      v-if="showPayslipModal && activePayslip"
      @click.self="showPayslipModal = false"
      class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs p-3 sm:p-6 flex justify-center items-start"
    >
      <div class="bg-white rounded-2xl border border-gray-200 shadow-2xl w-full max-w-2xl my-2 sm:my-6 overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95">
        <!-- Actions barre (Sticky Top) -->
        <div class="p-4 bg-gray-900 text-white flex items-center justify-between no-print print:hidden shrink-0 shadow-sm z-20">
          <div class="flex items-center gap-2">
            <FileText class="w-4 h-4 text-indigo-400" />
            <span class="text-xs sm:text-sm font-bold">Fiche de Paie & Décharge d'Honoraires</span>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="printElement('payslip-printable')"
              class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
            >
              <Printer class="w-3.5 h-3.5" />
              <span>Imprimer</span>
            </button>
            <button
              @click="showPayslipModal = false"
              class="text-gray-400 hover:text-white p-1 rounded-lg hover:bg-gray-800 transition-colors"
              title="Fermer"
            >
              <X class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Corps Déroulant Imprimable -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-8 bg-gray-50/50 print:p-0 print:bg-white print:overflow-visible">
          <div id="payslip-printable" class="p-6 sm:p-8 space-y-6 text-gray-900 bg-white rounded-xl border border-gray-200 print:border-none print:shadow-none print:p-0">
            <!-- En-tête Centre d'études -->
            <!-- En-tête Centre d'études -->
            <div class="avoid-break flex items-start justify-between border-b-2 border-gray-900 pb-5">
              <div>
                <h1 class="text-xl font-black tracking-tight text-gray-900">CENTRE D'ÉTUDES & SOUTIEN SCOLAIRE</h1>
                <p class="text-xs text-gray-500 mt-0.5">Administration Générale & Direction Financière</p>
                <p class="text-xs text-gray-500">Tunis, Tunisie • Tél: +216 71 000 000</p>
              </div>
              <div class="text-right">
                <span class="text-xs font-mono px-2.5 py-1 bg-gray-100 rounded-lg font-bold border border-gray-300">
                  {{ activePayslip.payslipNumber }}
                </span>
                <p class="text-xs text-gray-500 mt-2">Date: {{ activePayslip.generatedAt }}</p>
              </div>
            </div>

            <!-- Titre du document -->
            <div class="avoid-break text-center py-2 bg-gray-50 rounded-xl border border-gray-200">
              <h2 class="text-base font-black uppercase tracking-wider text-gray-900">FICHE DE DÉCHARGE D'HONORAIRES</h2>
              <p class="text-xs text-gray-600 font-medium">Période : {{ activePayslip.monthLabel }}</p>
            </div>

            <!-- Informations Enseignant -->
            <div class="avoid-break grid grid-cols-1 md:grid-cols-2 gap-4 text-xs bg-gray-50/70 p-4 rounded-xl border border-gray-200">
              <div>
                <p class="text-gray-500 uppercase text-[10px] tracking-wider font-semibold">Bénéficiaire</p>
                <p class="text-sm font-black text-gray-900 mt-0.5">{{ activePayslip.teacher.name }}</p>
                <p class="text-gray-600 mt-0.5">Matière : <span class="font-bold text-gray-900">{{ activePayslip.teacher.subject || 'Général' }}</span></p>
                <p class="text-gray-600">Tél : {{ activePayslip.teacher.phone || '—' }}</p>
              </div>
              <div class="text-right">
                <p class="text-gray-500 uppercase text-[10px] tracking-wider font-semibold">Formule Contractuelle</p>
                <p class="font-bold text-gray-900 mt-0.5">
                  <template v-if="activePayslip.teacher.remunerationType === 'percentage'">
                    Formule C : {{ activePayslip.teacher.ratePercentage || 70 }}% sur tarif brut
                  </template>
                  <template v-else-if="activePayslip.teacher.remunerationType === 'fixed_center'">
                    Formule A : Part Idara {{ activePayslip.teacher.centerFixedPerStudent || 20 }} DT/élève
                  </template>
                  <template v-else>
                    Formule B : {{ activePayslip.teacher.fixedStudentRate || 15 }} DT fixe par élève
                  </template>
                </p>
                <p class="text-gray-600 mt-1">Séances validées ce mois : <span class="font-bold text-gray-900">{{ activePayslip.summary.totalSessions }}</span></p>
              </div>
            </div>

            <!-- Détail des séances & élèves -->
            <div class="avoid-break">
              <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Détail des séances encaissées</h3>
              <table class="w-full text-left text-xs border border-gray-200 rounded-xl overflow-hidden whitespace-nowrap">
                <thead class="bg-gray-100 font-semibold text-gray-700 border-b border-gray-200">
                  <tr>
                    <th class="py-2 px-3">Date</th>
                    <th class="py-2 px-3">Élève</th>
                    <th class="py-2 px-2 text-center">Séances</th>
                    <th class="py-2 px-3 text-right">C.A. Élève</th>
                    <th class="py-2 px-3 text-right">Part Professeur</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="item in activePayslip.items" :key="item.paymentId" class="avoid-break">
                    <td class="py-2 px-3 text-gray-600 font-mono">{{ item.date }}</td>
                    <td class="py-2 px-3 font-semibold text-gray-900">{{ item.studentName }}</td>
                    <td class="py-2 px-2 text-center font-bold">{{ item.sessions }}</td>
                    <td class="py-2 px-3 text-right text-gray-700">{{ Number(item.amountPaid).toFixed(2) }} DT</td>
                    <td class="py-2 px-3 text-right font-black text-gray-900">{{ Number(item.teacherShare).toFixed(2) }} DT</td>
                  </tr>
                  <tr v-if="activePayslip.items.length === 0">
                    <td colspan="5" class="py-4 text-center text-gray-400">Aucune séance encaissée pour ce mois.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Totalisation Financière -->
            <div class="avoid-break border-t-2 border-gray-900 pt-4 flex flex-col items-end text-xs space-y-1">
              <div class="w-64 flex justify-between py-1 border-b border-gray-200">
                <span class="text-gray-600">Total Brut Dû :</span>
                <span class="font-bold text-gray-900">{{ activePayslip.summary.grossDue.toFixed(2) }} DT</span>
              </div>
              <div class="w-64 flex justify-between py-1 border-b border-gray-200">
                <span class="text-gray-600">Acomptes Déjà Versés :</span>
                <span class="font-bold text-emerald-700">− {{ activePayslip.summary.advancesPaid.toFixed(2) }} DT</span>
              </div>
              <div class="w-64 flex justify-between py-2 text-sm font-black bg-gray-100 px-3 rounded-xl border border-gray-300">
                <span class="text-gray-900">NET À RÉGLER :</span>
                <span class="text-indigo-950 font-black text-base">{{ activePayslip.summary.netPayable.toFixed(2) }} DT</span>
              </div>
            </div>

            <!-- Signatures et Décharge -->
            <div class="avoid-break grid grid-cols-1 md:grid-cols-2 gap-8 pt-8 border-t border-gray-200 text-xs">
              <div class="space-y-12">
                <p class="font-bold text-gray-900">Visa & Cachet de la Direction :</p>
                <div class="h-14 border-b border-dashed border-gray-400"></div>
              </div>
              <div class="space-y-12 text-right">
                <p class="font-bold text-gray-900">Signature de l'Enseignant (Pour décharge) :</p>
                <div class="h-14 border-b border-dashed border-gray-400"></div>
              </div>
            </div>
        </div>
      </div>
    </div>
  </div>

    <!-- ============================================================== -->
    <!-- MODAL 3 : BILAN COMPTABLE & RAPPORT FINANCIER IMPRIMABLE       -->
    <!-- ============================================================== -->
    <div
      v-if="showReportPrintModal"
      @click.self="showReportPrintModal = false"
      class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs p-3 sm:p-6 flex justify-center items-start"
    >
      <div class="bg-white rounded-2xl border border-gray-200 shadow-2xl w-full max-w-4xl my-2 sm:my-6 overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95">
        <!-- Actions barre (Sticky Top) -->
        <div class="p-4 bg-gray-900 text-white flex items-center justify-between no-print print:hidden shrink-0 shadow-sm z-20">
          <div class="flex items-center gap-2">
            <BarChart3 class="w-4 h-4 text-indigo-400" />
            <span class="text-xs sm:text-sm font-bold">Bilan Comptable & Financier Officiel</span>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="printElement('financial-report-printable')"
              class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
            >
              <Printer class="w-3.5 h-3.5" />
              <span>Imprimer / PDF</span>
            </button>
            <button
              @click="showReportPrintModal = false"
              class="text-gray-400 hover:text-white p-1 rounded-lg hover:bg-gray-800 transition-colors"
              title="Fermer"
            >
              <X class="w-5 h-5" />
            </button>
          </div>
        </div>

          <!-- Corps Déroulant Imprimable -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-8 bg-gray-50/50 print:p-0 print:bg-white print:overflow-visible">
          <div id="financial-report-printable" class="p-6 sm:p-8 space-y-6 text-gray-900 bg-white rounded-xl border border-gray-200 print:border-none print:shadow-none print:p-0">
            <!-- En-tête Centre d'études -->
            <div class="avoid-break flex items-start justify-between border-b-2 border-gray-900 pb-5">
              <div>
                <h1 class="text-xl font-black tracking-tight text-gray-900">CENTRE D'ÉTUDES & SOUTIEN SCOLAIRE</h1>
                <p class="text-xs text-gray-500 mt-0.5">Direction Financière & Comptabilité Générale</p>
                <p class="text-xs text-gray-500">Tunis, Tunisie • Document Certifié d'Exploitation</p>
              </div>
              <div class="text-right">
                <span class="text-xs font-mono px-2.5 py-1 bg-gray-100 rounded-lg font-bold border border-gray-300">
                  RAPPORT-{{ selectedMonth || 'ANNUEL' }}
                </span>
                <p class="text-xs text-gray-500 mt-2">Édité le: {{ new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }) }}</p>
                <p class="text-xs text-gray-500">Période: <span class="font-bold text-gray-900">{{ selectedPeriod === 'school_year' ? `Année Scolaire ${selectedSchoolYear}` : (selectedPeriod === 'current_month' ? `Mois ${selectedMonth}` : '12 derniers mois') }}</span></p>
              </div>
            </div>

            <!-- Titre du document -->
            <div class="avoid-break text-center py-2.5 bg-gray-50 rounded-xl border border-gray-200">
              <h2 class="text-sm font-black uppercase tracking-wider text-gray-900">SYNTHÈSE COMPTABLE & BILAN DE GESTION</h2>
              <p class="text-xs text-gray-600 mt-0.5">
                État réel des encaissements, dépenses d'exploitation, honoraires et marge nette
              </p>
            </div>

            <!-- Grille des 4 Grands Indicateurs -->
            <div class="avoid-break grid grid-cols-4 gap-3 text-xs">
              <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
                <span class="text-gray-500 uppercase text-[10px] tracking-wider font-semibold">Recettes Totales</span>
                <p class="text-lg font-black text-gray-900 mt-0.5">{{ (kpis.totalRevenue || 0).toLocaleString('fr-FR') }} DT</p>
                <p class="text-[10px] text-gray-500 mt-0.5">Élèves & Garderie</p>
              </div>

              <div class="p-3 bg-red-50/70 rounded-xl border border-red-200">
                <span class="text-red-700 uppercase text-[10px] tracking-wider font-semibold">Total Dépenses</span>
                <p class="text-lg font-black text-red-600 mt-0.5">{{ (kpis.totalExpenses || 0).toLocaleString('fr-FR') }} DT</p>
                <p class="text-[10px] text-red-600 mt-0.5">Charges d'exploitation</p>
              </div>

              <div class="p-3 bg-indigo-50/70 rounded-xl border border-indigo-200">
                <span class="text-indigo-700 uppercase text-[10px] tracking-wider font-semibold">Parts Enseignants</span>
                <p class="text-lg font-black text-indigo-900 mt-0.5">{{ (kpis.totalTeacherPayouts || 0).toLocaleString('fr-FR') }} DT</p>
                <p class="text-[10px] text-indigo-600 mt-0.5">Honoraires sur séances</p>
              </div>

              <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-300">
                <span class="text-emerald-800 uppercase text-[10px] tracking-wider font-semibold">Bénéfice Net</span>
                <p class="text-lg font-black text-emerald-950 mt-0.5">{{ (kpis.netProfit || 0).toLocaleString('fr-FR') }} DT</p>
                <p class="text-[10px] text-emerald-700 mt-0.5">Marge nette dégagée</p>
              </div>
            </div>

            <!-- Évolution Mensuelle Récapitulative -->
            <div class="avoid-break">
              <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">1. Évolution Mensuelle Réelle</h3>
              <table class="w-full text-left text-xs border border-gray-200 rounded-xl overflow-hidden whitespace-nowrap">
                <thead class="bg-gray-100 font-semibold text-gray-700 border-b border-gray-200">
                  <tr>
                    <th class="py-2 px-3">Mois</th>
                    <th class="py-2 px-3 text-right">Élèves</th>
                    <th class="py-2 px-3 text-right">Garderie</th>
                    <th class="py-2 px-3 text-right font-bold">Total Recettes</th>
                    <th class="py-2 px-3 text-right text-red-600">Dépenses</th>
                    <th class="py-2 px-3 text-right text-indigo-700">Parts Profs</th>
                    <th class="py-2 px-3 text-right font-black text-emerald-700">Bénéfice Net</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="m in summaryData?.monthsTrend || []" :key="m.month" class="avoid-break">
                    <td class="py-2 px-3 font-semibold text-gray-900">{{ m.label }}</td>
                    <td class="py-2 px-3 text-right text-gray-600">{{ Number(m.students).toFixed(2) }} DT</td>
                    <td class="py-2 px-3 text-right text-gray-600">{{ Number(m.daycare).toFixed(2) }} DT</td>
                    <td class="py-2 px-3 text-right font-bold text-gray-900">{{ Number(m.totalRevenue).toFixed(2) }} DT</td>
                    <td class="py-2 px-3 text-right text-red-600 font-medium">{{ Number(m.expenses).toFixed(2) }} DT</td>
                    <td class="py-2 px-3 text-right text-indigo-700 font-medium">{{ Number(m.teacherPayouts).toFixed(2) }} DT</td>
                    <td class="py-2 px-3 text-right font-black" :class="m.netProfit >= 0 ? 'text-emerald-700' : 'text-red-600'">
                      {{ Number(m.netProfit).toFixed(2) }} DT
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Ventilation des Dépenses par Catégorie -->
            <div class="avoid-break">
              <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">2. Répartition des Dépenses par Poste</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                <div
                  v-for="cat in summaryData?.expenseDonut || []"
                  :key="cat.category"
                  class="flex items-center justify-between p-2 bg-gray-50 rounded-lg border border-gray-100 avoid-break"
                >
                  <span class="text-gray-700 font-medium">{{ cat.label }} ({{ cat.count }})</span>
                  <span class="font-bold text-gray-900">{{ Number(cat.amount).toFixed(2) }} DT ({{ cat.percentage }}%)</span>
                </div>
              </div>
            </div>

            <!-- Synthèse Honoraires Enseignants -->
            <div class="avoid-break">
              <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">3. Honoraires & Parts des Enseignants</h3>
              <table class="w-full text-left text-xs border border-gray-200 rounded-xl overflow-hidden whitespace-nowrap">
                <thead class="bg-gray-100 font-semibold text-gray-700 border-b border-gray-200">
                  <tr>
                    <th class="py-2 px-3">Enseignant</th>
                    <th class="py-2 px-3">Matière</th>
                    <th class="py-2 px-2 text-center">Séances</th>
                    <th class="py-2 px-3 text-right">C.A. Encaissé</th>
                    <th class="py-2 px-3 text-right font-bold">Part Due</th>
                    <th class="py-2 px-3 text-right text-emerald-700">Déjà Payé</th>
                    <th class="py-2 px-3 text-right text-red-600 font-bold">Reste à Régler</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="t in summaryData?.byTeacher || []" :key="t.id" class="avoid-break">
                    <td class="py-2 px-3 font-semibold text-gray-900">{{ t.name }}</td>
                    <td class="py-2 px-3 text-gray-600">{{ t.subject || 'Général' }}</td>
                    <td class="py-2 px-2 text-center font-bold">{{ t.sessionsCount }}</td>
                    <td class="py-2 px-3 text-right text-gray-600">{{ Number(t.revenueCollected).toFixed(2) }} DT</td>
                    <td class="py-2 px-3 text-right font-bold text-indigo-900">{{ Number(t.teacherShare).toFixed(2) }} DT</td>
                    <td class="py-2 px-3 text-right text-emerald-700">{{ Number(t.paidAmount).toFixed(2) }} DT</td>
                    <td class="py-2 px-3 text-right font-black" :class="t.remainingToPay > 0 ? 'text-red-600' : 'text-gray-400'">
                      {{ Number(t.remainingToPay).toFixed(2) }} DT
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Visa et Certification -->
            <div class="avoid-break grid grid-cols-1 md:grid-cols-2 gap-8 pt-6 border-t-2 border-gray-900 text-xs">
              <div>
                <p class="font-bold text-gray-900">Visa & Rapprochement Comptable :</p>
                <p class="text-[10px] text-gray-500 mt-1">Conforme au grand livre et aux journaux de caisse.</p>
                <div class="h-14 border-b border-dashed border-gray-400 mt-4"></div>
              </div>
              <div class="text-right">
                <p class="font-bold text-gray-900">Cachet & Signature de la Direction :</p>
                <p class="text-[10px] text-gray-500 mt-1">Document certifié pour bilan d'exploitation.</p>
                <div class="h-14 border-b border-dashed border-gray-400 mt-4"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 4 : EXPORTS OFFICIELS EXPERT-COMPTABLE (PDF & IMPRESSION A4) -->
    <!-- ============================================================== -->
    <div
      v-if="showExpertPdfModal"
      @click.self="showExpertPdfModal = false"
      class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs p-3 sm:p-6 flex justify-center items-start"
    >
      <div class="bg-white rounded-2xl border border-gray-200 shadow-2xl w-full max-w-5xl my-2 sm:my-6 overflow-hidden flex flex-col max-h-[94vh] animate-in fade-in zoom-in-95">
        <!-- Actions barre (Sticky Top) -->
        <div class="p-4 bg-gray-900 text-white flex items-center justify-between no-print print:hidden shrink-0 shadow-sm z-20">
          <div class="flex items-center gap-2">
            <FileText class="w-4 h-4 text-indigo-400" />
            <span class="text-xs sm:text-sm font-bold">{{ expertPdfModalTitle }}</span>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="printExpertReport"
              class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer"
            >
              <Printer class="w-3.5 h-3.5" />
              <span>Imprimer / PDF (A4)</span>
            </button>
            <button
              @click="showExpertPdfModal = false"
              class="text-gray-400 hover:text-white p-1 rounded-lg hover:bg-gray-800 transition-colors cursor-pointer"
              title="Fermer"
            >
              <X class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Corps Déroulant Imprimable -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-8 bg-gray-50/50 print:p-0 print:bg-white print:overflow-visible">
          <div id="expert-report-printable" class="p-6 sm:p-8 space-y-6 text-gray-900 bg-white rounded-xl border border-gray-200 print:border-none print:shadow-none print:p-0">
            <!-- En-tête officiel Centre d'études -->
            <div class="avoid-break flex items-start justify-between border-b-2 border-gray-900 pb-5">
              <div>
                <h1 class="text-xl font-black tracking-tight text-gray-900">CENTRE D'ÉTUDES & SOUTIEN SCOLAIRE</h1>
                <p class="text-xs text-gray-500 mt-0.5">Direction Financière & Comptabilité Générale — États d'Expertise</p>
                <p class="text-xs text-gray-500">Tunis, Tunisie • Document Officiel Conforme</p>
              </div>
              <div class="text-right">
                <span class="text-xs font-mono px-2.5 py-1 bg-gray-100 rounded-lg font-bold border border-gray-300">
                  {{ expertPdfDocRef }}
                </span>
                <p class="text-xs text-gray-500 mt-2">Édité le: {{ new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }) }}</p>
                <p class="text-xs text-gray-500">Période: <span class="font-bold text-gray-900">{{ selectedPeriodLabel }}</span></p>
              </div>
            </div>

            <!-- DOCUMENT 1 : GRAND LIVRE DES RECETTES -->
            <div v-if="expertPdfType === 'receipts'" class="space-y-6">
              <div class="avoid-break text-center py-2.5 bg-gray-50 rounded-xl border border-gray-200">
                <h2 class="text-sm font-black uppercase tracking-wider text-gray-900">GRAND LIVRE GÉNÉRAL DES RECETTES & ENCAISSEMENTS</h2>
                <p class="text-xs text-gray-600 mt-0.5">
                  Relevé exhaustif des règlements perçus (cours de soutien, élèves et garderie scolaire)
                </p>
              </div>

              <!-- KPIs Recettes -->
              <div class="avoid-break grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
                  <span class="text-gray-500 uppercase text-[10px] tracking-wider font-semibold">Total Encaissé</span>
                  <p class="text-lg font-black text-emerald-700 mt-0.5">{{ totalFilteredPaymentsAmount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
                  <p class="text-[10px] text-gray-500 mt-0.5">{{ filteredPaymentsForExport.length }} transactions</p>
                </div>
                <div class="p-3 bg-blue-50/70 rounded-xl border border-blue-200">
                  <span class="text-blue-700 uppercase text-[10px] tracking-wider font-semibold">Part Élèves (Cours)</span>
                  <p class="text-lg font-black text-blue-900 mt-0.5">{{ totalFilteredPaymentsStudents.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
                  <p class="text-[10px] text-blue-600 mt-0.5">Soutien scolaire</p>
                </div>
                <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-200">
                  <span class="text-amber-700 uppercase text-[10px] tracking-wider font-semibold">Part Garderie</span>
                  <p class="text-lg font-black text-amber-900 mt-0.5">{{ totalFilteredPaymentsDaycare.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
                  <p class="text-[10px] text-amber-600 mt-0.5">Services de garde</p>
                </div>
                <div class="p-3 bg-indigo-50/70 rounded-xl border border-indigo-200">
                  <span class="text-indigo-700 uppercase text-[10px] tracking-wider font-semibold">Règlements Espèces</span>
                  <p class="text-lg font-black text-indigo-900 mt-0.5">{{ totalCashPaymentsAmount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
                  <p class="text-[10px] text-indigo-600 mt-0.5">Caisse directe</p>
                </div>
              </div>

              <!-- Tableau Grand Livre Recettes -->
              <div class="avoid-break">
                <table class="w-full text-left text-xs border border-gray-200 rounded-xl overflow-hidden whitespace-nowrap">
                  <thead class="bg-gray-100 font-semibold text-gray-700 border-b border-gray-200">
                    <tr>
                      <th class="py-2.5 px-3">Date</th>
                      <th class="py-2.5 px-3">Réf / Reçu</th>
                      <th class="py-2.5 px-3">Élève / Enfant</th>
                      <th class="py-2.5 px-3">Mois</th>
                      <th class="py-2.5 px-3">Type</th>
                      <th class="py-2.5 px-3">Mode Règlement</th>
                      <th class="py-2.5 px-3 text-right font-bold">Montant (DT)</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr v-for="p in filteredPaymentsForExport" :key="p.id" class="avoid-break hover:bg-gray-50/50">
                      <td class="py-2 px-3 text-gray-700">{{ p.date || p.created_at?.substring(0, 10) || '—' }}</td>
                      <td class="py-2 px-3 font-mono text-[11px] text-gray-500">#REC-{{ String(p.id).padStart(5, '0') }}</td>
                      <td class="py-2 px-3 font-bold text-gray-900">{{ p.student_name || 'Élève inconnu' }}</td>
                      <td class="py-2 px-3 text-gray-600">{{ p.month || '—' }}</td>
                      <td class="py-2 px-3">
                        <span
                          class="px-2 py-0.5 rounded text-[10px] font-bold"
                          :class="p.daycare_child_id || p.is_garderie ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'"
                        >
                          {{ p.daycare_child_id || p.is_garderie ? 'Garderie' : (p.is_primaire ? 'Primaire' : (p.is_secondaire ? 'Secondaire' : 'Cours')) }}
                        </span>
                      </td>
                      <td class="py-2 px-3 text-gray-600 capitalize">{{ p.payment_method || 'Espèces' }}</td>
                      <td class="py-2 px-3 text-right font-black text-gray-900">{{ Number(p.amount || 0).toFixed(2) }} DT</td>
                    </tr>
                    <tr v-if="filteredPaymentsForExport.length === 0">
                      <td colspan="7" class="py-8 text-center text-gray-400">Aucun encaissement enregistré sur cette période</td>
                    </tr>
                  </tbody>
                  <tfoot v-if="filteredPaymentsForExport.length > 0" class="bg-gray-100 font-bold border-t-2 border-gray-300">
                    <tr>
                      <td colspan="6" class="py-2.5 px-3 text-right uppercase text-[11px] text-gray-700">Total Général des Recettes :</td>
                      <td class="py-2.5 px-3 text-right font-black text-emerald-700 text-sm">{{ totalFilteredPaymentsAmount.toFixed(2) }} DT</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <!-- DOCUMENT 2 : JOURNAL DES DÉPENSES -->
            <div v-else-if="expertPdfType === 'expenses'" class="space-y-6">
              <div class="avoid-break text-center py-2.5 bg-gray-50 rounded-xl border border-gray-200">
                <h2 class="text-sm font-black uppercase tracking-wider text-gray-900">JOURNAL GÉNÉRAL DES DÉPENSES & CHARGES D'EXPLOITATION</h2>
                <p class="text-xs text-gray-600 mt-0.5">
                  Livre comptable exhaustif des factures, loyers, salaires annexes et achats d'exploitation
                </p>
              </div>

              <!-- KPIs Dépenses -->
              <div class="avoid-break grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="p-3 bg-red-50/70 rounded-xl border border-red-200">
                  <span class="text-red-700 uppercase text-[10px] tracking-wider font-semibold">Total Dépenses</span>
                  <p class="text-lg font-black text-red-600 mt-0.5">{{ totalFilteredExpenses.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
                  <p class="text-[10px] text-red-600 mt-0.5">{{ filteredExpenses.length }} écritures</p>
                </div>
                <div class="p-3 bg-indigo-50/70 rounded-xl border border-indigo-200">
                  <span class="text-indigo-700 uppercase text-[10px] tracking-wider font-semibold">Charges Récurrentes</span>
                  <p class="text-lg font-black text-indigo-900 mt-0.5">{{ recurringExpensesAmount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
                  <p class="text-[10px] text-indigo-600 mt-0.5">Loyer, abonnements...</p>
                </div>
                <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-200">
                  <span class="text-amber-700 uppercase text-[10px] tracking-wider font-semibold">Fournitures & Entretien</span>
                  <p class="text-lg font-black text-amber-900 mt-0.5">{{ suppliesExpensesAmount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
                  <p class="text-[10px] text-amber-600 mt-0.5">Consommables</p>
                </div>
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
                  <span class="text-gray-500 uppercase text-[10px] tracking-wider font-semibold">Règlements Espèces</span>
                  <p class="text-lg font-black text-gray-900 mt-0.5">{{ cashExpensesAmount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} DT</p>
                  <p class="text-[10px] text-gray-500 mt-0.5">Sorties de caisse</p>
                </div>
              </div>

              <!-- Tableau Dépenses -->
              <div class="avoid-break">
                <table class="w-full text-left text-xs border border-gray-200 rounded-xl overflow-hidden whitespace-nowrap">
                  <thead class="bg-gray-100 font-semibold text-gray-700 border-b border-gray-200">
                    <tr>
                      <th class="py-2.5 px-3">Date</th>
                      <th class="py-2.5 px-3">Catégorie</th>
                      <th class="py-2.5 px-3">Libellé / Titre de dépense</th>
                      <th class="py-2.5 px-3">Réf / Justificatif</th>
                      <th class="py-2.5 px-3">Mode</th>
                      <th class="py-2.5 px-3 text-center">Récurrent</th>
                      <th class="py-2.5 px-3 text-right font-bold">Montant (DT)</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr v-for="e in filteredExpenses" :key="e.id" class="avoid-break hover:bg-gray-50/50">
                      <td class="py-2 px-3 text-gray-700">{{ e.date || '—' }}</td>
                      <td class="py-2 px-3">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border" :class="getCategoryBadgeClass(e.category)">
                          {{ getCategoryLabel(e.category) }}
                        </span>
                      </td>
                      <td class="py-2 px-3 font-bold text-gray-900">{{ e.title }}</td>
                      <td class="py-2 px-3 font-mono text-[11px] text-gray-500">{{ e.receipt_reference || '—' }}</td>
                      <td class="py-2 px-3 text-gray-600 capitalize">{{ e.payment_method || 'especes' }}</td>
                      <td class="py-2 px-3 text-center">
                        <span v-if="e.is_recurring" class="text-indigo-600 font-bold">Oui</span>
                        <span v-else class="text-gray-400">Non</span>
                      </td>
                      <td class="py-2 px-3 text-right font-black text-red-600">{{ Number(e.amount || 0).toFixed(2) }} DT</td>
                    </tr>
                    <tr v-if="filteredExpenses.length === 0">
                      <td colspan="7" class="py-8 text-center text-gray-400">Aucune dépense enregistrée sur cette période</td>
                    </tr>
                  </tbody>
                  <tfoot v-if="filteredExpenses.length > 0" class="bg-gray-100 font-bold border-t-2 border-gray-300">
                    <tr>
                      <td colspan="6" class="py-2.5 px-3 text-right uppercase text-[11px] text-gray-700">Total Général des Dépenses :</td>
                      <td class="py-2.5 px-3 text-right font-black text-red-600 text-sm">{{ totalFilteredExpenses.toFixed(2) }} DT</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <!-- DOCUMENT 3 : ÉTATS DES HONORAIRES PROFESSEURS -->
            <div v-else-if="expertPdfType === 'teachers'" class="space-y-6">
              <div class="avoid-break text-center py-2.5 bg-gray-50 rounded-xl border border-gray-200">
                <h2 class="text-sm font-black uppercase tracking-wider text-gray-900">ÉTATS CERTIFIÉS DES HONORAIRES & RÉMUNÉRATIONS DES ENSEIGNANTS</h2>
                <p class="text-xs text-gray-600 mt-0.5">
                  Récapitulatif des cours, quotas de séances, montants versés et soldes d'honoraires par professeur
                </p>
              </div>

              <!-- KPIs Enseignants -->
              <div class="avoid-break grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
                  <span class="text-gray-500 uppercase text-[10px] tracking-wider font-semibold">CA Cours Encaissé</span>
                  <p class="text-lg font-black text-gray-900 mt-0.5">{{ totalTeachersGross.toFixed(2) }} DT</p>
                  <p class="text-[10px] text-gray-500 mt-0.5">{{ (summaryData?.byTeacher || []).length }} enseignants</p>
                </div>
                <div class="p-3 bg-indigo-50/70 rounded-xl border border-indigo-200">
                  <span class="text-indigo-700 uppercase text-[10px] tracking-wider font-semibold">Total Honoraires Dûs</span>
                  <p class="text-lg font-black text-indigo-900 mt-0.5">{{ Number(kpis.totalTeacherPayouts || 0).toFixed(2) }} DT</p>
                  <p class="text-[10px] text-indigo-600 mt-0.5">Rémunération totale</p>
                </div>
                <div class="p-3 bg-emerald-50/70 rounded-xl border border-emerald-200">
                  <span class="text-emerald-700 uppercase text-[10px] tracking-wider font-semibold">Déjà Versé</span>
                  <p class="text-lg font-black text-emerald-700 mt-0.5">{{ totalTeachersPaid.toFixed(2) }} DT</p>
                  <p class="text-[10px] text-emerald-600 mt-0.5">Règlements effectués</p>
                </div>
                <div class="p-3 bg-red-50/70 rounded-xl border border-red-200">
                  <span class="text-red-700 uppercase text-[10px] tracking-wider font-semibold">Reste à Régler</span>
                  <p class="text-lg font-black text-red-600 mt-0.5">{{ totalTeachersRemaining.toFixed(2) }} DT</p>
                  <p class="text-[10px] text-red-600 mt-0.5">Soldes en attente</p>
                </div>
              </div>

              <!-- Tableau Honoraires Enseignants -->
              <div class="avoid-break">
                <table class="w-full text-left text-xs border border-gray-200 rounded-xl overflow-hidden whitespace-nowrap">
                  <thead class="bg-gray-100 font-semibold text-gray-700 border-b border-gray-200">
                    <tr>
                      <th class="py-2.5 px-3">Enseignant</th>
                      <th class="py-2.5 px-3">Matière</th>
                      <th class="py-2.5 px-3">Formule Appliquée</th>
                      <th class="py-2.5 px-2 text-center">Séances</th>
                      <th class="py-2.5 px-3 text-right">CA Encaissé</th>
                      <th class="py-2.5 px-3 text-right font-bold text-indigo-900">Part Due (DT)</th>
                      <th class="py-2.5 px-3 text-right text-emerald-700">Déjà Versé</th>
                      <th class="py-2.5 px-3 text-right font-bold">Reste à Payer</th>
                      <th class="py-2.5 px-3 text-center">Statut</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr v-for="t in summaryData?.byTeacher || []" :key="t.id" class="avoid-break hover:bg-gray-50/50">
                      <td class="py-2 px-3 font-bold text-gray-900">{{ t.name }}</td>
                      <td class="py-2 px-3 text-gray-600">{{ t.subject || 'Général' }}</td>
                      <td class="py-2 px-3 text-gray-600">
                        <span v-if="t.remunerationType === 'percentage'" class="text-indigo-600 font-medium">Formule C ({{ t.ratePercentage || 70 }}%)</span>
                        <span v-else-if="t.remunerationType === 'fixed_center'" class="text-emerald-700 font-medium">Formule A (Idara {{ t.centerFixedPerStudent || 20 }} DT/él)</span>
                        <span v-else class="text-purple-700 font-medium">Formule B (Fixe {{ t.fixedStudentRate || 15 }} DT/él)</span>
                      </td>
                      <td class="py-2 px-2 text-center font-bold text-gray-800">{{ t.sessionsCount }}</td>
                      <td class="py-2 px-3 text-right text-gray-600">{{ Number(t.revenueCollected || 0).toFixed(2) }} DT</td>
                      <td class="py-2 px-3 text-right font-black text-indigo-900">{{ Number(t.teacherShare || 0).toFixed(2) }} DT</td>
                      <td class="py-2 px-3 text-right text-emerald-700 font-semibold">{{ Number(t.paidAmount || 0).toFixed(2) }} DT</td>
                      <td class="py-2 px-3 text-right font-black" :class="t.remainingToPay > 0 ? 'text-red-600' : 'text-gray-400'">
                        {{ Number(t.remainingToPay || 0).toFixed(2) }} DT
                      </td>
                      <td class="py-2 px-3 text-center">
                        <span
                          class="px-2 py-0.5 rounded text-[10px] font-bold"
                          :class="t.payoutStatus === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                        >
                          {{ t.payoutStatus === 'paid' ? 'Réglé' : 'En attente' }}
                        </span>
                      </td>
                    </tr>
                    <tr v-if="(summaryData?.byTeacher || []).length === 0">
                      <td colspan="9" class="py-8 text-center text-gray-400">Aucun enseignant enregistré sur cette période</td>
                    </tr>
                  </tbody>
                  <tfoot v-if="(summaryData?.byTeacher || []).length > 0" class="bg-gray-100 font-bold border-t-2 border-gray-300">
                    <tr>
                      <td colspan="5" class="py-2.5 px-3 text-right uppercase text-[11px] text-gray-700">Totaux des Honoraires :</td>
                      <td class="py-2.5 px-3 text-right font-black text-indigo-900 text-sm">{{ Number(kpis.totalTeacherPayouts || 0).toFixed(2) }} DT</td>
                      <td class="py-2.5 px-3 text-right text-emerald-700 text-sm">{{ totalTeachersPaid.toFixed(2) }} DT</td>
                      <td class="py-2.5 px-3 text-right font-black text-red-600 text-sm">{{ totalTeachersRemaining.toFixed(2) }} DT</td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <!-- Visa et Certification pour l'Expert-Comptable -->
            <div class="avoid-break grid grid-cols-1 md:grid-cols-2 gap-8 pt-6 border-t-2 border-gray-900 text-xs">
              <div>
                <p class="font-bold text-gray-900">Visa & Rapprochement Comptable :</p>
                <p class="text-[10px] text-gray-500 mt-1">Conforme aux livres auxiliaires, reçus de paiement et fiches d'émargement.</p>
                <div class="h-14 border-b border-dashed border-gray-400 mt-4"></div>
              </div>
              <div class="text-right">
                <p class="font-bold text-gray-900">Cachet & Signature de la Direction :</p>
                <p class="text-[10px] text-gray-500 mt-1">Document certifié pour audit et déclaration comptable.</p>
                <div class="h-14 border-b border-dashed border-gray-400 mt-4"></div>
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
  TrendingUp, TrendingDown, DollarSign, Users, Calendar,
  BarChart3, Plus, Download, Printer, Lock, Unlock,
  MessageCircle, RefreshCw, FileText, CheckCircle2, ShieldCheck,
  ShieldAlert, BookOpen, GraduationCap, ArrowRight, Zap, X, Pencil, Trash2
} from 'lucide-vue-next';
import api from '../utils/api';
import { printIsolatedElement } from '../utils/printHelper';

// ─── État Global & Rôle ───────────────────────────────────────────────────────
const currentUser = ref<{ role?: string; name?: string } | null>(null);
try {
  const saved = localStorage.getItem('user');
  if (saved) currentUser.value = JSON.parse(saved);
} catch {}

const isDirector = computed(() => currentUser.value?.role === 'director');

// ─── Onglets Métiers de la Comptabilité ────────────────────────────────────────
type AccountingSubTab = 'overview' | 'expenses' | 'teachers' | 'dues' | 'cash_audit' | 'closing';

const activeSubTab = ref<AccountingSubTab>('overview');

const accountingTabs = computed(() => [
  { id: 'overview' as AccountingSubTab, label: "Vue d'ensemble & KPIs", icon: BarChart3 },
  { id: 'expenses' as AccountingSubTab, label: "Dépenses Réelles", icon: TrendingDown, badge: expensesList.value.length ? `${expensesList.value.length}` : undefined },
  { id: 'teachers' as AccountingSubTab, label: "Parts Enseignants", icon: Users },
  { id: 'dues' as AccountingSubTab, label: "Reste à Encaisser", icon: MessageCircle, badge: summaryData.value?.topDebtors?.length ? `${summaryData.value.topDebtors.length}` : undefined },
  { id: 'cash_audit' as AccountingSubTab, label: "Journal Caisse & Traçabilité", icon: ShieldCheck },
  { id: 'closing' as AccountingSubTab, label: "Clôtures & Exports", icon: Lock },
]);

// ─── Période & Filtres ────────────────────────────────────────────────────────
const selectedPeriod = ref<'12months' | 'school_year' | 'current_month'>('12months');
const selectedSchoolYear = ref('2025-2026');
const selectedMonth = ref(new Date().toISOString().substring(0, 7));
const journalDate = ref(new Date().toISOString().substring(0, 10));

function changePeriod(mode: '12months' | 'school_year' | 'current_month') {
  selectedPeriod.value = mode;
  fetchSummary();
}

// ─── Données API ─────────────────────────────────────────────────────────────
const isLoading = ref(false);
const summaryData = ref<any>(null);
const expensesList = ref<any[]>([]);
const auditLogs = ref<any[]>([]);
const paymentsList = ref<any[]>([]);

const kpis = computed(() => summaryData.value?.kpis || {});

async function fetchPayments() {
  try {
    const res = await api.get('/api/payments');
    paymentsList.value = Array.isArray(res.data) ? res.data : (res.data?.data || []);
  } catch (e) {
    console.error('Erreur chargement paiements pour exports:', e);
  }
}

// ─── Chargement de la Synthèse ───────────────────────────────────────────────
async function fetchSummary() {
  if (!isDirector.value) return;
  isLoading.value = true;
  try {
    const params: Record<string, string> = {
      period: selectedPeriod.value,
      school_year: selectedSchoolYear.value,
      month: selectedMonth.value,
      journal_date: journalDate.value,
    };

    const res = await api.get('/api/accounting/summary', { params });
    summaryData.value = res.data;
  } catch (e) {
    console.error('Erreur chargement synthèse comptable:', e);
  } finally {
    isLoading.value = false;
  }
}

// ─── Chargement des Dépenses ──────────────────────────────────────────────────
async function fetchExpenses() {
  try {
    const res = await api.get('/api/accounting/expenses');
    expensesList.value = res.data?.data || [];
  } catch (e) {
    console.error('Erreur chargement dépenses:', e);
  }
}

// ─── Chargement des Audits ───────────────────────────────────────────────────
async function fetchAuditLogs() {
  try {
    const res = await api.get('/api/accounting/audit-logs');
    auditLogs.value = res.data || [];
  } catch (e) {
    console.error('Erreur chargement audit logs:', e);
  }
}

// ─── Filtres Dépenses ─────────────────────────────────────────────────────────
const expenseSearch = ref('');
const expenseCategoryFilter = ref('');
const expensePaymentMethodFilter = ref('');

const filteredExpenses = computed(() => {
  return expensesList.value.filter(exp => {
    if (expenseCategoryFilter.value && exp.category !== expenseCategoryFilter.value) return false;
    if (expensePaymentMethodFilter.value && exp.payment_method !== expensePaymentMethodFilter.value) return false;
    if (expenseSearch.value) {
      const q = expenseSearch.value.toLowerCase();
      const matchTitle = exp.title?.toLowerCase().includes(q);
      const matchRef = exp.receipt_reference?.toLowerCase().includes(q);
      const matchNotes = exp.notes?.toLowerCase().includes(q);
      if (!matchTitle && !matchRef && !matchNotes) return false;
    }
    return true;
  });
});

const totalFilteredExpenses = computed(() => {
  return filteredExpenses.value.reduce((sum, e) => sum + Number(e.amount || 0), 0);
});

// ─── Formulaire Ajout / Édition Dépense ───────────────────────────────────────
const showExpenseModal = ref(false);
const editingExpenseId = ref<number | null>(null);
const isSubmittingExpense = ref(false);

const expenseForm = ref({
  title: '',
  category: 'fournitures',
  amount: 0,
  date: new Date().toISOString().substring(0, 10),
  payment_method: 'especes',
  receipt_reference: '',
  is_recurring: false,
  notes: '',
});

function openExpenseModal(expenseToEdit?: any) {
  if (expenseToEdit) {
    editingExpenseId.value = expenseToEdit.id;
    expenseForm.value = {
      title: expenseToEdit.title,
      category: expenseToEdit.category,
      amount: Number(expenseToEdit.amount),
      date: expenseToEdit.date,
      payment_method: expenseToEdit.payment_method || 'especes',
      receipt_reference: expenseToEdit.receipt_reference || '',
      is_recurring: Boolean(expenseToEdit.is_recurring),
      notes: expenseToEdit.notes || '',
    };
  } else {
    editingExpenseId.value = null;
    expenseForm.value = {
      title: '',
      category: 'fournitures',
      amount: 0,
      date: new Date().toISOString().substring(0, 10),
      payment_method: 'especes',
      receipt_reference: '',
      is_recurring: false,
      notes: '',
    };
  }
  showExpenseModal.value = true;
}

async function saveExpense() {
  isSubmittingExpense.value = true;
  try {
    if (editingExpenseId.value) {
      await api.put(`/api/accounting/expenses/${editingExpenseId.value}`, expenseForm.value);
    } else {
      await api.post('/api/accounting/expenses', expenseForm.value);
    }
    showExpenseModal.value = false;
    await Promise.all([fetchExpenses(), fetchSummary()]);
  } catch (e: any) {
    alert('Erreur enregistrement dépense: ' + (e.response?.data?.message || e.message));
  } finally {
    isSubmittingExpense.value = false;
  }
}

async function deleteExpense(id: number) {
  if (!confirm('Confirmez-vous la suppression de cette dépense ?')) return;
  try {
    await api.delete(`/api/accounting/expenses/${id}`);
    await Promise.all([fetchExpenses(), fetchSummary()]);
  } catch (e: any) {
    alert('Erreur suppression dépense: ' + (e.response?.data?.message || e.message));
  }
}

async function generateRecurringExpenses() {
  try {
    const res = await api.post('/api/accounting/expenses/generate-recurring', {
      month: selectedMonth.value,
    });
    alert(res.data?.message || 'Dépenses récurrentes traitées.');
    await Promise.all([fetchExpenses(), fetchSummary()]);
  } catch (e: any) {
    alert('Erreur génération récurrentes: ' + (e.response?.data?.message || e.message));
  }
}

// ─── Fiche de Paie Imprimable ─────────────────────────────────────────────────
const showPayslipModal = ref(false);
const activePayslip = ref<any>(null);

async function openPayslip(teacherId: number) {
  try {
    const res = await api.get(`/api/accounting/teachers/${teacherId}/payslip`, {
      params: { month: selectedMonth.value }
    });
    activePayslip.value = res.data;
    showPayslipModal.value = true;
  } catch (e: any) {
    alert('Erreur génération fiche de paie: ' + (e.response?.data?.message || e.message));
  }
}

async function toggleTeacherPayoutStatus(teacher: any) {
  const isPaid = teacher.payoutStatus === 'paid' || teacher.payoutStatus === 'taken';
  const newStatus = isPaid ? 'pending' : 'paid';
  const label = newStatus === 'paid' ? 'marquer comme RÉGLÉ' : 'remettre en ATTENTE';
  if (!confirm(`Souhaitez-vous ${label} la part de ${teacher.name} (${teacher.teacherShare} DT) ?`)) return;

  try {
    await api.post(`/api/teachers/${teacher.id}/toggle-payout`, {
      status: newStatus,
      amount: teacher.teacherShare,
      date: new Date().toISOString().substring(0, 10),
      month: selectedMonth.value,
    });
    await fetchSummary();
  } catch (e: any) {
    alert('Erreur mise à jour versement: ' + (e.response?.data?.message || e.message));
  }
}

// ─── Clôture Mensuelle ────────────────────────────────────────────────────────
async function closeCurrentMonth() {
  if (!confirm(`Confirmez-vous la clôture comptable du mois ${selectedMonth.value} ? Cette opération fige le bilan mensuel.`)) return;

  try {
    const res = await api.post('/api/accounting/close-month', {
      month: selectedMonth.value,
      school_year: selectedSchoolYear.value,
    });
    alert(res.data?.message || 'Mois clôturé avec succès.');
    await fetchSummary();
  } catch (e: any) {
    alert('Erreur clôture: ' + (e.response?.data?.message || e.message));
  }
}

async function reopenCurrentMonth() {
  if (!confirm(`Voulez-vous réouvrir le mois ${selectedMonth.value} pour régularisation ?`)) return;

  try {
    const res = await api.post('/api/accounting/reopen-month', {
      month: selectedMonth.value,
    });
    alert(res.data?.message || 'Mois réouvert.');
    await fetchSummary();
  } catch (e: any) {
    alert('Erreur réouverture: ' + (e.response?.data?.message || e.message));
  }
}

// ─── Exports Comptables (Excel / CSV) ─────────────────────────────────────────
function exportCsv(type = 'all') {
  let rows: string[][] = [];
  let filename = `comptabilite-${selectedMonth.value}.csv`;

  if (type === 'expenses') {
    filename = `depenses-${selectedMonth.value}.csv`;
    rows.push(['Date', 'Titre', 'Categorie', 'Mode Paiement', 'Reference', 'Recurrent', 'Montant (DT)', 'Notes']);
    expensesList.value.forEach(e => {
      rows.push([
        e.date || '',
        `"${(e.title || '').replace(/"/g, '""')}"`,
        e.category || '',
        e.payment_method || '',
        e.receipt_reference || '',
        e.is_recurring ? 'Oui' : 'Non',
        String(e.amount || 0),
        `"${(e.notes || '').replace(/"/g, '""')}"`,
      ]);
    });
  } else if (type === 'teachers') {
    filename = `honoraires-enseignants-${selectedMonth.value}.csv`;
    rows.push(['Enseignant', 'Matière', 'Formule', 'Séances', 'CA Encaissé (DT)', 'Part Dûe (DT)', 'Déjà Versé (DT)', 'Reste à Payer (DT)', 'Statut']);
    (summaryData.value?.byTeacher || []).forEach((t: any) => {
      rows.push([
        `"${t.name}"`,
        t.subject || '',
        t.remunerationType === 'percentage'
          ? `Formule C (${t.ratePercentage || 70}%)`
          : t.remunerationType === 'fixed_center'
            ? `Formule A (Idara ${t.centerFixedPerStudent || 20} DT/él)`
            : `Formule B (${t.fixedStudentRate || 15} DT/él)`,
        String(t.sessionsCount),
        String(t.revenueCollected),
        String(t.teacherShare),
        String(t.paidAmount),
        String(t.remainingToPay),
        t.payoutStatus,
      ]);
    });
  } else {
    // Grand Livre Récapitulatif
    rows.push(['Mois', 'Recettes Élèves (DT)', 'Recettes Garderie (DT)', 'Recettes Totales (DT)', 'Dépenses (DT)', 'Parts Enseignants (DT)', 'Bénéfice Net (DT)']);
    (summaryData.value?.monthsTrend || []).forEach((m: any) => {
      rows.push([
        m.month,
        String(m.students),
        String(m.daycare),
        String(m.totalRevenue),
        String(m.expenses),
        String(m.teacherPayouts),
        String(m.netProfit),
      ]);
    });
  }

  const csvContent = '\uFEFF' + rows.map(r => r.join(';')).join('\r\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

const showReportPrintModal = ref(false);

function triggerPrint() {
  showReportPrintModal.value = true;
}

function printElement(elementId: string) {
  const docTitle = elementId === 'payslip-printable'
    ? `Fiche de Paie - ${activePayslip.value?.teacher?.name || 'Enseignant'}`
    : `Bilan Comptable & Financier - ${selectedMonth.value || 'Annuel'}`;
  printIsolatedElement(elementId, docTitle);
}

// ─── Modal & Fonctions d'Exports Expert-Comptable (PDF / A4) ─────────────────
const showExpertPdfModal = ref(false);
const expertPdfType = ref<'receipts' | 'expenses' | 'teachers'>('receipts');

const selectedPeriodLabel = computed(() => {
  if (selectedPeriod.value === 'school_year') return `Année Scolaire ${selectedSchoolYear.value}`;
  if (selectedPeriod.value === 'current_month') return `Mois ${selectedMonth.value}`;
  return '12 derniers mois';
});

const expertPdfModalTitle = computed(() => {
  if (expertPdfType.value === 'receipts') return 'Grand Livre des Recettes (Export PDF & Impression A4)';
  if (expertPdfType.value === 'expenses') return 'Journal Général des Dépenses (Export PDF & Impression A4)';
  return 'États des Honoraires Enseignants (Export PDF & Impression A4)';
});

const expertPdfDocRef = computed(() => {
  const m = selectedMonth.value || 'ANNUEL';
  if (expertPdfType.value === 'receipts') return `GL-RECETTES-${m}`;
  if (expertPdfType.value === 'expenses') return `JD-DEPENSES-${m}`;
  return `HONORAIRES-${m}`;
});

function openExpertPdfModal(type: 'receipts' | 'expenses' | 'teachers') {
  expertPdfType.value = type;
  showExpertPdfModal.value = true;
  if (type === 'receipts' && paymentsList.value.length === 0) {
    fetchPayments();
  }
}

function printExpertReport() {
  const titles = {
    receipts: `Grand Livre des Recettes - ${selectedMonth.value || 'Période'}`,
    expenses: `Journal des Dépenses - ${selectedMonth.value || 'Période'}`,
    teachers: `États des Honoraires Enseignants - ${selectedMonth.value || 'Période'}`,
  };
  printIsolatedElement('expert-report-printable', titles[expertPdfType.value]);
}

// ─── Calculs spécifiques pour les états comptables imprimables ────────────────
const filteredPaymentsForExport = computed(() => {
  return paymentsList.value.filter(p => {
    if (selectedPeriod.value === 'current_month') {
      const pMonth = p.month || (p.date ? p.date.substring(0, 7) : '');
      return pMonth === selectedMonth.value;
    }
    if (summaryData.value?.range?.start && summaryData.value?.range?.end && p.date) {
      return p.date >= summaryData.value.range.start && p.date <= summaryData.value.range.end;
    }
    return true;
  });
});

const totalFilteredPaymentsAmount = computed(() => {
  return filteredPaymentsForExport.value.reduce((sum, p) => sum + Number(p.amount || 0), 0);
});

const totalFilteredPaymentsStudents = computed(() => {
  return filteredPaymentsForExport.value
    .filter(p => !p.daycare_child_id && !p.is_garderie)
    .reduce((sum, p) => sum + Number(p.amount || 0), 0);
});

const totalFilteredPaymentsDaycare = computed(() => {
  return filteredPaymentsForExport.value
    .filter(p => p.daycare_child_id || p.is_garderie || (p.student_name || '').toLowerCase().includes('garde'))
    .reduce((sum, p) => sum + Number(p.amount || 0), 0);
});

const totalCashPaymentsAmount = computed(() => {
  return filteredPaymentsForExport.value
    .filter(p => (p.payment_method || 'especes').toLowerCase() === 'especes')
    .reduce((sum, p) => sum + Number(p.amount || 0), 0);
});

const recurringExpensesAmount = computed(() => {
  return filteredExpenses.value
    .filter(e => e.is_recurring)
    .reduce((sum, e) => sum + Number(e.amount || 0), 0);
});

const suppliesExpensesAmount = computed(() => {
  return filteredExpenses.value
    .filter(e => e.category === 'fournitures' || e.category === 'entretien')
    .reduce((sum, e) => sum + Number(e.amount || 0), 0);
});

const cashExpensesAmount = computed(() => {
  return filteredExpenses.value
    .filter(e => (e.payment_method || 'especes').toLowerCase() === 'especes')
    .reduce((sum, e) => sum + Number(e.amount || 0), 0);
});

const totalTeachersPaid = computed(() => {
  return (summaryData.value?.byTeacher || []).reduce((sum: number, t: any) => sum + Number(t.paidAmount || 0), 0);
});

const totalTeachersRemaining = computed(() => {
  return (summaryData.value?.byTeacher || []).reduce((sum: number, t: any) => sum + Number(t.remainingToPay || 0), 0);
});

// ─── Helpers Graphiques & Badges ──────────────────────────────────────────────
const categoryColors: Record<string, string> = {
  loyer: '#4F46E5',        // Indigo
  électricité: '#F59E0B',  // Amber
  fournitures: '#10B981',  // Emerald
  entretien: '#06B6D4',    // Cyan
  publicité: '#EC4899',    // Pink
  internet_tel: '#8B5CF6', // Purple
  salaires: '#3B82F6',     // Blue
  autres: '#6B7280',       // Gray
};

const expenseCategoriesSummary = computed(() => {
  return (summaryData.value?.expenseDonut || []).map((cat: any) => ({
    ...cat,
    color: categoryColors[cat.category] || '#9CA3AF',
  }));
});

const expenseDonutSlices = computed(() => {
  const cats = expenseCategoriesSummary.value;
  const circ = 2 * Math.PI * 38;
  let currentOffset = 0;

  return cats.map((cat: any) => {
    const strokeLength = ((cat.percentage || 0) / 100) * circ;
    const strokeOffset = -currentOffset;
    currentOffset += strokeLength;

    return {
      ...cat,
      strokeLength,
      strokeOffset,
    };
  });
});

// Points pour le graphique mensuel SVG
const chartPoints = computed(() => {
  const trend = summaryData.value?.monthsTrend || [];
  if (trend.length === 0) return [];

  const maxVal = Math.max(...trend.map((t: any) => Math.max(t.totalRevenue, t.expenses, 1000)), 5000);
  const minX = 60;
  const maxX = 560;
  const baseY = 240;
  const topY = 40;
  const step = trend.length > 1 ? (maxX - minX) / (trend.length - 1) : 0;

  return trend.map((t: any, idx: number) => {
    const x = minX + idx * step;
    const revenueY = baseY - (t.totalRevenue / maxVal) * (baseY - topY);
    const expenseY = baseY - (t.expenses / maxVal) * (baseY - topY);
    const profitY = baseY - (Math.max(0, t.netProfit) / maxVal) * (baseY - topY);

    return {
      x,
      label: t.label,
      revenue: t.totalRevenue,
      expenses: t.expenses,
      students: t.students,
      daycare: t.daycare,
      teacherPayouts: t.teacherPayouts,
      netProfit: t.netProfit,
      revenueY,
      expenseY,
      profitY,
    };
  });
});

const profitLinePath = computed(() => {
  const pts = chartPoints.value;
  if (pts.length === 0) return '';
  return pts.reduce((acc: string, p: any, idx: number) => {
    return idx === 0 ? `M ${p.x} ${p.profitY}` : `${acc} L ${p.x} ${p.profitY}`;
  }, '');
});

// Totaux Enseignants
const totalTeachersGross = computed(() => {
  return (summaryData.value?.byTeacher || []).reduce((acc: number, t: any) => acc + (t.revenueCollected || 0), 0);
});

const totalCenterTeacherMargin = computed(() => {
  return (summaryData.value?.byTeacher || []).reduce((acc: number, t: any) => acc + (t.centerMargin || 0), 0);
});

// ─── Formatage Texte & Dates ──────────────────────────────────────────────────
function getCategoryLabel(cat: string): string {
  const map: Record<string, string> = {
    loyer: 'Loyer',
    électricité: 'Électricité / Eau',
    fournitures: 'Fournitures & Copies',
    entretien: 'Entretien',
    publicité: 'Publicité & Marketing',
    internet_tel: 'Internet & Tel',
    salaires: 'Salaires Annexes',
    autres: 'Autres Dépenses',
  };
  return map[cat] || cat;
}

function getCategoryBadgeClass(cat: string): string {
  const map: Record<string, string> = {
    loyer: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    électricité: 'bg-amber-50 text-amber-700 border-amber-200',
    fournitures: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    entretien: 'bg-cyan-50 text-cyan-700 border-cyan-200',
    publicité: 'bg-pink-50 text-pink-700 border-pink-200',
    internet_tel: 'bg-purple-50 text-purple-700 border-purple-200',
    salaires: 'bg-blue-50 text-blue-700 border-blue-200',
    autres: 'bg-gray-50 text-gray-700 border-gray-200',
  };
  return map[cat] || 'bg-gray-50 text-gray-700 border-gray-200';
}

function formatAuditAction(act: string): string {
  const map: Record<string, string> = {
    created: 'Paiement Enregistré',
    updated: 'Modification',
    marked_paid: 'Validé Payé',
    deleted: 'Paiement Supprimé',
  };
  return map[act] || act;
}

function getAuditActionBadge(act: string): string {
  const map: Record<string, string> = {
    created: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    updated: 'bg-blue-50 text-blue-700 border-blue-200',
    marked_paid: 'bg-purple-50 text-purple-700 border-purple-200',
    deleted: 'bg-red-50 text-red-700 border-red-200',
  };
  return map[act] || 'bg-gray-50 text-gray-700 border-gray-200';
}

function formatLogDate(d: string): string {
  if (!d) return '—';
  try {
    const date = new Date(d);
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
  } catch {
    return d;
  }
}

function formatDateTime(d: string): string {
  if (!d) return '—';
  try {
    const date = new Date(d);
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  } catch {
    return d;
  }
}

// ─── Initialisation ───────────────────────────────────────────────────────────
onMounted(async () => {
  await Promise.all([
    fetchSummary(),
    fetchExpenses(),
    fetchAuditLogs(),
    fetchPayments(),
  ]);
});
</script>

<style scoped>
@media print {
  /* Cache tous les éléments de l'interface sauf le document actif */
  body * {
    visibility: hidden;
  }
  .no-print, .no-print * {
    display: none !important;
  }

  /* Si Fiche de paie ouverte */
  #payslip-printable, #payslip-printable * {
    visibility: visible;
  }
  #payslip-printable {
    position: static !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    background: white !important;
    border: none !important;
    box-shadow: none !important;
  }

  /* Si Bilan Financier ouvert */
  #financial-report-printable, #financial-report-printable * {
    visibility: visible;
  }
  #financial-report-printable {
    position: static !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    background: white !important;
    border: none !important;
    box-shadow: none !important;
  }

  /* Si Document Expert-Comptable ouvert (Grand Livre, Dépenses, Honoraires) */
  #expert-report-printable, #expert-report-printable * {
    visibility: visible;
  }
  #expert-report-printable {
    position: static !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    background: white !important;
    border: none !important;
    box-shadow: none !important;
  }
}
</style>
