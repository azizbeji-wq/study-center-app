export interface TunisianLevel {
  id: string;
  name: string;
  cycle: 'college' | 'lycee';
  subjects: string[];
  groups: string[];
}

export const TUNISIAN_LEVELS: TunisianLevel[] = [
  // --- COLLÈGE (Enseignement de Base) ---
  {
    id: '7_base',
    name: '7ème Année de Base',
    cycle: 'college',
    subjects: [
      'Mathématiques',
      'Sciences de la Vie et de la Terre (SVT)',
      'Physique',
      'Arabe',
      'Français',
      'Anglais',
      'Informatique',
      'Histoire-Géographie',
      'Éducation Civique',
      'Éducation Islamique',
      'Technologie'
    ],
    groups: ['B1', 'B2', 'B3', 'Groupe A', 'Groupe B']
  },
  {
    id: '8_base',
    name: '8ème Année de Base',
    cycle: 'college',
    subjects: [
      'Mathématiques',
      'Sciences de la Vie et de la Terre (SVT)',
      'Physique',
      'Arabe',
      'Français',
      'Anglais',
      'Informatique',
      'Histoire-Géographie',
      'Éducation Civique',
      'Éducation Islamique',
      'Technologie'
    ],
    groups: ['B1', 'B2', 'B3', 'Groupe A', 'Groupe B']
  },
  {
    id: '9_base',
    name: '9ème Année de Base (Brevet/Concours)',
    cycle: 'college',
    subjects: [
      'Mathématiques',
      'Sciences Physiques',
      'Sciences de la Vie et de la Terre (SVT)',
      'Arabe',
      'Français',
      'Anglais',
      'Informatique',
      'Histoire-Géographie',
      'Technologie'
    ],
    groups: ['9B1', '9B2', '9B3', 'Pilote', 'Groupe A']
  },

  // --- LYCÉE (Secondaire) ---
  {
    id: '1_sec',
    name: '1ère Année Secondaire (Tronc Commun)',
    cycle: 'lycee',
    subjects: [
      'Mathématiques',
      'Sciences Physiques',
      'Sciences de la Vie et de la Terre (SVT)',
      'Technologie',
      'Informatique',
      'Arabe',
      'Français',
      'Anglais',
      'Histoire-Géographie'
    ],
    groups: ['S1', 'S2', 'S3', 'TC1', 'TC2']
  },

  // 2ème Année
  {
    id: '2_sciences',
    name: '2ème Année Sciences',
    cycle: 'lycee',
    subjects: [
      'Mathématiques',
      'Sciences Physiques',
      'Sciences de la Vie et de la Terre (SVT)',
      'Technologie',
      'Informatique',
      'Français',
      'Anglais',
      'Arabe'
    ],
    groups: ['Sc1', 'Sc2', 'Sc3', 'Groupe 1', 'Groupe 2']
  },
  {
    id: '2_eco',
    name: '2ème Année Économie et Services',
    cycle: 'lycee',
    subjects: [
      'Économie',
      'Gestion',
      'Mathématiques',
      'Histoire-Géographie',
      'Informatique',
      'Français',
      'Anglais',
      'Arabe'
    ],
    groups: ['Eco1', 'Eco2', 'Groupe 1']
  },
  {
    id: '2_info',
    name: '2ème Année Technologies de l\'Informatique',
    cycle: 'lycee',
    subjects: [
      'Algorithmique & Programmation',
      'TIC & Réseaux',
      'Mathématiques',
      'Sciences Physiques',
      'Français',
      'Anglais',
      'Arabe'
    ],
    groups: ['Info1', 'Info2']
  },
  {
    id: '2_lettres',
    name: '2ème Année Lettres',
    cycle: 'lycee',
    subjects: [
      'Arabe',
      'Français',
      'Anglais',
      'Histoire-Géographie',
      'Philosophie / Pensée Islamique',
      'Mathématiques'
    ],
    groups: ['L1', 'L2']
  },

  // 3ème Année
  {
    id: '3_math',
    name: '3ème Année Mathématiques',
    cycle: 'lycee',
    subjects: [
      'Mathématiques',
      'Sciences Physiques',
      'Sciences de la Vie et de la Terre (SVT)',
      'Informatique',
      'Français',
      'Anglais',
      'Philosophie'
    ],
    groups: ['M1', 'M2', 'M3']
  },
  {
    id: '3_sc_exp',
    name: '3ème Année Sciences Expérimentales',
    cycle: 'lycee',
    subjects: [
      'Sciences de la Vie et de la Terre (SVT)',
      'Sciences Physiques',
      'Mathématiques',
      'Informatique',
      'Français',
      'Anglais',
      'Philosophie'
    ],
    groups: ['Sc1', 'Sc2', 'Sc3']
  },
  {
    id: '3_tech',
    name: '3ème Année Sciences Techniques',
    cycle: 'lycee',
    subjects: [
      'Génie Mécanique',
      'Génie Électrique',
      'Sciences Physiques',
      'Mathématiques',
      'Informatique',
      'Français',
      'Anglais'
    ],
    groups: ['Tech1', 'Tech2']
  },
  {
    id: '3_eco',
    name: '3ème Année Économie & Gestion',
    cycle: 'lycee',
    subjects: [
      'Économie',
      'Gestion',
      'Mathématiques',
      'Histoire-Géographie',
      'Informatique',
      'Français',
      'Anglais'
    ],
    groups: ['Eco1', 'Eco2']
  },
  {
    id: '3_info',
    name: '3ème Année Sciences de l\'Informatique',
    cycle: 'lycee',
    subjects: [
      'Algorithmique & Programmation',
      'Bases de Données',
      'Mathématiques',
      'Sciences Physiques',
      'Français',
      'Anglais'
    ],
    groups: ['Info1', 'Info2']
  },
  {
    id: '3_lettres',
    name: '3ème Année Lettres',
    cycle: 'lycee',
    subjects: [
      'Arabe',
      'Français',
      'Anglais',
      'Histoire-Géographie',
      'Philosophie',
      'Langue 3 (Espagnol/Allemand/Italien)'
    ],
    groups: ['L1', 'L2']
  },

  // 4ème Année (BAC)
  {
    id: '4_bac_math',
    name: '4ème Année Bac Mathématiques',
    cycle: 'lycee',
    subjects: [
      'Mathématiques',
      'Sciences Physiques',
      'Sciences de la Vie et de la Terre (SVT)',
      'Informatique',
      'Philosophie',
      'Français',
      'Anglais',
      'Arabe'
    ],
    groups: ['Bac M1', 'Bac M2', 'Bac M3', 'M1', 'M2']
  },
  {
    id: '4_bac_sc_exp',
    name: '4ème Année Bac Sciences Expérimentales',
    cycle: 'lycee',
    subjects: [
      'Sciences de la Vie et de la Terre (SVT)',
      'Sciences Physiques',
      'Mathématiques',
      'Informatique',
      'Philosophie',
      'Français',
      'Anglais'
    ],
    groups: ['Bac Sc1', 'Bac Sc2', 'Bac Sc3', 'Sc1', 'Sc2']
  },
  {
    id: '4_bac_tech',
    name: '4ème Année Bac Sciences Techniques',
    cycle: 'lycee',
    subjects: [
      'Génie Mécanique',
      'Génie Électrique',
      'Sciences Physiques',
      'Mathématiques',
      'Philosophie',
      'Français',
      'Anglais'
    ],
    groups: ['Bac Tech1', 'Bac Tech2', 'Tech1', 'Tech2']
  },
  {
    id: '4_bac_eco',
    name: '4ème Année Bac Économie & Gestion',
    cycle: 'lycee',
    subjects: [
      'Économie',
      'Gestion',
      'Mathématiques',
      'Histoire-Géographie',
      'Philosophie',
      'Français',
      'Anglais',
      'Informatique'
    ],
    groups: ['Bac Eco1', 'Bac Eco2', 'Eco1', 'Eco2']
  },
  {
    id: '4_bac_info',
    name: '4ème Année Bac Sciences de l\'Informatique',
    cycle: 'lycee',
    subjects: [
      'Algorithmique & Programmation',
      'Bases de Données & STI',
      'Mathématiques',
      'Sciences Physiques',
      'Philosophie',
      'Français',
      'Anglais'
    ],
    groups: ['Bac Info1', 'Bac Info2', 'Info1', 'Info2']
  },
  {
    id: '4_bac_lettres',
    name: '4ème Année Bac Lettres',
    cycle: 'lycee',
    subjects: [
      'Arabe',
      'Philosophie',
      'Histoire-Géographie',
      'Français',
      'Anglais',
      'Langue 3'
    ],
    groups: ['Bac L1', 'Bac L2', 'L1', 'L2']
  }
];

export const COMMON_TUNISIAN_GROUPS = [
  'M1', 'M2', 'M3',
  'Sc1', 'Sc2', 'Sc3',
  'Eco1', 'Eco2',
  'Info1', 'Info2',
  'Tech1', 'Tech2',
  'L1', 'L2',
  '9B1', '9B2', 'Pilote',
  'TC1', 'TC2'
];

export const ALL_TUNISIAN_SUBJECTS: string[] = [
  'Mathématiques',
  'Sciences Physiques',
  'Sciences de la Vie et de la Terre (SVT)',
  'Algorithmique & Programmation',
  'Bases de Données & STI',
  'TIC & Réseaux',
  'Génie Mécanique',
  'Génie Électrique',
  'Technologie',
  'Économie',
  'Gestion',
  'Philosophie',
  'Français',
  'Anglais',
  'Arabe',
  'Histoire-Géographie',
  'Éducation Civique',
  'Éducation Islamique',
  'Italien',
  'Espagnol',
  'Allemand'
];

export const SAMPLE_TEACHERS_TUNISIA = [
  {
    name: 'Prof. Karim Belhadj',
    subject: 'Mathématiques',
    phone: '21 456 789',
    email: 'k.belhadj@ecole.tn',
    weeklyLessons: 6,
    remuneration_type: 'percentage',
    rate_percentage: 70, // 70% of collected student fees
    salary_per_lesson: 35.0
  },
  {
    name: 'Prof. Monia Chaabane',
    subject: 'Sciences Physiques',
    phone: '98 123 456',
    email: 'm.chaabane@ecole.tn',
    weeklyLessons: 4,
    remuneration_type: 'percentage',
    rate_percentage: 65,
    salary_per_lesson: 30.0
  },
  {
    name: 'Prof. Yassine Ben Salem',
    subject: 'Sciences de la Vie et de la Terre (SVT)',
    phone: '55 987 654',
    email: 'y.bensalem@ecole.tn',
    weeklyLessons: 4,
    remuneration_type: 'fixed',
    rate_percentage: 0,
    salary_per_lesson: 40.0
  },
  {
    name: 'Prof. Sami Mejri',
    subject: 'Algorithmique & Programmation',
    phone: '26 333 444',
    email: 's.mejri@ecole.tn',
    weeklyLessons: 5,
    remuneration_type: 'percentage',
    rate_percentage: 75,
    salary_per_lesson: 45.0
  },
  {
    name: 'Prof. Amira Trabelsi',
    subject: 'Économie & Gestion',
    phone: '94 888 777',
    email: 'a.trabelsi@ecole.tn',
    weeklyLessons: 4,
    remuneration_type: 'percentage',
    rate_percentage: 60,
    salary_per_lesson: 30.0
  }
];

export function getSubjectsForLevel(levelNameOrId: string): string[] {
  const found = TUNISIAN_LEVELS.find(l => l.name === levelNameOrId || l.id === levelNameOrId);
  return found ? found.subjects : [
    'Mathématiques',
    'Sciences Physiques',
    'SVT',
    'Français',
    'Anglais',
    'Informatique',
    'Arabe'
  ];
}

export function getSuggestedGroups(levelNameOrId: string): string[] {
  const found = TUNISIAN_LEVELS.find(l => l.name === levelNameOrId || l.id === levelNameOrId);
  return found ? found.groups : COMMON_TUNISIAN_GROUPS;
}

