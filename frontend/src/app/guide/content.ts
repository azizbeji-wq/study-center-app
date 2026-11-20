// src/app/guide/content.ts
// Contenu du guide par rôle et par onglet
// Aucune donnée personnelle, aucun mot de passe

import type {
  GuideContent,
  UserRole,
  TabKey,
  GuideAssistantTabConfig,
  GuideWorkflow,
} from './types';

const DIRECTOR_ASSISTANT_BY_TAB: GuideAssistantTabConfig[] = [
  {
    tab: 'dashboard',
    contextLabel: 'Tableau de bord',
    defaultRoleMode: 'general',
    suggestions: [
      {
        label: 'Résumer la situation du centre',
        prompt: 'Résume la situation actuelle du centre (effectifs, impayés et points de vigilance).',
        roleMode: 'general',
      },
      {
        label: 'Routine hebdomadaire de suivi',
        prompt: 'Propose une routine hebdomadaire simple pour suivre les présences et les impayés depuis le tableau de bord.',
        roleMode: 'management',
      },
    ],
  },
  {
    tab: 'students',
    contextLabel: 'Élèves',
    defaultRoleMode: 'general',
    suggestions: [
      {
        label: 'Calcul remise % vs remise fixe',
        prompt: 'Explique la différence entre une remise en pourcentage et une remise fixe sur la fiche élève avec un exemple en DT.',
        roleMode: 'general',
      },
      {
        label: 'Organiser les groupes par niveau',
        prompt: 'Quelles bonnes pratiques appliquer pour répartir les élèves en groupes équilibrés par niveau ?',
        roleMode: 'management',
      },
    ],
  },
  {
    tab: 'attendance',
    contextLabel: 'Présences & Groupes',
    defaultRoleMode: 'general',
    suggestions: [
      {
        label: 'Impact des présences sur les impayés',
        prompt: 'Comment les présences et retards enregistrés influencent-ils le calcul des séances dues dans les paiements ?',
        roleMode: 'general',
      },
      {
        label: 'Informer un parent après une absence',
        prompt: 'Rédige un message WhatsApp bienveillant pour informer un parent de l’absence de son enfant à la séance d’aujourd’hui.',
        roleMode: 'communication',
      },
    ],
  },
  {
    tab: 'payments',
    contextLabel: 'Paiements',
    defaultRoleMode: 'general',
    suggestions: [
      {
        label: 'Règle des 4 et 7 séances dues',
        prompt: 'Comment fonctionne la règle des seuils de paiement (À jour, En attente dès 4 séances, En retard après 7 séances) ?',
        roleMode: 'general',
      },
      {
        label: 'Rédiger un rappel WhatsApp',
        prompt: 'Rédige un message WhatsApp courtois et discret pour rappeler un règlement de séances en attente sans brusquer les parents.',
        roleMode: 'communication',
      },
      {
        label: 'Analyser les tendances mensuelles',
        prompt: 'Comment utiliser le graphique des tendances mensuelles des paiements élèves pour anticiper la trésorerie ?',
        roleMode: 'management',
      },
    ],
  },
  {
    tab: 'schedule',
    contextLabel: 'Emploi du temps',
    defaultRoleMode: 'management',
    suggestions: [
      {
        label: 'Éviter les conflits de salles',
        prompt: 'Comment organiser les créneaux hebdomadaires et les salles pour éviter tout chevauchement entre groupes ?',
        roleMode: 'management',
      },
      {
        label: 'Annoncer un changement d’horaire',
        prompt: 'Rédige un message WhatsApp clair pour prévenir les parents d’un report de séance au samedi matin.',
        roleMode: 'communication',
      },
    ],
  },
  {
    tab: 'teachers',
    contextLabel: 'Enseignants',
    defaultRoleMode: 'general',
    suggestions: [
      {
        label: 'Différence Formule A, B et C',
        prompt: 'Explique concrètement les 3 formules de rémunération des enseignants (Pourcentage, Fixe par élève, Part fixe centre) avec un exemple chiffré en DT.',
        roleMode: 'general',
      },
      {
        label: 'Valider les versements mensuels',
        prompt: 'Quelle est la procédure recommandée pour vérifier et valider la rémunération mensuelle de chaque enseignant ?',
        roleMode: 'management',
      },
    ],
  },
  {
    tab: 'reports',
    contextLabel: 'Comptabilité & Rapports',
    defaultRoleMode: 'management',
    suggestions: [
      {
        label: 'Calcul du bénéfice net réel',
        prompt: 'Comment est calculé le bénéfice net dans la synthèse comptable (recettes, parts enseignants et dépenses) ?',
        roleMode: 'general',
      },
      {
        label: 'Clôturer ou réouvrir un mois',
        prompt: 'À quoi sert la clôture mensuelle dans Comptabilité et comment réouvrir un mois en cas d’ajustement ?',
        roleMode: 'management',
      },
    ],
  },
  {
    tab: 'daycare',
    contextLabel: 'Élèves de garde',
    defaultRoleMode: 'general',
    suggestions: [
      {
        label: 'Facturation mensuelle garderie',
        prompt: 'Comment sont calculés les mois dus et les retards de paiement pour les enfants inscrits à la garderie ?',
        roleMode: 'general',
      },
      {
        label: 'Message de suivi aux parents',
        prompt: 'Rédige un court message rassurant pour les parents de la garderie concernant les horaires de sortie.',
        roleMode: 'communication',
      },
    ],
  },
  {
    tab: 'schoolcalendar',
    contextLabel: 'Calendrier scolaire',
    defaultRoleMode: 'management',
    suggestions: [
      {
        label: 'Anticiper les vacances scolaires',
        prompt: 'Comment adapter les groupes et l’emploi du temps du centre pendant les périodes de vacances scolaires ?',
        roleMode: 'management',
      },
      {
        label: 'Informer sur le planning des vacances',
        prompt: 'Rédige une annonce WhatsApp pour informer les parents des horaires spéciaux de révision pendant les vacances.',
        roleMode: 'communication',
      },
    ],
  },
];

const DIRECTOR_WORKFLOWS: GuideWorkflow[] = [
  {
    id: 'back_to_school',
    title: 'Préparer la rentrée en 5 minutes',
    duration: '5 min',
    summary: 'Mettre en place la structure du centre dans le bon ordre avant d’encaisser.',
    steps: [
      {
        title: '1. Créer les matières et les enseignants',
        description: 'Ajoutez vos matières et enregistrez chaque enseignant avec sa formule de rémunération.',
        targetTab: 'teachers',
        actionLabel: 'Ouvrir Enseignants',
      },
      {
        title: '2. Configurer les tarifs et inscrire les élèves',
        description: 'Fixez les tarifs par matière/niveau puis inscrivez vos premiers élèves dans leurs groupes.',
        targetTab: 'students',
        actionLabel: 'Ouvrir Élèves',
      },
      {
        title: '3. Planifier les créneaux de la semaine',
        description: 'Saisissez l’emploi du temps pour préremplir automatiquement les feuilles d’appel.',
        targetTab: 'schedule',
        actionLabel: 'Ouvrir Emploi du temps',
      },
      {
        title: '4. Lancer la première feuille d’appel',
        description: 'Validez les présences du jour pour déclencher le suivi automatique des séances dues.',
        targetTab: 'attendance',
        actionLabel: 'Ouvrir Présences',
      },
    ],
  },
  {
    id: 'overdue_followup',
    title: 'Gérer un retard de paiement',
    duration: '3 min',
    summary: 'Repérer les impayés, relancer poliment sur WhatsApp et éditer le reçu A5.',
    steps: [
      {
        title: '1. Repérer les soldes en attente ou en retard',
        description: 'Consultez la carte Impayés du tableau de bord ou ouvrez l’onglet Soldes à régler.',
        targetTab: 'payments',
        actionLabel: 'Voir les impayés',
      },
      {
        title: '2. Envoyer une relance WhatsApp',
        description: 'Cliquez sur le bouton WhatsApp de la ligne concernée pour envoyer un rappel prêt à l’emploi.',
        targetTab: 'payments',
        actionLabel: 'Ouvrir Paiements',
      },
      {
        title: '3. Encaisser et imprimer le reçu A5',
        description: 'Cliquez sur « Encaisser », vérifiez le montant prérempli puis imprimez immédiatement le reçu.',
        targetTab: 'payments',
        actionLabel: 'Encaisser un paiement',
      },
    ],
  },
  {
    id: 'monthly_closing',
    title: 'Clôturer le mois sereinement',
    duration: '5 min',
    summary: 'Vérifier la trésorerie, les parts enseignants et figer le bilan mensuel.',
    steps: [
      {
        title: '1. Vérifier les encaissements et tendances élèves',
        description: 'Contrôlez les derniers paiements encaissés et les relances restantes.',
        targetTab: 'payments',
        actionLabel: 'Vérifier Paiements',
      },
      {
        title: '2. Saisir les dépenses fixes et variables',
        description: 'Enregistrez loyer, factures STEG et fournitures dans Comptabilité > Dépenses.',
        targetTab: 'reports',
        actionLabel: 'Ouvrir Comptabilité',
      },
      {
        title: '3. Valider les honoraires et clôturer le mois',
        description: 'Vérifiez la part de chaque enseignant puis figez la clôture mensuelle et exportez le bilan.',
        targetTab: 'reports',
        actionLabel: 'Aller à la clôture',
      },
    ],
  },
];

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
      description: 'Enregistrez chaque enseignant avec sa matière et son mode de rémunération.',
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
      description: 'Utilisez le bouton « Ajouter un élève » pour créer la première fiche.',
      targetTab: 'students',
    },
    {
      key: 'checklist.first_attendance',
      label: 'Faire le premier appel',
      description: "Ouvrez « Présences & Groupes » et lancez une feuille d'appel pour un groupe.",
      targetTab: 'attendance',
    },
    {
      key: 'checklist.first_payment',
      label: 'Encaisser un premier paiement',
      description: 'Validez un règlement élève ou garderie depuis l’onglet Paiements.',
      targetTab: 'payments',
    },
    {
      key: 'checklist.first_expense',
      label: 'Enregistrer une première dépense',
      description: 'Ajoutez une charge (loyer, STEG, fournitures) dans Comptabilité pour suivre votre bénéfice net.',
      targetTab: 'reports',
    },
  ],

  pages: [
    {
      tab: 'dashboard',
      title: 'Tableau de bord',
      steps: [
        { title: 'Actions rapides', description: "Les boutons en haut vous permettent d'inscrire un élève, faire l'appel ou encaisser un paiement en un clic." },
        { title: 'Impayés à relancer', description: 'Le compteur ambre indique les élèves avec des séances dues non réglées. Cliquez-le pour ouvrir la liste filtrée.' },
        { title: 'Statistiques & activité en direct', description: 'Les cartes affichent en temps réel les effectifs, revenus perçus et dernières opérations du centre.' },
      ],
    },
    {
      tab: 'students',
      title: 'Élèves',
      steps: [
        { title: 'Ajouter un élève', description: 'Cliquez « Ajouter un élève » pour saisir le nom, le niveau scolaire et les coordonnées du parent.' },
        { title: 'Fiche élève', description: 'Cliquez sur une ligne pour ouvrir la fiche complète : historique de paiement, présences et groupe.' },
        { title: 'Recherche et filtres', description: 'Filtrez par niveau, groupe ou statut de paiement avec la barre de recherche et les menus déroulants.' },
        { title: 'Tarification', description: "L'onglet Tarification vous permet de fixer les mensualités par niveau ou formule." },
      ],
    },
    {
      tab: 'attendance',
      title: 'Présences & Groupes',
      steps: [
        { title: 'Lancer un appel', description: "Cliquez « Faire l'appel » pour ouvrir le formulaire. Le groupe et la matière sont préremplis d'après le planning." },
        { title: 'Statuts de présence', description: 'Touchez le bouton de chaque élève pour alterner : Présent, Absent, En retard, Excusé.' },
        { title: 'Synthèse en direct', description: 'Le bandeau de synthèse se met à jour en temps réel pendant la saisie.' },
        { title: 'Enregistrer', description: "Le bouton « Enregistrer la feuille d'appel » en bas confirme la saisie. Vous avez 10 secondes pour annuler." },
      ],
    },
    {
      tab: 'payments',
      title: 'Paiements',
      steps: [
        { title: 'Tendances mensuelles (Graphique)', description: 'Visualisez en un coup d’œil l’évolution mensuelle des encaissements élèves sur 6 ou 12 mois avec la moyenne mensuelle.' },
        { title: 'Encaisser un paiement', description: 'Cliquez « Encaisser » sur un élève. Le montant dû est prérempli ; vous pouvez le modifier avant de valider.' },
        { title: 'Rappel WhatsApp', description: 'Sur les élèves en attente, le bouton « Envoyer le rappel » ouvre WhatsApp avec un message prêt à envoyer.' },
        { title: 'Statuts de paiement', description: 'À jour (moins de 4 séances dues), En attente (4 à 7), En retard (plus de 7).' },
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
        { title: 'Ajouter un enseignant', description: 'Renseignez le nom, la matière principale et le mode de rémunération (fixe par séance ou part du montant élève).' },
        { title: 'Matières', description: "L'onglet Matières vous permet de créer et renommer les matières dispensées au centre." },
        { title: 'Rémunérations', description: 'Chaque mois, cochez « Rémunéré » pour confirmer le versement et générer un bulletin.' },
      ],
    },
    {
      tab: 'reports',
      title: 'Comptabilité',
      steps: [
        { title: 'Période & graphique revenus élèves', description: 'Filtrez sur 12 mois, l’année scolaire ou un mois précis pour comparer les recettes mensuelles des paiements élèves.' },
        { title: 'Tableau de synthèse', description: 'Le bilan mensuel affiche les encaissements, les dépenses, les parts enseignants et le bénéfice net.' },
        { title: 'Dépenses', description: 'Enregistrez les charges fixes (loyer, STEG, fournitures) pour les inclure dans le bilan.' },
        { title: 'Clôture mensuelle', description: "Clôturer un mois le verrouille pour l'audit. L'action est réversible par le directeur." },
      ],
    },
    {
      tab: 'daycare',
      title: 'Élèves de garde',
      steps: [
        { title: 'Inscrire un enfant', description: 'Ajoutez les enfants accueillis à la garderie avec leur tarif mensuel et leurs horaires.' },
        { title: 'Pointage journalier', description: 'Cliquez sur le badge Présent / Absent de chaque carte pour mettre à jour la présence du jour.' },
      ],
    },
    {
      tab: 'schoolcalendar',
      title: 'Calendrier scolaire',
      steps: [
        { title: 'Écoles partenaires', description: 'Enregistrez les écoles primaires partenaires et leurs classes pour organiser vos groupes de garde.' },
        { title: "Import d'emploi du temps", description: "Téléversez un fichier PDF ou image de l'emploi du temps officiel pour référence rapide." },
      ],
    },
  ],

  tours: [
    {
      tab: 'dashboard',
      steps: [
        { target: '[data-tour="dashboard-quick-actions"]', title: 'Actions rapides', text: 'Inscrivez un élève, lancez un appel ou encaissez un paiement directement depuis cet espace.' },
        { target: '[data-tour="dashboard-unpaid-card"]', title: 'Impayés à relancer', text: 'Cliquez sur cette carte pour ouvrir immédiatement la liste filtrée des paiements en attente ou en retard.' },
        { target: '[data-tour="dashboard-activity"]', title: 'Activité récente', text: 'Suivez en temps réel les derniers paiements, présences et inscriptions effectués par votre équipe.' },
      ],
    },
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
        { target: '[data-tour="payments-revenue-chart"]', title: 'Tendances mensuelles', text: 'Visualisez les revenus mensuels des paiements élèves sur 6 ou 12 mois avec la moyenne mensuelle.' },
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
    {
      tab: 'reports',
      steps: [
        { target: '[data-tour="reports-period-bar"]', title: 'Période & exports', text: 'Choisissez la période d’analyse (12 mois, année scolaire ou mois précis), ajoutez une dépense ou exportez en CSV/PDF.' },
        { target: '[data-tour="reports-tabs"]', title: 'Modules comptables', text: 'Naviguez entre la vue d’ensemble, les dépenses, les honoraires enseignants, le journal de caisse et la clôture.' },
        { target: '[data-tour="reports-revenue-chart"]', title: 'Revenus paiements élèves', text: 'Survolez chaque barre pour consulter le détail mensuel des encaissements élèves.' },
      ],
    },
    {
      tab: 'daycare',
      steps: [
        { target: '[data-tour="daycare-add-btn"]', title: 'Inscrire un enfant', text: 'Ajoutez un nouvel enfant à la garderie avec ses horaires et sa mensualité.' },
        { target: '[data-tour="daycare-filters"]', title: 'Recherche & filtres', text: 'Filtrez rapidement les enfants présents ou absents aujourd’hui.' },
        { target: '[data-tour="daycare-grid"]', title: 'Pointage en un clic', text: 'Cliquez sur le badge Présent / Absent d’une carte pour pointer l’enfant.' },
      ],
    },
    {
      tab: 'schoolcalendar',
      steps: [
        { target: '[data-tour="schoolcalendar-add-school"]', title: 'Écoles partenaires', text: 'Ajoutez les écoles primaires fréquentées par vos élèves de garde.' },
        { target: '[data-tour="schoolcalendar-import-btn"]', title: 'Importer un emploi du temps', text: 'Téléversez une photo ou un PDF de l’emploi du temps d’une classe.' },
        { target: '[data-tour="schoolcalendar-tabs"]', title: 'Imports & Écoles', text: 'Basculez entre la bibliothèque des emplois du temps importés et la liste des écoles.' },
      ],
    },
  ],

  workflows: DIRECTOR_WORKFLOWS,
  assistantByTab: DIRECTOR_ASSISTANT_BY_TAB,
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
      description: 'Utilisez le bouton « Ajouter un élève » pour créer la première fiche.',
      targetTab: 'students',
    },
    {
      key: 'checklist.first_attendance',
      label: 'Faire le premier appel',
      description: "Ouvrez « Présences & Groupes » et lancez une feuille d'appel.",
      targetTab: 'attendance',
    },
    {
      key: 'checklist.first_payment',
      label: 'Encaisser un premier paiement',
      description: 'Enregistrez un règlement et éditez le reçu A5 depuis Paiements.',
      targetTab: 'payments',
    },
  ],

  pages: directorContent.pages.filter(p => p.tab !== 'reports'),
  tours: directorContent.tours.filter(t => t.tab !== 'reports'),
  workflows: [
    {
      id: 'secretary_new_student',
      title: 'Accueillir un nouvel élève',
      duration: '3 min',
      summary: 'Créer la fiche, vérifier le créneau du groupe et pointer la première présence.',
      steps: [
        {
          title: '1. Créer la fiche élève',
          description: 'Saisissez les coordonnées du parent, le niveau et les matières suivies.',
          targetTab: 'students',
          actionLabel: 'Ouvrir Élèves',
        },
        {
          title: '2. Vérifier les horaires du groupe',
          description: 'Confirmez avec le parent les jours et salles de cours dans le planning.',
          targetTab: 'schedule',
          actionLabel: 'Voir le planning',
        },
        {
          title: '3. Pointer sa première présence',
          description: 'Enregistrez sa présence lors de la première séance dans Présences & Groupes.',
          targetTab: 'attendance',
          actionLabel: 'Ouvrir Présences',
        },
      ],
    },
    DIRECTOR_WORKFLOWS[1],
  ],
  assistantByTab: DIRECTOR_ASSISTANT_BY_TAB.filter(a => a.tab !== 'reports'),
};

// ─────────────────────────────────────────────────────────────
// ENSEIGNANT
// ─────────────────────────────────────────────────────────────
const teacherContent: GuideContent = {
  role: 'teacher',

  checklist: [
    {
      key: 'checklist.today_sessions',
      label: 'Consulter mes séances du jour',
      description: "L'onglet « Séances du jour » liste vos cours pour aujourd'hui.",
      targetTab: 'today',
    },
    {
      key: 'checklist.first_attendance',
      label: "Faire l'appel d'un groupe",
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
        { title: "Faire l'appel", description: "Le bouton « Faire l'appel » sur chaque carte ouvre directement le formulaire de présence pour ce groupe." },
        { title: 'Actualiser', description: 'Cliquez « Actualiser mes séances » pour recharger les données depuis le planning.' },
      ],
    },
    {
      tab: 'schedule',
      title: 'Emploi du temps',
      steps: [
        { title: 'Mon planning', description: 'Vue complète de votre emploi du temps hebdomadaire.' },
        { title: "Faire l'appel depuis le planning", description: "Chaque séance affiche un bouton pour lancer l'appel directement." },
      ],
    },
    {
      tab: 'groups',
      title: 'Mes Groupes',
      steps: [
        { title: 'Mes groupes', description: "Liste de tous les groupes qui vous sont assignés avec le nombre d'élèves." },
        { title: 'Voir les élèves', description: 'Dépliez un groupe pour voir la liste de ses élèves.' },
      ],
    },
    {
      tab: 'attendance',
      title: "Faire l'Appel",
      steps: [
        { title: 'Groupe et matière', description: "Sélectionnez votre groupe et la matière. Ils sont préremplis d'après le planning actuel." },
        { title: 'Statuts de présence', description: 'Touchez le bouton de chaque élève pour alterner entre Présent, Absent, En retard et Excusé.' },
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
      tab: 'schedule',
      steps: [
        { target: '[data-tour="teacher-schedule-add-btn"]', title: 'Planifier un cours', text: 'Ajoutez un nouveau créneau ou une séance de rattrapage directement dans votre emploi du temps.' },
        { target: '[data-tour="teacher-schedule-print-btn"]', title: 'Imprimer votre planning', text: 'Exportez ou imprimez votre semaine complète en un clic.' },
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

  workflows: [
    {
      id: 'teacher_session_routine',
      title: 'Routine d’une séance de cours',
      duration: '2 min',
      summary: 'Vérifier vos cours du jour, pointer les présences et consulter les élèves du groupe.',
      steps: [
        {
          title: '1. Consulter les séances du jour',
          description: 'Repérez votre prochain cours, la salle et l’horaire depuis l’accueil enseignant.',
          targetTab: 'today',
          actionLabel: 'Voir Séances du jour',
        },
        {
          title: '2. Faire l’appel rapidement',
          description: 'Touchez les statuts des absents ou retardataires puis validez la feuille d’appel.',
          targetTab: 'attendance',
          actionLabel: "Ouvrir l'Appel",
        },
        {
          title: '3. Consulter la composition du groupe',
          description: 'Retrouvez tous vos élèves et gérez vos groupes depuis Mes Groupes.',
          targetTab: 'groups',
          actionLabel: 'Voir Mes Groupes',
        },
      ],
    },
  ],

  assistantByTab: [
    {
      tab: 'today',
      contextLabel: 'Séances du jour',
      defaultRoleMode: 'pedagogic',
      suggestions: [
        {
          label: 'Préparer un plan de séance (1h30)',
          prompt: 'Prépare un plan de séance structuré de 1h30 avec exercices progressifs adapté au programme tunisien.',
          roleMode: 'pedagogic',
        },
        {
          label: 'Comment faire l’appel rapidement ?',
          prompt: 'Comment enregistrer rapidement les présences de mes séances du jour dans le portail enseignant ?',
          roleMode: 'general',
        },
      ],
    },
    {
      tab: 'schedule',
      contextLabel: 'Mon emploi du temps',
      defaultRoleMode: 'pedagogic',
      suggestions: [
        {
          label: 'Annoncer une séance de rattrapage',
          prompt: 'Rédige un court message WhatsApp pour informer les parents d’une séance de rattrapage cette semaine.',
          roleMode: 'communication',
        },
        {
          label: 'Organiser les révisions d’examen',
          prompt: 'Propose un calendrier de révision sur 4 séances avant les devoirs de synthèse.',
          roleMode: 'pedagogic',
        },
      ],
    },
    {
      tab: 'groups',
      contextLabel: 'Mes Groupes',
      defaultRoleMode: 'pedagogic',
      suggestions: [
        {
          label: 'Gérer un groupe hétérogène',
          prompt: 'Quelles stratégies pédagogiques utiliser pour faire progresser un groupe de niveaux hétérogènes ?',
          roleMode: 'pedagogic',
        },
        {
          label: 'Créer une série d’exercices',
          prompt: 'Crée 5 exercices progressifs avec corrigé rapide pour travailler en groupe en classe.',
          roleMode: 'pedagogic',
        },
      ],
    },
    {
      tab: 'attendance',
      contextLabel: "Faire l'Appel",
      defaultRoleMode: 'general',
      suggestions: [
        {
          label: 'Différence Absent vs Excusé',
          prompt: 'Quelle différence entre marquer un élève Absent, En retard ou Excusé sur la feuille d’appel ?',
          roleMode: 'general',
        },
        {
          label: 'Message parent après absences répétées',
          prompt: 'Rédige un message bienveillant pour alerter un parent suite à deux absences consécutives.',
          roleMode: 'communication',
        },
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
    const isTabAllowed = (tab?: TabKey) => {
      if (!tab) return true;
      const perm = tabToPermission[tab];
      return !perm || perms.has(perm);
    };

    return {
      ...base,
      checklist: base.checklist.filter(item => isTabAllowed(item.targetTab)),
      pages: base.pages.filter(p => isTabAllowed(p.tab)),
      tours: base.tours.filter(t => isTabAllowed(t.tab)),
      workflows: base.workflows
        .map(w => ({
          ...w,
          steps: w.steps.filter(s => isTabAllowed(s.targetTab)),
        }))
        .filter(w => w.steps.length > 0),
      assistantByTab: base.assistantByTab.filter(a => isTabAllowed(a.tab)),
    };
  }

  return base;
}

export { GUIDE_CONTENT };
