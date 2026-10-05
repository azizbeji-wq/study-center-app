<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Gestion des Paiements</h2>
        <p class="text-sm text-gray-500 mt-1">Séances des élèves par enseignant et mois de garderie</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-if="isDirector"
          @click="openExtractModal('teachers')"
          class="flex items-center gap-2 px-3.5 py-2 bg-emerald-50 text-emerald-800 font-bold rounded-lg hover:bg-emerald-100 transition-colors border border-emerald-200 text-sm shadow-xs"
          title="Exporter un extrait officiel des paiements pour les enseignants ou les élèves (PDF / Excel)"
        >
          <FileText class="w-4 h-4 text-emerald-600" />
          <span>Extraits de Paiements</span>
        </button>

        <button
          @click="openAddModal()"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
        >
          <Plus class="w-4 h-4" />
          Enregistrer un paiement
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-green-50/80 border border-green-200 rounded-xl p-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
            <DollarSign class="w-5 h-5 text-green-600" />
          </div>
          <div>
            <p class="text-xs font-semibold text-green-700 uppercase">Total Payé</p>
            <p class="text-2xl font-bold text-green-900">{{ totalPaid.toFixed(2) }} TND</p>
          </div>
        </div>
      </div>
      <div class="bg-yellow-50/80 border border-yellow-200 rounded-xl p-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
            <Clock class="w-5 h-5 text-yellow-600" />
          </div>
          <div>
            <p class="text-xs font-semibold text-yellow-700 uppercase">En Attente</p>
            <p class="text-2xl font-bold text-yellow-900">{{ dues.totals.pending.toFixed(2) }} TND</p>
            <p class="text-[11px] text-yellow-700">{{ dues.totals.pendingCount }} solde(s)</p>
          </div>
        </div>
      </div>
      <div class="bg-red-50/80 border border-red-200 rounded-xl p-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
            <AlertCircle class="w-5 h-5 text-red-600" />
          </div>
          <div>
            <p class="text-xs font-semibold text-red-700 uppercase">En Retard</p>
            <p class="text-2xl font-bold text-red-900">{{ dues.totals.overdue.toFixed(2) }} TND</p>
            <p class="text-[11px] text-red-700">{{ dues.totals.overdueCount }} solde(s)</p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loadError" class="text-xs text-red-600 bg-red-50 p-3 rounded-lg border border-red-200">
      {{ loadError }}
    </div>

    <!-- Search -->
    <div class="relative">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
      <input
        type="text"
        placeholder="Rechercher un élève, un enseignant ou un enfant de garde..."
        v-model="searchTerm"
        class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm shadow-sm"
      />
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <template v-else>
      <!-- ============ Soldes à régler ============ -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-gray-200 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
          <div>
            <h3 class="text-base font-bold text-gray-900">Soldes à régler</h3>
            <p class="text-xs text-gray-500 mt-0.5">{{ rulesText }}</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="k in kinds"
              :key="k.value"
              @click="duesKind = k.value"
              :class="[
                'px-3.5 py-1.5 rounded-lg text-sm font-medium transition-colors',
                duesKind === k.value
                  ? 'bg-indigo-600 text-white'
                  : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
              ]"
            >
              {{ k.label }} ({{ k.value === 'student' ? dues.students.length : dues.daycare.length }})
            </button>
            <span class="w-px bg-gray-200 mx-1"></span>
            <button
              v-for="f in duesFilters"
              :key="f.value"
              @click="duesFilter = f.value"
              :class="[
                'px-3.5 py-1.5 rounded-lg text-sm font-medium transition-colors',
                duesFilter === f.value
                  ? 'bg-gray-800 text-white'
                  : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
              ]"
            >
              {{ f.label }}
            </button>
          </div>
        </div>

        <!-- Élèves -->
        <div v-if="duesKind === 'student'" class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50/75 border-b border-gray-200">
              <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Élève</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Enseignant</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Séances non payées</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Prix / séance</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant dû</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="row in filteredStudentDues" :key="row.key" class="hover:bg-gray-50/80 transition-colors">
                <td class="px-5 py-3.5">
                  <p class="text-sm font-semibold text-gray-900">{{ row.studentName }}</p>
                  <p v-if="row.groupName" class="text-xs text-gray-500">{{ row.groupName }}</p>
                </td>
                <td class="px-5 py-3.5">
                  <p class="text-sm text-gray-900">{{ row.teacherName }}</p>
                  <p class="text-xs text-gray-500">{{ row.subjects.join(', ') }}</p>
                </td>
                <td class="px-5 py-3.5">
                  <p class="text-sm font-bold text-gray-900">{{ row.sessionsUnpaid }} séance(s)</p>
                  <p class="text-xs text-gray-500">{{ row.sessionsAttended }} suivies · {{ row.sessionsPaid }} payées</p>
                </td>
                <td class="px-5 py-3.5 text-sm text-gray-700">
                  {{ row.unitAmount > 0 ? row.unitAmount.toFixed(2) + ' TND' : 'À définir' }}
                  <p v-if="row.priceSource === 'monthly'" class="text-[11px] text-gray-400">
                    prix mensuel ÷ {{ dues.pricing.sessionsPerMonth }}
                  </p>
                  <p v-else-if="row.priceSource === 'default'" class="text-[11px] text-amber-600">
                    tarif par défaut
                  </p>
                </td>
                <td class="px-5 py-3.5">
                  <span :class="['text-sm font-bold', row.unitAmount > 0 ? 'text-gray-900' : 'text-gray-400']">
                    {{ row.unitAmount > 0 ? row.amountDue.toFixed(2) + ' TND' : 'À définir' }}
                  </span>
                </td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-1.5">
                    <component :is="getStatusIcon(row.status)" :class="['w-4 h-4', getStatusColor(row.status)]" />
                    <span class="text-xs font-medium" :class="getStatusTextColor(row.status)">
                      {{ getStatusLabel(row.status) }}
                    </span>
                  </div>
                </td>
                <td class="px-5 py-3.5 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      v-if="isDirector"
                      @click="openExtractModal('students', row.studentId)"
                      class="p-1 hover:bg-indigo-50 text-indigo-600 rounded-md transition-colors"
                      title="Générer l'extrait de compte de cet élève"
                    >
                      <FileText class="w-4 h-4" />
                    </button>
                    <button
                      @click="openAddModal({ kind: 'student', studentId: row.studentId, teacherId: row.teacherId })"
                      class="px-2.5 py-1 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700 transition-colors shadow-xs"
                    >
                      Encaisser
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="filteredStudentDues.length === 0" class="text-center py-10">
            <p class="text-gray-500 text-sm">Aucun solde à régler pour les élèves</p>
          </div>
        </div>

        <!-- Garderie -->
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50/75 border-b border-gray-200">
              <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Enfant</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mois non payés</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tarif mensuel</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant dû</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="row in filteredDaycareDues" :key="row.key" class="hover:bg-gray-50/80 transition-colors">
                <td class="px-5 py-3.5">
                  <p class="text-sm font-semibold text-gray-900">👶 {{ row.name }}</p>
                  <p class="text-xs text-gray-500">Parent : {{ row.parentName }}</p>
                </td>
                <td class="px-5 py-3.5">
                  <p class="text-sm font-bold text-gray-900">{{ row.monthsUnpaid }} mois</p>
                  <p class="text-xs text-gray-500">{{ row.monthsDue }} écoulés · {{ row.monthsPaid }} payés</p>
                </td>
                <td class="px-5 py-3.5 text-sm text-gray-700">
                  {{ row.monthlyFee > 0 ? row.monthlyFee.toFixed(2) + ' TND' : 'À définir' }}
                  <p v-if="row.feeSource === 'default'" class="text-[11px] text-amber-600">tarif par défaut</p>
                </td>
                <td class="px-5 py-3.5">
                  <span :class="['text-sm font-bold', row.monthlyFee > 0 ? 'text-gray-900' : 'text-gray-400']">
                    {{ row.monthlyFee > 0 ? row.amountDue.toFixed(2) + ' TND' : 'À définir' }}
                  </span>
                </td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-1.5">
                    <component :is="getStatusIcon(row.status)" :class="['w-4 h-4', getStatusColor(row.status)]" />
                    <span class="text-xs font-medium" :class="getStatusTextColor(row.status)">
                      {{ getStatusLabel(row.status) }}
                    </span>
                  </div>
                </td>
                <td class="px-5 py-3.5 text-right">
                  <button
                    @click="openAddModal({ kind: 'daycare', daycareChildId: row.daycareChildId })"
                    class="px-2.5 py-1 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700 transition-colors shadow-xs"
                  >
                    Encaisser
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="filteredDaycareDues.length === 0" class="text-center py-10">
            <p class="text-gray-500 text-sm">Aucun solde à régler pour la garderie</p>
          </div>
        </div>
      </div>

      <!-- ============ Historique des paiements ============ -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-gray-200">
          <h3 class="text-base font-bold text-gray-900">Historique des paiements</h3>
          <p class="text-xs text-gray-500 mt-0.5">Paiements reçus</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50/75 border-b border-gray-200">
              <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Payeur</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Détail</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mois</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Montant</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Encaissé par</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="payment in filteredPayments" :key="payment.id" class="hover:bg-gray-50/80 transition-colors">
                <td class="px-5 py-3.5">
                  <p class="text-sm font-medium text-gray-900">{{ payment.studentName }}</p>
                  <span
                    :class="[
                      'inline-flex px-2 py-0.5 rounded text-[11px] font-semibold',
                      payment.daycareChildId ? 'bg-orange-100 text-orange-800' : 'bg-indigo-50 text-indigo-700'
                    ]"
                  >
                    {{ payment.daycareChildId ? 'Garderie' : 'Élève' }}
                  </span>
                </td>
                <td class="px-5 py-3.5 text-sm text-gray-600">{{ paymentDetail(payment) }}</td>
                <td class="px-5 py-3.5 text-sm text-gray-600">{{ payment.month }}</td>
                <td class="px-5 py-3.5 text-sm font-semibold text-gray-900">{{ Number(payment.amount).toFixed(2) }} TND</td>
                <td class="px-5 py-3.5 text-sm text-gray-600">{{ payment.date || '-' }}</td>
                <td class="px-5 py-3.5">
                  <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold"
                    :class="payment.createdByRole === 'secretary' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200'"
                  >
                    <span>{{ payment.createdByName || 'Directeur Fondateur' }}</span>
                    <span class="text-[9px] font-medium opacity-75">({{ payment.createdByRole === 'secretary' ? 'Secrétaire' : 'Directeur' }})</span>
                  </span>
                </td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-1.5">
                    <component :is="getStatusIcon(payment.status)" :class="['w-4 h-4', getStatusColor(payment.status)]" />
                    <span class="text-xs font-medium" :class="getStatusTextColor(payment.status)">
                      {{ getStatusLabel(payment.status) }}
                    </span>
                  </div>
                </td>
                <td class="px-5 py-3.5 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      v-if="payment.status !== 'paid'"
                      @click="markAsPaid(payment)"
                      class="px-2.5 py-1 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700 transition-colors shadow-xs"
                      title="Marquer comme payé aujourd'hui"
                    >
                      Marquer payé
                    </button>
                    <button
                      v-if="isDirector && (payment.studentId || payment.teacherId)"
                      @click="payment.teacherId ? openExtractModal('teachers', payment.teacherId) : openExtractModal('students', payment.studentId)"
                      class="p-1.5 hover:bg-emerald-50 text-gray-600 hover:text-emerald-600 rounded-lg transition-colors"
                      :title="payment.teacherId ? 'Extrait de paiement de cet enseignant' : 'Extrait de paiement de cet élève'"
                    >
                      <FileText class="w-4 h-4" />
                    </button>
                    <button
                      @click="openReceipt(payment)"
                      class="p-1.5 hover:bg-indigo-50 text-gray-600 hover:text-indigo-600 rounded-lg transition-colors"
                      title="Imprimer le reçu"
                    >
                      <Printer class="w-4 h-4" />
                    </button>
                    <button
                      @click="openEditModal(payment)"
                      class="p-1.5 hover:bg-gray-100 text-gray-600 hover:text-blue-600 rounded-lg transition-colors"
                      title="Modifier"
                    >
                      <Edit2 class="w-4 h-4" />
                    </button>
                    <button
                      @click="deletePayment(payment.id)"
                      class="p-1.5 hover:bg-red-50 text-gray-600 hover:text-red-600 rounded-lg transition-colors"
                      title="Supprimer"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="filteredPayments.length === 0" class="text-center py-10">
            <p class="text-gray-500 text-sm">Aucun paiement trouvé</p>
          </div>
        </div>
      </div>
    </template>

    <!-- Add/Edit Payment Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click="showModal = false">
      <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full max-h-[92vh] overflow-y-auto" @click.stop>
        <h3 class="text-xl font-bold text-gray-900 mb-4">
          {{ isEditing ? 'Modifier le paiement' : 'Enregistrer un paiement' }}
        </h3>

        <form @submit.prevent="savePayment" class="space-y-4">

          <!-- ===== Création ===== -->
          <template v-if="!isEditing">
            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="k in kinds"
                :key="k.value"
                type="button"
                @click="setKind(k.value)"
                :class="[
                  'px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                  form.kind === k.value
                    ? 'bg-indigo-600 text-white'
                    : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
                ]"
              >
                {{ k.label }}
              </button>
            </div>

            <!-- Élève + enseignant -->
            <template v-if="form.kind === 'student'">
              <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Élève *</label>
                <select
                  v-model="form.studentId"
                  @change="onStudentChange"
                  required
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                >
                  <option :value="null" disabled>Sélectionner un élève...</option>
                  <option v-for="s in students" :key="s.id" :value="s.id">
                    {{ s.name }} ({{ s.group_name || 'Sans groupe' }} - {{ s.level }})
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Enseignant *</label>
                <select
                  v-model="form.teacherId"
                  @change="onTeacherChange"
                  required
                  :disabled="!form.studentId"
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none disabled:bg-gray-100"
                >
                  <option :value="null" disabled>Sélectionner un enseignant...</option>
                  <option v-for="t in teacherOptions" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
                <p v-if="form.studentId && teacherOptions.length === 0" class="text-xs text-red-600 mt-1">
                  Cet élève n'a aucune matière assignée.
                </p>
              </div>

              <div v-if="form.studentId && form.teacherId" class="text-xs p-2.5 bg-indigo-50 rounded-lg border border-indigo-100 text-indigo-900 space-y-0.5">
                <p>
                  Séances non payées :
                  <span class="font-bold">{{ currentStudentDue ? currentStudentDue.sessionsUnpaid : 0 }}</span>
                  <template v-if="currentStudentDue"> (sur {{ currentStudentDue.sessionsAttended }} suivies)</template>
                </p>
                <p>
                  Prix par séance :
                  <span class="font-bold">
                    {{ currentUnitAmount > 0 ? currentUnitAmount.toFixed(2) + ' TND' : 'à définir (modifiez l\'élève)' }}
                  </span>
                </p>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Séances payées *</label>
                  <input
                    v-model.number="form.sessionsPaid"
                    @input="recomputeAmount"
                    type="number"
                    min="1"
                    step="1"
                    required
                    class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                  />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Montant (TND) *</label>
                  <input
                    v-model.number="form.amount"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                  />
                </div>
              </div>
            </template>

            <!-- Garderie -->
            <template v-else>
              <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Enfant de garde *</label>
                <select
                  v-model="form.daycareChildId"
                  @change="onChildChange"
                  required
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                >
                  <option :value="null" disabled>Sélectionner un enfant...</option>
                  <option v-for="c in children" :key="c.id" :value="c.id">{{ c.name }} (parent : {{ c.parentName }})</option>
                </select>
              </div>

              <div v-if="form.daycareChildId" class="text-xs p-2.5 bg-orange-50 rounded-lg border border-orange-100 text-orange-900 space-y-0.5">
                <p>
                  Mois non payés :
                  <span class="font-bold">{{ currentChildDue ? currentChildDue.monthsUnpaid : 0 }}</span>
                </p>
                <p>
                  Tarif mensuel :
                  <span class="font-bold">
                    {{ currentChildFee > 0 ? currentChildFee.toFixed(2) + ' TND' : 'à définir (page Élèves de garde)' }}
                  </span>
                </p>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Mois payés *</label>
                  <input
                    v-model.number="form.monthsPaid"
                    @input="recomputeAmount"
                    type="number"
                    min="1"
                    step="1"
                    required
                    class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                  />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Montant (TND) *</label>
                  <input
                    v-model.number="form.amount"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                  />
                </div>
              </div>
            </template>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Date de paiement</label>
              <input
                v-model="form.date"
                type="date"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
          </template>

          <!-- ===== Modification ===== -->
          <template v-else>
            <div class="text-sm p-2.5 bg-gray-50 rounded-lg border border-gray-200">
              <p class="font-semibold text-gray-900">{{ form.studentName }}</p>
              <p class="text-xs text-gray-500">{{ editingPayment ? paymentDetail(editingPayment) : '' }}</p>
            </div>

            <div v-if="editingPayment && editingPayment.type === 'student' && editingPayment.teacherId">
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Séances payées</label>
              <input
                v-model.number="form.sessionsPaid"
                type="number"
                min="0"
                step="1"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>

            <div v-if="editingPayment && editingPayment.type === 'daycare'">
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Mois payés</label>
              <input
                v-model.number="form.monthsPaid"
                type="number"
                min="0"
                step="1"
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>

            <div v-if="isLegacyEdit" class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Mois</label>
                <input
                  v-model="form.month"
                  type="text"
                  required
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Statut</label>
                <select
                  v-model="form.status"
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                >
                  <option value="paid">Payé</option>
                  <option value="pending">En attente</option>
                  <option value="overdue">En retard</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Montant (TND) *</label>
                <input
                  v-model.number="form.amount"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Date de paiement</label>
                <input
                  v-model="form.date"
                  type="date"
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                />
              </div>
            </div>
          </template>

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

    <!-- Modal Reçu de Paiement Imprimable -->
    <div v-if="showReceiptModal && receiptPayment" class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click="showReceiptModal = false">
      <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8 max-w-lg w-full" @click.stop>
        <!-- Cadre Reçu -->
        <div id="printable-receipt" class="border-2 border-dashed border-indigo-200 rounded-xl p-6 bg-slate-50/50">
          <!-- En-tête -->
          <div class="flex items-center justify-between border-b border-gray-200 pb-4 mb-4">
            <div class="flex items-center gap-3">
              <img src="/logo.png" alt="Logo" class="w-12 h-12 object-contain" />
              <div>
                <h2 class="text-xl font-black text-indigo-900 tracking-tight">CENTRE D'ÉTUDES</h2>
                <p class="text-xs text-gray-500 font-medium">Soutien Scolaire & Garderie</p>
              </div>
            </div>
            <div class="text-right">
              <span class="inline-block px-2.5 py-1 bg-green-100 text-green-800 font-bold text-xs rounded-full uppercase tracking-wider">
                Reçu N° {{ receiptPayment.id.toString().padStart(5, '0') }}
              </span>
              <p class="text-[11px] text-gray-500 mt-1">Date: {{ receiptPayment.date || today() }}</p>
            </div>
          </div>

          <!-- Corps du reçu -->
          <div class="space-y-3 text-sm">
            <div class="flex justify-between py-1 border-b border-gray-100">
              <span class="text-gray-500">Bénéficiaire :</span>
              <span class="font-bold text-gray-900">{{ receiptPayment.studentName }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-gray-100">
              <span class="text-gray-500">Prestation / Détail :</span>
              <span class="font-semibold text-gray-800">{{ paymentDetail(receiptPayment) }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-gray-100">
              <span class="text-gray-500">Période concernée :</span>
              <span class="text-gray-800">{{ receiptPayment.month }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-gray-100">
              <span class="text-gray-500">Quantité réglée :</span>
              <span class="font-semibold text-indigo-700">
                {{ receiptPayment.daycareChildId ? receiptPayment.monthsPaid + ' mois de garderie' : receiptPayment.sessionsPaid + ' séance(s)' }}
              </span>
            </div>

            <!-- Cadre Montant -->
            <div class="mt-4 p-4 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-between">
              <span class="text-sm font-bold text-indigo-950 uppercase">Montant Réglé :</span>
              <span class="text-2xl font-black text-indigo-700">{{ Number(receiptPayment.amount).toFixed(2) }} TND</span>
            </div>

            <!-- Signatures -->
            <div class="grid grid-cols-2 gap-4 pt-6 text-xs text-center">
              <div>
                <p class="text-gray-400 italic">Signature du parent</p>
                <div class="h-10 border-b border-gray-300 mt-2"></div>
              </div>
              <div>
                <p class="text-gray-400 italic">Cachet & Direction</p>
                <div class="h-10 border-b border-gray-300 mt-2 flex items-center justify-center">
                  <span class="text-[10px] text-green-700 font-bold border border-green-500 px-2 py-0.5 rounded uppercase">
                    ✓ RÉGLÉ
                  </span>
                </div>
                <p class="text-[10px] text-gray-500 font-medium mt-1">
                  Encaissé par : {{ receiptPayment.createdByName || 'Direction' }} ({{ receiptPayment.createdByRole === 'secretary' ? 'Secrétaire' : 'Direction' }})
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Boutons d'action -->
        <div class="flex items-center justify-end gap-3 mt-6 print:hidden">
          <button
            @click="showReceiptModal = false"
            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors"
          >
            Fermer
          </button>
          <button
            @click="printReceipt"
            class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 shadow-md transition-colors"
          >
            <Printer class="w-4 h-4" />
            Imprimer le reçu
          </button>
        </div>
      </div>
    </div>

    <!-- Official Payment Extract Modal (Director only) -->
    <PaymentExtractModal
      :isOpen="showExtractModal"
      :initialType="extractInitialType"
      :initialTeacherId="extractInitialTeacherId"
      :initialStudentId="extractInitialStudentId"
      @close="showExtractModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { DollarSign, Clock, AlertCircle, CheckCircle, Search, Plus, Edit2, Trash2, Printer, FileText } from 'lucide-vue-next';
import api from '../utils/api';
import PaymentExtractModal from './PaymentExtractModal.vue';

type DueStatus = 'pending' | 'overdue' | 'ok';

interface Payment {
  id: number;
  studentName: string;
  month: string;
  amount: number;
  date: string | null;
  status: 'paid' | 'pending' | 'overdue';
  type: 'student' | 'daycare';
  studentId: number | null;
  daycareChildId: number | null;
  teacherId: number | null;
  teacherName: string | null;
  sessionsPaid: number;
  monthsPaid: number;
  createdByName?: string;
  createdByRole?: string;
}

interface DueStudent {
  key: string;
  studentId: number;
  studentName: string;
  groupName: string | null;
  teacherId: number;
  teacherName: string;
  subjects: string[];
  sessionsAttended: number;
  sessionsPaid: number;
  sessionsUnpaid: number;
  pricePerSession: number;
  priceSource: 'session' | 'monthly' | 'default';
  unitAmount: number;
  amountDue: number;
  status: DueStatus;
}

interface DueDaycare {
  key: string;
  daycareChildId: number;
  name: string;
  parentName: string;
  monthlyFee: number;
  feeSource?: 'set' | 'default';
  monthsDue: number;
  monthsPaid: number;
  monthsUnpaid: number;
  amountDue: number;
  status: DueStatus;
}

interface Dues {
  thresholds: {
    studentPendingFrom: number;
    studentOverdueAfter: number;
    daycarePendingFrom: number;
    daycareOverdueAfter: number;
  };
  pricing: { sessionsPerMonth: number; defaultSessionPrice: number; defaultDaycareMonthlyFee: number };
  students: DueStudent[];
  daycare: DueDaycare[];
  totals: { pending: number; overdue: number; pendingCount: number; overdueCount: number };
}

interface StudentLite {
  id: number;
  name: string;
  level: string;
  group_name?: string;
  is_remise?: boolean;
  remise?: number;
  discount_type?: 'percentage' | 'fixed';
  subjects?: Array<{ teacher_id?: number; teacher?: string; price_per_session?: number; price_per_month?: number }>;
}

interface ChildLite {
  id: number;
  name: string;
  parentName: string;
  monthlyFee?: number;
}

type Kind = 'student' | 'daycare';

const kinds: Array<{ value: Kind; label: string }> = [
  { value: 'student', label: 'Élèves' },
  { value: 'daycare', label: 'Garderie' }
];

const duesFilters: Array<{ value: 'all' | 'pending' | 'overdue'; label: string }> = [
  { value: 'all', label: 'Tous' },
  { value: 'pending', label: 'En attente' },
  { value: 'overdue', label: 'En retard' }
];

const payments = ref<Payment[]>([]);
const students = ref<StudentLite[]>([]);
const children = ref<ChildLite[]>([]);
const dues = ref<Dues>({
  thresholds: { studentPendingFrom: 4, studentOverdueAfter: 7, daycarePendingFrom: 1, daycareOverdueAfter: 2 },
  pricing: { sessionsPerMonth: 4, defaultSessionPrice: 15, defaultDaycareMonthlyFee: 0 },
  students: [],
  daycare: [],
  totals: { pending: 0, overdue: 0, pendingCount: 0, overdueCount: 0 }
});

const searchTerm = ref('');
const duesKind = ref<Kind>('student');
const duesFilter = ref<'all' | 'pending' | 'overdue'>('all');
const loading = ref(false);
const submitting = ref(false);
const errorMessage = ref('');
const loadError = ref('');

const showModal = ref(false);
const isEditing = ref(false);
const editingPayment = ref<Payment | null>(null);

// Extract modal state (Director only)
const showExtractModal = ref(false);
const extractInitialType = ref<'teachers' | 'students'>('teachers');
const extractInitialTeacherId = ref<number | null>(null);
const extractInitialStudentId = ref<number | null>(null);

const currentUser = ref<{ role?: string; name?: string } | null>(null);
try {
  const saved = localStorage.getItem('user');
  if (saved) currentUser.value = JSON.parse(saved);
} catch {}

const isDirector = computed(() => {
  return currentUser.value?.role === 'director' || localStorage.getItem('user')?.includes('director');
});

function openExtractModal(type: 'teachers' | 'students' = 'teachers', entityId: number | null = null) {
  extractInitialType.value = type;
  if (type === 'teachers') {
    extractInitialTeacherId.value = entityId;
    extractInitialStudentId.value = null;
  } else {
    extractInitialStudentId.value = entityId;
    extractInitialTeacherId.value = null;
  }
  showExtractModal.value = true;
}

const today = () => new Date().toISOString().split('T')[0];

function emptyForm() {
  return {
    kind: 'student' as Kind,
    studentId: null as number | null,
    teacherId: null as number | null,
    daycareChildId: null as number | null,
    sessionsPaid: 1,
    monthsPaid: 1,
    amount: 0,
    date: today(),
    month: '',
    status: 'paid' as 'paid' | 'pending' | 'overdue',
    studentName: ''
  };
}

const form = ref(emptyForm());

function firstError(e: any, fallback: string) {
  const errors = e?.response?.data?.errors;
  if (errors) {
    const first = Object.values(errors)[0] as string[];
    if (first?.length) return first[0];
  }
  return e?.response?.data?.message || fallback;
}

function round2(value: number) {
  return Math.round(value * 100) / 100;
}

/* ---------- Chargement ---------- */

async function loadPayments() {
  try {
    const res = await api.get('/api/payments');
    payments.value = res.data || [];
  } catch (e) {
    console.error('Erreur chargement paiements:', e);
    payments.value = [];
  }
}

async function loadDues() {
  try {
    const res = await api.get('/api/payment-dues');
    dues.value = res.data;
    loadError.value = '';
  } catch (e) {
    console.error('Erreur chargement soldes:', e);
    loadError.value = firstError(
      e,
      'Impossible de calculer les soldes. Vérifiez que la migration a bien été exécutée.'
    );
  }
}

async function loadData() {
  loading.value = true;
  try {
    const [, , sRes, cRes] = await Promise.all([
      loadPayments(),
      loadDues(),
      api.get('/api/students').catch(() => ({ data: [] })),
      api.get('/api/daycare').catch(() => ({ data: [] }))
    ]);
    students.value = sRes.data || [];
    children.value = cRes.data || [];
  } finally {
    loading.value = false;
  }
}

onMounted(loadData);

/* ---------- Statistiques et filtres ---------- */

const totalPaid = computed(() =>
  payments.value
    .filter(p => p.status === 'paid')
    .reduce((sum, p) => sum + Number(p.amount), 0)
);

const rulesText = computed(() => {
  const t = dues.value.thresholds;
  if (duesKind.value === 'student') {
    return `En attente : de ${t.studentPendingFrom} à ${t.studentOverdueAfter} séances non payées avec un même enseignant. En retard : plus de ${t.studentOverdueAfter} séances.`;
  }
  return `En attente : de ${t.daycarePendingFrom} à ${t.daycareOverdueAfter} mois non payés. En retard : plus de ${t.daycareOverdueAfter} mois.`;
});

const term = computed(() => searchTerm.value.trim().toLowerCase());

const filteredStudentDues = computed(() =>
  dues.value.students.filter(r => {
    const matchesSearch =
      !term.value ||
      r.studentName.toLowerCase().includes(term.value) ||
      r.teacherName.toLowerCase().includes(term.value);
    const matchesStatus = duesFilter.value === 'all' || r.status === duesFilter.value;
    return matchesSearch && matchesStatus;
  })
);

const filteredDaycareDues = computed(() =>
  dues.value.daycare.filter(r => {
    const matchesSearch =
      !term.value ||
      r.name.toLowerCase().includes(term.value) ||
      r.parentName.toLowerCase().includes(term.value);
    const matchesStatus = duesFilter.value === 'all' || r.status === duesFilter.value;
    return matchesSearch && matchesStatus;
  })
);

const filteredPayments = computed(() =>
  payments.value.filter(p => {
    if (!term.value) return true;
    return (
      p.studentName.toLowerCase().includes(term.value) ||
      (p.month || '').toLowerCase().includes(term.value) ||
      (p.teacherName || '').toLowerCase().includes(term.value)
    );
  })
);

function paymentDetail(p: Payment) {
  if (p.daycareChildId) {
    return `${p.monthsPaid} mois`;
  }
  if (p.teacherId) {
    return `${p.sessionsPaid} séance(s) · ${p.teacherName ?? 'Enseignant'}`;
  }
  return '-';
}

/* ---------- Statuts (affichage) ---------- */

function getStatusLabel(status: string) {
  switch (status) {
    case 'paid': return 'Payé';
    case 'pending': return 'En attente';
    case 'overdue': return 'En retard';
    case 'ok': return 'En cours';
    default: return status;
  }
}

function getStatusIcon(status: string) {
  switch (status) {
    case 'paid': return CheckCircle;
    case 'overdue': return AlertCircle;
    default: return Clock;
  }
}

function getStatusColor(status: string) {
  switch (status) {
    case 'paid': return 'text-green-600';
    case 'pending': return 'text-yellow-600';
    case 'overdue': return 'text-red-600';
    default: return 'text-gray-400';
  }
}

function getStatusTextColor(status: string) {
  switch (status) {
    case 'paid': return 'text-green-700';
    case 'pending': return 'text-yellow-700';
    case 'overdue': return 'text-red-700';
    default: return 'text-gray-500';
  }
}

/* ---------- Formulaire : calculs automatiques ---------- */

const isLegacyEdit = computed(
  () => !!editingPayment.value && !editingPayment.value.studentId && !editingPayment.value.daycareChildId
);

const teacherOptions = computed(() => {
  const s = students.value.find(st => st.id === form.value.studentId);
  if (!s) return [] as Array<{ id: number; name: string }>;
  const seen = new Map<number, string>();
  for (const sub of s.subjects || []) {
    if (sub.teacher_id && !seen.has(sub.teacher_id)) {
      seen.set(sub.teacher_id, sub.teacher || `Enseignant ${sub.teacher_id}`);
    }
  }
  return Array.from(seen.entries()).map(([id, name]) => ({ id, name }));
});

const currentStudentDue = computed(
  () =>
    dues.value.students.find(
      r => r.studentId === form.value.studentId && r.teacherId === form.value.teacherId
    ) ?? null
);

const currentChildDue = computed(
  () => dues.value.daycare.find(r => r.daycareChildId === form.value.daycareChildId) ?? null
);

function unitAmountFor(studentId: number | null, teacherId: number | null): number {
  if (!studentId || !teacherId) return 0;

  const due = dues.value.students.find(r => r.studentId === studentId && r.teacherId === teacherId);
  if (due) return due.unitAmount;

  // Pas de solde en cours : on prend le prix de la séance de l'inscription
  const s = students.value.find(st => st.id === studentId);
  if (!s) return 0;
  // Même règle que le serveur : prix par séance, sinon prix mensuel / 4, sinon tarif par défaut
  const p = dues.value.pricing;
  const prices: number[] = [];
  for (const x of s.subjects || []) {
    if (x.teacher_id !== teacherId) continue;
    const perSession = Number(x.price_per_session ?? 0);
    const perMonth = Number(x.price_per_month ?? 0);
    if (perSession > 0) prices.push(perSession);
    else if (perMonth > 0) prices.push(round2(perMonth / p.sessionsPerMonth));
  }
  const price = prices.length > 0
    ? round2(prices.reduce((a, b) => a + b, 0) / prices.length)
    : p.defaultSessionPrice;
  const factor =
    s.is_remise && s.discount_type === 'percentage' && Number(s.remise) > 0
      ? Math.max(0, 1 - Number(s.remise) / 100)
      : 1;
  return round2(price * factor);
}

function childFeeFor(childId: number | null): number {
  if (!childId) return 0;
  const c = children.value.find(ch => ch.id === childId);
  const fee = Number(c?.monthlyFee ?? 0);
  return fee > 0 ? fee : dues.value.pricing.defaultDaycareMonthlyFee;
}

const currentUnitAmount = computed(() => unitAmountFor(form.value.studentId, form.value.teacherId));
const currentChildFee = computed(() => childFeeFor(form.value.daycareChildId));

function recomputeAmount() {
  if (form.value.kind === 'student') {
    const unit = unitAmountFor(form.value.studentId, form.value.teacherId);
    form.value.amount = unit > 0 ? round2(Number(form.value.sessionsPaid || 0) * unit) : 0;
  } else {
    const fee = childFeeFor(form.value.daycareChildId);
    form.value.amount = fee > 0 ? round2(Number(form.value.monthsPaid || 0) * fee) : 0;
  }
}

function onStudentChange() {
  form.value.teacherId = teacherOptions.value[0]?.id ?? null;
  onTeacherChange();
}

function onTeacherChange() {
  const due = currentStudentDue.value;
  form.value.sessionsPaid = due ? due.sessionsUnpaid : 1;
  recomputeAmount();
}

function onChildChange() {
  const due = currentChildDue.value;
  form.value.monthsPaid = due ? due.monthsUnpaid : 1;
  recomputeAmount();
}

function setKind(kind: Kind) {
  form.value = { ...emptyForm(), kind };
}

/* ---------- Ouverture des fenêtres ---------- */

function openAddModal(
  prefill: { kind?: Kind; studentId?: number; teacherId?: number; daycareChildId?: number } = {}
) {
  isEditing.value = false;
  editingPayment.value = null;
  errorMessage.value = '';
  form.value = { ...emptyForm(), kind: prefill.kind ?? 'student' };

  if (form.value.kind === 'student' && prefill.studentId) {
    form.value.studentId = prefill.studentId;
    form.value.teacherId = prefill.teacherId ?? teacherOptions.value[0]?.id ?? null;
    onTeacherChange();
  } else if (form.value.kind === 'daycare' && prefill.daycareChildId) {
    form.value.daycareChildId = prefill.daycareChildId;
    onChildChange();
  }

  showModal.value = true;
}

function openEditModal(payment: Payment) {
  isEditing.value = true;
  editingPayment.value = payment;
  errorMessage.value = '';
  form.value = {
    ...emptyForm(),
    kind: payment.daycareChildId ? 'daycare' : 'student',
    studentName: payment.studentName,
    sessionsPaid: payment.sessionsPaid,
    monthsPaid: payment.monthsPaid,
    amount: Number(payment.amount),
    date: payment.date || '',
    month: payment.month,
    status: payment.status
  };
  showModal.value = true;
}

/* ---------- Enregistrement ---------- */

async function savePayment() {
  submitting.value = true;
  errorMessage.value = '';

  try {
    if (isEditing.value && editingPayment.value) {
      const p = editingPayment.value;
      const payload: Record<string, any> = {
        amount: form.value.amount,
        date: form.value.date || null
      };
      if (p.type === 'student' && p.teacherId) payload.sessionsPaid = form.value.sessionsPaid;
      if (p.type === 'daycare') payload.monthsPaid = form.value.monthsPaid;
      if (!p.studentId && !p.daycareChildId) {
        payload.month = form.value.month;
        payload.status = form.value.status;
        payload.studentName = form.value.studentName;
      }
      await api.put(`/api/payments/${p.id}`, payload);
    } else if (form.value.kind === 'student') {
      await api.post('/api/payments', {
        studentId: form.value.studentId,
        teacherId: form.value.teacherId,
        sessionsPaid: form.value.sessionsPaid,
        amount: form.value.amount,
        date: form.value.date || null
      });
    } else {
      await api.post('/api/payments', {
        daycareChildId: form.value.daycareChildId,
        monthsPaid: form.value.monthsPaid,
        amount: form.value.amount,
        date: form.value.date || null
      });
    }

    showModal.value = false;
    await Promise.all([loadPayments(), loadDues()]);
  } catch (e: any) {
    console.error('Erreur sauvegarde paiement:', e);
    errorMessage.value = firstError(e, 'Erreur lors de la sauvegarde');
  } finally {
    submitting.value = false;
  }
}

async function markAsPaid(payment: Payment) {
  const todayStr = today();
  const oldStatus = payment.status;
  const oldDate = payment.date;
  payment.status = 'paid';
  payment.date = todayStr;
  try {
    await api.put(`/api/payments/${payment.id}`, { status: 'paid', date: todayStr });
  } catch (e) {
    payment.status = oldStatus;
    payment.date = oldDate;
    console.error('Erreur validation paiement:', e);
    alert('Impossible de marquer ce paiement comme payé.');
  }
}

async function deletePayment(id: number) {
  if (!confirm('Voulez-vous vraiment supprimer cet enregistrement de paiement ?')) return;
  try {
    await api.delete(`/api/payments/${id}`);
    payments.value = payments.value.filter(p => p.id !== id);
    await loadDues();
  } catch (e) {
    console.error('Erreur suppression paiement:', e);
    alert('Impossible de supprimer ce paiement.');
  }
}

// Reçus de paiement
const receiptPayment = ref<Payment | null>(null);
const showReceiptModal = ref(false);

function openReceipt(payment: Payment) {
  receiptPayment.value = payment;
  showReceiptModal.value = true;
}

function printReceipt() {
  window.print();
}
</script>

<style>
@media print {
  body * {
    visibility: hidden;
  }
  #printable-receipt, #printable-receipt * {
    visibility: visible;
  }
  #printable-receipt {
    position: fixed;
    left: 50%;
    top: 50px;
    transform: translateX(-50%);
    width: 600px;
    max-width: 90%;
    margin: 0;
    padding: 24px;
    background: white;
    border: 2px solid #4f46e5 !important;
    border-radius: 12px;
    box-shadow: none;
    z-index: 99999;
  }
}
</style>