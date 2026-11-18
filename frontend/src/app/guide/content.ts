// src/app/guide/content.ts
// Contenu du guide par rôle et par onglet
// Aucune donnée personnelle, aucun mot de passe

import type { GuideContent, UserRole, TabKey } from './types';

// ─────────────────────────────────────────────────────────────
// DIRECTEUR
// ─────────────────────────────────────────────────────────────
const directorContent: GuideContent = {
  role: 'director',

  checklist: [
    {
      key: 'checklist.subjects',
      label: 'Créer les matières',
      description: "Ajoutez les matières enseignées dans l'onglet Enseignants > Matières.",
      targetTab: 'teachers',
    },
    {
      key: 'checklist.teachers',
      label: 'Ajouter les enseignants',
      description: "Enregistrez chaque enseignant avec sa matière et son mode de rémunération.",
      targetTab: 'teachers',
    },
    {
      key: 'checklist.pricing',
      label: 'Définir les tarifs',
      description: "Configurez les mensualités par niveau dans l'onglet Élèves > Tarification.",
      targetTab: 'students',
    },
    {
      key: 'checklist.first_student',
      label: 'Inscrire un premier élève',
      description: "Utilisez le bouton « Ajouter un élève » pour créer la première fiche.",
      targetTab: 'students',
    },
    {
      key: 'checklist.first_attendance',
      label: 'Faire le premier appel',
      description: "Ouvrez « Présences & Groupes » et lancez une feuille d'appel pour un groupe.",
      targetTab: 'attendance',
    },
  ],

  pages: [
    {
      tab: 'dashboard',
      title: 'Tableau de bord',
      steps: [
        { title: 'Actions rapides', description: "Les boutons en haut vous permettent d'inscrire un élève, faire l'appel ou encaisser un paiement en un clic." },
        { title: 'Impayés à relancer', description: "Le compteur rouge indique les élèves avec des séances dues non réglées. Cliquez-le pour ouvrir la liste filtrée." },
        { title: 'Statistiques du jour', description: "Les cartes affichent en temps réel les présences, paiements encaissés et élèves inscrits." },
      ],
    },
    {
      tab: 'students',
      title: 'Élèves',
      steps: [
        { title: 'Ajouter un élève', description: "Cliquez « Ajouter un élève » pour saisir le nom, le niveau scolaire et les coordonnées du parent." },
        { title: 'Fiche élève', description: "Cliquez sur une ligne pour ouvrir la fiche complète : historique de paiement, présences et groupe." },
        { title: 'Recherche et filtres', description: "Filtrez par niveau, groupe ou statut de paiement avec la barre de recherche et les menus déroulants." },
        { title: 'Tarification', description: "L'onglet Tarification vous permet de fixer les mensualités par niveau ou formule." },
      ],
    },
    {
      tab: 'attendance',
      title: 'Présences & Groupes',
      steps: [
        { title: 'Lancer un appel', description: "Cliquez « Faire l'appel » pour ouvrir le formulaire. Le groupe et la matière sont préremplis d'après le planning." },
        { title: 'Statuts de présence', description: "Touchez le bouton de chaque élève pour alterner : Présent, Absent, En retard, Excusé." },
        { title: 'Synthèse en direct', description: "Le bandeau de synthèse se met à jour en temps réel pendant la saisie." },
        { title: 'Enregistrer', description: "Le bouton « Enregistrer la feuille d'appel » en bas confirme la saisie. Vous avez 10 secondes pour annuler." },
      ],
    },
    {
      tab: 'payments',
      title: 'Paiements',
      steps: [
        { title: 'Encaisser un paiement', description: "Cliquez « Encaisser » sur un élève. Le montant dû est prérempli ; vous pouvez le modifier avant de valider." },
        { title: 'Rappel WhatsApp', description: "Sur les élèves en attente, le bouton « Envoyer le rappel » ouvre WhatsApp avec un message prêt à envoyer." },
        { title: 'Statuts de paiement', description: "À jour (moins de 4 séances dues), En attente (4 à 7), En retard (plus de 7)." },
        { title: 'Reçus et extraits', description: "Validez un paiement pour imprimer un reçu A5. L'onglet Historique liste toutes les transactions." },
      ],
    },
    {
      tab: 'schedule',
      title: 'Emploi du temps',
      steps: [
        { title: 'Ajouter une séance', description: "Cliquez « Ajouter une séance » pour choisir le jour, l'heure, le groupe et l'enseignant." },
        { title: 'Modifier ou supprimer', description: "Cliquez sur une ligne du tableau pour l'éditer ou la supprimer." },
        { title: 'Vue hebdomadaire', description: "Le planning s'affiche sous forme de grille par jour et par créneau horaire." },
      ],
    },
    {
      tab: 'teachers',
      title: 'Enseignants',
      steps: [
        { title: 'Ajouter un enseignant', description: "Renseignez le nom, la matière principale et le mode de rémunération (fixe par séance ou part du montant élève)." },
        { title: 'Matières', description: "L'onglet Matières vous permet de créer et renommer les matières dispensées au centre." },
        { title: 'Rémunérations', description: "Chaque mois, cochez « Rémunéré » pour confirmer le versement et générer un bulletin." },
      ],
    },
    {
      tab: 'reports',
      title: 'Comptabilité',
      steps: [
        { title: 'Tableau de synthèse', description: "Le bilan mensuel affiche les encaissements, les dépenses et le solde net." },
        { title: 'Dépenses', description: "Enregistrez les charges fixes (loyer, charges, etc.) pour les inclure dans le bilan." },
        { title: 'Clôture mensuelle', description: "Clôturer un mois le verrouille pour l'audit. L'action est réversible par le directeur." },
      ],
    },
    {
      tab: 'daycare',
      title: 'Élèves de garde',
      steps: [
        { title: 'Inscrire un enfant', description: "Ajoutez les enfants accueillis à la garderie avec leur école et leurs horaires." },
        { title: 'Pointage journalier', description: "Cochez les présences chaque matin pour calculer automatiquement la facturation mensuelle." },
      ],
    },
    {
      tab: 'schoolcalendar',
      title: 'Calendrier scolaire',
      steps: [
        { title: 'Calendrier des vacances', description: "Visualisez les périodes de vacances scolaires pour anticiper les absences." },
        { title: 'Import d\'emploi du temps', description: "Téléversez un fichier PDF ou image de l'emploi du temps officiel pour référence." },
      ],
    },
  ],

  tours: [
    {
      tab: 'students',
      steps: [
        { target: '[data-tour="students-add-btn"]', title: 'Ajouter un élève', text: "Cliquez ici pour ouvrir le formulaire d'inscription." },
        { target: '[data-tour="students-search"]', title: 'Rechercher', text: 'Tapez un nom, un niveau ou un groupe pour filtrer la liste instantanément.' },
        { target: '[data-tour="students-table"]', title: 'Liste des élèves', text: "Cliquez sur une ligne pour ouvrir la fiche complète de l'élève." },
        { target: '[data-tour="students-filter"]', title: 'Filtres', text: 'Affinez par statut de paiement ou par groupe.' },
      ],
    },
    {
      tab: 'attendance',
      steps: [
        { target: '[data-tour="attendance-take-btn"]', title: "Faire l'appel", text: 'Ouvre le formulaire avec le groupe et la matière préremplis.' },
        { target: '[data-tour="attendance-table"]', title: "Feuilles d'appel", text: "Retrouvez ici l'historique de toutes les présences enregistrées." },
      ],
    },
    {
      tab: 'payments',
      steps: [
        { target: '[data-tour="payments-collect-btn"]', title: 'Encaisser', text: 'Ouvre le panneau de paiement avec le montant dû prérempli.' },
        { target: '[data-tour="payments-whatsapp"]', title: 'Rappel WhatsApp', text: 'Envoie un message de rappel sans exposer le montant exact.' },
        { target: '[data-tour="payments-status"]', title: 'Statut', text: 'À jour, En attente ou En retard selon le nombre de séances dues.' },
      ],
    },
    {
      tab: 'teachers',
      steps: [
        { target: '[data-tour="teachers-add-btn"]', title: 'Ajouter', text: 'Enregistrez un nouvel enseignant avec sa matière et sa rémunération.' },
        { target: '[data-tour="teachers-table"]', title: 'Liste', text: 'Cliquez sur un enseignant pour modifier sa fiche.' },
      ],
    },
    {
      tab: 'schedule',
      steps: [
        { target: '[data-tour="schedule-add-btn"]', title: 'Ajouter une séance', text: "Choisissez le jour, le créneau, le groupe et l'enseignant." },
        { target: '[data-tour="schedule-table"]', title: 'Planning', text: 'Vue hebdomadaire de toutes les séances planifiées.' },
      ],
    },
  ],
};

// ─────────────────────────────────────────────────────────────
// SECRÉTAIRE (hérite du directeur, filtré par permissions)
// ─────────────────────────────────────────────────────────────
const secretaryContent: GuideContent = {
  role: 'secretary',

  checklist: [
    {
      key: 'checklist.first_student',
      label: 'Inscrire un premier élève',
      description: "Utilisez le bouton « Ajouter un élève » pour créer la première fiche.",
      targetTab: 'students',
    },
    {
      key: 'checklist.first_attendance',
      label: 'Faire le premier appel',
      description: "Ouvrez « Présences & Groupes » et lancez une feuille d'appel.",
      targetTab: 'attendance',
    },
  ],

  pages: directorContent.pages.filter(p => p.tab !== 'reports'),
  tours: directorContent.tours,
};

// ─────────────────────────────────────────────────────────────
// ENSEIGNANT
// ─────────────────────────────────────────────────────────────
const teacherContent: GuideContent = {
  role: 'teacher',

  checklist: [
    {
      key: 'checklist.first_attendance',
      label: 'Consulter mes séances du jour',
      description: "L'onglet « Séances du jour » liste vos cours pour aujourd'hui.",
      targetTab: 'today',
    },
    {
      key: 'checklist.first_attendance',
      label: 'Faire l\'appel',
      description: "Depuis une carte de séance, cliquez « Faire l'appel » pour pointer les présences.",
      targetTab: 'attendance',
    },
  ],

  pages: [
    {
      tab: 'today',
      title: 'Séances du jour',
      steps: [
        { title: 'Mes séances', description: "Cette page affiche tous vos cours planifiés pour aujourd'hui, avec l'heure, la salle et le groupe." },
        { title: 'Faire l\'appel', description: "Le bouton « Faire l'appel » sur chaque carte ouvre directement le formulaire de présence pour ce groupe." },
        { title: 'Actualiser', description: "Cliquez « Actualiser mes séances » pour recharger les données depuis le planning." },
      ],
    },
    {
      tab: 'schedule',
      title: 'Emploi du temps',
      steps: [
        { title: 'Mon planning', description: "Vue complète de votre emploi du temps hebdomadaire." },
        { title: 'Faire l\'appel depuis le planning', description: "Chaque séance affiche un bouton pour lancer l'appel directement." },
      ],
    },
    {
      tab: 'groups',
      title: 'Mes Groupes',
      steps: [
        { title: 'Mes groupes', description: "Liste de tous les groupes qui vous sont assignés avec le nombre d'élèves." },
        { title: 'Voir les élèves', description: "Dépliez un groupe pour voir la liste de ses élèves." },
      ],
    },
    {
      tab: 'attendance',
      title: "Faire l'Appel",
      steps: [
        { title: 'Groupe et matière', description: "Sélectionnez votre groupe et la matière. Ils sont préremplis d'après le planning actuel." },
        { title: 'Statuts de présence', description: "Touchez le bouton de chaque élève pour alterner entre Présent, Absent, En retard et Excusé." },
        { title: 'Enregistrer', description: "Confirmez avec « Enregistrer la feuille d'appel ». Vous avez 10 secondes pour annuler." },
      ],
    },
  ],

  tours: [
    {
      tab: 'today',
      steps: [
        { target: '[data-tour="teacher-today-cards"]', title: 'Séances du jour', text: "Chaque carte représente un cours planifié pour aujourd'hui." },
        { target: '[data-tour="teacher-attendance-btn"]', title: "Faire l'appel", text: 'Ce bouton ouvre le formulaire de présence pour ce groupe.' },
      ],
    },
    {
      tab: 'attendance',
      steps: [
        { target: '[data-tour="teacher-attendance-group"]', title: 'Groupe sélectionné', text: 'Le groupe est prérempli depuis votre planning. Vous pouvez le modifier.' },
        { target: '[data-tour="teacher-attendance-list"]', title: 'Liste des élèves', text: 'Tous les élèves sont « Présent » par défaut. Touchez pour changer le statut.' },
      ],
    },
    {
      tab: 'groups',
      steps: [
        { target: '[data-tour="teacher-groups-list"]', title: 'Mes groupes', text: 'Vos groupes assignés. Dépliez pour voir les élèves.' },
      ],
    },
  ],
};

// ─────────────────────────────────────────────────────────────
// Registre des contenus par rôle
// ─────────────────────────────────────────────────────────────
const GUIDE_CONTENT: Record<UserRole, GuideContent> = {
  director: directorContent,
  secretary: secretaryContent,
  teacher: teacherContent,
};

/**
 * Retourne le contenu du guide filtré selon le rôle et les permissions.
 * Les onglets non accessibles à l'utilisateur sont exclus.
 */
export function getGuideContent(
  role: UserRole,
  permissions?: string[]
): GuideContent {
  const base = GUIDE_CONTENT[role];
  if (!base) return GUIDE_CONTENT.teacher;

  // Pour le directeur : tout est accessible
  if (role === 'director') return base;

  // Pour la secrétaire : filtre selon permissions
  if (role === 'secretary' && permissions && permissions.length > 0) {
    const perms = new Set(permissions);
    // Mapping onglet → clé de permission
    const tabToPermission: Partial<Record<TabKey, string>> = {
      students: 'students',
      attendance: 'attendance',
      payments: 'payments',
      schedule: 'schedule',
      teachers: 'teachers',
      reports: 'reports',
      daycare: 'daycare',
      schoolcalendar: 'schoolcalendar',
    };
    return {
      ...base,
      pages: base.pages.filter(p => {
        const perm = tabToPermission[p.tab];
        return !perm || perms.has(perm);
      }),
      tours: base.tours.filter(t => {
        const perm = tabToPermission[t.tab];
        return !perm || perms.has(perm);
      }),
    };
  }

  return base;
}

export { GUIDE_CONTENT };

