# Study Center API (Backend)

Backend RESTful sécurisé propulsé par **Laravel 12** pour la gestion administrative, financière et pédagogique du centre d'études et de garderie périscolaire.

---

## Architecture & Technologies

- **Framework** : Laravel 12 (PHP 8.2+)
- **Authentification & Sécurité** : Laravel Sanctum (jetons porteurs avec expiration et gestion de session active)
- **Base de données** : 
  - Production : TiDB Cloud (Serverless MySQL 8.0 avec chiffrement TLS/SSL obligatoire)
  - Développement local : SQLite / MySQL
  - Tests automatisés : SQLite `:memory:`
- **Conteneurisation & Déploiement** : Docker (Alpine Linux) sur Render

---

## Rôles & Niveaux d'Accès (RBAC)

1. **Directeur (`director`)**
   - Accès intégral à tous les modules opérationnels et financiers.
   - Droits exclusifs de suppression (`can_delete`) et de validation financière.
   - Journal de caisse, audit financier, clôtures mensuelles et fiches de paie enseignants.
   - Possibilité de basculer en mode Enseignant (double rôle).

2. **Secrétaire (`secretary`)**
   - Gestion opérationnelle quotidienne : inscriptions élèves, garderie périscolaire.
   - Pointage des présences par groupe et par séance.
   - Encaissements des mensualités et impression immédiate des reçus officiels.
   - Accès filtré selon les permissions granulaires configurées par la Direction.

3. **Enseignant (`teacher`)**
   - Portail dédié : consultation de l'emploi du temps hebdomadaire.
   - Gestion de ses propres groupes d'élèves.
   - Saisie rapide et feuille d'appel numérique avec calcul d'assiduité.

---

## Comptes de Démonstration (Seeder)

Initialisés de façon idempotente via `DatabaseSeeder` :

| Rôle | Email | Mot de passe | Description |
|---|---|---|---|
| Directeur | `director@studycenter.com` | `director123` | Accès superviseur complet |
| Secrétaire | `secretary@studycenter.com` | `secretary123` | Accueil & encaissements |
| Enseignant | `teacher@studycenter.com` | `teacher123` | Professeur Mohamed (Maths / Physique) |

---

## Installation Locale

### 1. Prérequis
- PHP 8.2 ou supérieur avec extensions `pdo`, `pdo_sqlite`, `pdo_mysql`, `bcmath`, `mbstring`, `curl`.
- Composer 2.x

### 2. Démarrage
```bash
# Se placer dans le répertoire backend
cd backend

# Installer les dépendances
composer install

# Copier l'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Exécuter les migrations et injecter les comptes de départ
php artisan migrate --seed

# Lancer le serveur de développement
php artisan serve
```

L'API est accessible localement sur `http://127.0.0.1:8000`.

---

## Endpoints Principaux

### Authentification & Session
- `POST /api/login` : Authentification et obtention du jeton Sanctum (limité à 10 tentatives/min).
- `POST /api/logout` : Révocation du jeton actif.
- `GET /api/profile` : Consultation du profil utilisateur connecté.
- `PUT /api/profile` : Mise à jour des informations personnelles.
- `PUT /api/profile/password` : Changement sécurisé du mot de passe.

### Recherche & Guide Intégré
- `GET /api/search` : Recherche globale instantanée (élèves, enseignants, groupes).
- `GET /api/guide/progress` : État d'avancement du guide de l'utilisateur.
- `POST /api/guide/progress/{key}` : Marquer une étape du guide comme terminée.
- `DELETE /api/guide/progress/{key}` : Réinitialiser une étape du guide.

### Pédagogie & Présences
- `apiResource('students', StudentController)` : Fiches élèves et matières suivies.
- `apiResource('groups', GroupController)` : Groupes par niveau scolaire.
- `POST /api/groups/{id}/students` : Rattachement dynamique d'un élève à un groupe.
- `apiResource('attendances', AttendanceController)` : Feuille d'appel et pointage des séances.
- `POST /api/attendances/batch` : Pointage en lot pour une séance complète.

### Finances & Moteur de Créances
- `GET /api/payment-dues` : Calcul dynamique des séances dues par élève et mois de garderie impayés.
- `apiResource('payments', PaymentController)` : Enregistrement des règlements et reçus.
- `GET /api/payments/extract-teachers` : Extraits officiels consolidés pour le corps professoral.
- `GET /api/payments/extract-students` : Extraits officiels par élève.

### Comptabilité & Clôtures (Directeur)
- `GET /api/accounting/summary` : Bilan financier mensuel et synthèse des flux de trésorerie.
- `apiResource('accounting/expenses')` : Dépenses du centre (loyer, salaires, fournitures).
- `POST /api/accounting/close-month` : Clôture mensuelle définitive avec scellement de caisse.

---

## Tests Automatisés

Le backend dispose d'une suite complète de tests unitaires et fonctionnels (Unit & Feature) :

```bash
php artisan test
```

Vérifie l'intégrité de :
- L'isolation des données entre rôles.
- Le calcul de rémunération des enseignants selon les formules (fixe, pourcentage, par élève).
- La validation des requêtes et permissions granulaires.
- La progression idempotente du guide utilisateur.
