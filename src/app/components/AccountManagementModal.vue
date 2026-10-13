<template>
  <div
    v-if="isOpen"
    @click.self="$emit('close')"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-6 overflow-y-auto animate-in fade-in"
  >
    <div class="bg-white rounded-2xl border border-gray-200 shadow-2xl w-full max-w-4xl my-auto overflow-hidden flex flex-col max-h-[92vh]">
      <!-- Header -->
      <div class="px-6 py-4 bg-gray-900 text-white flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-indigo-600/30 border border-indigo-400/30 flex items-center justify-center text-indigo-400">
            <UserCog class="w-5 h-5" />
          </div>
          <div>
            <h2 class="text-base font-bold">Gestion du Compte & Accès</h2>
            <p class="text-xs text-gray-400">
              {{ currentRole === 'director' ? 'Paramètres du compte directeur et gestion du personnel' : 'Mes informations personnelles et sécurité' }}
            </p>
          </div>
        </div>

        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-white p-1 rounded-lg hover:bg-gray-800 transition-colors"
          title="Fermer"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Navigation Onglets -->
      <div class="bg-gray-50 border-b border-gray-200 px-6 shrink-0 flex items-center justify-between">
        <div class="flex gap-2">
          <button
            @click="activeTab = 'profile'"
            :class="[
              'flex items-center gap-2 py-3 px-4 border-b-2 font-bold text-xs sm:text-sm transition-all',
              activeTab === 'profile'
                ? 'border-indigo-600 text-indigo-600 bg-white/70'
                : 'border-transparent text-gray-600 hover:text-gray-900'
            ]"
          >
            <ShieldCheck class="w-4 h-4" />
            <span>Mon Profil & Mot de passe</span>
          </button>

          <button
            v-if="currentRole === 'director'"
            @click="activeTab = 'team'"
            :class="[
              'flex items-center gap-2 py-3 px-4 border-b-2 font-bold text-xs sm:text-sm transition-all',
              activeTab === 'team'
                ? 'border-indigo-600 text-indigo-600 bg-white/70'
                : 'border-transparent text-gray-600 hover:text-gray-900'
            ]"
          >
            <Users class="w-4 h-4" />
            <span>Équipe & Personnel ({{ usersList.length }})</span>
          </button>
        </div>

        <span
          class="text-[11px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider hidden sm:inline-block"
          :class="currentRole === 'director' ? 'bg-indigo-100 text-indigo-800' : 'bg-emerald-100 text-emerald-800'"
        >
          {{ currentRole === 'director' ? 'Directeur (Accès Maître)' : 'Personnel' }}
        </span>
      </div>

      <!-- Contenu Déroulant -->
      <div class="p-6 overflow-y-auto space-y-6 flex-1 bg-white">
        <!-- Message global de succès ou d'erreur -->
        <div v-if="successMessage" class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold flex items-center justify-between animate-in fade-in">
          <div class="flex items-center gap-2">
            <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
            <span>{{ successMessage }}</span>
          </div>
          <button @click="successMessage = ''" class="text-emerald-600 hover:text-emerald-900">
            <X class="w-3.5 h-3.5" />
          </button>
        </div>

        <div v-if="errorMessage" class="p-3.5 bg-red-50 border border-red-200 text-red-800 rounded-xl text-xs font-semibold flex items-center justify-between animate-in fade-in">
          <div class="flex items-center gap-2">
            <AlertCircle class="w-4 h-4 text-red-600 shrink-0" />
            <span>{{ errorMessage }}</span>
          </div>
          <button @click="errorMessage = ''" class="text-red-600 hover:text-red-900">
            <X class="w-3.5 h-3.5" />
          </button>
        </div>

        <!-- ============================================================== -->
        <!-- ONGLET 1 : MON PROFIL & MOT DE PASSE                           -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'profile'" class="space-y-6">
          <!-- Bloc 1 : Informations Générales -->
          <div class="p-5 border border-gray-200 rounded-2xl bg-gray-50/50 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-200/80 pb-3">
              <div>
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                  <User class="w-4 h-4 text-indigo-600" />
                  <span>Informations Personnelles</span>
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">Votre identité et vos coordonnées dans l'application</p>
              </div>
              <span class="text-xs px-2.5 py-1 rounded-lg font-bold border" :class="currentRole === 'director' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'">
                {{ currentRole === 'director' ? 'Directeur' : 'Personnel' }}
              </span>
            </div>

            <form @submit.prevent="saveProfile" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Nom complet *</label>
                <input
                  v-model="profileForm.name"
                  type="text"
                  required
                  placeholder="Ex: Mohamed Ben Salem"
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-xl text-base focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Adresse Email de connexion *</label>
                <input
                  v-model="profileForm.email"
                  type="email"
                  required
                  placeholder="nom@studycenter.com"
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-xl text-base focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Numéro de Téléphone</label>
                <input
                  v-model="profileForm.phone"
                  type="text"
                  placeholder="+216 20 000 000"
                  class="w-full px-3.5 py-2 border border-gray-300 rounded-xl text-base focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                />
              </div>

              <div class="flex items-end justify-end">
                <button
                  type="submit"
                  :disabled="loadingProfile"
                  class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
                >
                  <RefreshCw v-if="loadingProfile" class="w-3.5 h-3.5 animate-spin" />
                  <Save v-else class="w-3.5 h-3.5" />
                  <span>Enregistrer mon profil</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Bloc 2 : Sécurité & Changement de mot de passe -->
          <div class="p-5 border border-gray-200 rounded-2xl bg-gray-50/50 space-y-4">
            <div class="border-b border-gray-200/80 pb-3">
              <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <KeyRound class="w-4 h-4 text-amber-600" />
                <span>Sécurité & Changement de Mot de Passe</span>
              </h3>
              <p class="text-xs text-gray-500 mt-0.5">Assurez-vous d'utiliser un mot de passe solide d'au moins 6 caractères</p>
            </div>

            <form @submit.prevent="changePassword" class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label class="block text-xs font-bold text-gray-700 mb-1">Mot de passe actuel *</label>
                  <div class="relative">
                    <input
                      v-model="passwordForm.current_password"
                      :type="showCurrentPass ? 'text' : 'password'"
                      required
                      placeholder="••••••••"
                      class="w-full px-3.5 py-2 pr-10 border border-gray-300 rounded-xl text-base focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                    />
                    <button
                      type="button"
                      @click="showCurrentPass = !showCurrentPass"
                      class="absolute right-2.5 top-2.5 text-gray-400 hover:text-gray-600"
                    >
                      <Eye v-if="!showCurrentPass" class="w-4 h-4" />
                      <EyeOff v-else class="w-4 h-4" />
                    </button>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 mb-1">Nouveau mot de passe *</label>
                  <div class="relative">
                    <input
                      v-model="passwordForm.new_password"
                      :type="showNewPass ? 'text' : 'password'"
                      required
                      minlength="6"
                      placeholder="••••••••"
                      class="w-full px-3.5 py-2 pr-10 border border-gray-300 rounded-xl text-base focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                    />
                    <button
                      type="button"
                      @click="showNewPass = !showNewPass"
                      class="absolute right-2.5 top-2.5 text-gray-400 hover:text-gray-600"
                    >
                      <Eye v-if="!showNewPass" class="w-4 h-4" />
                      <EyeOff v-else class="w-4 h-4" />
                    </button>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 mb-1">Confirmer le nouveau *</label>
                  <div class="relative">
                    <input
                      v-model="passwordForm.new_password_confirmation"
                      :type="showConfirmPass ? 'text' : 'password'"
                      required
                      minlength="6"
                      placeholder="••••••••"
                      class="w-full px-3.5 py-2 pr-10 border border-gray-300 rounded-xl text-base focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                    />
                    <button
                      type="button"
                      @click="showConfirmPass = !showConfirmPass"
                      class="absolute right-2.5 top-2.5 text-gray-400 hover:text-gray-600"
                    >
                      <Eye v-if="!showConfirmPass" class="w-4 h-4" />
                      <EyeOff v-else class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between pt-2">
                <span class="text-[11px] text-gray-500 italic">
                  💡 En cas d'oubli, contactez le Directeur qui peut réinitialiser votre accès.
                </span>

                <button
                  type="submit"
                  :disabled="loadingPassword"
                  class="inline-flex items-center gap-2 px-5 py-2 bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
                >
                  <RefreshCw v-if="loadingPassword" class="w-3.5 h-3.5 animate-spin" />
                  <Lock v-else class="w-3.5 h-3.5" />
                  <span>Mettre à jour le mot de passe</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- ONGLET 2 : GESTION DU PERSONNEL (RÉSERVÉ AU DIRECTEUR)          -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'team' && currentRole === 'director'" class="space-y-4">
          <!-- Bandeau d'action -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 bg-indigo-50/70 border border-indigo-200 rounded-2xl">
            <div>
              <h3 class="text-sm font-bold text-indigo-950 flex items-center gap-2">
                <ShieldAlert class="w-4 h-4 text-indigo-600" />
                <span>Gestion Exclusive des Accès par la Direction (Idée 1)</span>
              </h3>
              <p class="text-xs text-indigo-800/80 mt-0.5">
                Seul le Directeur a l'autorité de créer des membres du personnel et de gérer les accès au centre.
              </p>
            </div>

            <button
              @click="openCreateModal"
              class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs shrink-0 self-start sm:self-auto"
            >
              <UserPlus class="w-4 h-4" />
              <span>+ Nouveau Personnel</span>
            </button>
          </div>

          <!-- Tableau des utilisateurs -->
          <div class="border border-gray-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="w-full overflow-x-auto pb-4"><table class="w-full text-left text-xs whitespace-nowrap">
              <thead class="bg-gray-100/80 text-gray-700 font-bold border-b border-gray-200">
                <tr>
                  <th class="py-3 px-4">Utilisateur</th>
                  <th class="py-3 px-4">Rôle</th>
                  <th class="py-3 px-4">Téléphone</th>
                  <th class="py-3 px-3 text-center">Statut</th>
                  <th class="py-3 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="user in usersList" :key="user.id" class="hover:bg-gray-50/80 transition-colors">
                  <td class="py-3 px-4">
                    <div class="flex items-center gap-2.5">
                      <div
                        class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs"
                        :class="user.role === 'director' ? 'bg-indigo-100 text-indigo-800' : (user.role === 'teacher' ? 'bg-violet-100 text-violet-800' : 'bg-emerald-100 text-emerald-800')"
                      >
                        {{ user.name.charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <p class="font-bold text-gray-900 flex items-center gap-1.5">
                          <span>{{ user.name }}</span>
                          <span v-if="user.is_founder" class="text-[9px] px-1.5 py-0.2 bg-amber-100 text-amber-900 rounded-md font-extrabold border border-amber-300">
                            FONDATEUR MAÎTRE
                          </span>
                        </p>
                        <p class="text-[11px] text-gray-500">{{ user.email }}</p>
                      </div>
                    </div>
                  </td>

                  <td class="py-3 px-4">
                    <span
                      class="text-xs px-2.5 py-1 rounded-full font-bold inline-flex items-center gap-1"
                      :class="user.role === 'director' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : (user.role === 'teacher' ? 'bg-violet-50 text-violet-700 border border-violet-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200')"
                    >
                      <UserCheck class="w-3 h-3" />
                      {{ user.role === 'director' ? 'Directeur' : (user.role === 'teacher' ? 'Enseignant' : 'Personnel') }}
                    </span>
                    <div class="mt-1">
                      <span
                        v-if="user.role === 'director'"
                        class="text-[10px] text-indigo-600 font-semibold"
                      >
                        Accès Maître
                      </span>
                      <span
                        v-else
                        class="text-[10px] text-gray-500 font-medium"
                      >
                        {{ (user.permissions?.length ?? 7) }} autorisation(s)
                      </span>
                    </div>
                  </td>

                  <td class="py-3 px-4 text-gray-600 font-mono">
                    {{ user.phone || '—' }}
                  </td>

                  <td class="py-3 px-3 text-center">
                    <button
                      v-if="!user.is_founder"
                      @click="toggleUserActive(user)"
                      class="text-[10px] px-2 py-0.5 rounded-full font-bold transition-all border"
                      :class="user.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-red-50 hover:text-red-700' : 'bg-red-50 text-red-700 border-red-300 hover:bg-emerald-50 hover:text-emerald-700'"
                      :title="user.is_active ? 'Désactiver le compte' : 'Activer le compte'"
                    >
                      {{ user.is_active ? '● Actif' : '○ Inactif' }}
                    </button>
                    <span v-else class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-amber-50 text-amber-800 border border-amber-200">
                      Permanent
                    </span>
                  </td>

                  <td class="py-3 px-4 text-right">
                    <div class="inline-flex items-center gap-1">
                      <!-- Réinitialiser mot de passe -->
                      <button
                        @click="openResetPasswordModal(user)"
                        class="p-1.5 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                        title="Définir un nouveau mot de passe"
                      >
                        <KeyRound class="w-4 h-4" />
                      </button>

                      <!-- Modifier les infos -->
                      <button
                        @click="openEditModal(user)"
                        class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                        title="Modifier le compte"
                      >
                        <Pencil class="w-4 h-4" />
                      </button>

                      <!-- Supprimer (bloqué pour fondateur et compte connecté) -->
                      <button
                        v-if="!user.is_founder && user.id !== currentUserId"
                        @click="confirmDeleteUser(user)"
                        class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                        title="Supprimer définitivement"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="usersList.length === 0">
                  <td colspan="5" class="py-6 text-center text-gray-400">
                    Aucun utilisateur trouvé.
                  </td>
                </tr>
              </tbody>
            </table></div>
          </div>
        </div>
      </div>

      <!-- Footer Modal -->
      <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-200 flex justify-between items-center text-xs text-gray-500 shrink-0">
        <span>Centre d'Études & Soutien Scolaire • Système d'authentification certifié</span>
        <button
          @click="$emit('close')"
          class="px-4 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold rounded-xl transition-colors"
        >
          Fermer
        </button>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SOUS-MODAL : CRÉATION / MODIFICATION D'UTILISATEUR              -->
    <!-- ============================================================== -->
    <div
      v-if="showUserEditModal"
      class="fixed inset-0 z-60 flex items-center justify-center bg-black/60 p-4 animate-in fade-in"
    >
      <div class="bg-white rounded-2xl border border-gray-200 shadow-2xl w-full max-w-2xl p-6 space-y-4 max-h-[90vh] overflow-y-auto mx-4 md:mx-auto">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <UserPlus v-if="!editingUser" class="w-5 h-5 text-indigo-600" />
            <Pencil v-else class="w-5 h-5 text-indigo-600" />
            <h3 class="text-sm font-bold text-gray-900">
              {{ editingUser ? 'Modifier le compte' : 'Créer un compte Personnel' }}
            </h3>
          </div>
          <button @click="showUserEditModal = false" class="text-gray-400 hover:text-gray-600">
            <X class="w-4 h-4" />
          </button>
        </div>

        <form @submit.prevent="submitUserForm" class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-gray-700 mb-1">Nom complet *</label>
            <input
              v-model="userForm.name"
              type="text"
              required
              placeholder="Ex: Sonia Ben Ali"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 text-xs"
            />
          </div>

          <div>
            <label class="block font-bold text-gray-700 mb-1">Adresse Email *</label>
            <input
              v-model="userForm.email"
              type="email"
              required
              placeholder="personnel@studycenter.com"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 text-xs"
            />
          </div>

          <div>
            <label class="block font-bold text-gray-700 mb-1">Téléphone</label>
            <input
              v-model="userForm.phone"
              type="text"
              placeholder="+216 20 000 000"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 text-xs"
            />
          </div>

          <div>
            <label class="block font-bold text-gray-700 mb-1">Rôle attribué *</label>
            <select
              v-model="userForm.role"
              :disabled="editingUser?.is_founder"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 text-xs bg-white"
            >
              <option value="secretary">Personnel (Autorisations modulables personnalisées)</option>
              <option value="teacher">Enseignant (Emploi du temps, présences, portail prof)</option>
              <option value="director">Directeur (Accès complet + comptabilité générale)</option>
            </select>
          </div>

          <!-- BLOC PERMISSIONS PERSONNALISÉES (Si rôle Personnel) -->
          <div v-if="userForm.role === 'secretary'" class="space-y-3 pt-2 border-t border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
              <div>
                <label class="block font-bold text-gray-800 text-xs">
                  Permissions & Accès modulaires
                </label>
                <p class="text-[11px] text-gray-500">
                  Cochez les droits attribués ou appliquez un profil rapide :
                </p>
              </div>
              <div class="flex items-center gap-2 text-[11px] shrink-0">
                <button
                  type="button"
                  @click="selectAllPermissions"
                  class="text-indigo-600 hover:text-indigo-800 font-bold hover:underline"
                >
                  Tout cocher
                </button>
                <span class="text-gray-300">•</span>
                <button
                  type="button"
                  @click="deselectAllPermissions"
                  class="text-gray-500 hover:text-gray-700 font-medium hover:underline"
                >
                  Tout décocher
                </button>
              </div>
            </div>

            <!-- Profils rapides (Presets) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <button
                v-for="preset in PRESET_PROFILES"
                :key="preset.id"
                type="button"
                @click="applyPreset(preset)"
                :class="[
                  'p-2.5 rounded-xl border text-left transition-all',
                  isPresetActive(preset)
                    ? 'bg-indigo-50 border-indigo-400 ring-2 ring-indigo-400/40 shadow-xs'
                    : 'bg-gray-50/80 border-gray-200 hover:bg-white hover:border-gray-300'
                ]"
              >
                <div class="flex items-center justify-between mb-1">
                  <span class="text-[11px] font-bold text-gray-900 truncate">{{ preset.title }}</span>
                  <span
                    class="text-[9px] px-1.5 py-0.2 rounded font-bold"
                    :class="isPresetActive(preset) ? 'bg-indigo-200 text-indigo-800' : 'bg-gray-200/80 text-gray-700'"
                  >
                    {{ preset.badge }}
                  </span>
                </div>
                <p class="text-[10px] text-gray-500 line-clamp-1 leading-tight">{{ preset.description }}</p>
              </button>
            </div>

            <!-- Cases à cocher par catégorie -->
            <div class="space-y-2.5 max-h-[260px] overflow-y-auto pr-1">
              <div
                v-for="category in PERMISSION_CATEGORIES"
                :key="category.name"
                class="bg-gray-50/70 border border-gray-200 rounded-xl p-3 space-y-2"
              >
                <div class="flex items-center justify-between border-b border-gray-200/60 pb-1.5">
                  <h4 class="text-xs font-bold text-gray-800">
                    {{ category.name }}
                  </h4>
                  <span class="text-[10px] text-gray-500 font-semibold">
                    {{ countCategoryActive(category) }} / {{ category.items.length }}
                  </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-0.5">
                  <label
                    v-for="item in category.items"
                    :key="item.key"
                    :class="[
                      'flex items-start gap-2.5 p-2 rounded-lg border cursor-pointer transition-all select-none',
                      userForm.permissions.includes(item.key)
                        ? (item.isSensitive ? 'bg-rose-50 border-rose-300 text-rose-950 font-semibold' : 'bg-indigo-50/80 border-indigo-300 text-indigo-950 font-semibold')
                        : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'
                    ]"
                  >
                    <input
                      type="checkbox"
                      :value="item.key"
                      v-model="userForm.permissions"
                      class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5 cursor-pointer"
                    />
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs font-bold leading-tight">{{ item.label }}</span>
                        <span
                          v-if="item.badge"
                          class="text-[9px] px-1 py-0.2 rounded font-extrabold bg-amber-100 text-amber-800 border border-amber-200"
                        >
                          {{ item.badge }}
                        </span>
                        <span
                          v-if="item.isSensitive"
                          class="text-[9px] px-1 py-0.2 rounded font-extrabold bg-rose-100 text-rose-800 border border-rose-200"
                        >
                          Sensible
                        </span>
                      </div>
                      <p class="text-[10px] text-gray-500 leading-tight mt-0.5">
                        {{ item.description }}
                      </p>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Message informatif si rôle Directeur -->
          <div v-else class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl text-indigo-900 text-xs flex items-center gap-2">
            <ShieldCheck class="w-4 h-4 text-indigo-600 shrink-0" />
            <span><strong>Rôle Directeur :</strong> Accès maître complet à l'ensemble des modules, clôtures et gestion du système.</span>
          </div>

          <!-- Mot de passe initial (création uniquement) -->
          <div v-if="!editingUser">
            <div class="flex items-center justify-between mb-1">
              <label class="block font-bold text-gray-700">Mot de passe provisoire *</label>
              <button
                type="button"
                @click="generateRandomPassword"
                class="text-[10px] text-indigo-600 hover:text-indigo-800 font-bold"
              >
                ⚡ Générer automatique
              </button>
            </div>
            <input
              v-model="userForm.password"
              type="text"
              required
              minlength="6"
              placeholder="Au moins 6 caractères"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 text-xs font-mono"
            />
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
            <button
              type="button"
              @click="showUserEditModal = false"
              class="px-3.5 py-1.5 rounded-xl border border-gray-300 text-gray-700 font-bold hover:bg-gray-50"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="submittingUser"
              class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition-all shadow-xs flex items-center gap-1.5"
            >
              <RefreshCw v-if="submittingUser" class="w-3.5 h-3.5 animate-spin" />
              <span>{{ editingUser ? 'Sauvegarder' : 'Créer le compte' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SOUS-MODAL : RÉINITIALISATION DU MOT DE PASSE                   -->
    <!-- ============================================================== -->
    <div
      v-if="showResetModal && targetResetUser"
      class="fixed inset-0 z-60 flex items-center justify-center bg-black/60 p-4 animate-in fade-in"
    >
      <div class="bg-white rounded-2xl border border-gray-200 shadow-2xl w-full max-w-sm p-6 space-y-4 max-h-[90vh] overflow-y-auto mx-4 md:mx-auto">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2 text-amber-700">
            <KeyRound class="w-5 h-5" />
            <h3 class="text-sm font-bold">Réinitialiser le mot de passe</h3>
          </div>
          <button @click="showResetModal = false" class="text-gray-400 hover:text-gray-600">
            <X class="w-4 h-4" />
          </button>
        </div>

        <p class="text-xs text-gray-600">
          Définir un nouveau mot de passe provisoire pour <span class="font-bold text-gray-900">{{ targetResetUser.name }}</span> ({{ targetResetUser.email }}).
        </p>

        <form @submit.prevent="submitResetPassword" class="space-y-3 text-xs">
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block font-bold text-gray-700">Nouveau mot de passe *</label>
              <button
                type="button"
                @click="resetPasswordValue = 'Pass' + Math.floor(100000 + Math.random() * 900000)"
                class="text-[10px] text-indigo-600 hover:text-indigo-800 font-bold"
              >
                ⚡ Aléatoire
              </button>
            </div>
            <input
              v-model="resetPasswordValue"
              type="text"
              required
              minlength="6"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 text-xs font-mono"
            />
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button
              type="button"
              @click="showResetModal = false"
              class="px-3 py-1.5 rounded-xl border border-gray-300 text-gray-700 font-bold hover:bg-gray-50"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="submittingReset"
              class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold transition-all shadow-xs flex items-center gap-1.5"
            >
              <RefreshCw v-if="submittingReset" class="w-3.5 h-3.5 animate-spin" />
              <span>Valider le nouveau mot de passe</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import {
  UserCog, User, Users, ShieldCheck, ShieldAlert, KeyRound,
  Lock, Save, RefreshCw, X, AlertCircle, CheckCircle2,
  UserPlus, Pencil, Trash2, Eye, EyeOff, UserCheck
} from 'lucide-vue-next';
import api from '../utils/api';
import { PERMISSION_CATEGORIES, PRESET_PROFILES } from '../utils/permissions';

const props = defineProps<{
  isOpen: boolean;
  currentRole: 'director' | 'secretary' | 'teacher';
  currentUserName: string;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'userUpdated', newName: string): void;
}>();

const activeTab = ref<'profile' | 'team'>('profile');
const successMessage = ref('');
const errorMessage = ref('');

// ID de l'utilisateur connecté
const currentUserId = ref<number | null>(null);

// ─── 1. Formulaire Profil & Mot de passe ─────────────────────────────────────
const profileForm = ref({
  name: '',
  email: '',
  phone: '',
});

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
});

const showCurrentPass = ref(false);
const showNewPass = ref(false);
const showConfirmPass = ref(false);

const loadingProfile = ref(false);
const loadingPassword = ref(false);

async function loadProfile() {
  try {
    const res = await api.get('/api/profile');
    if (res.data) {
      currentUserId.value = res.data.id;
      profileForm.value = {
        name: res.data.name || '',
        email: res.data.email || '',
        phone: res.data.phone || '',
      };
    }
  } catch (err: any) {
    console.error('Erreur chargement profil:', err);
  }
}

async function saveProfile() {
  loadingProfile.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  try {
    const res = await api.put('/api/profile', profileForm.value);
    successMessage.value = res.data.message || 'Profil mis à jour avec succès.';
    emit('userUpdated', profileForm.value.name);

    // Mettre à jour le localStorage
    try {
      const saved = localStorage.getItem('user');
      if (saved) {
        const u = JSON.parse(saved);
        u.name = profileForm.value.name;
        localStorage.setItem('user', JSON.stringify(u));
      }
    } catch {}
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Erreur lors de la mise à jour du profil.';
  } finally {
    loadingProfile.value = false;
  }
}

async function changePassword() {
  loadingPassword.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  try {
    const res = await api.put('/api/profile/password', passwordForm.value);
    successMessage.value = res.data.message || 'Mot de passe modifié avec succès.';
    passwordForm.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation: '',
    };
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Erreur lors du changement de mot de passe.';
  } finally {
    loadingPassword.value = false;
  }
}

// ─── 2. Gestion de l'équipe (Directeur uniquement) ──────────────────────────
const usersList = ref<any[]>([]);
const showUserEditModal = ref(false);
const editingUser = ref<any | null>(null);
const submittingUser = ref(false);

const userForm = ref<{
  name: string;
  email: string;
  phone: string;
  role: 'secretary' | 'director';
  password: string;
  permissions: string[];
}>({
  name: '',
  email: '',
  phone: '',
  role: 'secretary',
  password: '',
  permissions: [
    'students', 'attendance', 'daycare', 'teachers',
    'schedule', 'schoolcalendar', 'payments'
  ],
});

const showResetModal = ref(false);
const targetResetUser = ref<any | null>(null);
const resetPasswordValue = ref('');
const submittingReset = ref(false);

async function loadUsers() {
  if (props.currentRole !== 'director') return;
  try {
    const res = await api.get('/api/users');
    usersList.value = Array.isArray(res.data) ? res.data : [];
  } catch (err) {
    console.error('Erreur chargement utilisateurs:', err);
  }
}

function selectAllPermissions() {
  const allKeys = PERMISSION_CATEGORIES.flatMap(cat => cat.items.map(i => i.key));
  userForm.value.permissions = [...allKeys];
}

function deselectAllPermissions() {
  userForm.value.permissions = [];
}

function applyPreset(preset: typeof PRESET_PROFILES[0]) {
  userForm.value.permissions = [...preset.permissions];
}

function isPresetActive(preset: typeof PRESET_PROFILES[0]) {
  if (userForm.value.permissions.length !== preset.permissions.length) return false;
  return preset.permissions.every(k => userForm.value.permissions.includes(k));
}

function countCategoryActive(category: typeof PERMISSION_CATEGORIES[0]) {
  return category.items.filter(i => userForm.value.permissions.includes(i.key)).length;
}

function openCreateModal() {
  editingUser.value = null;
  userForm.value = {
    name: '',
    email: '',
    phone: '',
    role: 'secretary',
    password: generatePassword(),
    permissions: [
      'students', 'attendance', 'daycare', 'teachers',
      'schedule', 'schoolcalendar', 'payments'
    ],
  };
  showUserEditModal.value = true;
}

function openEditModal(user: any) {
  editingUser.value = user;
  userForm.value = {
    name: user.name,
    email: user.email,
    phone: user.phone || '',
    role: user.role,
    password: '',
    permissions: Array.isArray(user.permissions)
      ? [...user.permissions]
      : [
          'students', 'attendance', 'daycare', 'teachers',
          'schedule', 'schoolcalendar', 'payments'
        ],
  };
  showUserEditModal.value = true;
}

function generatePassword() {
  return 'Sec' + Math.floor(100000 + Math.random() * 900000);
}

function generateRandomPassword() {
  userForm.value.password = generatePassword();
}

async function submitUserForm() {
  submittingUser.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  try {
    const payload: any = {
      name: userForm.value.name,
      email: userForm.value.email,
      phone: userForm.value.phone,
      role: userForm.value.role,
      permissions: userForm.value.role === 'secretary' ? userForm.value.permissions : null,
    };

    if (editingUser.value) {
      // Mise à jour
      const res = await api.put(`/api/users/${editingUser.value.id}`, payload);
      successMessage.value = res.data.message || 'Utilisateur mis à jour.';
    } else {
      // Création
      payload.password = userForm.value.password;
      const res = await api.post('/api/users', payload);
      successMessage.value = res.data.message || 'Compte personnel créé.';
    }
    showUserEditModal.value = false;
    await loadUsers();
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Erreur lors de l\'enregistrement de l\'utilisateur.';
  } finally {
    submittingUser.value = false;
  }
}

async function toggleUserActive(user: any) {
  try {
    const res = await api.put(`/api/users/${user.id}`, {
      name: user.name,
      email: user.email,
      is_active: !user.is_active,
    });
    user.is_active = !user.is_active;
    successMessage.value = user.is_active
      ? `Le compte de ${user.name} a été activé.`
      : `Le compte de ${user.name} a été désactivé.`;
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Erreur lors du changement de statut.';
  }
}

function openResetPasswordModal(user: any) {
  targetResetUser.value = user;
  resetPasswordValue.value = 'Pass' + Math.floor(100000 + Math.random() * 900000);
  showResetModal.value = true;
}

async function submitResetPassword() {
  if (!targetResetUser.value) return;
  submittingReset.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  try {
    const res = await api.post(`/api/users/${targetResetUser.value.id}/reset-password`, {
      password: resetPasswordValue.value,
    });
    successMessage.value = res.data.message || 'Mot de passe réinitialisé.';
    showResetModal.value = false;
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Erreur lors de la réinitialisation.';
  } finally {
    submittingReset.value = false;
  }
}

async function confirmDeleteUser(user: any) {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer définitivement le compte de ${user.name} (${user.email}) ? Cette action est irréversible.`)) {
    return;
  }

  try {
    const res = await api.delete(`/api/users/${user.id}`);
    successMessage.value = res.data.message || 'Compte supprimé avec succès.';
    await loadUsers();
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Impossible de supprimer cet utilisateur.';
  }
}

// ─── Initialisation ───────────────────────────────────────────────────────────
watch(
  () => props.isOpen,
  (val) => {
    if (val) {
      loadProfile();
      if (props.currentRole === 'director') {
        loadUsers();
      }
    }
  },
  { immediate: true }
);

onMounted(() => {
  if (props.isOpen) {
    loadProfile();
    if (props.currentRole === 'director') {
      loadUsers();
    }
  }
});
</script>
