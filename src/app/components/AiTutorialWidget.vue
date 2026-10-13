<template>
  <div class="font-sans">
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- 1. BOUTON DÉCLENCHEUR FLOTTANT (BULLE EN BAS À DROITE)                       -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="fixed bottom-6 right-6 z-50 flex items-center gap-3">
      <!-- Info-bulle d'invitation (visible uniquement quand le panneau est fermé) -->
      <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-x-3 scale-95"
        enter-to-class="opacity-100 translate-x-0 scale-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div
          v-if="!isOpen && showTooltip"
          class="hidden sm:flex items-center gap-2 bg-gray-900/90 text-white text-xs px-3.5 py-2 rounded-2xl shadow-xl backdrop-blur-md border border-white/10"
        >
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
          <span class="font-medium">{{ tooltipText }}</span>
          <button
            @click.stop="showTooltip = false"
            class="text-gray-400 hover:text-white p-0.5 ml-1 transition-colors"
          >
            <X class="w-3.5 h-3.5" />
          </button>
        </div>
      </transition>

      <!-- Bulle principale cliquable -->
      <button
        @click="toggleOpen"
        :aria-expanded="isOpen"
        :title="isOpen ? 'Fermer l\'assistant IA' : 'Ouvrir l\'assistant IA Gemini'"
        class="relative group w-14 h-14 rounded-2xl bg-gradient-to-tr from-violet-600 via-purple-600 to-indigo-700 text-white shadow-2xl flex items-center justify-center transition-all duration-300 hover:scale-105 active:scale-95 focus:outline-none focus:ring-4 focus:ring-violet-400/40 cursor-pointer"
      >
        <!-- Halo lumineux d'arrière-plan -->
        <span
          class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-violet-600 to-indigo-600 opacity-60 blur-md group-hover:opacity-100 transition-opacity pointer-events-none -z-10 animate-pulse"
        ></span>

        <!-- Icône dynamique (Étincelles / Croix) -->
        <transition
          mode="out-in"
          enter-active-class="transition duration-200"
          enter-from-class="rotate-90 opacity-0 scale-75"
          enter-to-class="rotate-0 opacity-100 scale-100"
          leave-active-class="transition duration-150"
          leave-from-class="rotate-0 opacity-100 scale-100"
          leave-to-class="-rotate-90 opacity-0 scale-75"
        >
          <X v-if="isOpen" class="w-6 h-6 text-white" />
          <div v-else class="relative flex items-center justify-center">
            <Sparkles class="w-6 h-6 text-amber-300 drop-shadow-sm" />
            <!-- Pastille de notification IA -->
            <span
              class="absolute -top-1 -right-1 px-1 py-0.2 bg-amber-400 text-gray-900 text-[9px] font-black rounded-full shadow-xs uppercase tracking-tighter"
            >
              IA
            </span>
          </div>
        </transition>
      </button>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- 2. PANNEAU DE CHAT FLOTTANT (MODALE RÉTRACTABLE)                            -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <transition
      enter-active-class="transition duration-300 ease-out transform"
      enter-from-class="opacity-0 translate-y-6 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-200 ease-in transform"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-6 scale-95"
    >
      <div
        v-if="isOpen"
        class="fixed bottom-24 right-4 sm:right-6 z-50 w-[calc(100vw-2rem)] sm:w-[440px] md:w-[460px] h-[640px] max-h-[85vh] bg-white rounded-3xl shadow-2xl border border-gray-200/90 flex flex-col overflow-hidden backdrop-blur-xl animate-in"
      >
        <!-- En-tête du widget -->
        <header class="bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-700 text-white p-4 shrink-0 relative overflow-hidden">
          <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-white/10 rounded-full blur-xl pointer-events-none"></div>

          <div class="relative z-10 flex items-center justify-between gap-2 mb-2.5">
            <!-- Titre & Rôle -->
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="w-9 h-9 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center shrink-0 border border-white/25">
                <Sparkles class="w-5 h-5 text-amber-300" />
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                  <h3 class="text-sm font-black tracking-tight truncate">Assistant Gemini</h3>
                  <span class="text-[10px] font-bold px-2 py-0.2 rounded-full bg-white/20 text-white border border-white/20 shrink-0">
                    {{ roleBadge }}
                  </span>
                </div>
                <div class="flex items-center gap-1 text-[11px] text-violet-100 font-medium">
                  <span
                    :class="[
                      'w-1.5 h-1.5 rounded-full',
                      isFallbackActive ? 'bg-amber-400' : 'bg-emerald-400 animate-pulse'
                    ]"
                  ></span>
                  <span>{{ isFallbackActive ? 'Tuteur local' : activeModel }}</span>
                  <span class="text-violet-300">• FR & Dérja</span>
                </div>
              </div>
            </div>

            <!-- Actions header (Réduire & Fermer) -->
            <div class="flex items-center gap-1">
              <button
                @click="clearChat"
                title="Effacer la conversation"
                class="p-1.5 rounded-lg text-violet-200 hover:text-white hover:bg-white/15 transition-colors"
              >
                <RotateCcw class="w-4 h-4" />
              </button>
              <button
                @click="toggleOpen"
                title="Fermer"
                class="p-1.5 rounded-lg text-violet-200 hover:text-white hover:bg-white/15 transition-colors"
              >
                <X class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Onglets Sélecteur : Assistant IA vs Tutoriel Guide -->
          <div class="flex items-center gap-1.5 bg-black/20 p-1 rounded-xl border border-white/15 backdrop-blur-md">
            <button
              @click="activeView = 'assistant'"
              :class="[
                'flex-1 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5',
                activeView === 'assistant'
                  ? 'bg-white text-violet-900 shadow-xs'
                  : 'text-violet-100 hover:text-white hover:bg-white/10'
              ]"
            >
              <Bot class="w-3.5 h-3.5" />
              <span>Chatbot IA</span>
            </button>
            <button
              @click="activeView = 'tutorial'"
              :class="[
                'flex-1 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5',
                activeView === 'tutorial'
                  ? 'bg-white text-violet-900 shadow-xs'
                  : 'text-violet-100 hover:text-white hover:bg-white/10'
              ]"
            >
              <GraduationCap class="w-3.5 h-3.5" />
              <span>Guide & Tutoriel</span>
            </button>
          </div>
        </header>

        <!-- ─────────────────────────────────────────────────────────────────── -->
        <!-- VUE 1 : ASSISTANT CHATBOT                                           -->
        <!-- ─────────────────────────────────────────────────────────────────── -->
        <div v-if="activeView === 'assistant'" class="flex-1 flex flex-col min-h-0 bg-gray-50/60">
          <!-- Tiroir rétractable des suggestions rapides -->
          <div class="border-b border-gray-200 bg-white px-3 py-2 shrink-0">
            <div class="flex items-center justify-between">
              <button
                @click="showPromptsDrawer = !showPromptsDrawer"
                class="text-[11px] font-bold text-violet-700 hover:text-violet-900 flex items-center gap-1.5 transition-colors cursor-pointer"
              >
                <Zap class="w-3.5 h-3.5 text-amber-500" />
                <span>Suggestions pour {{ roleName }}</span>
                <ChevronRight
                  :class="['w-3.5 h-3.5 transition-transform duration-200', showPromptsDrawer ? 'rotate-90' : '']"
                />
              </button>
              <span class="text-[10px] font-bold text-gray-400">
                {{ quickPrompts.length }} idées
              </span>
            </div>

            <!-- Liste des suggestions -->
            <div v-show="showPromptsDrawer" class="mt-2 space-y-1.5 max-h-36 overflow-y-auto pr-1">
              <button
                v-for="(item, idx) in quickPrompts"
                :key="idx"
                @click="applyQuickPrompt(item)"
                class="w-full text-left p-2 rounded-xl border border-gray-100 bg-gray-50 hover:bg-violet-50/80 hover:border-violet-200 transition-all flex items-start gap-2 group cursor-pointer text-xs"
              >
                <component :is="item.icon" class="w-3.5 h-3.5 text-violet-600 mt-0.5 shrink-0" />
                <div class="min-w-0 flex-1">
                  <div class="flex items-center justify-between gap-1">
                    <p class="font-bold text-gray-900 group-hover:text-violet-800 truncate text-[11px]">{{ item.title }}</p>
                    <span v-if="item.lang" class="text-[9px] font-extrabold text-amber-700 bg-amber-50 px-1 rounded shrink-0">
                      {{ item.lang }}
                    </span>
                  </div>
                  <p class="text-[10px] text-gray-500 truncate">{{ item.description }}</p>
                </div>
              </button>
            </div>
          </div>

          <!-- Zone des Messages -->
          <div ref="chatContainer" class="flex-1 overflow-y-auto p-3.5 space-y-3">
            <div
              v-for="(msg, index) in messages"
              :key="index"
              :class="['flex gap-2.5', msg.sender === 'user' ? 'justify-end' : 'justify-start']"
            >
              <!-- Avatar IA -->
              <div
                v-if="msg.sender === 'ai'"
                class="w-7 h-7 rounded-lg bg-gradient-to-tr from-violet-600 to-indigo-600 text-white flex items-center justify-center shrink-0 shadow-2xs mt-0.5"
              >
                <Sparkles class="w-3.5 h-3.5 text-amber-300" />
              </div>

              <!-- Bulle Message -->
              <div
                :class="[
                  'max-w-[85%] rounded-2xl p-3 text-xs leading-relaxed relative group shadow-2xs',
                  msg.sender === 'user'
                    ? 'bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-tr-xs'
                    : 'bg-white text-gray-800 border border-gray-200/80 rounded-tl-xs'
                ]"
              >
                <!-- Bouton Copier pour les réponses IA -->
                <button
                  v-if="msg.sender === 'ai'"
                  @click="copyText(msg.text, index)"
                  class="absolute top-2 right-2 p-1 rounded-md bg-gray-50 hover:bg-violet-50 text-gray-400 hover:text-violet-600 transition-colors opacity-0 group-hover:opacity-100 cursor-pointer"
                  title="Copier le texte"
                >
                  <CheckCircle2 v-if="copiedIndex === index" class="w-3.5 h-3.5 text-emerald-500" />
                  <Copy v-else class="w-3.5 h-3.5" />
                </button>

                <!-- Contenu avec retour à la ligne préservé et rendu Markdown sécurisé -->
                <div class="whitespace-pre-wrap font-normal select-text" v-html="formatMessage(msg.text)"></div>
              </div>

              <!-- Avatar Utilisateur -->
              <div
                v-if="msg.sender === 'user'"
                class="w-7 h-7 rounded-lg bg-gray-200 text-gray-700 flex items-center justify-center shrink-0 text-xs font-bold shadow-2xs mt-0.5 uppercase"
              >
                {{ currentUserName ? currentUserName.charAt(0) : 'U' }}
              </div>
            </div>

            <!-- Indicateur de saisie IA -->
            <div v-if="isTyping" class="flex gap-2.5 items-center">
              <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-violet-600 to-indigo-600 text-white flex items-center justify-center shrink-0">
                <Sparkles class="w-3.5 h-3.5 text-amber-300" />
              </div>
              <div class="bg-white border border-gray-200/80 rounded-2xl rounded-tl-xs p-3 shadow-2xs flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-violet-600 animate-bounce"></span>
                <span class="w-2 h-2 rounded-full bg-purple-600 animate-bounce [animation-delay:0.2s]"></span>
                <span class="w-2 h-2 rounded-full bg-indigo-600 animate-bounce [animation-delay:0.4s]"></span>
                <span class="text-[11px] text-gray-400 font-medium ml-1">Gemini réfléchit...</span>
              </div>
            </div>
          </div>

          <!-- Formulaire de saisie -->
          <footer class="p-3 bg-white border-t border-gray-200 shrink-0">
            <form @submit.prevent="sendMessage" class="flex items-center gap-2">
              <input
                v-model="inputQuery"
                type="text"
                maxlength="1000"
                :placeholder="inputPlaceholder"
                class="flex-1 text-xs px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-violet-500 focus:outline-none transition-all font-medium"
                :disabled="isTyping"
              />
              <button
                type="submit"
                :disabled="!inputQuery.trim() || isTyping"
                class="p-2.5 bg-violet-600 hover:bg-violet-700 disabled:opacity-40 text-white font-bold rounded-xl transition-all flex items-center justify-center shadow-md shadow-violet-600/20 cursor-pointer"
                title="Envoyer le message"
              >
                <Send class="w-4 h-4" />
              </button>
            </form>
            <div class="flex items-center justify-between mt-1.5 text-[10px] text-gray-400 px-1">
              <span>Français & Dérja tunisienne</span>
              <span>{{ inputQuery.length }}/1000</span>
            </div>
          </footer>
        </div>

        <!-- ─────────────────────────────────────────────────────────────────── -->
        <!-- VUE 2 : GUIDE & TUTORIEL CONTEXTUALISÉ                               -->
        <!-- ─────────────────────────────────────────────────────────────────── -->
        <div v-else class="flex-1 overflow-y-auto p-4 space-y-3.5 bg-gray-50/60">
          <div class="bg-violet-50 rounded-2xl p-3 border border-violet-100 text-xs text-violet-800">
            <div class="flex items-center gap-2 font-bold mb-1">
              <GraduationCap class="w-4 h-4 text-violet-600" />
              <span>Guide interactif — Espace {{ roleName }}</span>
            </div>
            <p class="text-[11px] text-violet-700/90 leading-relaxed">
              Consultez les procédures types pour naviguer et maîtriser l'application Study Center en toute autonomie.
            </p>
          </div>

          <div
            v-for="guide in activeGuides"
            :key="guide.id"
            class="bg-white rounded-2xl p-4 border border-gray-200/90 shadow-2xs hover:border-violet-300 transition-all space-y-2.5"
          >
            <div class="flex items-center gap-3">
              <div :class="['w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-2xs', guide.bgClass]">
                <component :is="guide.icon" :class="['w-4.5 h-4.5', guide.iconClass]" />
              </div>
              <div class="min-w-0 flex-1">
                <span class="text-[10px] font-extrabold text-violet-600 uppercase tracking-wider block">Étape {{ guide.step }}</span>
                <h4 class="text-xs font-bold text-gray-900 truncate">{{ guide.title }}</h4>
              </div>
            </div>

            <p class="text-[11px] text-gray-600 leading-relaxed">{{ guide.description }}</p>

            <ul class="space-y-1.5 text-[11px] text-gray-700 bg-gray-50/70 p-2.5 rounded-xl border border-gray-100">
              <li v-for="(point, pIdx) in guide.points" :key="pIdx" class="flex items-start gap-2">
                <CheckCircle2 class="w-3.5 h-3.5 text-emerald-500 shrink-0 mt-0.5" />
                <span>{{ point }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted } from 'vue';
import {
  Sparkles, Bot, GraduationCap, Zap, Send, Copy, RotateCcw, X,
  BookOpen, FileText, MessageSquare, CheckCircle2, ChevronRight,
  Calendar, Users, UserCheck, DollarSign, Wallet, Shield, PhoneCall
} from 'lucide-vue-next';
import api from '../utils/api';

const props = withDefaults(
  defineProps<{
    userRole?: 'director' | 'secretary' | 'teacher';
    userName?: string;
  }>(),
  {
    userRole: 'teacher',
    userName: 'Utilisateur',
  }
);

// État d'ouverture du widget flottant
const isOpen = ref(false);
const showTooltip = ref(true);
const activeView = ref<'assistant' | 'tutorial'>('assistant');
const showPromptsDrawer = ref(false);

const inputQuery = ref('');
const isTyping = ref(false);
const chatContainer = ref<HTMLElement | null>(null);
const copiedIndex = ref<number | null>(null);

const activeModel = ref('Gemini 2.5 Flash');
const isFallbackActive = ref(false);

interface ChatMessage {
  sender: 'user' | 'ai';
  text: string;
}

const currentUserName = computed(() => props.userName || 'Utilisateur');

const roleBadge = computed(() => {
  switch (props.userRole) {
    case 'director':
      return 'Directeur';
    case 'teacher':
      return 'Enseignant';
    default:
      return 'Secrétaire';
  }
});

const roleName = computed(() => {
  switch (props.userRole) {
    case 'director':
      return 'la Direction';
    case 'teacher':
      return 'les Enseignants';
    default:
      return 'le Secrétariat';
  }
});

const tooltipText = computed(() => {
  switch (props.userRole) {
    case 'director':
      return 'Conseiller IA : Bilan, Finances & Stratégie';
    case 'teacher':
      return 'Copilote IA : Cours, Séances & Présences';
    default:
      return 'Assistant IA : Inscriptions & Caisse';
  }
});

const inputPlaceholder = computed(() => {
  switch (props.userRole) {
    case 'director':
      return 'Posez une question sur le bilan, la marge, les profs...';
    case 'teacher':
      return 'Posez une question (ex: Kifech na3mel appel ?)...';
    default:
      return 'Posez une question sur les paiements, élèves, garderie...';
  }
});

// Message d'accueil selon le rôle
const messages = ref<ChatMessage[]>([]);

function initWelcomeMessage() {
  let welcomeText = '';
  if (props.userRole === 'director') {
    welcomeText = `Bonjour Monsieur le Directeur ! 3asslema ! Je suis votre conseiller IA de gestion pour Study Center.\n\nJe suis formé pour vous assister sur :\n• Le pilotage financier, l'analyse du chiffre d'affaires et la marge nette\n• Les 3 formules de rémunération des enseignants (Formules A, B et C)\n• La vérification de la règle d'absorption à 100% des remises par le centre\n• La clôture mensuelle des comptes et les audits d'absentéisme\n\nN'hésitez pas à me poser vos questions en Français ou en Dérja !`;
  } else if (props.userRole === 'secretary') {
    welcomeText = `Bonjour ! 3asslema ! Je suis votre assistante administrative IA pour Study Center.\n\nJe peux vous assister sur :\n• L'accueil, l'inscription des élèves et la gestion des groupes\n• L'encaissement des paiements, facturation et reçus de caisse\n• La rédaction de relances bienveillantes pour les impayés (WhatsApp/SMS)\n• Le pointage journalier et forfaits de la garderie parascolaire\n\nComment puis-je vous aider aujourd'hui ?`;
  } else {
    welcomeText = `Bonjour ! 3asslema ! Je suis votre copilote pédagogique IA pour Study Center.\n\nJe peux vous assister sur :\n• La préparation de plans de cours de 2h adaptés au programme tunisien\n• La génération d'exercices progressifs avec corrigés types Bac\n• La prise d'appel et le suivi de vos présences en ligne\n• La communication bienveillante avec les parents d'élèves\n\nN'hésitez pas à me poser vos questions en Français ou en Dérja !`;
  }

  messages.value = [{ sender: 'ai', text: welcomeText }];
}

// ─── SUGGESTIONS RAPIDES PAR RÔLE ─────────────────────────────────────────────
const quickPrompts = computed(() => {
  if (props.userRole === 'director') {
    return [
      {
        icon: DollarSign,
        title: 'Analyse du Chiffre d\'Affaires & Marge',
        description: 'Explication de la marge nette et rentabilité',
        query: "Comment analyser la rentabilité mensuelle du centre et s'assurer que les remises accordées aux élèves sont bien absorbées par la marge du centre sans léser les professeurs ?",
        lang: 'FR',
      },
      {
        icon: Wallet,
        title: 'Formules de Rémunération (A, B, C)',
        description: 'Règles des 3 modes de rémunération des enseignants',
        query: "Explique-moi les 3 modes de rémunération configurés pour les enseignants (Formule A fixed_center, Formule B fixed, Formule C percentage) et la règle de calcul sur le plein tarif avant remise.",
        lang: 'FR',
      },
      {
        icon: Shield,
        title: 'Kifech ncloturi el ch\'har ?',
        description: 'Étapes de clôture mensuelle comptable',
        query: "3asslema, kifech ncloturi el ch'har comptable fel application w nthabbet f les recettes wel masrouf mte3 el centre ?",
        lang: 'Dérja',
      },
      {
        icon: Users,
        title: 'Audit Présences & Décrochage',
        description: 'Détecter les élèves à faible assiduité',
        query: "Comment vérifier le taux d'assiduité global des élèves pour repérer les risques de décrochage et organiser un point avec les parents ?",
        lang: 'FR',
      },
    ];
  }

  if (props.userRole === 'secretary') {
    return [
      {
        icon: PhoneCall,
        title: 'Message WhatsApp Relance Impayé',
        description: 'Rappel courtois pour mensualité en retard',
        query: "Rédige un message WhatsApp professionnel et bienveillant destiné aux parents d'un élève dont la mensualité de cours du mois en cours est en attente de règlement.",
        lang: 'FR',
      },
      {
        icon: MessageSquare,
        title: 'Relance Impayé en Dérja',
        description: 'Rappel WhatsApp chaleureux en dérja tunisienne',
        query: "3asslema, a3tini modèle mte3 message WhatsApp m2addab w behi l parents 3al mensualité mte3 weldom eli mazzelet en attente.",
        lang: 'Dérja',
      },
      {
        icon: Users,
        title: 'Procédure Inscription Nouvel Élève',
        description: 'Étapes pour inscrire et affecter à un groupe',
        query: "Quelles sont les informations indispensables à renseigner lors de l'inscription d'un nouvel élève et comment lui associer sa matière et son groupe ?",
        lang: 'FR',
      },
      {
        icon: Calendar,
        title: 'Pointage Garderie Parascolaire',
        description: 'Suivi journalier et forfaits garderie',
        query: "Comment pointer les arrivées des enfants à la garderie parascolaire et vérifier les forfaits mensuels souscrits ?",
        lang: 'FR',
      },
    ];
  }

  // Rôle enseignant
  return [
    {
      icon: BookOpen,
      title: 'Plan de séance (2h)',
      description: 'Déroulé avec rappels, cours et pratique',
      query: "Conçois un plan de séance détaillé de 2h pour un cours de soutien au niveau Lycée/Bac. Inclus les objectifs, 15 min de rappel, 45 min de notion clé, et 60 min d'exercices d'application.",
      lang: 'FR',
    },
    {
      icon: FileText,
      title: 'Série de 3 Exercices + Corrigé',
      description: 'Niveau facile, intermédiaire et synthèse Bac',
      query: "Rédige une série de 3 exercices progressifs avec corrigés détaillés. Exercice 1 (Application directe), Exercice 2 (Approfondissement), Exercice 3 (Problème de synthèse type Baccalauréat).",
      lang: 'FR',
    },
    {
      icon: UserCheck,
      title: 'Kifech na3mel appel ?',
      description: 'Pointage des présences dans le portail',
      query: "3asslema, kifech na3mel l'appel fel application w nvalider les présences mte3 el groupe mte3i ?",
      lang: 'Dérja',
    },
    {
      icon: MessageSquare,
      title: 'Message WhatsApp Parents (Absence)',
      description: 'Signalement d\'absence imprévue avec bienveillance',
      query: "Rédige un message bienveillant destiné aux parents pour signaler l'absence de leur enfant à la séance de ce jour et demander de justifier cette absence.",
      lang: 'FR',
    },
    {
      icon: Calendar,
      title: 'Kifech nchouf l\'emploi ?',
      description: 'Planning hebdomadaire et salles attribuées',
      query: "Kifech nchouf l'emploi du temps mte3i w les salles eli n9arri fehom jem3a hedhi ?",
      lang: 'Dérja',
    },
  ];
});

// ─── GUIDES TUTORIEL PAR RÔLE ─────────────────────────────────────────────────
const activeGuides = computed(() => {
  if (props.userRole === 'director') {
    return [
      {
        id: 'reports',
        step: 1,
        title: 'Bilan Comptable & Marges',
        description: 'Visualisez les recettes d\'élèves, les frais et la marge nette du centre.',
        points: [
          'Tableau de bord financier consolidé',
          'Suivi des remises absorbées par le centre',
          'Clôture mensuelle certifiée en 1 clic',
        ],
        icon: DollarSign,
        bgClass: 'bg-emerald-100 text-emerald-700',
        iconClass: 'text-emerald-600',
      },
      {
        id: 'teachers',
        step: 2,
        title: 'Rémunération des Enseignants',
        description: 'Configurez et suivez les fiches de paie selon les 3 formules conventionnées.',
        points: [
          'Formule A (Part fixe centre), B (Tarif fixe), C (Pourcentage)',
          'Garantie du plein tarif prof sans impact des remises élèves',
          'Bascule du statut en « Rémunéré » à la remise des fonds',
        ],
        icon: Wallet,
        bgClass: 'bg-violet-100 text-violet-700',
        iconClass: 'text-violet-600',
      },
      {
        id: 'users',
        step: 3,
        title: 'Équipe & Gestion des Droits',
        description: 'Créez les accès de vos secrétaires et enseignants avec permissions granulaires.',
        points: [
          'Attribution des profils d\'accès',
          'Réinitialisation des mots de passe',
          'Journal d\'audit des actions sensibles',
        ],
        icon: Shield,
        bgClass: 'bg-indigo-100 text-indigo-700',
        iconClass: 'text-indigo-600',
      },
    ];
  }

  if (props.userRole === 'secretary') {
    return [
      {
        id: 'students',
        step: 1,
        title: 'Inscriptions & Fiches Élèves',
        description: 'Inscrivez les élèves, constituez leurs dossiers et affectez-les aux groupes.',
        points: [
          'Coordonnées des parents et élèves',
          'Niveaux scolaires et matières souscrites',
          'Application des tarifs ou remises autorisées',
        ],
        icon: Users,
        bgClass: 'bg-blue-100 text-blue-700',
        iconClass: 'text-blue-600',
      },
      {
        id: 'payments',
        step: 2,
        title: 'Encaissements & Caisse',
        description: 'Enregistrez les règlements des séances et imprimez les reçus officiels.',
        points: [
          'Calcul automatique des soldes dus',
          'Gestion des acomptes et mensualités',
          'Impression propre des reçus en 1 clic',
        ],
        icon: DollarSign,
        bgClass: 'bg-emerald-100 text-emerald-700',
        iconClass: 'text-emerald-600',
      },
      {
        id: 'schedule',
        step: 3,
        title: 'Emploi du Temps & Salles',
        description: 'Consultez les plannings et attribuez les salles sans conflit.',
        points: [
          'Vue hebdomadaire complète par jour',
          'Alerte visuelle en cas de chevauchement',
          'Impression des plannings de classes',
        ],
        icon: Calendar,
        bgClass: 'bg-violet-100 text-violet-700',
        iconClass: 'text-violet-600',
      },
    ];
  }

  // Enseignant
  return [
    {
      id: 'schedule',
      step: 1,
      title: 'Mon Emploi du Temps',
      description: 'Consultez votre planning hebdomadaire et vos salles attribuées.',
      points: [
        'Vue claire par jour de la semaine',
        'Affichage de la salle attribuée',
        'Accès direct au pointage de vos cours',
      ],
      icon: Calendar,
      bgClass: 'bg-violet-100 text-violet-700',
      iconClass: 'text-violet-600',
    },
    {
      id: 'groups',
      step: 2,
      title: 'Mes Groupes & Élèves',
      description: 'Retrouvez la liste complète de vos élèves par groupe avec coordonnées.',
      points: [
        'Effectifs en temps réel',
        'Numéros des élèves et parents',
        'Taux d\'assiduité par élève',
      ],
      icon: Users,
      bgClass: 'bg-indigo-100 text-indigo-700',
      iconClass: 'text-indigo-600',
    },
    {
      id: 'attendance',
      step: 3,
      title: 'Faire l\'Appel en Ligne',
      description: 'Pointez les présences, absences et retards en quelques secondes.',
      points: [
        'Bouton rapide « Tous Présents » en 1 clic',
        'Statuts : Présent, Absent, Retard, Excusé',
        'Historique immédiat de chaque séance',
      ],
      icon: UserCheck,
      bgClass: 'bg-emerald-100 text-emerald-700',
      iconClass: 'text-emerald-600',
    },
  ];
});

function toggleOpen() {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    showTooltip.value = false;
    scrollToBottom();
  }
}

function applyQuickPrompt(promptItem: { query: string }) {
  inputQuery.value = promptItem.query;
  showPromptsDrawer.value = false;
  sendMessage();
}

async function sendMessage() {
  const query = inputQuery.value.trim();
  if (!query || isTyping.value) return;

  messages.value.push({ sender: 'user', text: query });
  inputQuery.value = '';
  isTyping.value = true;
  scrollToBottom();

  try {
    const res = await api.post('/api/ai/ask', {
      message: query,
      history: messages.value.slice(0, -1).slice(-10).map(m => ({
        sender: m.sender,
        text: m.text,
      })),
      context: {
        role: props.userRole,
      },
    });

    const reply = res.data?.reply || "Une réponse n'a pas pu être générée.";
    activeModel.value = res.data?.model || 'Gemini 2.5 Flash';
    isFallbackActive.value = !!res.data?.is_fallback;
    messages.value.push({ sender: 'ai', text: reply });
  } catch (err: any) {
    console.error('Erreur API Chatbot Gemini:', err);
    const errMsg = err.response?.data?.message || "Impossible de joindre le service IA. Vérifiez la connexion au serveur.";
    messages.value.push({ sender: 'ai', text: `⚠️ ${errMsg}` });
  } finally {
    isTyping.value = false;
    scrollToBottom();
  }
}

/**
 * Formate le texte du message avec échappement XSS et support basique du Markdown
 * - Échappe d'abord &, < et > (protection XSS)
 * - Convertit **texte** en <strong>texte</strong>
 * - Convertit *texte* en <em>texte</em>
 */
function formatMessage(text: string): string {
  if (!text) return '';
  let safe = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
  safe = safe.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  safe = safe.replace(/\*([^\*]+?)\*/g, '<em>$1</em>');
  return safe;
}

function copyText(text: string, index: number) {
  navigator.clipboard.writeText(text);
  copiedIndex.value = index;
  setTimeout(() => {
    copiedIndex.value = null;
  }, 2000);
}

function clearChat() {
  initWelcomeMessage();
}

function scrollToBottom() {
  nextTick(() => {
    if (chatContainer.value) {
      chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
  });
}

onMounted(() => {
  initWelcomeMessage();
});
</script>
