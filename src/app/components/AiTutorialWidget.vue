<template>
  <div class="space-y-6">
    <!-- En-tête -->
    <div class="bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-700 rounded-3xl p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
      <div class="absolute -right-8 -bottom-8 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-semibold mb-3 border border-white/20">
            <Sparkles class="w-3.5 h-3.5 text-amber-300" />
            <span>Assistant Pédagogique Google Gemini & Tutoriels</span>
          </div>
          <h2 class="text-2xl md:text-3xl font-black tracking-tight">Gemini Chatbot & Guide Interactif</h2>
          <p class="text-violet-100 text-sm mt-1 max-w-2xl leading-relaxed">
            Votre copilote intelligent pour concevoir vos plans de cours, générer des exercices sur-mesure, rédiger des messages aux parents et maîtriser l'application Study Center en Français ou en Dérja.
          </p>
        </div>

        <!-- Onglet Sélecteur -->
        <div class="flex items-center gap-2 bg-black/20 p-1.5 rounded-2xl border border-white/15 backdrop-blur-md shrink-0">
          <button
            @click="activeView = 'assistant'"
            :class="[
              'px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2',
              activeView === 'assistant'
                ? 'bg-white text-violet-900 shadow-md'
                : 'text-violet-100 hover:text-white hover:bg-white/10'
            ]"
          >
            <Bot class="w-4 h-4" />
            Chatbot Gemini
          </button>
          <button
            @click="activeView = 'tutorial'"
            :class="[
              'px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2',
              activeView === 'tutorial'
                ? 'bg-white text-violet-900 shadow-md'
                : 'text-violet-100 hover:text-white hover:bg-white/10'
            ]"
          >
            <GraduationCap class="w-4 h-4" />
            Tutoriel Portail
          </button>
        </div>
      </div>
    </div>

    <!-- VUE 1 : ASSISTANT IA -->
    <div v-if="activeView === 'assistant'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Panneau latéral : Suggestions rapides & Raccourcis pédagogiques -->
      <div class="lg:col-span-4 space-y-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-xs">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
              <Zap class="w-4 h-4 text-violet-600" />
              Suggestions Rapides
            </h3>
            <span class="text-[10px] font-bold bg-violet-50 text-violet-700 px-2 py-0.5 rounded-full border border-violet-200">
              FR & Dérja
            </span>
          </div>
          <div class="space-y-2">
            <button
              v-for="(promptItem, idx) in quickPrompts"
              :key="idx"
              @click="applyQuickPrompt(promptItem)"
              class="w-full text-left p-2.5 rounded-xl border border-gray-100 bg-gray-50/60 hover:bg-violet-50/80 hover:border-violet-200 transition-all group cursor-pointer"
            >
              <div class="flex items-start gap-2.5">
                <span class="p-1.5 rounded-lg bg-white group-hover:bg-violet-100 text-violet-600 transition-colors shadow-2xs shrink-0">
                  <component :is="promptItem.icon" class="w-3.5 h-3.5" />
                </span>
                <div class="min-w-0 flex-1">
                  <div class="flex items-center justify-between gap-1">
                    <p class="text-xs font-bold text-gray-900 group-hover:text-violet-800 truncate">{{ promptItem.title }}</p>
                    <span v-if="promptItem.lang" class="text-[9px] font-extrabold text-amber-700 bg-amber-50 px-1 py-0.2 rounded shrink-0">
                      {{ promptItem.lang }}
                    </span>
                  </div>
                  <p class="text-[11px] text-gray-500 line-clamp-1 mt-0.5">{{ promptItem.description }}</p>
                </div>
              </div>
            </button>
          </div>
        </div>

        <!-- Paramètres Pédagogiques -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-xs space-y-3">
          <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider text-gray-500">
            Contexte de classe (Optionnel)
          </h3>
          <div>
            <label class="block text-[11px] font-semibold text-gray-700 mb-1">Matière</label>
            <input
              v-model="contextSubject"
              type="text"
              placeholder="Ex: Mathématiques, Physique..."
              class="w-full text-xs px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-violet-500 focus:outline-none"
            />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-700 mb-1">Niveau scolaire</label>
            <select
              v-model="contextLevel"
              class="w-full text-xs px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-violet-500 focus:outline-none"
            >
              <option value="Primaire">Primaire (5ème / 6ème année)</option>
              <option value="Collège">Collège (7ème - 9ème année de base)</option>
              <option value="Lycée 1ère / 2ème">Lycée (1ère - 2ème secondaire)</option>
              <option value="Baccalauréat">Baccalauréat (Math, Sc, Info, Éco, Lettres)</option>
            </select>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-700 mb-1">Ton souhaité</label>
            <select
              v-model="contextTone"
              class="w-full text-xs px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-violet-500 focus:outline-none"
            >
              <option value="didactique">Didactique & Structuré (Plan de cours)</option>
              <option value="stimulant">Encourageant & Motivateur (Élèves)</option>
              <option value="bienveillant">Bienveillant & Rassurant (Parents)</option>
              <option value="derja">Authentique en Dérja Tunisienne (دارجة)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Zone de Conversation Chatbot IA -->
      <div class="lg:col-span-8 flex flex-col bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden h-[640px]">
        <!-- Barre supérieure du chat -->
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/70">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-violet-600 to-purple-500 text-white flex items-center justify-center shadow-xs">
              <Sparkles class="w-5 h-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h4 class="text-sm font-bold text-gray-900">Assistant IA Gemini</h4>
                <span
                  :class="[
                    'inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full border',
                    isFallbackActive
                      ? 'text-amber-700 bg-amber-50 border-amber-200'
                      : 'text-emerald-700 bg-emerald-50 border-emerald-200'
                  ]"
                >
                  <span
                    :class="[
                      'w-1.5 h-1.5 rounded-full',
                      isFallbackActive ? 'bg-amber-500' : 'bg-emerald-500 animate-pulse'
                    ]"
                  ></span>
                  <span>{{ activeModel }}</span>
                </span>
              </div>
              <p class="text-[11px] text-gray-500">Tuteur officiel Study Center • Programme Tunisien (FR / دارجة)</p>
            </div>
          </div>

          <button
            @click="clearChat"
            class="text-xs text-gray-500 hover:text-red-600 px-2.5 py-1.5 rounded-lg hover:bg-gray-100 transition-colors flex items-center gap-1.5"
            title="Effacer la conversation"
          >
            <RotateCcw class="w-3.5 h-3.5" />
            <span>Réinitialiser</span>
          </button>
        </div>

        <!-- Messages du Chat -->
        <div ref="chatContainer" class="flex-1 overflow-y-auto p-4 md:p-6 space-y-4">
          <div
            v-for="(msg, i) in messages"
            :key="i"
            :class="[
              'flex gap-3 max-w-[85%]',
              msg.sender === 'user' ? 'ml-auto flex-row-reverse' : ''
            ]"
          >
            <div
              :class="[
                'w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-xs font-bold shadow-2xs',
                msg.sender === 'user'
                  ? 'bg-indigo-600 text-white'
                  : 'bg-violet-100 text-violet-700'
              ]"
            >
              <User v-if="msg.sender === 'user'" class="w-4 h-4" />
              <Bot v-else class="w-4 h-4" />
            </div>

            <div
              :class="[
                'rounded-2xl p-4 text-xs leading-relaxed space-y-2 select-text shadow-2xs',
                msg.sender === 'user'
                  ? 'bg-indigo-600 text-white rounded-tr-none'
                  : 'bg-gray-50 border border-gray-200/80 text-gray-800 rounded-tl-none'
              ]"
            >
              <div class="whitespace-pre-wrap font-sans">{{ msg.text }}</div>

              <div
                v-if="msg.sender === 'ai'"
                class="pt-2 border-t border-gray-200/60 flex items-center justify-between text-[10px] text-gray-400"
              >
                <span class="text-[9px] text-gray-400 font-mono">Study Center AI</span>
                <button
                  @click="copyText(msg.text, i)"
                  class="hover:text-violet-600 flex items-center gap-1 transition-colors px-1.5 py-0.5 rounded hover:bg-white"
                >
                  <Copy class="w-3 h-3" />
                  <span>{{ copiedIndex === i ? 'Copié !' : 'Copier' }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Indicateur de réflexion IA -->
          <div v-if="isTyping" class="flex gap-3 max-w-[85%] animate-in fade-in">
            <div class="w-8 h-8 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center shrink-0">
              <Bot class="w-4 h-4" />
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-3.5 flex items-center gap-2">
              <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-violet-400 animate-bounce"></span>
                <span class="w-2 h-2 rounded-full bg-violet-500 animate-bounce [animation-delay:0.15s]"></span>
                <span class="w-2 h-2 rounded-full bg-violet-600 animate-bounce [animation-delay:0.3s]"></span>
              </div>
              <span class="text-xs text-violet-700 font-medium ml-1">Gemini analyse et rédige votre réponse...</span>
            </div>
          </div>
        </div>

        <!-- Zone de saisie -->
        <div class="p-3 border-t border-gray-100 bg-white">
          <form @submit.prevent="sendMessage" class="flex items-center gap-2">
            <input
              v-model="inputQuery"
              type="text"
              placeholder="Posez une question en Français ou en Dérja (ex: Kifech na3mel appel ?)..."
              class="flex-1 text-xs px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-violet-500 focus:outline-none transition-all font-medium"
              :disabled="isTyping"
            />
            <button
              type="submit"
              :disabled="!inputQuery.trim() || isTyping"
              class="px-5 py-3 bg-violet-600 hover:bg-violet-700 disabled:opacity-40 text-white font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-md shadow-violet-600/20 cursor-pointer"
            >
              <Send class="w-4 h-4" />
              <span class="hidden sm:inline">Envoyer</span>
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- VUE 2 : TUTORIEL DU PORTAIL ENSEIGNANT -->
    <div v-else class="space-y-6">
      <!-- Cartes d'étapes interactives -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div
          v-for="guide in portalGuides"
          :key="guide.id"
          class="bg-white rounded-2xl p-6 border border-gray-200 shadow-xs hover:border-violet-300 hover:shadow-md transition-all flex flex-col justify-between"
        >
          <div>
            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center mb-4', guide.bgClass]">
              <component :is="guide.icon" :class="['w-5 h-5', guide.iconClass]" />
            </div>
            <div class="text-[10px] font-bold text-violet-600 uppercase tracking-wider mb-1">
              Étape {{ guide.step }}
            </div>
            <h3 class="text-base font-bold text-gray-900 mb-2">{{ guide.title }}</h3>
            <p class="text-xs text-gray-600 leading-relaxed mb-4">{{ guide.description }}</p>

            <ul class="space-y-2 text-xs text-gray-700">
              <li v-for="(point, pIdx) in guide.points" :key="pIdx" class="flex items-start gap-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                <span>{{ point }}</span>
              </li>
            </ul>
          </div>

          <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
            <span class="text-[11px] font-semibold text-gray-400">{{ guide.badge }}</span>
            <button
              @click="$emit('navigate', guide.targetTab)"
              class="text-xs font-bold text-violet-600 hover:text-violet-800 flex items-center gap-1 group cursor-pointer"
            >
              <span>Accéder</span>
              <ChevronRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
            </button>
          </div>
        </div>
      </div>

      <!-- FAQ & Bonnes Pratiques Pédagogiques -->
      <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-xs">
        <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
          <HelpCircle class="w-5 h-5 text-violet-600" />
          Foire aux questions & Bonnes pratiques
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
            <h4 class="text-xs font-bold text-gray-900 mb-1 flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-violet-600"></span>
              Quand faire l'appel ?
            </h4>
            <p class="text-xs text-gray-600 leading-relaxed">
              Il est conseillé de valider l'appel durant les 10 premières minutes de chaque séance. Cela permet à l'administration de suivre les assiduités et de générer les récapitulatifs exacts.
            </p>
          </div>

          <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
            <h4 class="text-xs font-bold text-gray-900 mb-1 flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-violet-600"></span>
              Comment les remises sont-elles calculées ?
            </h4>
            <p class="text-xs text-gray-600 leading-relaxed">
              Votre rémunération d'enseignant est calculée selon votre formule contractuelle. Les remises accordées à certains élèves sont intégralement prises en charge par la direction du centre.
            </p>
          </div>

          <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
            <h4 class="text-xs font-bold text-gray-900 mb-1 flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-violet-600"></span>
              Que faire en cas d'absence d'un élève répétée ?
            </h4>
            <p class="text-xs text-gray-600 leading-relaxed">
              Vous pouvez inscrire une note dans le champ d'observation lors de l'appel. Le secrétariat est automatiquement averti pour contacter les parents par téléphone.
            </p>
          </div>

          <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
            <h4 class="text-xs font-bold text-gray-900 mb-1 flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-violet-600"></span>
              Comment imprimer mon planning ?
            </h4>
            <p class="text-xs text-gray-600 leading-relaxed">
              Dans l'onglet « Emploi du temps », cliquez sur le bouton « Imprimer mon planning » pour générer une version imprimable propre de votre semaine de cours.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, nextTick } from 'vue';
import {
  Sparkles, Bot, GraduationCap, Zap, User, Send, Copy, RotateCcw,
  BookOpen, FileText, MessageSquare, CheckCircle2, ChevronRight, HelpCircle,
  Calendar, Users, UserCheck
} from 'lucide-vue-next';
import api from '../utils/api';

defineEmits<{
  (e: 'navigate', tab: string): void;
}>();

const activeView = ref<'assistant' | 'tutorial'>('assistant');
const inputQuery = ref('');
const isTyping = ref(false);
const chatContainer = ref<HTMLElement | null>(null);
const copiedIndex = ref<number | null>(null);

const activeModel = ref('Gemini 1.5 Flash');
const isFallbackActive = ref(false);

const contextSubject = ref('Mathématiques');
const contextLevel = ref('Baccalauréat');
const contextTone = ref('didactique');

interface ChatMessage {
  sender: 'user' | 'ai';
  text: string;
}

const messages = ref<ChatMessage[]>([
  {
    sender: 'ai',
    text: "Bonjour ! 3asslema ! Je suis l'assistant IA officiel de Study Center propulsé par Google Gemini.\n\nJe suis formé pour vous assister sur :\n• La préparation de vos séances de cours et exercices adaptés au programme tunisien\n• La prise d'appel et le suivi de vos présences\n• La consultation de vos plannings et groupes\n• La communication bienveillante avec les parents d'élèves\n\nN'hésitez pas à me poser vos questions en Français ou en Dérja tunisienne !",
  },
]);

const quickPrompts = [
  {
    icon: BookOpen,
    title: 'Plan de séance (2h)',
    description: 'Déroulé chronologique avec rappels, cours et pratique',
    query: "Conçois un plan de séance détaillé de 2h pour le cours de [Matière] niveau [Niveau]. Inclus les objectifs, 15 min de rappel, 45 min de notion clé, et 60 min d'exercices d'application.",
    lang: 'FR',
  },
  {
    icon: FileText,
    title: 'Série de 3 Exercices + Corrigé',
    description: 'Niveau facile, intermédiaire et type examen',
    query: "Rédige une série de 3 exercices progressifs de [Matière] pour le niveau [Niveau]. Exercice 1 (Application directe), Exercice 2 (Approfondissement), Exercice 3 (Problème de synthèse). Fournis les solutions pas à pas.",
    lang: 'FR',
  },
  {
    icon: MessageSquare,
    title: 'Message WhatsApp Parents',
    description: 'Bilan de progression ou encouragement',
    query: "Rédige un message bienveillant et professionnel destiné aux parents d'un élève pour les informer de ses progrès en [Matière] et l'encourager à maintenir ses efforts pour les prochains devoirs.",
    lang: 'FR',
  },
  {
    icon: UserCheck,
    title: 'Kifech na3mel appel ?',
    description: 'Tari9at tasjil el 7oudhour fel portail',
    query: "3asslema, kifech na3mel l'appel fel application w nvalider les présences mte3 el groupe mte3i ?",
    lang: 'Dérja',
  },
  {
    icon: Calendar,
    title: 'Kifech nchouf l\'emploi ?',
    description: 'Planning w les salles attribuées',
    query: "Kifech nchouf l'emploi du temps mte3i w les salles eli n9arri fehom jem3a hedhi ?",
    lang: 'Dérja',
  },
  {
    icon: Zap,
    title: 'QCM de Révision Express (5 questions)',
    description: 'Test rapide de 10 minutes en début de séance',
    query: "Crée un QCM de 5 questions à choix multiples avec explications détaillées sur le chapitre en cours pour le niveau [Niveau].",
    lang: 'FR',
  },
];

const portalGuides = [
  {
    id: 'schedule',
    step: 1,
    title: 'Mon Emploi du Temps',
    description: 'Consultez votre planning hebdomadaire, visualisez vos salles de cours et vos créneaux horaires.',
    points: [
      'Vue claire par jour de la semaine',
      'Affichage de la salle attribuée',
      'Accès direct au pointage d\'un cours',
    ],
    badge: 'Planning Hebdo',
    targetTab: 'schedule',
    icon: Calendar,
    bgClass: 'bg-violet-100 text-violet-700',
    iconClass: 'text-violet-600',
  },
  {
    id: 'groups',
    step: 2,
    title: 'Mes Groupes & Élèves',
    description: 'Retrouvez la liste complète de vos élèves par groupe, avec coordonnées téléphoniques et assiduité.',
    points: [
      'Effectifs en temps réel',
      'Numéros de téléphone des élèves et parents',
      'Taux d\'assiduité par élève',
    ],
    badge: 'Gestion de Classe',
    targetTab: 'groups',
    icon: Users,
    bgClass: 'bg-indigo-100 text-indigo-700',
    iconClass: 'text-indigo-600',
  },
  {
    id: 'attendance',
    step: 3,
    title: 'Faire l\'Appel en Ligne',
    description: 'Pointez les présences, absences et retards en quelques secondes à chaque séance.',
    points: [
      'Boutons rapides : Présent, Absent, Retard, Excusé',
      'Option « Tous présents » en un clic',
      'Observations et historique des séances',
    ],
    badge: 'Feuille de Présence',
    targetTab: 'attendance',
    icon: UserCheck,
    bgClass: 'bg-emerald-100 text-emerald-700',
    iconClass: 'text-emerald-600',
  },
];

function applyQuickPrompt(promptItem: { query: string }) {
  const query = promptItem.query
    .replace(/\[Matière\]/g, contextSubject.value)
    .replace(/\[Niveau\]/g, contextLevel.value);
  inputQuery.value = query;
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
      history: messages.value.slice(0, -1).map(m => ({
        sender: m.sender,
        text: m.text,
      })),
      context: {
        subject: contextSubject.value,
        level: contextLevel.value,
        tone: contextTone.value,
      },
    });

    const reply = res.data?.reply || "Une réponse n'a pas pu être générée.";
    activeModel.value = res.data?.model || 'Gemini 1.5 Flash';
    isFallbackActive.value = !!res.data?.is_fallback;
    messages.value.push({ sender: 'ai', text: reply });
  } catch (err: any) {
    console.error('Erreur API Chatbot Gemini:', err);
    // En cas d'erreur réseau, message élégant
    const errMsg = err.response?.data?.message || "Impossible de joindre le service IA. Vérifiez la connexion au serveur.";
    messages.value.push({ sender: 'ai', text: `⚠️ ${errMsg}` });
  } finally {
    isTyping.value = false;
    scrollToBottom();
  }
}

function copyText(text: string, index: number) {
  navigator.clipboard.writeText(text);
  copiedIndex.value = index;
  setTimeout(() => {
    copiedIndex.value = null;
  }, 2000);
}

function clearChat() {
  messages.value = [
    {
      sender: 'ai',
      text: "Historique réinitialisé. Comment puis-je vous assister pour vos prochains cours ?",
    },
  ];
}

function scrollToBottom() {
  nextTick(() => {
    if (chatContainer.value) {
      chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
  });
}
</script>
