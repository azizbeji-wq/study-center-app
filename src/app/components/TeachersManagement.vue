<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Gestion des Enseignants</h2>
        <p class="text-sm text-gray-500 mt-1">
          {{ teachers.length }} enseignants • Rémunération par séance (% ou montant fixe)
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-if="isDirector"
          @click="openExtractModal()"
          class="flex items-center gap-2 px-3.5 py-2 bg-indigo-50 text-indigo-700 font-semibold rounded-lg hover:bg-indigo-100 transition-colors border border-indigo-200 text-sm shadow-xs"
          title="Exporter un extrait officiel des paiements pour les enseignants (PDF / Excel)"
        >
          <FileText class="w-4 h-4 text-indigo-600" />
          <span>Extraits de Paiements</span>
        </button>

        <button
          v-if="isDirector"
          @click="showDirectorSharesModal = true"
          class="flex items-center gap-2 px-3.5 py-2 bg-emerald-50 text-emerald-700 font-semibold rounded-lg hover:bg-emerald-100 transition-colors border border-emerald-200 text-sm shadow-xs"
          title="Consulter le récapitulatif détaillé des parts professeurs"
        >
          <DollarSign class="w-4 h-4 text-emerald-600" />
          <span>Bilan des Parts</span>
        </button>

        <button
          @click="openAddModal"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
        >
          <Plus class="w-4 h-4" />
          Ajouter un enseignant
        </button>
      </div>
    </div>

    <!-- Director Financial Summary Banner -->
    <div
      v-if="isDirector"
      class="bg-linear-to-r from-emerald-50 via-teal-50 to-indigo-50 border border-emerald-200/80 rounded-2xl p-4 shadow-xs"
    >
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3 pb-3 border-b border-emerald-200/50">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-2xs">
            💰
          </div>
          <div>
            <h3 class="text-sm font-bold text-gray-900">Espace Directeur • Répartition Financière des Enseignants</h3>
            <p class="text-xs text-gray-600">Suivi mensuel de la part de chaque enseignant et des marges générées pour le centre.</p>
          </div>
        </div>
        <button
          @click="showDirectorSharesModal = true"
          class="text-xs font-bold text-emerald-800 bg-white px-3 py-1.5 rounded-lg border border-emerald-200 hover:bg-emerald-100 transition-colors shadow-2xs self-start sm:self-auto flex items-center gap-1.5"
        >
          <span>Voir le tableau détaillé</span>
          <span>→</span>
        </button>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
        <div class="bg-white/80 backdrop-blur-xs p-3 rounded-xl border border-emerald-200/60">
          <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">C.A. Brut Estimé</p>
          <p class="text-lg font-extrabold text-gray-900 mt-0.5">{{ totalGrossRevenue.toLocaleString('fr-FR') }} TND</p>
          <p class="text-[10px] text-gray-500">Revenus des cours</p>
        </div>

        <div class="bg-emerald-600 text-white p-3 rounded-xl shadow-xs">
          <p class="text-[11px] font-bold text-emerald-100 uppercase tracking-wider">Part Totale Enseignants</p>
          <p class="text-lg font-black mt-0.5">{{ totalTeachersShare.toLocaleString('fr-FR') }} TND</p>
          <p class="text-[10px] text-emerald-200">À reverser aux professeurs</p>
        </div>

        <div class="bg-white/80 backdrop-blur-xs p-3 rounded-xl border border-indigo-200/60">
          <p class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider">Part Nette Centre</p>
          <p class="text-lg font-extrabold text-indigo-900 mt-0.5">{{ totalCenterShare.toLocaleString('fr-FR') }} TND</p>
          <p class="text-[10px] text-indigo-500">Marge conservée</p>
        </div>

        <div class="bg-white/80 backdrop-blur-xs p-3 rounded-xl border border-teal-200/60">
          <p class="text-[11px] font-bold text-teal-700 uppercase tracking-wider">Moyenne / Professeur</p>
          <p class="text-lg font-extrabold text-teal-900 mt-0.5">{{ avgTeacherShare.toLocaleString('fr-FR') }} TND</p>
          <p class="text-[10px] text-teal-600">Sur {{ teachers.length }} enseignant(s)</p>
        </div>
      </div>
    </div>

    <!-- Search and Director Payout Filters -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
        <input
          type="text"
          placeholder="Rechercher par nom ou matière..."
          v-model="searchTerm"
          class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-base shadow-sm"
        />
      </div>

      <!-- Director Payout Status Filter -->
      <div v-if="isDirector" class="inline-flex rounded-xl bg-gray-200/80 p-1 border border-gray-300/60 shrink-0">
        <button
          type="button"
          @click="payoutFilter = 'all'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
            payoutFilter === 'all'
              ? 'bg-white text-indigo-950 shadow-xs'
              : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          Tous ({{ teachers.length }})
        </button>
        <button
          type="button"
          @click="payoutFilter = 'paid'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1 cursor-pointer',
            payoutFilter === 'paid'
              ? 'bg-emerald-600 text-white shadow-xs'
              : 'text-emerald-800 hover:text-emerald-950'
          ]"
        >
          <Check class="w-3.5 h-3.5" />
          <span>A pris son argent ({{ paidTeachersCount }})</span>
        </button>
        <button
          type="button"
          @click="payoutFilter = 'pending'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1 cursor-pointer',
            payoutFilter === 'pending'
              ? 'bg-amber-500 text-white shadow-xs'
              : 'text-amber-800 hover:text-amber-950'
          ]"
        >
          <Clock class="w-3.5 h-3.5" />
          <span>En attente ({{ pendingTeachersCount }})</span>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Teachers Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="teacher in filteredTeachers"
        :key="teacher.id"
        class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-all space-y-4"
      >
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-indigo-50 border border-indigo-100 rounded-full flex items-center justify-center">
              <span class="text-base font-bold text-indigo-600">{{ getInitial(teacher.name) }}</span>
            </div>
            <div class="min-w-0">
              <h3 class="text-sm font-semibold text-gray-900">{{ teacher.name }}</h3>
              <div class="flex flex-wrap gap-1 mt-1">
                <span
                  v-for="sub in getTeacherSubjects(teacher)"
                  :key="sub"
                  class="px-2 py-0.5 bg-indigo-50 text-indigo-700 font-semibold text-[11px] rounded-md border border-indigo-100"
                >
                  {{ sub }}
                </span>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-1">
            <button
              v-if="isDirector"
              @click="openExtractModal(teacher.id)"
              class="p-1.5 hover:bg-emerald-50 text-gray-600 hover:text-emerald-600 rounded-lg transition-colors"
              title="Exporter l'extrait de paiement de cet enseignant (PDF / Excel)"
            >
              <FileText class="w-4 h-4" />
            </button>
            <button
              @click="openEditModal(teacher)"
              class="p-1.5 hover:bg-gray-100 text-gray-600 hover:text-blue-600 rounded-lg transition-colors"
              title="Modifier"
            >
              <Edit2 class="w-4 h-4" />
            </button>
            <button
              @click="deleteTeacher(teacher.id)"
              class="p-1.5 hover:bg-red-50 text-gray-600 hover:text-red-600 rounded-lg transition-colors"
              title="Supprimer"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>

        <div class="space-y-1.5 text-xs text-gray-600 bg-gray-50/75 p-3 rounded-lg border border-gray-100">
          <p class="flex items-center gap-2"><span>📱</span> <span>{{ teacher.phone }}</span></p>
          <p class="flex items-center gap-2"><span>✉️</span> <span>{{ teacher.email }}</span></p>
        </div>

        <div class="pt-2 border-t border-gray-100">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-center">
            <div class="bg-blue-50/70 p-2 rounded-lg border border-blue-100">
              <p class="text-[10px] uppercase font-semibold text-gray-500">Élèves</p>
              <p class="text-sm font-bold text-gray-900">{{ teacher.studentCount }}</p>
            </div>
            <div class="bg-purple-50/70 p-2 rounded-lg border border-purple-100">
              <p class="text-[10px] uppercase font-semibold text-gray-500">Leçons/sem</p>
              <p class="text-sm font-bold text-gray-900">{{ teacher.weeklyLessons }}</p>
            </div>
            <div class="bg-emerald-50/70 p-2 rounded-lg border border-emerald-100">
              <p class="text-[10px] uppercase font-semibold text-emerald-700">Formule Gain</p>
              <p class="text-xs font-bold text-emerald-800 mt-0.5">
                <template v-if="teacher.remuneration_type === 'percentage' || (teacher.rate_percentage && teacher.rate_percentage > 0)">
                  C: {{ teacher.rate_percentage || 70 }}%
                </template>
                <template v-else-if="teacher.remuneration_type === 'fixed_center'">
                  A: Idara {{ teacher.center_fixed_per_student || 20 }} DT/él
                </template>
                <template v-else>
                  B: {{ teacher.fixed_student_rate || 15 }} DT/él
                </template>
              </p>
            </div>
          </div>
        </div>

        <!-- Director Remuneration & Teacher Share Section -->
        <div v-if="isDirector" class="p-3 bg-linear-to-r from-emerald-50/90 to-teal-50/90 rounded-xl border border-emerald-200/90 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-emerald-950 uppercase tracking-wider flex items-center gap-1.5">
              <span>💰 Part du Professeur :</span>
            </span>
            <span class="text-sm font-black text-emerald-700 bg-white px-2.5 py-0.5 rounded-lg border border-emerald-200 shadow-2xs">
              {{ getTeacherShare(teacher) }} TND <span class="text-[10px] font-medium text-emerald-600">/ mois</span>
            </span>
          </div>

          <div class="flex items-center justify-between text-[11px] text-emerald-800 pt-1.5 border-t border-emerald-200/60">
            <span>
              <span class="text-gray-500">Calcul :</span>
              <strong class="text-emerald-950 font-bold ml-1">
                <template v-if="teacher.remuneration_type === 'percentage' || (teacher.rate_percentage && teacher.rate_percentage > 0)">
                  Formule C : {{ teacher.rate_percentage || 70 }}% du brut ({{ getGrossRevenue(teacher) }} TND)
                </template>
                <template v-else-if="teacher.remuneration_type === 'fixed_center'">
                  Formule A : Brut ({{ getGrossRevenue(teacher) }} DT) - Idara ({{ (teacher.center_fixed_per_student || 20) * (teacher.studentCount || 0) }} DT)
                </template>
                <template v-else>
                  Formule B : {{ teacher.fixed_student_rate || 15 }} TND × {{ teacher.studentCount || 0 }} élève(s)
                </template>
              </strong>
            </span>
            <span class="text-indigo-700 font-bold bg-white/80 px-2 py-0.5 rounded text-[11px] border border-indigo-100" title="Part conservée par le centre d'études">
              Centre : +{{ getCenterShare(teacher) }} TND
            </span>
          </div>

          <!-- Payout Status and Action (Directeur peut marquer si l'enseignant prend son argent ou non) -->
          <div class="pt-2 border-t border-emerald-200/80 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-1.5">
              <span
                :class="[
                  'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border shadow-2xs',
                  teacher.payout_status === 'paid'
                    ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
                    : 'bg-amber-100 text-amber-900 border-amber-300'
                ]"
              >
                <span>{{ teacher.payout_status === 'paid' ? '✓ A pris son argent' : '⏳ Non encore payé' }}</span>
                <span v-if="teacher.payout_status === 'paid' && teacher.payout_date" class="text-[10px] font-normal opacity-85">
                  ({{ formatDate(teacher.payout_date) }})
                </span>
              </span>
            </div>

            <div class="flex items-center gap-1.5">
              <button
                type="button"
                @click="togglePayoutStatus(teacher)"
                :disabled="togglingPayoutId === teacher.id"
                :class="[
                  'px-2.5 py-1 rounded-lg text-xs font-bold transition-all shadow-2xs cursor-pointer flex items-center gap-1',
                  teacher.payout_status === 'paid'
                    ? 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-100'
                    : 'bg-emerald-600 hover:bg-emerald-700 text-white'
                ]"
                :title="teacher.payout_status === 'paid' ? 'Remettre le versement en attente' : 'Confirmer que l\'enseignant a bien pris son argent'"
              >
                <Check v-if="teacher.payout_status !== 'paid'" class="w-3.5 h-3.5" />
                <RotateCcw v-else class="w-3 h-3 text-gray-500" />
                <span>{{ teacher.payout_status === 'paid' ? 'Annuler' : 'Marquer payé' }}</span>
              </button>

              <button
                v-if="teacher.payout_status === 'paid'"
                type="button"
                @click="openDischargeReceipt(teacher)"
                class="p-1 hover:bg-white text-emerald-800 rounded border border-emerald-200 cursor-pointer shadow-2xs"
                title="Imprimer le reçu de décharge de versement enseignant"
              >
                <Printer class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!loading && filteredTeachers.length === 0" class="text-center py-12">
      <p class="text-gray-500 text-sm">Aucun enseignant trouvé</p>
    </div>

    <!-- Add/Edit Teacher Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4"
      @click="showModal = false"
    >
      <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-lg w-full space-y-4 max-h-[90vh] overflow-y-auto mx-4 md:mx-auto" @click.stop>
        <div class="flex items-center justify-between border-b pb-3 border-gray-100">
          <div>
            <h3 class="text-xl font-bold text-gray-900">
              {{ isEditing ? "Modifier l'enseignant" : 'Ajouter un enseignant' }}
            </h3>
            <p class="text-xs text-gray-500">Programme tunisien & Mode de rémunération par séance</p>
          </div>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-lg">×</button>
        </div>

        <form @submit.prevent="saveTeacher" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nom complet *</label>
            <input
              v-model="formData.name"
              type="text"
              required
              placeholder="Ex: Prof. Karim Belhadj"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <!-- Tunisian Multiple Subjects Select -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
                Matière(s) enseignée(s) *
              </label>
              <span class="text-[11px] text-indigo-600 font-medium">Un prof peut enseigner plusieurs matières</span>
            </div>

            <!-- Selected Subjects Chips -->
            <div class="min-h-[44px] p-2 bg-gray-50 border border-gray-300 rounded-lg flex flex-wrap items-center gap-1.5 mb-2">
              <span
                v-for="sub in formData.subjects"
                :key="sub"
                class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-600 text-white text-xs font-medium rounded-md shadow-2xs"
              >
                <span>{{ sub }}</span>
                <button
                  type="button"
                  @click="removeSubject(sub)"
                  class="text-indigo-200 hover:text-white ml-0.5 p-0.5"
                  title="Retirer cette matière"
                >
                  <X class="w-3 h-3" />
                </button>
              </span>

              <span v-if="formData.subjects.length === 0" class="text-xs text-rose-500 italic pl-1">
                Veuillez sélectionner au moins une matière ci-dessous.
              </span>
            </div>

            <!-- Subject Dropdown to Add -->
            <div class="flex gap-2">
              <select
                v-model="newSubjectSelect"
                @change="onAddSubjectFromSelect"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              >
                <option value="">+ Ajouter une matière du programme...</option>
                <option
                  v-for="sub in tunisianSubjects"
                  :key="sub"
                  :value="sub"
                  :disabled="formData.subjects.includes(sub)"
                >
                  {{ sub }} {{ formData.subjects.includes(sub) ? '(déjà ajoutée)' : '' }}
                </option>
              </select>
            </div>

            <!-- Quick Suggestions Tags -->
            <div class="flex flex-wrap gap-1 mt-2">
              <button
                v-for="quick in commonQuickSubjects"
                :key="quick"
                type="button"
                @click="toggleSubject(quick)"
                :class="[
                  'text-[11px] px-2 py-0.5 rounded-full border transition-colors cursor-pointer',
                  formData.subjects.includes(quick)
                    ? 'bg-indigo-100 text-indigo-800 border-indigo-300 font-semibold'
                    : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
                ]"
              >
                {{ formData.subjects.includes(quick) ? '✓ ' : '+ ' }}{{ quick }}
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Téléphone *</label>
              <input
                v-model="formData.phone"
                type="text"
                required
                placeholder="Ex: 21 345 678"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Leçons / sem</label>
              <input
                v-model.number="formData.weeklyLessons"
                type="number"
                min="0"
                placeholder="Ex: 4"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Email *</label>
            <input
              v-model="formData.email"
              type="email"
              required
              placeholder="Ex: karim.belhadj@email.tn"
              class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <!-- Rémunération Enseignant : Formule B & Formule C -->
          <div class="bg-emerald-50/70 p-4 rounded-xl border border-emerald-200/80 space-y-3.5">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold text-emerald-950 uppercase tracking-wider">
                Paramètres Financiers & Formule de Rémunération
              </label>
              <span class="text-[11px] font-semibold text-emerald-700 bg-white px-2 py-0.5 rounded-full border border-emerald-200">
                Idara & Enseignant
              </span>
            </div>

            <!-- 1. Paramètres de Base (Part Administration & Prix Séance) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                  Part Fixe de l'Administration par Élève *
                </label>
                <div class="relative">
                  <input
                    v-model.number="formData.center_fixed_per_student"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="Ex: 20 TND"
                    required
                    class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-white font-bold text-indigo-700 focus:ring-2 focus:ring-emerald-500 pr-14"
                  />
                  <span class="absolute right-3 top-2.5 text-xs text-gray-400 font-bold">DT / élève</span>
                </div>
                <p class="text-[11px] text-gray-500 mt-1">
                  Montant fixe prélevé par l'administration sur chaque élève inscrit.
                </p>
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                  Prix de la Séance pour l'Élève *
                </label>
                <div class="relative">
                  <input
                    v-model.number="formData.salary_per_lesson"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="Ex: 25 TND"
                    required
                    class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-white font-bold text-gray-900 focus:ring-2 focus:ring-emerald-500 pr-14"
                  />
                  <span class="absolute right-3 top-2.5 text-xs text-gray-400 font-bold">DT / séance</span>
                </div>
                <p class="text-[11px] text-gray-500 mt-1">
                  Appliqué lors de l'inscription de l'élève (Frais = séances × ce prix).
                </p>
              </div>
            </div>

            <!-- 2. Formule de rémunération (A, B ou C) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Formule de rémunération</label>
                <select
                  v-model="formData.remuneration_type"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white font-semibold text-gray-900 focus:ring-2 focus:ring-emerald-500"
                >
                  <option value="percentage">Formule C : Pourcentage (%) du cours</option>
                  <option value="fixed">Formule B : Part Fixe Enseignant par Élève</option>
                  <option value="fixed_center">Formule A : Part Fixe Administration par Élève</option>
                </select>
              </div>

              <div>
                <template v-if="formData.remuneration_type === 'percentage'">
                  <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Part Enseignant (%) *
                  </label>
                  <div class="relative">
                    <input
                      v-model.number="formData.rate_percentage"
                      type="number"
                      min="1"
                      max="100"
                      step="1"
                      placeholder="Ex: 70%"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500 pr-8"
                    />
                    <span class="absolute right-3 top-2 text-xs text-gray-400 font-bold">%</span>
                  </div>
                </template>
                <template v-else-if="formData.remuneration_type === 'fixed_center'">
                  <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Part Fixe Administration (TND / élève) *
                  </label>
                  <div class="relative">
                    <input
                      v-model.number="formData.center_fixed_per_student"
                      type="number"
                      min="0"
                      step="1"
                      placeholder="Ex: 20 TND"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white font-bold text-indigo-700 focus:ring-2 focus:ring-emerald-500 pr-12"
                    />
                    <span class="absolute right-3 top-2 text-xs text-gray-400 font-bold">DT / élève</span>
                  </div>
                </template>
                <template v-else>
                  <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Part Fixe Enseignant par Élève (TND / élève) *
                  </label>
                  <div class="relative">
                    <input
                      v-model.number="formData.fixed_student_rate"
                      type="number"
                      min="0"
                      step="1"
                      placeholder="Ex: 15 TND"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500 pr-12"
                    />
                    <span class="absolute right-3 top-2 text-xs text-gray-400 font-bold">DT / élève</span>
                  </div>
                </template>
              </div>
            </div>

            <!-- Simulation & Règle Remise Absorbée par l'Administration -->
            <div class="text-[11px] text-emerald-900 bg-white/90 p-3 rounded-lg border border-emerald-200 space-y-1">
              <div v-if="formData.remuneration_type === 'percentage'">
                💡 <strong>Formule C (Pourcentage) :</strong> Pour un cours de 4 séances à {{ formData.salary_per_lesson || 25 }} TND (100 TND brut par élève), l'enseignant touche <strong>{{ formData.rate_percentage || 70 }}%</strong> ({{ ((formData.salary_per_lesson || 25) * 4 * (formData.rate_percentage || 70) / 100).toFixed(0) }} TND/élève) et le centre conserve le reste.
              </div>
              <div v-else-if="formData.remuneration_type === 'fixed_center'">
                💡 <strong>Formule A (Part Fixe Administration) :</strong> L'administration prélève <strong>{{ formData.center_fixed_per_student || 20 }} TND par élève</strong>. L'enseignant touche tout le reste du cours brut (ex: 100 TND - {{ formData.center_fixed_per_student || 20 }} TND = {{ Math.max(0, ((formData.salary_per_lesson || 25) * 4) - (formData.center_fixed_per_student || 20)) }} TND/élève).
              </div>
              <div v-else>
                💡 <strong>Formule B (Fixe par élève) :</strong> L'enseignant touche un montant fixe de <strong>{{ formData.fixed_student_rate || 15 }} TND par élève</strong> inscrit chaque mois. Le centre conserve la marge restante.
              </div>
              <div class="text-amber-800 font-medium pt-1 border-t border-emerald-100 flex items-start gap-1">
                <span>🛡️</span>
                <span><strong>Protection Enseignant :</strong> Toute remise (discount) accordée à un élève est absorbée à 100% par l'administration. La rémunération du professeur reste calculée sur le plein tarif brut.</span>
              </div>
            </div>
          </div>

          <div v-if="errorMessage" class="text-xs text-red-600 bg-red-50 p-2.5 rounded-lg border border-red-200">
            {{ errorMessage }}
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-sm"
            >
              {{ submitting ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Director Detailed Shares Breakdown Modal -->
    <div
      v-if="showDirectorSharesModal"
      class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
      @click="showDirectorSharesModal = false"
    >
      <div
        class="bg-white rounded-2xl shadow-2xl p-6 max-w-4xl w-full space-y-4 max-h-[92vh] overflow-y-auto mx-4 md:mx-auto"
        @click.stop
      >
        <div class="flex items-center justify-between border-b pb-3 border-gray-100">
          <div>
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
              <span>📊 Bilan Financier & Parts des Professeurs</span>
            </h3>
            <p class="text-xs text-gray-500 mt-0.5">
              Récapitulatif mensuel des gains de chaque enseignant et de la marge du centre d'études.
            </p>
          </div>
          <button
            @click="showDirectorSharesModal = false"
            class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100"
            aria-label="Fermer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="overflow-x-auto border border-gray-200 rounded-xl">
          <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase tracking-wider border-b border-gray-200">
              <tr>
                <th class="py-3 px-4">Enseignant</th>
                <th class="py-3 px-3">Matière(s)</th>
                <th class="py-3 px-3 text-center">Élèves</th>
                <th class="py-3 px-3 text-center">Séances/Mois</th>
                <th class="py-3 px-3">Formule Rémunération</th>
                <th class="py-3 px-3 text-right">C.A. Brut</th>
                <th class="py-3 px-3 text-right text-emerald-700 font-bold">Part Enseignant</th>
                <th class="py-3 px-4 text-right text-indigo-700 font-bold">Part Centre</th>
                <th class="py-3 px-3 text-center">Statut Versement</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="t in teachers" :key="t.id" class="hover:bg-gray-50/80 transition-colors">
                <td class="py-3 px-4 font-bold text-gray-900">{{ t.name }}</td>
                <td class="py-3 px-3 text-xs text-gray-600">
                  <span
                    v-for="s in getTeacherSubjects(t)"
                    :key="s"
                    class="inline-block bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded text-[10px] font-medium mr-1 mb-0.5"
                  >
                    {{ s }}
                  </span>
                </td>
                <td class="py-3 px-3 text-center font-semibold text-gray-800">{{ t.studentCount || 0 }}</td>
                <td class="py-3 px-3 text-center font-medium text-gray-700">{{ (t.weeklyLessons || 4) * 4 }}</td>
                <td class="py-3 px-3 text-xs">
                  <span
                    v-if="t.remuneration_type === 'percentage' || (t.rate_percentage && t.rate_percentage > 0)"
                    class="px-2 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 rounded font-semibold text-[11px]"
                  >
                    Formule C : {{ t.rate_percentage || 70 }}% du Brut
                  </span>
                  <span
                    v-else-if="t.remuneration_type === 'fixed_center'"
                    class="px-2 py-0.5 bg-indigo-50 text-indigo-800 border border-indigo-200 rounded font-semibold text-[11px]"
                  >
                    Formule A : Idara {{ t.center_fixed_per_student || 20 }} TND / élève
                  </span>
                  <span
                    v-else
                    class="px-2 py-0.5 bg-blue-50 text-blue-800 border border-blue-200 rounded font-semibold text-[11px]"
                  >
                    Formule B : {{ t.fixed_student_rate || 15 }} TND / élève
                  </span>
                </td>
                <td class="py-3 px-3 text-right font-medium text-gray-800">{{ getGrossRevenue(t) }} TND</td>
                <td class="py-3 px-3 text-right font-bold text-emerald-700">{{ getTeacherShare(t) }} TND</td>
                <td class="py-3 px-4 text-right font-bold text-indigo-700">+{{ getCenterShare(t) }} TND</td>
                <td class="py-3 px-3 text-center">
                  <button
                    type="button"
                    @click="togglePayoutStatus(t)"
                    :disabled="togglingPayoutId === t.id"
                    :class="[
                      'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border transition-colors cursor-pointer',
                      t.payout_status === 'paid'
                        ? 'bg-emerald-100 text-emerald-800 border-emerald-300 hover:bg-emerald-200'
                        : 'bg-amber-100 text-amber-900 border-amber-300 hover:bg-amber-200'
                    ]"
                    :title="t.payout_status === 'paid' ? 'Cliquer pour annuler le versement' : 'Cliquer pour marquer que l\'enseignant a pris son argent'"
                  >
                    <Check v-if="t.payout_status === 'paid'" class="w-3 h-3" />
                    <Clock v-else class="w-3 h-3" />
                    <span>{{ t.payout_status === 'paid' ? 'A pris son argent' : 'En attente' }}</span>
                  </button>
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50/90 font-bold border-t-2 border-gray-200 text-gray-900">
              <tr>
                <td class="py-3 px-4" colspan="2">TOTAL GÉNÉRAL</td>
                <td class="py-3 px-3 text-center">{{ totalStudentsCount }}</td>
                <td class="py-3 px-3 text-center">{{ totalMonthlySessions }}</td>
                <td></td>
                <td class="py-3 px-3 text-right font-bold text-gray-900">{{ totalGrossRevenue.toLocaleString('fr-FR') }} TND</td>
                <td class="py-3 px-3 text-right font-black text-emerald-700 text-base">{{ totalTeachersShare.toLocaleString('fr-FR') }} TND</td>
                <td class="py-3 px-4 text-right font-black text-indigo-700 text-base">+{{ totalCenterShare.toLocaleString('fr-FR') }} TND</td>
                <td class="py-3 px-3 text-center text-xs text-gray-600 font-semibold">{{ paidTeachersCount }}/{{ teachers.length }} payé(s)</td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
          <button
            @click="showDirectorSharesModal = false"
            class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-xs"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- Official Teacher Payout Discharge Receipt Modal (Reçu de Décharge Enseignant) -->
    <div
      v-if="showDischargeModal && dischargeTeacher"
      class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
      @click="showDischargeModal = false"
    >
      <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8 max-w-xl w-full max-h-[90vh] overflow-y-auto mx-4 md:mx-auto" @click.stop>
        <!-- Printable Area -->
        <div id="printable-teacher-discharge" class="border-2 border-dashed border-emerald-300 rounded-2xl p-6 bg-slate-50/60">
          <!-- Header -->
          <div class="flex items-center justify-between border-b border-gray-200 pb-4 mb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg shadow-xs">
                🎓
              </div>
              <div>
                <h2 class="text-base font-black text-gray-900 tracking-tight uppercase">Centre d'Études & de Formation</h2>
                <p class="text-xs text-gray-500 font-medium">Direction Générale • Service Comptabilité</p>
              </div>
            </div>
            <div class="text-right">
              <span class="inline-block px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold text-xs rounded-full uppercase tracking-wider border border-emerald-200">
                Décharge N° DCH-{{ dischargeTeacher.id.toString().padStart(4, '0') }}
              </span>
              <p class="text-[11px] text-gray-500 mt-1">Date : {{ formatDate(dischargeTeacher.payout_date) }}</p>
            </div>
          </div>

          <!-- Title -->
          <div class="text-center py-2 mb-4 bg-emerald-50 rounded-xl border border-emerald-200">
            <h3 class="text-xs font-black text-emerald-950 uppercase tracking-wider">
              Reçu de Décharge de Paiement — Honoraires Enseignant
            </h3>
            <p class="text-[11px] text-emerald-700">Attestation officielle de versement de la part professeur</p>
          </div>

          <!-- Body -->
          <div class="space-y-2.5 text-xs text-gray-700">
            <div class="flex justify-between py-1.5 border-b border-gray-200/60">
              <span class="text-gray-500 font-medium">Nom de l'enseignant :</span>
              <span class="font-bold text-gray-900 text-sm">{{ dischargeTeacher.name }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-gray-200/60">
              <span class="text-gray-500 font-medium">Matière(s) enseignée(s) :</span>
              <span class="font-semibold text-gray-800">{{ getTeacherSubjects(dischargeTeacher).join(', ') }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-gray-200/60">
              <span class="text-gray-500 font-medium">Téléphone :</span>
              <span class="font-medium text-gray-800">{{ dischargeTeacher.phone || '-' }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-gray-200/60">
              <span class="text-gray-500 font-medium">Période concernée :</span>
              <span class="font-semibold text-indigo-700">{{ dischargeTeacher.payout_month || 'Mois en cours' }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-gray-200/60">
              <span class="text-gray-500 font-medium">Formule appliquée :</span>
              <span class="font-medium text-gray-800">
                {{ dischargeTeacher.remuneration_type === 'percentage' || (dischargeTeacher.rate_percentage && dischargeTeacher.rate_percentage > 0)
                  ? `Formule C : ${dischargeTeacher.rate_percentage || 70}% sur ${getGrossRevenue(dischargeTeacher)} TND brut`
                  : `Formule B : ${dischargeTeacher.fixed_student_rate || 15} TND × ${dischargeTeacher.studentCount || 0} élève(s)` }}
              </span>
            </div>

            <!-- Amount Highlight Box -->
            <div class="mt-4 p-4 rounded-xl bg-linear-to-r from-emerald-600 to-teal-700 text-white shadow-xs flex items-center justify-between">
              <div>
                <p class="text-[10px] uppercase font-bold text-emerald-100 tracking-wider">Montant Net Perçu :</p>
                <p class="text-xs text-emerald-200">Reçu en totalité en espèces / virement</p>
              </div>
              <p class="text-2xl font-black">{{ getTeacherShare(dischargeTeacher) }} TND</p>
            </div>

            <!-- Legal Discharge Clause -->
            <p class="text-[11px] text-gray-500 italic pt-2 leading-relaxed text-justify">
              « Je soussigné(e), {{ dischargeTeacher.name }}, atteste par la présente avoir reçu l'intégralité de mes honoraires et de ma part d'enseignement relative à la période susmentionnée de la part de la direction du Centre d'Études. »
            </p>

            <!-- Signature Boxes -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-6 text-center text-xs">
              <div class="p-3 bg-white rounded-xl border border-gray-200">
                <p class="font-bold text-gray-700 mb-1">Signature de l'Enseignant</p>
                <p class="text-[10px] text-gray-400 italic">(Mention manuscrite "Bon pour accord et reçu")</p>
                <div class="h-14 border-b border-dashed border-gray-300 mt-2"></div>
              </div>
              <div class="p-3 bg-white rounded-xl border border-gray-200">
                <p class="font-bold text-gray-700 mb-1">Cachet & Signature Direction</p>
                <div class="h-14 border-b border-dashed border-gray-300 mt-2 flex items-center justify-center">
                  <span class="text-[10px] text-emerald-700 font-extrabold border-2 border-emerald-500 px-2 py-0.5 rounded uppercase rotate-[-5deg]">
                    ✓ RÈGLEMENT EFFECTUÉ
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions (Hidden on Print) -->
        <div class="flex items-center justify-end gap-3 mt-6 print:hidden">
          <button
            type="button"
            @click="showDischargeModal = false"
            class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800 transition-colors cursor-pointer"
          >
            Fermer
          </button>
          <button
            type="button"
            @click="printDischargeReceipt"
            class="flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition-colors cursor-pointer"
          >
            <Printer class="w-4 h-4" />
            <span>Imprimer la Décharge (A4/A5)</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Official Payment Extract Modal (Director only) -->
    <PaymentExtractModal
      :isOpen="showExtractModal"
      initialType="teachers"
      :initialTeacherId="selectedTeacherExtractId"
      @close="showExtractModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Plus, Search, Edit2, Trash2, Users, Calendar, X, DollarSign, FileText, Check, RotateCcw, Clock, Printer } from 'lucide-vue-next';
import api from '../utils/api';
import { ALL_TUNISIAN_SUBJECTS } from '../utils/tunisianEducation';
import { parseSubjects } from '../utils/teacherSubjects';
import PaymentExtractModal from './PaymentExtractModal.vue';

interface Teacher {
  id: number;
  name: string;
  subject: string;
  subjects?: string[];
  phone: string;
  email: string;
  studentCount: number;
  weeklyLessons: number;
  monthlyLessons?: number;
  remuneration_type?: 'percentage' | 'fixed' | 'fixed_center';
  rate_percentage?: number;
  salary_per_lesson?: number;
  fixed_student_rate?: number;
  center_fixed_per_student?: number;
  monthly_gross_revenue?: number;
  teacher_share?: number;
  center_share?: number;
  per_session_share?: number;
  payout_status?: 'paid' | 'pending';
  payout_date?: string | null;
  payout_month?: string | null;
  payout_amount?: number | null;
}

const tunisianSubjects = ALL_TUNISIAN_SUBJECTS;
const commonQuickSubjects = [
  'Mathématiques',
  'Sciences Physiques',
  'SVT',
  'Informatique',
  'Français',
  'Anglais',
  'Arabe',
  'Philosophie',
  'Économie',
  'Gestion',
  'Histoire-Géo'
];

const teachers = ref<Teacher[]>([]);
const searchTerm = ref('');
const loading = ref(false);
const submitting = ref(false);
const errorMessage = ref('');

const showModal = ref(false);
const showDirectorSharesModal = ref(false);
const showExtractModal = ref(false);
const selectedTeacherExtractId = ref<number | null>(null);
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const newSubjectSelect = ref('');

function openExtractModal(teacherId: number | null = null) {
  selectedTeacherExtractId.value = teacherId;
  showExtractModal.value = true;
}

const currentUser = ref<{ role?: string; name?: string } | null>(null);
try {
  const saved = localStorage.getItem('user');
  if (saved) currentUser.value = JSON.parse(saved);
} catch {}

const isDirector = computed(() => {
  return currentUser.value?.role === 'director' || teachers.value.some(t => t.teacher_share !== undefined || t.rate_percentage !== undefined);
});

function getGrossRevenue(teacher: Teacher): number {
  if (teacher.monthly_gross_revenue !== undefined) return Number(teacher.monthly_gross_revenue);
  const count = Number(teacher.studentCount) || 0;
  return count * 60;
}

function getTeacherShare(teacher: Teacher): number {
  if (teacher.teacher_share !== undefined) return Number(teacher.teacher_share);
  const gross = getGrossRevenue(teacher);
  const count = Number(teacher.studentCount) || 0;
  if (teacher.remuneration_type === 'fixed_center') {
    const centerFixed = Number(teacher.center_fixed_per_student) || 20;
    return Math.max(0, gross - (count * centerFixed));
  }
  const isPct = teacher.remuneration_type === 'percentage' || (teacher.rate_percentage && teacher.rate_percentage > 0);
  if (isPct) {
    const pct = Number(teacher.rate_percentage) || 70;
    return Math.round((gross * pct) / 100);
  }
  const fixedRate = Number(teacher.fixed_student_rate) || 15;
  return Math.round(fixedRate * count);
}

function getCenterShare(teacher: Teacher): number {
  if (teacher.center_share !== undefined) return Number(teacher.center_share);
  const gross = getGrossRevenue(teacher);
  const tShare = getTeacherShare(teacher);
  return Math.max(0, gross - tShare);
}

const totalGrossRevenue = computed(() =>
  teachers.value.reduce((sum, t) => sum + getGrossRevenue(t), 0)
);

const totalTeachersShare = computed(() =>
  teachers.value.reduce((sum, t) => sum + getTeacherShare(t), 0)
);

const totalCenterShare = computed(() =>
  teachers.value.reduce((sum, t) => sum + getCenterShare(t), 0)
);

const avgTeacherShare = computed(() => {
  if (teachers.value.length === 0) return 0;
  return Math.round(totalTeachersShare.value / teachers.value.length);
});

const totalStudentsCount = computed(() =>
  teachers.value.reduce((sum, t) => sum + (Number(t.studentCount) || 0), 0)
);

const totalMonthlySessions = computed(() =>
  teachers.value.reduce((sum, t) => sum + ((Number(t.weeklyLessons) || 4) * 4), 0)
);

const formData = ref<{
  name: string;
  subjects: string[];
  phone: string;
  email: string;
  weeklyLessons: number;
  remuneration_type: 'percentage' | 'fixed' | 'fixed_center';
  rate_percentage: number;
  salary_per_lesson: number;
  fixed_student_rate: number;
  center_fixed_per_student: number;
}>({
  name: '',
  subjects: ['Mathématiques'],
  phone: '',
  email: '',
  weeklyLessons: 4,
  remuneration_type: 'percentage',
  rate_percentage: 70,
  salary_per_lesson: 25,
  fixed_student_rate: 15,
  center_fixed_per_student: 20
});

function getTeacherSubjects(teacher: Teacher): string[] {
  if (teacher.subjects && teacher.subjects.length > 0) {
    return teacher.subjects;
  }
  return parseSubjects(teacher.subject);
}

function addSubject(sub: string) {
  if (!sub) return;
  if (!formData.value.subjects.includes(sub)) {
    formData.value.subjects.push(sub);
  }
}

function removeSubject(sub: string) {
  formData.value.subjects = formData.value.subjects.filter(s => s !== sub);
}

function toggleSubject(sub: string) {
  if (formData.value.subjects.includes(sub)) {
    removeSubject(sub);
  } else {
    addSubject(sub);
  }
}

function onAddSubjectFromSelect() {
  if (newSubjectSelect.value) {
    addSubject(newSubjectSelect.value);
    newSubjectSelect.value = '';
  }
}

function getInitial(name: string): string {
  const clean = name.replace(/^prof\.?\s*/i, '').trim();
  return (clean.charAt(0) || name.charAt(0) || 'P').toUpperCase();
}

async function loadTeachers() {
  loading.value = true;
  try {
    const res = await api.get('/api/teachers');
    teachers.value = res.data;
  } catch (e: any) {
    console.error('Erreur chargement enseignants:', e);
  } finally {
    loading.value = false;
  }
}

onMounted(loadTeachers);

const payoutFilter = ref<'all' | 'paid' | 'pending'>('all');
const togglingPayoutId = ref<number | null>(null);
const dischargeTeacher = ref<Teacher | null>(null);
const showDischargeModal = ref(false);

const paidTeachersCount = computed(() => teachers.value.filter(t => t.payout_status === 'paid').length);
const pendingTeachersCount = computed(() => teachers.value.filter(t => t.payout_status !== 'paid').length);

const filteredTeachers = computed(() => {
  let list = teachers.value;
  if (isDirector.value && payoutFilter.value !== 'all') {
    if (payoutFilter.value === 'paid') {
      list = list.filter(t => t.payout_status === 'paid');
    } else {
      list = list.filter(t => t.payout_status !== 'paid');
    }
  }

  if (!searchTerm.value.trim()) return list;
  const q = searchTerm.value.toLowerCase().trim();
  return list.filter((teacher) => {
    const matchName = teacher.name.toLowerCase().includes(q);
    const matchSub = (teacher.subject || '').toLowerCase().includes(q);
    const matchSubs = getTeacherSubjects(teacher).some(s => s.toLowerCase().includes(q));
    return matchName || matchSub || matchSubs;
  });
});

async function togglePayoutStatus(teacher: Teacher) {
  if (!isDirector.value) return;
  const isCurrentlyPaid = teacher.payout_status === 'paid';
  const targetStatus = isCurrentlyPaid ? 'pending' : 'paid';
  const amount = getTeacherShare(teacher);
  const confirmMsg = targetStatus === 'paid'
    ? `Confirmer que l'enseignant "${teacher.name}" a bien pris son argent (${amount} TND) ?`
    : `Remettre le versement de l'enseignant "${teacher.name}" en attente ?`;

  if (!confirm(confirmMsg)) return;

  togglingPayoutId.value = teacher.id;
  try {
    const todayStr = new Date().toISOString().split('T')[0];
    const monthStr = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' }).format(new Date());

    const res = await api.post(`/api/teachers/${teacher.id}/toggle-payout`, {
      status: targetStatus,
      amount: amount,
      date: todayStr,
      month: monthStr,
    });

    if (res.data?.teacher) {
      const idx = teachers.value.findIndex(t => t.id === teacher.id);
      if (idx !== -1) {
        teachers.value[idx] = { ...teachers.value[idx], ...res.data.teacher };
      }
    } else {
      teacher.payout_status = targetStatus;
      teacher.payout_date = targetStatus === 'paid' ? todayStr : null;
      teacher.payout_amount = targetStatus === 'paid' ? amount : null;
      teacher.payout_month = targetStatus === 'paid' ? monthStr : null;
    }
  } catch (err: any) {
    console.error('Erreur changement statut versement:', err);
    alert(err?.response?.data?.message || 'Erreur lors de la mise à jour du statut.');
  } finally {
    togglingPayoutId.value = null;
  }
}

function openDischargeReceipt(teacher: Teacher) {
  dischargeTeacher.value = teacher;
  showDischargeModal.value = true;
}

function printDischargeReceipt() {
  window.print();
}

function formatDate(d: string | null | undefined): string {
  if (!d) return '-';
  try {
    const parts = d.split('-');
    if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
    return d;
  } catch {
    return d;
  }
}

function openAddModal() {
  isEditing.value = false;
  editingId.value = null;
  errorMessage.value = '';
  newSubjectSelect.value = '';
  formData.value = {
    name: '',
    subjects: ['Mathématiques'],
    phone: '',
    email: '',
    weeklyLessons: 4,
    remuneration_type: 'percentage',
    rate_percentage: 70,
    salary_per_lesson: 25,
    fixed_student_rate: 15,
    center_fixed_per_student: 20
  };
  showModal.value = true;
}

function openEditModal(teacher: Teacher) {
  isEditing.value = true;
  editingId.value = teacher.id;
  errorMessage.value = '';
  newSubjectSelect.value = '';
  const subs = getTeacherSubjects(teacher);
  formData.value = {
    name: teacher.name,
    subjects: subs.length > 0 ? [...subs] : ['Mathématiques'],
    phone: teacher.phone,
    email: teacher.email,
    weeklyLessons: teacher.weeklyLessons || 0,
    remuneration_type: teacher.remuneration_type || 'percentage',
    rate_percentage: teacher.rate_percentage || 70,
    salary_per_lesson: teacher.salary_per_lesson || 25,
    fixed_student_rate: teacher.fixed_student_rate || 15,
    center_fixed_per_student: teacher.center_fixed_per_student || 20
  };
  showModal.value = true;
}

async function saveTeacher() {
  if (formData.value.subjects.length === 0) {
    errorMessage.value = 'Veuillez sélectionner au moins une matière pour cet enseignant.';
    return;
  }

  submitting.value = true;
  errorMessage.value = '';

  const payload = {
    ...formData.value,
    rate_percentage: Number(formData.value.rate_percentage) || 70,
    salary_per_lesson: Number(formData.value.salary_per_lesson) || 25,
    fixed_student_rate: Number(formData.value.fixed_student_rate) || 15,
    center_fixed_per_student: Number(formData.value.center_fixed_per_student) || 20,
    subject: formData.value.subjects.join(', '),
    subjects: formData.value.subjects
  };

  try {
    if (isEditing.value && editingId.value) {
      const res = await api.put(`/api/teachers/${editingId.value}`, payload);
      const idx = teachers.value.findIndex(t => t.id === editingId.value);
      if (idx !== -1) {
        teachers.value[idx] = res.data;
      }
    } else {
      const res = await api.post('/api/teachers', payload);
      teachers.value.push(res.data);
    }
    showModal.value = false;
  } catch (e: any) {
    console.error('Erreur enregistrement enseignant:', e);
    errorMessage.value = e.response?.data?.message || 'Erreur lors de l\'enregistrement';
  } finally {
    submitting.value = false;
  }
}


async function deleteTeacher(id: number) {
  if (!confirm('Voulez-vous vraiment supprimer cet enseignant ?')) return;
  try {
    await api.delete(`/api/teachers/${id}`);
    teachers.value = teachers.value.filter(t => t.id !== id);
  } catch (e: any) {
    console.error('Erreur suppression enseignant:', e);
    alert('Impossible de supprimer cet enseignant.');
  }
}
</script>

<style>
@media print {
  body * {
    visibility: hidden;
  }
  #printable-teacher-discharge, #printable-teacher-discharge * {
    visibility: visible;
  }
  #printable-teacher-discharge {
    position: fixed;
    left: 50%;
    top: 40px;
    transform: translateX(-50%);
    width: 650px;
    max-width: 95%;
    margin: 0;
    padding: 24px;
    background: white;
    border: 2px solid #059669 !important;
    border-radius: 16px;
    box-shadow: none;
    z-index: 99999;
  }
}
</style>
