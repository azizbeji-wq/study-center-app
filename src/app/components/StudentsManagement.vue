<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Gestion des Élèves</h2>
        <p class="text-sm text-gray-500 mt-1">{{ students.length }} élèves inscrits</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-if="isDirector"
          @click="openExtractModal()"
          class="flex items-center gap-2 px-3.5 py-2 bg-emerald-50 text-emerald-800 font-bold rounded-lg hover:bg-emerald-100 transition-colors border border-emerald-200 text-sm shadow-xs"
          title="Exporter un extrait officiel des paiements pour les élèves (PDF / Excel)"
        >
          <FileText class="w-4 h-4 text-emerald-600" />
          <span>Extraits de Paiements</span>
        </button>

        <button
          @click="openAddModal"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"
        >
          <Plus class="w-4 h-4" />
          Ajouter un élève
        </button>
      </div>
    </div>

    <!-- Search & Filter -->
    <div class="relative">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
      <input
        type="text"
        placeholder="Rechercher par nom ou niveau..."
        v-model="searchTerm"
        class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-base shadow-sm"
      />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Students Table -->
    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
      <div class="w-full overflow-x-auto pb-4"><table class="w-full whitespace-nowrap">
        <thead class="bg-gray-50/75 border-b border-gray-200">
          <tr>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nom</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Niveau</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Matières</th>
            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Frais mensuels</th>
            <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="student in filteredStudents" :key="student.id" class="hover:bg-gray-50/80 transition-colors">
            <td class="px-6 py-4">
              <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold">
                  {{ student.name.charAt(0) }}
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-900">{{ student.name }}</span>
                    <span
                      v-if="student.is_remise"
                      class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800"
                    >
                      Remise {{ student.remise }}{{ student.discount_type === 'percentage' ? '%' : ' TND' }}
                    </span>
                  </div>
                  <p class="text-xs text-gray-500">Tél: {{ student.phone }}</p>
                </div>
              </div>
            </td>
            <td class="px-6 py-4">
              <div class="space-y-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                  {{ student.group_name || 'Non assigné' }}
                </span>
                <p class="text-xs text-gray-600 font-medium">{{ student.level }}</p>
              </div>
            </td>
            <td class="px-6 py-4">
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="(sub, idx) in (student.subjects || []).slice(0, 2)"
                  :key="idx"
                  class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded-md border border-gray-200"
                >
                  <BookOpen class="w-3 h-3 text-indigo-500" />
                  {{ sub.subject }}
                </span>
                <span v-if="(student.subjects || []).length > 2" class="inline-flex px-2 py-0.5 text-xs font-medium text-gray-500 bg-gray-100 rounded-md">
                  +{{ (student.subjects || []).length - 2 }}
                </span>
                <span v-if="!student.subjects || student.subjects.length === 0" class="text-xs text-gray-400">
                  Aucune
                </span>
              </div>
            </td>
            <td class="px-6 py-4">
              <div class="text-xs space-y-0.5">
                <p :class="['text-sm font-bold', feeValue(student) > 0 ? 'text-gray-900' : 'text-gray-400']">
                  {{ feeValue(student) > 0 ? feeValue(student) + ' TND' : 'À définir' }}
                </p>
                <p v-if="student.is_remise && feeValue(student) > 0" class="text-emerald-600 text-[11px] font-medium">
                  Après remise (Brut: {{ student.calculated_fee?.gross ?? 0 }} TND)
                </p>
              </div>
            </td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-1.5">
                <button
                  v-if="isDirector"
                  @click="openExtractModal(student.id)"
                  class="p-1.5 hover:bg-emerald-50 text-emerald-600 rounded-lg transition-colors"
                  title="Générer l'extrait de compte de cet élève (PDF / Excel)"
                >
                  <FileText class="w-4 h-4" />
                </button>
                <button
                  @click="selectedStudent = student"
                  class="p-1.5 hover:bg-gray-100 text-gray-600 hover:text-indigo-600 rounded-lg transition-colors"
                  title="Voir details"
                >
                  <Eye class="w-4 h-4" />
                </button>
                <button
                  @click="openEditModal(student)"
                  class="p-1.5 hover:bg-gray-100 text-gray-600 hover:text-blue-600 rounded-lg transition-colors"
                  title="Modifier"
                >
                  <Edit2 class="w-4 h-4" />
                </button>
                <button
                  @click="deleteStudent(student.id)"
                  class="p-1.5 hover:bg-red-50 text-gray-600 hover:text-red-600 rounded-lg transition-colors"
                  title="Supprimer"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table></div>

      <div v-if="filteredStudents.length === 0" class="text-center py-12">
        <p class="text-gray-500 text-sm">Aucun élève trouvé</p>
      </div>
    </div>

    <!-- Student Details Modal -->
    <div v-if="selectedStudent" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click="selectedStudent = null">
      <div class="bg-white rounded-2xl p-6 max-w-lg w-full shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto mx-4 md:mx-auto" @click.stop>
        <div class="flex items-center justify-between border-b pb-3 border-gray-100">
          <div>
            <h3 class="text-xl font-bold text-gray-900">{{ selectedStudent.name }}</h3>
            <p class="text-xs text-gray-500">Groupe: {{ selectedStudent.group_name || 'Non assigné' }} • {{ selectedStudent.level }}</p>
          </div>
          <button @click="selectedStudent = null" class="text-gray-400 hover:text-gray-600 text-lg leading-none">×</button>
        </div>

        <div class="space-y-4">
          <!-- Discount & Calculation Card -->
          <div class="bg-linear-to-r from-indigo-50 to-purple-50 p-4 rounded-xl border border-indigo-100 space-y-2">
            <div class="flex justify-between items-center text-xs">
              <span class="text-gray-600">Tarif brut:</span>
              <span class="font-semibold text-gray-900">{{ selectedStudent.calculated_fee?.gross ?? selectedStudent.base_fee ?? 0 }} TND</span>
            </div>
            <div v-if="selectedStudent.is_remise" class="flex justify-between items-center text-xs text-amber-700">
              <span>Remise accordée ({{ selectedStudent.remise }}{{ selectedStudent.discount_type === 'percentage' ? '%' : ' TND' }}):</span>
              <span class="font-bold">-{{ selectedStudent.calculated_fee?.discount ?? 0 }} TND</span>
            </div>
            <div class="pt-2 border-t border-indigo-200/60 flex justify-between items-center">
              <span class="text-sm font-bold text-gray-900">Total Mensuel à Payer:</span>
              <span class="text-lg font-black text-indigo-700">{{ feeValue(selectedStudent) }} TND</span>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 bg-gray-50 p-3.5 rounded-xl border border-gray-100 text-xs">
            <div>
              <p class="text-gray-500">Téléphone élève</p>
              <p class="font-semibold text-gray-800 mt-0.5">{{ selectedStudent.phone }}</p>
            </div>
            <div>
              <p class="text-gray-500">Présences / Séances</p>
              <p class="font-semibold text-gray-800 mt-0.5">
                {{ selectedStudent.attendance_stats?.present || 0 }} présents sur {{ selectedStudent.attendance_stats?.total || 0 }}
              </p>
            </div>
          </div>

          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Matières et Enseignants</p>
            <div v-if="selectedStudent.subjects && selectedStudent.subjects.length > 0" class="space-y-2">
              <div
                v-for="(sub, idx) in selectedStudent.subjects"
                :key="idx"
                class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-200"
              >
                <div class="flex items-center gap-2">
                  <BookOpen class="w-4 h-4 text-indigo-600" />
                  <span class="text-sm font-medium text-gray-900">{{ sub.subject }}</span>
                </div>
                <span class="text-xs text-gray-600 bg-white px-2 py-1 rounded border">{{ sub.teacher }}</span>
              </div>
            </div>
            <p v-else class="text-sm text-gray-400 italic">Aucune matière assignée.</p>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button @click="selectedStudent = null" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200">
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Student Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4" @click="closeModal">
      <div class="bg-white rounded-2xl p-6 max-w-2xl w-full shadow-2xl space-y-4 max-h-[92vh] flex flex-col max-h-[90vh] overflow-y-auto mx-4 md:mx-auto" @click.stop>
        <div class="flex items-center justify-between border-b pb-3 border-gray-100">
          <div>
            <h3 class="text-xl font-bold text-gray-900">
              {{ isEditing ? "Modifier l'élève" : 'Inscrire un nouvel élève' }}
            </h3>
            <p class="text-xs text-gray-500">Système éducatif tunisien (Collège & Lycée), groupe et tarification</p>
          </div>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600 text-lg">×</button>
        </div>

        <form @submit.prevent="saveStudent" class="space-y-4 flex-1 overflow-y-auto pr-1">
          <!-- Personal Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nom complet *</label>
              <input
                v-model="formData.name"
                type="text"
                required
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none text-base"
                placeholder="Ex: Yassine Trabelsi"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Téléphone de l'élève *</label>
              <input
                v-model="formData.phone"
                type="text"
                required
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none text-base"
                placeholder="Ex: 21 345 678"
              />
            </div>
          </div>

          <!-- Tunisian Level & Group -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-gray-50/70 p-3.5 rounded-xl border border-gray-200">
            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                Niveau scolaire (Tunisie) *
              </label>
              <select
                v-model="formData.level"
                @change="onLevelChange"
                required
                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              >
                <option value="" disabled>Sélectionner un niveau</option>
                <optgroup label="Collège (Base)">
                  <option
                    v-for="lvl in tunisianLevels.filter(l => l.cycle === 'college')"
                    :key="lvl.id"
                    :value="lvl.name"
                  >
                    {{ lvl.name }}
                  </option>
                </optgroup>
                <optgroup label="Secondaire & Baccalauréat">
                  <option
                    v-for="lvl in tunisianLevels.filter(l => l.cycle === 'lycee')"
                    :key="lvl.id"
                    :value="lvl.name"
                  >
                    {{ lvl.name }}
                  </option>
                </optgroup>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                Groupe *
              </label>
              <div class="relative">
                <input
                  v-model="formData.group_name"
                  list="group-suggestions"
                  required
                  placeholder="Ex: M1, Sc2..."
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none uppercase font-semibold"
                />
                <datalist id="group-suggestions">
                  <option v-for="grp in suggestedGroups" :key="grp" :value="grp" />
                </datalist>
              </div>
            </div>
          </div>

          <!-- Remise / Discount -->
          <div class="bg-amber-50/50 p-3.5 rounded-xl border border-amber-200/80 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <input
                  id="has-remise"
                  v-model="formData.is_remise"
                  type="checkbox"
                  class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"
                />
                <label for="has-remise" class="text-xs font-bold text-amber-900 uppercase tracking-wider cursor-pointer">
                  Accorder une Remise (Discount)
                </label>
              </div>
              <span v-if="formData.is_remise" class="text-xs font-medium text-amber-700">
                Remise activée
              </span>
            </div>

            <div v-if="formData.is_remise" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-amber-200/60">
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Type de remise</label>
                <select
                  v-model="formData.discount_type"
                  class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500"
                >
                  <option value="fixed">Montant Fixe (TND)</option>
                  <option value="percentage">Pourcentage (%)</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                  Valeur de la remise ({{ formData.discount_type === 'percentage' ? '%' : 'TND' }}) *
                </label>
                <input
                  v-model.number="formData.remise"
                  type="number"
                  min="0"
                  step="0.5"
                  required
                  placeholder="Ex: 20"
                  class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-base bg-white focus:ring-2 focus:ring-indigo-500"
                />
              </div>
            </div>

            <!-- Synthèse automatique des frais -->
            <div class="pt-2 border-t border-amber-200/50 space-y-1.5 text-xs">
              <div class="flex items-center justify-between text-gray-600">
                <span>Total brut des cours ({{ formData.subjects.length }} matière{{ formData.subjects.length > 1 ? 's' : '' }}) :</span>
                <span class="font-bold text-gray-900">{{ calculatedGrossPreview }} TND / mois</span>
              </div>
              <div v-if="formData.is_remise && calculatedDiscountValue > 0" class="flex items-center justify-between text-amber-700 font-medium">
                <span>Déduction remise ({{ formData.remise }}{{ formData.discount_type === 'percentage' ? '%' : ' TND' }}) :</span>
                <span class="font-bold">-{{ calculatedDiscountValue }} TND</span>
              </div>
              <div class="flex items-center justify-between pt-1 border-t border-amber-200/70 text-indigo-950 font-bold">
                <span class="text-xs uppercase tracking-wide text-indigo-900">Total net mensuel élève :</span>
                <span class="text-base font-black text-indigo-700">{{ calculatedNetPreview }} TND / mois</span>
              </div>
              <div v-if="formData.is_remise && calculatedDiscountValue > 0" class="mt-1 p-2 rounded-lg bg-amber-50 border border-amber-200/80 text-[11px] text-amber-900 flex items-start gap-1.5">
                <span class="font-black text-amber-600">ℹ️</span>
                <span><strong>Règle de gestion :</strong> La remise de <strong>{{ calculatedDiscountValue }} TND</strong> est déduite à 100% de la part de l'administration. La rémunération des enseignants reste calculée sur le brut intégral ({{ calculatedGrossPreview }} TND).</span>
              </div>
            </div>
          </div>

          <!-- Subjects Selection (Tunisian Curriculum) -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <div>
                <label class="text-xs font-semibold text-gray-700 uppercase tracking-wider">
                  Enseignants & Matières sélectionnées
                </label>
                <p class="text-[11px] text-gray-500">Calcul : Nombre de séances × Prix séance fixé par l'administration (somme de toutes les matières)</p>
              </div>
              <button
                type="button"
                @click="addSubjectRow"
                class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1"
              >
                <Plus class="w-3 h-3" /> Ajouter matière
              </button>
            </div>

            <div class="space-y-2.5 max-h-56 overflow-y-auto pr-1">
              <div v-for="(sub, idx) in formData.subjects" :key="idx" class="p-2.5 bg-gray-50 rounded-xl border border-gray-200 space-y-2">
                <div class="flex items-center gap-2">
                  <!-- 1. Enseignant -->
                  <div class="flex-1">
                    <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-0.5">Enseignant</label>
                    <select
                      v-model="sub.teacher_id"
                      @change="onTeacherChange(sub)"
                      required
                      class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 font-medium"
                    >
                      <option :value="undefined" disabled>Choisir l'enseignant...</option>
                      <option v-for="t in teachers" :key="t.id" :value="t.id">
                        {{ t.name }} ({{ t.subject }})
                      </option>
                    </select>
                  </div>

                  <!-- 2. Matière -->
                  <div class="flex-1">
                    <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-0.5">Matière</label>
                    <input
                      v-model="sub.subject_name"
                      :list="'teacher-subjects-' + idx"
                      required
                      :disabled="!sub.teacher_id"
                      :placeholder="!sub.teacher_id ? 'Choisir le prof d\'abord' : 'Nom matière'"
                      class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-400 font-medium"
                    />
                    <datalist :id="'teacher-subjects-' + idx">
                      <option v-for="s in getSubjectsForTeacher(sub.teacher_id)" :key="s" :value="s" />
                    </datalist>
                  </div>

                  <button
                    type="button"
                    @click="removeSubjectRow(idx)"
                    class="mt-4 p-1.5 text-gray-400 hover:text-red-600 rounded transition-colors"
                    title="Supprimer cette matière"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>

                <!-- 3. Formule : Nombre de séances × Prix séance fixé par l'administration -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-1.5 border-t border-gray-200/70 text-xs">
                  <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5">
                      <span class="text-gray-600 font-medium text-[11px]">Prix séance (Idara) :</span>
                      <div class="relative w-20">
                        <input
                          v-model.number="sub.price_per_session"
                          type="number"
                          min="0"
                          step="1"
                          placeholder="0"
                          class="w-full pl-2 pr-6 py-1 border border-gray-300 rounded-md text-xs font-bold text-gray-800 bg-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <span class="absolute right-1.5 top-1 text-[10px] text-gray-400 font-bold">DT</span>
                      </div>
                    </div>

                    <div class="flex items-center gap-1.5">
                      <span class="text-gray-600 font-medium text-[11px]">Séances / mois :</span>
                      <input
                        v-model.number="sub.sessions_per_month"
                        type="number"
                        min="1"
                        max="30"
                        placeholder="4"
                        class="w-14 px-1.5 py-1 border border-gray-300 rounded-md text-xs font-bold text-gray-800 bg-white text-center focus:ring-2 focus:ring-indigo-500"
                      />
                    </div>
                  </div>

                  <div class="px-2.5 py-1 bg-indigo-50 border border-indigo-200 rounded-lg text-xs font-bold text-indigo-700 whitespace-nowrap flex items-center gap-1">
                    <span class="text-[10px] text-indigo-500 uppercase font-semibold">Total matière :</span>
                    <span>{{ Math.round((sub.sessions_per_month || 4) * (sub.price_per_session || 0)) }} TND / mois</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="errorMessage" class="text-xs text-red-600 bg-red-50 p-2.5 rounded-lg border border-red-200">
            {{ errorMessage }}
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
            <button
              type="button"
              @click="closeModal"
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

    <!-- Official Payment Extract Modal (Director only) -->
    <PaymentExtractModal
      :isOpen="showExtractModal"
      initialType="students"
      :initialStudentId="selectedStudentExtractId"
      @close="showExtractModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Plus, Search, Edit2, Trash2, Eye, BookOpen, Tag, Users, FileText } from 'lucide-vue-next';
import api from '../utils/api';
import PaymentExtractModal from './PaymentExtractModal.vue';
import { filterTeachersBySubject, teacherSubjectList } from '../utils/teacherSubjects';
import {
  TUNISIAN_LEVELS,
  COMMON_TUNISIAN_GROUPS,
  getSubjectsForLevel,
  getSuggestedGroups
} from '../utils/tunisianEducation';

interface SubjectEnrollment {
  id?: number;
  subject?: string;
  teacher?: string;
  teacher_id?: number;
  group_name?: string;
  sessions_per_month?: number;
  price_per_month?: number;
  price_per_session?: number;
}

interface Student {
  id: number;
  name: string;
  level: string;
  group_name?: string;
  phone: string;
  parent_phone?: string;
  status?: string;
  is_remise?: boolean;
  remise?: number;
  discount_type?: 'percentage' | 'fixed';
  base_fee?: number;
  calculated_fee?: {
    gross: number;
    discount: number;
    netPayable: number;
  };
  attendance_stats?: {
    total: number;
    present: number;
    absent: number;
    excused: number;
  };
  subjects: SubjectEnrollment[];
}

interface Teacher {
  id: number;
  name: string;
  subject: string;
  subjects?: string[];
}

const tunisianLevels = TUNISIAN_LEVELS;
const students = ref<Student[]>([]);
const teachers = ref<Teacher[]>([]);
const searchTerm = ref('');
const loading = ref(false);
const submitting = ref(false);
const errorMessage = ref('');

const selectedStudent = ref<Student | null>(null);
const showModal = ref(false);
const showExtractModal = ref(false);
const selectedStudentExtractId = ref<number | null>(null);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const currentUser = ref<{ role?: string; name?: string } | null>(null);
try {
  const saved = localStorage.getItem('user');
  if (saved) currentUser.value = JSON.parse(saved);
} catch {}

const isDirector = computed(() => {
  return currentUser.value?.role === 'director' || localStorage.getItem('user')?.includes('director');
});

function openExtractModal(studentId: number | null = null) {
  selectedStudentExtractId.value = studentId;
  showExtractModal.value = true;
}

const formData = ref<{
  name: string;
  level: string;
  group_name: string;
  phone: string;
  parent_phone: string;
  status: string;
  is_remise: boolean;
  remise: number;
  discount_type: 'percentage' | 'fixed';
  base_fee: number;
  subjects: Array<{
    subject_name: string;
    teacher_id?: number;
    group_name?: string;
    sessions_per_month?: number;
    price_per_month?: number;
    price_per_session?: number;
  }>;
}>({
  name: '',
  level: TUNISIAN_LEVELS[3].name, // Default: 1ère Année Secondaire
  group_name: 'S1',
  phone: '',
  parent_phone: '',
  status: 'active',
  is_remise: false,
  remise: 0,
  discount_type: 'fixed',
  base_fee: 0,
  subjects: []
});

const suggestedGroups = computed(() => {
  return getSuggestedGroups(formData.value.level);
});

const availableLevelSubjects = computed(() => {
  return getSubjectsForLevel(formData.value.level);
});

function onLevelChange() {
  const groups = getSuggestedGroups(formData.value.level);
  if (groups.length > 0 && (!formData.value.group_name || !groups.includes(formData.value.group_name))) {
    formData.value.group_name = groups[0];
  }
}

function getTeacherLessonRate(teacherId?: number): number {
  if (!teacherId) return 35;
  const t = teachers.value.find(teach => teach.id === teacherId);
  return Number((t as any)?.lesson_rate ?? (t as any)?.salary_per_lesson) || 35;
}

function getTeacherMonthlyRate(teacherId?: number): number {
  return getTeacherLessonRate(teacherId) * 4;
}


const calculatedGrossPreview = computed(() => {
  return formData.value.subjects.reduce((sum, s) => {
    if (!s.teacher_id) return sum;
    const sessionRate = Number(s.price_per_session) || getTeacherLessonRate(s.teacher_id);
    const sessions = Number(s.sessions_per_month) || 4;
    return sum + Math.round(sessionRate * sessions * 100) / 100;
  }, 0);
});

const calculatedDiscountValue = computed(() => {
  if (!formData.value.is_remise) return 0;
  const gross = calculatedGrossPreview.value;
  const rem = Number(formData.value.remise) || 0;
  if (formData.value.discount_type === 'percentage') {
    return Math.round(((gross * rem) / 100) * 100) / 100;
  }
  return Math.min(gross, rem);
});

const calculatedNetPreview = computed(() => {
  const gross = calculatedGrossPreview.value;
  const discount = calculatedDiscountValue.value;
  return Math.max(0, Math.round((gross - discount) * 100) / 100);
});

const filteredStudents = computed(() => {
  if (!searchTerm.value) return students.value;
  const term = searchTerm.value.toLowerCase();
  return students.value.filter(s =>
    s.name.toLowerCase().includes(term) ||
    s.level.toLowerCase().includes(term) ||
    (s.group_name && s.group_name.toLowerCase().includes(term)) ||
    s.phone.toLowerCase().includes(term)
  );
});

// Montant mensuel à payer (après remise) calculé automatiquement
function feeValue(student: Student): number {
  const val = Number(student.calculated_fee?.netPayable ?? 0);
  if (val > 0) return val;
  // Recalcul dynamique selon les enseignants de l'élève
  const gross = (student.subjects || []).reduce((sum, s: any) => {
    const teacherId = s.teacher_id || teachers.value.find(t => t.name === s.teacher)?.id;
    const sessionRate = Number(s.price_per_session) > 0 ? Number(s.price_per_session) : getTeacherLessonRate(teacherId);
    const sessions = Number(s.sessions_per_month) > 0 ? Number(s.sessions_per_month) : 4;
    const rate = Number(s.price_per_month) > 0 ? Number(s.price_per_month) : (sessionRate * sessions);
    return sum + rate;
  }, 0);
  if (student.is_remise && student.remise) {
    if (student.discount_type === 'percentage') {
      return Math.max(0, Math.round((gross - (gross * student.remise / 100)) * 100) / 100);
    }
    return Math.max(0, gross - student.remise);
  }
  return gross;
}

async function loadData() {
  loading.value = true;
  try {
    const [studentsRes, teachersRes] = await Promise.all([
      api.get('/api/students'),
      api.get('/api/teachers'),
    ]);
    students.value = studentsRes.data;
    teachers.value = teachersRes.data;
  } catch (e: any) {
    console.error('Erreur chargement:', e);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadData();
});

function getSubjectsForTeacher(teacherId?: number): string[] {
  if (!teacherId) return availableLevelSubjects.value;
  const t = teachers.value.find(teach => teach.id === teacherId);
  if (!t) return availableLevelSubjects.value;
  const list = teacherSubjectList(t);
  return list.length > 0 ? list : (t.subject ? [t.subject] : availableLevelSubjects.value);
}

function onTeacherChange(sub: { subject_name: string; teacher_id?: number; sessions_per_month?: number; price_per_month?: number; price_per_session?: number }) {
  if (sub.teacher_id) {
    const t = teachers.value.find(teach => teach.id === sub.teacher_id);
    if (t) {
      // Met TOUJOURS à jour la matière avec la matière de l'enseignant sélectionné
      const subjects = teacherSubjectList(t);
      sub.subject_name = subjects[0] || (t.subject ? t.subject.split(/[,;/،]+/)[0].trim() : '');
      const rate = getTeacherLessonRate(t.id);
      sub.price_per_session = rate;
      sub.sessions_per_month = sub.sessions_per_month || 4;
      sub.price_per_month = rate * sub.sessions_per_month;
    }
  } else {
    sub.subject_name = '';
    sub.price_per_session = 0;
    sub.sessions_per_month = 4;
    sub.price_per_month = 0;
  }
}

function openAddModal() {
  if (teachers.value.length === 0) {
    loadData();
  }
  isEditing.value = false;
  editingId.value = null;
  errorMessage.value = '';
  formData.value = {
    name: '',
    level: TUNISIAN_LEVELS[4].name, // 2ème Sciences
    group_name: 'Sc1',
    phone: '',
    parent_phone: '',
    status: 'active',
    is_remise: false,
    remise: 0,
    discount_type: 'fixed',
    base_fee: 0,
    subjects: [
      { subject_name: '', teacher_id: undefined, group_name: 'Sc1', sessions_per_month: 4, price_per_session: 0, price_per_month: 0 }
    ]
  };
  showModal.value = true;
}

function openEditModal(student: Student) {
  isEditing.value = true;
  editingId.value = student.id;
  errorMessage.value = '';
  formData.value = {
    name: student.name,
    level: student.level,
    group_name: student.group_name || 'M1',
    phone: student.phone,
    parent_phone: student.parent_phone || '',
    status: student.status || 'active',
    is_remise: !!student.is_remise,
    remise: student.remise || 0,
    discount_type: student.discount_type || 'fixed',
    base_fee: 0,
    subjects: (student.subjects || []).map((s: any) => {
      const teacherId = s.teacher_id || teachers.value.find(t => t.name === s.teacher)?.id;
      const rate = getTeacherLessonRate(teacherId);
      const sessionRate = s.price_per_session > 0 ? s.price_per_session : rate;
      const sessions = s.sessions_per_month > 0 ? s.sessions_per_month : 4;
      return {
        subject_name: s.subject ?? '',
        teacher_id: teacherId,
        group_name: s.group_name || student.group_name,
        sessions_per_month: sessions,
        price_per_session: sessionRate,
        price_per_month: s.price_per_month > 0 ? s.price_per_month : (sessionRate * sessions),
      };
    })
  };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  errorMessage.value = '';
}

function addSubjectRow() {
  formData.value.subjects.push({
    subject_name: '',
    teacher_id: undefined,
    group_name: formData.value.group_name,
    sessions_per_month: 4,
    price_per_session: 0,
    price_per_month: 0
  });
}

function removeSubjectRow(index: number) {
  formData.value.subjects.splice(index, 1);
}

async function saveStudent() {
  submitting.value = true;
  errorMessage.value = '';
  const payload = {
    name: formData.value.name,
    level: formData.value.level,
    group_name: formData.value.group_name,
    phone: formData.value.phone,
    parent_phone: formData.value.parent_phone,
    status: formData.value.status,
    is_remise: formData.value.is_remise,
    remise: formData.value.remise,
    discount_type: formData.value.discount_type,
    base_fee: 0,
    subjects: formData.value.subjects.map(s => {
      const teacherRate = getTeacherLessonRate(s.teacher_id);
      const sessionRate = Number(s.price_per_session) || teacherRate;
      const sessions = Number(s.sessions_per_month) || 4;
      return {
        subject: s.subject_name,
        teacher_id: s.teacher_id,
        group_name: s.group_name || formData.value.group_name,
        sessions_per_month: sessions,
        price_per_session: sessionRate,
        price_per_month: Math.round(sessionRate * sessions * 100) / 100,
      };
    })
  };
  try {
    if (isEditing.value && editingId.value) {
      const res = await api.put(`/api/students/${editingId.value}`, payload);
      const idx = students.value.findIndex(s => s.id === editingId.value);
      if (idx !== -1) students.value[idx] = res.data;
    } else {
      const res = await api.post('/api/students', payload);
      students.value.unshift(res.data);
    }
    closeModal();
  } catch (e: any) {
    console.error('Erreur sauvegarde eleve:', e);
    errorMessage.value = e.response?.data?.message || 'Erreur lors de la sauvegarde';
  } finally {
    submitting.value = false;
  }
}

async function deleteStudent(id: number) {
  if (!confirm('Voulez-vous vraiment supprimer cet élève ?')) return;
  try {
    await api.delete(`/api/students/${id}`);
    students.value = students.value.filter(s => s.id !== id);
    if (selectedStudent.value?.id === id) selectedStudent.value = null;
  } catch (e: any) {
    console.error('Erreur suppression:', e);
    alert("Erreur lors de la suppression de l'élève");
  }
}
</script>