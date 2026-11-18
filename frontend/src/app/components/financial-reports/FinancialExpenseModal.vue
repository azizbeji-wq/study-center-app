<script setup lang="ts">
import { ref, watch } from 'vue';
import { TrendingDown, X } from 'lucide-vue-next';

const props = defineProps<{
  isOpen: boolean;
  editingExpenseId: number | null;
  initialExpense: any;
  isSubmitting: boolean;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'save', formData: any): void;
}>();

const form = ref({
  title: '',
  category: 'fournitures',
  amount: 0,
  date: new Date().toISOString().substring(0, 10),
  payment_method: 'especes',
  receipt_reference: '',
  is_recurring: false,
  notes: '',
});

watch(
  () => props.initialExpense,
  (val) => {
    if (val) {
      form.value = {
        title: val.title || '',
        category: val.category || 'fournitures',
        amount: Number(val.amount || 0),
        date: val.date || new Date().toISOString().substring(0, 10),
        payment_method: val.payment_method || 'especes',
        receipt_reference: val.receipt_reference || '',
        is_recurring: Boolean(val.is_recurring),
        notes: val.notes || '',
      };
    } else {
      form.value = {
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
  },
  { immediate: true }
);

function handleSubmit() {
  emit('save', { ...form.value });
}
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
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
        <button @click="emit('close')" class="text-gray-400 hover:text-gray-600 p-1 cursor-pointer">
          <X class="w-5 h-5" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="p-5 space-y-4 text-xs">
        <!-- Titre / Libellé -->
        <div>
          <label class="block font-semibold text-gray-700 mb-1">Libellé / Titre de la dépense *</label>
          <input
            v-model="form.title"
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
              v-model="form.category"
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
              v-model.number="form.amount"
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
              v-model="form.date"
              type="date"
              required
              class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium"
            />
          </div>

          <!-- Mode de paiement -->
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Mode de règlement *</label>
            <select
              v-model="form.payment_method"
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
            v-model="form.receipt_reference"
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
            v-model="form.is_recurring"
            type="checkbox"
            class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"
          />
        </div>

        <!-- Notes -->
        <div>
          <label class="block font-semibold text-gray-700 mb-1">Notes / Commentaires</label>
          <textarea
            v-model="form.notes"
            rows="2"
            placeholder="Détails complémentaires..."
            class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
          <button
            type="button"
            @click="emit('close')"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-colors cursor-pointer"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition-colors shadow-xs disabled:opacity-50 cursor-pointer"
          >
            {{ isSubmitting ? 'Enregistrement...' : (editingExpenseId ? 'Mettre à jour' : 'Enregistrer la Dépense') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
