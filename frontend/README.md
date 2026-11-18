# Centre d'Études & Garderie Périscolaire

Plateforme web intégrée de gestion administrative, pédagogique et financière conçue pour les centres de soutien scolaire et garderies périscolaires (système éducatif tunisien).

---

## 🛠️ Stack Technique

### Frontend
- **Framework** : Vue 3 (Composition API, `<script setup lang="ts">`)
- **Langage & Typage** : TypeScript 5.7+ avec validation stricte via `vue-tsc`
- **Design & Styles** : Tailwind CSS 4, polices *Bricolage Grotesque* & *Atkinson Hyperlegible*
- **Icônes** : Lucide Icons (`lucide-vue-next`)
- **Routing & État** : Navigation par ancre (Hash Routing dynamique), Pinia & Composables réactifs
- **Build tool** : Vite 6

### Backend
- **Framework** : Laravel 12 (PHP 8.2+)
- **Sécurité** : Laravel Sanctum (authentification par jeton avec politique d'expiration)
- **Base de données** : TiDB Cloud (Serverless MySQL 8.0 sécurisé par TLS/SSL) / SQLite local
- **Déploiement** : Docker multi-stage (Alpine Linux) sur Render

---

## 🚀 Fonctionnalités Clés

1. **Tableaux de Bord Multi-Profils (RBAC)**
   - **Directeur** : Supervision globale, finances avancées, validation de caisse, double rôle enseignant.
   - **Secrétaire** : Accueil, inscriptions, feuille d'appel rapide, encaissements et reçus.
   - **Enseignant** : Emploi du temps personnalisé, fiches d'appel numériques et suivi de ses groupes.

2. **Système de Guide Intégré & Visite Guidée**
   - Tiroir d'aide contextuel (raccourci clavier `?`) adapté à l'écran actif et aux permissions de l'utilisateur.
   - Visite guidée pas à pas avec mise en lumière (`TourOverlay`) sans dépendance tierce.
   - Suivi de progression persisté par utilisateur en base de données avec reprise optimiste hors-ligne.

3. **Moteur Financier & Créances**
   - Calcul automatique des séances dues selon les fiches d'appel réelles.
   - Gestion des forfaits de garderie périscolaire (périscolaire, demi-pension, temps plein).
   - Alertes impayés : statuts *À jour*, *En attente* (≥ 4 séances) et *En retard* (> 7 séances).
   - Impression instantanée de reçus au format ticket A5 et extraits consolidés (PDF / Excel).

4. **Recherche Globale & Raccourcis Clavier**
   - Raccourci `/` pour ouvrir instantanément la recherche universelle (élèves, professeurs, groupes).
   - Navigation clavier intégrale conforme aux normes d'accessibilité WCAG AA.

---

## 📦 Démarrage Rapide

### 1. Frontend
```bash
# Installer les dépendances
npm install

# Lancer le serveur de développement
npm run dev

# Vérifier les types TypeScript
npm run typecheck

# Compiler pour la production
npm run build
```

Le frontend est accessible sur `http://localhost:5173`.

### 2. Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

L'API est accessible sur `http://127.0.0.1:8000`.

---

## 🔑 Identifiants Démonstration

L'écran de connexion propose des raccourcis en un clic pour tester chaque profil :

- **Directeur** : `director@studycenter.com` / `director123`
- **Secrétaire** : `secretary@studycenter.com` / `secretary123`
- **Enseignant** : `teacher@studycenter.com` / `teacher123`
