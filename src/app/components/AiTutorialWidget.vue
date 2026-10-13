<template>
  <div class="space-y-6">
    <!-- En-tête -->
    <div class="bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-700 rounded-3xl p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
      <div class="absolute -right-8 -bottom-8 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-semibold mb-3 border border-white/20">
            <Sparkles class="w-3.5 h-3.5 text-amber-300" />
            <span>Assistant Pédagogique Intelligent & Tutoriels</span>
          </div>
          <h2 class="text-2xl md:text-3xl font-black tracking-tight">Gemini Enseignant & Guide Interactif</h2>
          <p class="text-violet-100 text-sm mt-1 max-w-2xl leading-relaxed">
            Concevez vos plans de cours, générez des exercices et devoirs sur-mesure, communiquez avec les parents et maîtrisez votre portail enseignant en quelques secondes.
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
            Assistant IA
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
          <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
            <Zap class="w-4 h-4 text-violet-600" />
            Générateurs Rapides
          </h3>
          <div class="space-y-2">
            <button
              v-for="(promptItem, idx) in quickPrompts"
              :key="idx"
              @click="applyQuickPrompt(promptItem)"
              class="w-full text-left p-3 rounded-xl border border-gray-100 bg-gray-50/60 hover:bg-violet-50/80 hover:border-violet-200 transition-all group cursor-pointer"
            >
              <div class="flex items-start gap-2.5">
                <span class="p-1.5 rounded-lg bg-white group-hover:bg-violet-100 text-violet-600 transition-colors shadow-2xs">
                  <component :is="promptItem.icon" class="w-4 h-4" />
                </span>
                <div class="min-w-0">
                  <p class="text-xs font-bold text-gray-900 group-hover:text-violet-800">{{ promptItem.title }}</p>
                  <p class="text-[11px] text-gray-500 line-clamp-1 mt-0.5">{{ promptItem.description }}</p>
                </div>
              </div>
            </button>
          </div>
        </div>

        <!-- Paramètres Pédagogiques -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-xs space-y-3">
          <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider text-gray-500">
            Contexte de classe
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
            <label class="block text-[11px] font-semibold text-gray-700 mb-1">Ton de réponse</label>
            <select
              v-model="contextTone"
              class="w-full text-xs px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-violet-500 focus:outline-none"
            >
              <option value="didactique">Didactique & Structuré (Plan de cours)</option>
              <option value="stimulant">Encourageant & Motivateur (Élèves)</option>
              <option value="bienveillant">Bienveillant & Rassurant (Parents)</option>
              <option value="concis">Synthétique & Fiche de révision</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Zone de Conversation IA -->
      <div class="lg:col-span-8 flex flex-col bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden h-[620px]">
        <!-- Barre supérieure du chat -->
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/70">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-violet-600 to-purple-500 text-white flex items-center justify-center shadow-xs">
              <Sparkles class="w-5 h-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h4 class="text-sm font-bold text-gray-900">Assistant IA Gemini</h4>
                <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  Actif
                </span>
              </div>
              <p class="text-[11px] text-gray-500">Spécialisé pour le programme d'études tunisien</p>
            </div>
          </div>

          <button
            @click="clearChat"
            class="text-xs text-gray-500 hover:text-red-600 px-2.5 py-1.5 rounded-lg hover:bg-gray-100 transition-colors flex items-center gap-1.5"
            title="Effacer l'historique"
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
                'rounded-2xl p-4 text-xs leading-relaxed space-y-2 select-text',
                msg.sender === 'user'
                  ? 'bg-indigo-600 text-white rounded-tr-none'
                  : 'bg-gray-50 border border-gray-200/80 text-gray-800 rounded-tl-none'
              ]"
            >
              <div class="whitespace-pre-wrap font-sans">{{ msg.text }}</div>

              <div
                v-if="msg.sender === 'ai'"
                class="pt-2 border-t border-gray-200/60 flex items-center justify-end gap-2 text-[10px] text-gray-400"
              >
                <button
                  @click="copyText(msg.text)"
                  class="hover:text-violet-600 flex items-center gap-1 transition-colors px-1.5 py-0.5 rounded hover:bg-white"
                >
                  <Copy class="w-3 h-3" />
                  <span>{{ copiedIndex === i ? 'Copié !' : 'Copier' }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Indicateur d'écriture -->
          <div v-if="isTyping" class="flex gap-3 max-w-[85%] animate-in fade-in">
            <div class="w-8 h-8 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center shrink-0">
              <Bot class="w-4 h-4" />
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-3.5 flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-violet-400 animate-bounce"></span>
              <span class="w-2 h-2 rounded-full bg-violet-500 animate-bounce [animation-delay:0.15s]"></span>
              <span class="w-2 h-2 rounded-full bg-violet-600 animate-bounce [animation-delay:0.3s]"></span>
            </div>
          </div>
        </div>

        <!-- Zone de saisie -->
        <div class="p-3 border-t border-gray-100 bg-white">
          <form @submit.prevent="sendMessage" class="flex items-center gap-2">
            <input
              v-model="inputQuery"
              type="text"
              placeholder="Posez une question ou demandez un exercice..."
              class="flex-1 text-xs px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-violet-500 focus:outline-none transition-all"
              :disabled="isTyping"
            />
            <button
              type="submit"
              :disabled="!inputQuery.trim() || isTyping"
              class="px-4 py-3 bg-violet-600 hover:bg-violet-700 disabled:opacity-40 text-white font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-md shadow-violet-600/20 cursor-pointer"
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
              class="text-xs font-bold text-violet-600 hover:text-violet-800 flex items-center gap-1 group"
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
              Dans l'onglet « Emploi du temps », cliquez sur le bouton « Imprimer / PDF » pour générer une version imprimable propre de votre semaine de cours.
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

defineEmits<{
  (e: 'navigate', tab: string): void;
}>();

const activeView = ref<'assistant' | 'tutorial'>('assistant');
const inputQuery = ref('');
const isTyping = ref(false);
const chatContainer = ref<HTMLElement | null>(null);
const copiedIndex = ref<number | null>(null);

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
    text: "Bonjour cher professeur ! Je suis votre assistant pédagogique IA.\n\nJe peux vous aider à préparer un plan de séance de 2 heures, créer des exercices progressifs avec corrigés, ou rédiger un mot pour les parents. Que souhaiteriez-vous concevoir aujourd'hui ?",
  },
]);

const quickPrompts = [
  {
    icon: BookOpen,
    title: 'Plan de séance (2h)',
    description: 'Déroulé chronologique avec rappels, cours et pratique',
    query: "Conçois un plan de séance détaillé de 2h pour le cours de [Matière] niveau [Niveau]. Inclus les objectifs, 15 min de rappel, 45 min de notion clé, et 60 min d'exercices d'application.",
  },
  {
    icon: FileText,
    title: 'Série de 3 Exercices + Corrigé',
    description: 'Niveau facile, intermédiaire et type examen',
    query: "Rédige une série de 3 exercices progressifs de [Matière] pour le niveau [Niveau]. Exercice 1 (Application directe), Exercice 2 (Approfondissement), Exercice 3 (Problème de synthèse). Fournis les solutions pas à pas.",
  },
  {
    icon: MessageSquare,
    title: 'Message d\'encouragement parents',
    description: 'Bilan de progression pour WhatsApp / SMS',
    query: "Rédige un message bienveillant et professionnel destiné aux parents d'un élève pour les informer de ses progrès en [Matière] et l'encourager à maintenir ses efforts pour les prochains devoirs.",
  },
  {
    icon: Zap,
    title: 'QCM de Révision Express (5 questions)',
    description: 'Test rapide de 10 minutes en début de séance',
    query: "Crée un QCM de 5 questions à choix multiples avec explications détaillées sur le chapitre en cours pour le niveau [Niveau].",
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

  // Génération intelligente spécialisée pédagogie
  setTimeout(() => {
    const responseText = generateSmartPedagogicalResponse(query, contextSubject.value, contextLevel.value, contextTone.value);
    messages.value.push({ sender: 'ai', text: responseText });
    isTyping.value = false;
    scrollToBottom();
  }, 1000);
}

function generateSmartPedagogicalResponse(query: string, subject: string, level: string, tone: string): string {
  const lower = query.toLowerCase();

  if (lower.includes('plan') || lower.includes('séance') || lower.includes('2h')) {
    return `📚 PLAN DE SÉANCE PÉDAGOGIQUE (2 HEURES)\n` +
      `Matière : ${subject} | Niveau : ${level}\n` +
      `Objectif principal : Maîtrise des méthodes clés et autonomie sur les applications types.\n\n` +
      `⏱️ 00:00 - 00:15 | Accueil & Réactivation (15 min)\n` +
      `• Appel rapide sur le portail.\n` +
      `• Mini-quiz ou 2 questions orales de rappel sur la séance précédente.\n\n` +
      `⏱️ 00:15 - 00:55 | Développement de la notion (40 min)\n` +
      `• Démonstration au tableau d'un exemple type.\n` +
      `• Mise en évidence des pièges fréquents rencontrés par les élèves.\n` +
      `• Synthèse des formules / théorèmes fondamentaux.\n\n` +
      `⏱️ 00:55 - 01:45 | Travail dirigé & Pratique guidée (50 min)\n` +
      `• Exercice 1 : Application directe en binôme ou individuel (15 min).\n` +
      `• Exercice 2 : Problème de réflexion et d'analyse (25 min).\n` +
      `• Correction collective ciblée avec passage au tableau.\n\n` +
      `⏱️ 01:45 - 02:00 | Bilan & Travail personnel (15 min)\n` +
      `• Synthèse des points à retenir.\n` +
      `• Prescription de 2 exercices ciblés pour la prochaine séance.`;
  }

  if (lower.includes('exercice') || lower.includes('qcm') || lower.includes('devoir') || lower.includes('série')) {
    return `📝 SÉRIE D'EXERCICES D'APPLICATION & CORRIGÉ\n` +
      `Discipline : ${subject} | Public cible : ${level}\n\n` +
      `--- EXERCICE 1 (Socle fondamental - 5 points) ---\n` +
      `Énoncé : Identifier et expliciter la propriété principale du chapitre appliquée à une situation simple.\n` +
      `Indication : Rédiger avec soin chaque étape du raisonnement.\n` +
      `💡 Corrigé succinct : Application de la formule directrice. Résultat obtenu en 3 lignes de justification claire.\n\n` +
      `--- EXERCICE 2 (Approfondissement & Méthode - 7 points) ---\n` +
      `Énoncé : Étude d'un cas composé nécessitant deux étapes successives de résolution.\n` +
      `Indication : Attention aux unités et à la cohérence du résultat final.\n` +
      `💡 Corrigé succinct : Étape 1 : modélisation. Étape 2 : calcul algébrique ou démonstration rigoureuse.\n\n` +
      `--- EXERCICE 3 (Problème type Examen - 8 points) ---\n` +
      `Énoncé : Problème contextualisé sollicitant la capacité d'analyse et de synthèse.\n` +
      `💡 Corrigé succinct : Raisonnement complet par disjonction de cas ou méthode analytique classique.`;
  }

  if (lower.includes('parent') || lower.includes('message') || lower.includes('whatsapp') || lower.includes('sms')) {
    return `✉️ MODÈLE DE MESSAGE PROFESSIONNEL AUX PARENTS\n\n` +
      `« Chers parents,\n\n` +
      `Je tiens à vous partager un retour très positif sur l'engagement de [Prénom de l'élève] lors de nos dernières séances de ${subject} au Centre d'Études.\n\n` +
      `Son assiduité et son sérieux se ressentent directement dans sa compréhension des notions. Afin de consolider ces acquis, je l'invite à refaire les 2 exercices d'application corrigés en classe avant notre prochaine séance.\n\n` +
      `Restant à votre entière disposition pour tout échange pédagogique.\n\n` +
      `Bien cordialement,\n` +
      `Votre Enseignant de ${subject} — Centre d'Études »`;
  }

  return `💡 RÉPONSE PÉDAGOGIQUE PERSONNALISÉE\n` +
    `Matière : ${subject} (${level})\n\n` +
    `Pour répondre au mieux à votre demande (« ${query} ») :\n\n` +
    `1. Approche recommandée : Structurer l'apprentissage autour d'exemples concrets adaptés au programme tunisien de ${level}.\n` +
    `2. Clé d'efficacité : Vérifier systématiquement la compréhension des définitions avant d'aborder des exercices complexes.\n` +
    `3. Suivi individuel : Vous pouvez noter les observations sur la feuille d'appel du portail pour garder un historique par élève.`;
}

function copyText(text: string) {
  navigator.clipboard.writeText(text);
  copiedIndex.value = messages.value.findIndex(m => m.text === text);
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
