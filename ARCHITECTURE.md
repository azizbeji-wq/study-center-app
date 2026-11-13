# Architecture Complète du Système — Centre d'Études & Garderie

> **Document de Référence Technique & Modélisation UML**  
> **Application** : Study Center Management System  
> **Version** : 2.0.0 (Production Ready)  
> **Dernière mise à jour** : 13 Novembre 2026  
> **Conformité** : Standards PFE & Architectures Logicielle Modernes

---

## 1. Vue d'Ensemble de l'Architecture Système

L'application repose sur un modèle d'architecture **client-serveur découplé** (Single Page Application + API REST Stateless), sécurisé par jetons d'accès révocables (Laravel Sanctum) et déployé sur une infrastructure cloud conteneurisée.

```mermaid
graph TD
    Client["Client Web (Navigateur Desktop & Mobile)"]
    
    subgraph Frontend ["Frontend (SPA — Vue 3 + TypeScript 5.7 + Vite 6)"]
        UI["Composants d'Interface (Tailwind CSS v4 + Lucide)"]
        Router["Hash Router & Navigation Guard réactif"]
        GuideEngine["Moteur de Guide Intégré & TourOverlay"]
        ApiLayer["Couche Client API (Axios + Intercepteurs Auth)"]
    end
    
    subgraph Backend ["Backend (API REST — Laravel 12 + PHP 8.2)"]
        Throttle["Rate Limiter (Throttle Middleware)"]
        Sanctum["Middleware d'Authentification (Sanctum Tokens)"]
        RBAC["Contrôle d'Accès Granulaire (Role & Permission Middleware)"]
        Controllers["Contrôleurs RESTful (Api/*)"]
        Services["Services Métier (TeacherPayoutService, Dues Engine, Session)"]
        Eloquent["ORM Eloquent (Modèles, SoftDeletes & Transactions)"]
    end
    
    subgraph Storage ["Persistance & Infrastructure Cloud"]
        TiDB[("Base de Données TiDB Cloud\n(Serverless MySQL 8.0 TLS/SSL)")]
        Files[("Stockage Fichiers\n(Emplois du temps & Reçus)")]
    end

    Client --> UI
    UI --> Router
    UI --> GuideEngine
    UI --> ApiLayer
    ApiLayer -- "HTTP / JSON (Bearer Token)" --> Throttle
    Throttle --> Sanctum
    Sanctum --> RBAC
    RBAC --> Controllers
    Controllers --> Services
    Services --> Eloquent
    Controllers --> Eloquent
    Eloquent --> TiDB
    Controllers --> Files
```

---

## 2. Modélisation Métier — Diagramme de Classes UML (@uml-architect)

Ce diagramme de classes Mermaid modélise l'intégralité des entités de la couche domaine, leurs attributs typés, leurs méthodes métiers ainsi que leurs cardinalités exactes.

```mermaid
classDiagram
    direction TB

    class User {
        +BigInt id
        +String name
        +String email
        +String password
        +String role
        +String phone
        +Boolean is_active
        +Array permissions
        +Timestamp created_at
        +Timestamp updated_at
        +teacher() Teacher
        +guideProgress() UserGuideProgress
        +activityLogs() ActivityLog
        +hasPermission(key) Boolean
        +isDirector() Boolean
    }

    class Teacher {
        +BigInt id
        +BigInt user_id
        +String name
        +String email
        +String phone
        +String subject
        +Integer weekly_lessons
        +Decimal salary_per_lesson
        +Decimal fixed_student_rate
        +Decimal center_fixed_per_student
        +String remuneration_type
        +Decimal rate_percentage
        +String payout_status
        +Date payout_date
        +String payout_by
        +Timestamp deleted_at
        +user() User
        +subjects() Subject
        +groups() Group
        +students() Student
        +attendances() Attendance
        +payments() Payment
        +calculateMonthlyShare(gross) Decimal
    }

    class Student {
        +BigInt id
        +String name
        +String level
        +String group_name
        +String phone
        +String parent_phone
        +String status
        +Boolean is_remise
        +Decimal remise
        +String discount_type
        +Decimal base_fee
        +Timestamp deleted_at
        +groups() Group
        +subjects() Subject
        +attendances() Attendance
        +payments() Payment
        +calculateNetFee() Decimal
    }

    class Group {
        +BigInt id
        +String name
        +String level
        +String subject
        +BigInt teacher_id
        +Timestamp created_at
        +teacher() Teacher
        +students() Student
        +scheduleItems() ScheduleItem
    }

    class Subject {
        +BigInt id
        +String name
        +String slug
        +teachers() Teacher
        +students() Student
    }

    class DaycareChild {
        +BigInt id
        +String name
        +Integer age
        +String parent_name
        +String phone
        +String schedule
        +Decimal monthly_fee
        +String status
        +Date start_date
        +Timestamp deleted_at
        +payments() Payment
    }

    class Attendance {
        +BigInt id
        +BigInt student_id
        +BigInt teacher_id
        +Date session_date
        +String status
        +String group_name
        +String subject_name
        +Decimal price_per_session
        +Text notes
        +student() Student
        +teacher() Teacher
    }

    class Payment {
        +BigInt id
        +BigInt student_id
        +BigInt teacher_id
        +BigInt daycare_child_id
        +String student_name
        +String month
        +Decimal amount
        +Date date
        +String status
        +Integer sessions_paid
        +Integer months_paid
        +String payment_method
        +String receipt_number
        +Timestamp deleted_at
        +student() Student
        +teacher() Teacher
        +daycareChild() DaycareChild
        +auditLogs() PaymentAuditLog
    }

    class ScheduleItem {
        +BigInt id
        +BigInt teacher_id
        +BigInt group_id
        +BigInt room_id
        +Integer day_of_week
        +Time start_time
        +Time end_time
        +String subject
        +String group_name
        +teacher() Teacher
        +room() Room
        +group() Group
    }

    class Room {
        +BigInt id
        +String name
        +Integer capacity
        +scheduleItems() ScheduleItem
    }

    class School {
        +BigInt id
        +String name
        +String city
        +String address
        +classes() SchoolClass
        +imports() SchoolCalendarImport
    }

    class SchoolClass {
        +BigInt id
        +BigInt school_id
        +String name
        +String grade_level
        +school() School
        +imports() SchoolCalendarImport
    }

    class SchoolCalendarImport {
        +BigInt id
        +BigInt school_id
        +BigInt school_class_id
        +String title
        +String file_path
        +String file_type
        +school() School
        +schoolClass() SchoolClass
    }

    class Expense {
        +BigInt id
        +String title
        +String category
        +Decimal amount
        +Date expense_date
        +Boolean is_recurring
        +String frequency
        +BigInt created_by
    }

    class MonthlyClosing {
        +BigInt id
        +String month
        +Decimal total_revenue
        +Decimal total_teacher_payouts
        +Decimal total_expenses
        +Decimal net_profit
        +Timestamp closed_at
        +BigInt closed_by
        +String status
    }

    class UserGuideProgress {
        +BigInt id
        +BigInt user_id
        +String key
        +Timestamp completed_at
        +user() User
    }

    class ActivityLog {
        +BigInt id
        +BigInt user_id
        +String module
        +String action
        +String description
        +Array metadata
        +String ip_address
        +Timestamp created_at
        +user() User
    }

    %% Relations et Cardinalités
    User "1" -- "0..1" Teacher : est associé à
    User "1" -- "*" UserGuideProgress : suit
    User "1" -- "*" ActivityLog : génère

    Teacher "1" -- "*" Group : anime
    Teacher "1" -- "*" Attendance : valide
    Teacher "1" -- "*" Payment : perçoit
    Teacher "1" -- "*" ScheduleItem : planifie
    Teacher "*" -- "*" Subject : enseigne

    Student "*" -- "*" Group : participe
    Student "*" -- "*" Subject : suit
    Student "1" -- "*" Attendance : enregistre
    Student "1" -- "*" Payment : règle

    DaycareChild "1" -- "*" Payment : justifie
    Room "1" -- "*" ScheduleItem : accueille
    School "1" -- "*" SchoolClass : regroupe
    School "1" -- "*" SchoolCalendarImport : possède
    SchoolClass "1" -- "*" SchoolCalendarImport : associe
```

---

## 3. Diagrammes de Séquence Métier (@uml-architect)

### 3.1 Authentification Sanctum & Contrôle d'Accès RBAC

```mermaid
sequenceDiagram
    autonumber
    actor Utilisateur as Client Web
    participant View as LoginPage.vue
    participant API as AuthController (Laravel)
    participant Sanctum as Token Guard
    participant DB as TiDB Cloud Database

    Utilisateur->>View: Saisie email + mot de passe
    View->>API: POST /api/login { email, password }
    API->>DB: Recherche de l'utilisateur par email
    DB-->>API: Utilisateur trouvé (mot de passe hashé Argon2/Bcrypt)
    
    alt Identifiants incorrects
        API-->>View: 422 Unprocessable Entity (Message d'erreur)
        View-->>Utilisateur: Affichage alerte de sécurité
    else Identifiants valides
        API->>DB: Création du token d'accès (personal_access_tokens)
        DB-->>API: Token ID + Jeton brut généré
        API-->>View: 200 OK { token, user: { id, name, role, permissions } }
        View->>View: setSession(token, user) dans localStorage
        
        alt Rôle = director
            View-->>Utilisateur: Redirection vers DirectorDashboard (#dashboard)
        else Rôle = secretary
            View-->>Utilisateur: Redirection vers SecretaryDashboard (#students)
        else Rôle = teacher
            View-->>Utilisateur: Redirection vers TeacherDashboard (#schedule)
        end
    end
```

---

### 3.2 Moteur de Créances & Encaissement avec Reçu Officiel A5

```mermaid
sequenceDiagram
    autonumber
    actor Secrétaire as Personnel / Directeur
    participant View as PaymentsManagement.vue
    participant API as PaymentController
    participant Engine as DuesCalculationService
    participant DB as TiDB Cloud Database
    participant Modal as PaymentReceiptModal.vue

    Secrétaire->>View: Accès à l'onglet Paiements (#payments)
    View->>API: GET /api/payment-dues
    API->>Engine: Calcul des créances par élève et séances dues
    Engine->>DB: Récupération des présences (Present/Retard)
    Engine->>DB: Récupération des règlements antérieurs
    Engine-->>API: Synthèse créances (Statuts: À jour, En attente, En retard)
    API-->>View: 200 OK { students: [...], daycare: [...], totals }
    View-->>Secrétaire: Affichage tableau des soldes avec alertes colorées

    Secrétaire->>View: Clic sur « Encaisser » pour un élève
    View->>API: POST /api/payments { student_id, amount, sessions_paid, method }
    
    rect rgb(240, 245, 240)
        API->>DB: DB::beginTransaction()
        API->>DB: Insertion enregistrement Payment (status: paid)
        API->>DB: Génération numéro officiel REC-XXXXX
        API->>DB: Enregistrement dans PaymentAuditLog
        API->>DB: DB::commit()
    end

    API-->>View: 201 Created { payment, receipt_number }
    View->>Modal: Ouverture automatique du reçu de paiement
    Modal-->>Secrétaire: Affichage format A5 prêt pour impression / PDF
```

---

### 3.3 Pointage des Présences en Lot & Fenêtre d'Annulation (Undo 10s)

```mermaid
sequenceDiagram
    autonumber
    actor Enseignant as Enseignant / Secrétaire
    participant View as AttendanceManagement.vue
    participant API as AttendanceController
    participant DB as TiDB Cloud Database

    Enseignant->>View: Choix du groupe et de la date de séance
    View->>API: GET /api/groups/{id}/students
    API->>DB: Récupération des élèves inscrits dans le groupe
    DB-->>API: Liste des élèves
    API-->>View: 200 OK (Élèves du groupe)

    Enseignant->>View: Pointage en un clic (Présent, Absent, Excusé, En retard)
    Enseignant->>View: Clic sur « Valider la feuille d'appel »
    View->>API: POST /api/attendances/batch { session_date, attendances: [...] }
    
    rect rgb(240, 245, 240)
        API->>DB: DB::transaction()
        API->>DB: Upsert des fiches de présence pour chaque élève
        API->>DB: Recalcul automatique des séances dues
    end

    API-->>View: 200 OK { success: true, count: N }
    View-->>Enseignant: Toast confirmation vert + Compte à rebours « Annuler (10s) »

    opt L'utilisateur clique sur « Annuler » dans les 10 secondes
        Enseignant->>View: Clic « Annuler »
        View->>API: POST /api/attendances/batch (Restauration état antérieur)
        API->>DB: Restauration des statuts précédents
        API-->>View: 200 OK
        View-->>Enseignant: Notification : « Feuille d'appel annulée. »
    end
```

---

## 4. Diagramme de Cas d'Utilisation UML (@uml-architect)

```mermaid
graph LR
    subgraph Acteurs
        D((Directeur))
        S((Secrétaire))
        E((Enseignant))
    end

    subgraph Administration & Finances
        UC1[Supervision Tableau de Bord]
        UC2[Gestion des Dépenses & Journal Caisse]
        UC3[Clôture Mensuelle de Caisse]
        UC4[Suppression Définitive can_delete]
        UC5[Extraction Relevés & Paies Enseignants]
        UC6[Configuration Tarifs & Formules]
    end

    subgraph Opérations Quotidiennes
        UC7[Inscription & Fiche Élèves]
        UC8[Gestion de la Garderie Périscolaire]
        UC9[Encaissement & Émission Reçus A5]
        UC10[Recherche Globale Rapide /]
        UC11[Consultation Guide Intégré ?]
    end

    subgraph Activités Pédagogiques
        UC12[Consultation Emploi du Temps]
        UC13[Pointage Présences Feuille d'Appel]
        UC14[Gestion des Groupes par Matière]
    end

    %% Rôle Directeur
    D --> UC1
    D --> UC2
    D --> UC3
    D --> UC4
    D --> UC5
    D --> UC6
    D --> UC7
    D --> UC9
    D --> UC10
    D --> UC11
    D -. Double rôle .-> E

    %% Rôle Secrétaire
    S --> UC7
    S --> UC8
    S --> UC9
    S --> UC10
    S --> UC11
    S --> UC13

    %% Rôle Enseignant
    E --> UC12
    E --> UC13
    E --> UC14
    E --> UC10
    E --> UC11
```

---

## 5. Moteur Financier & Règles de Rémunération

Le système intègre trois formules de rémunération enseignante paramétrables par la Direction :

1. **Formule C — Pourcentage sur C.A. Brut (`percentage`)** :
   $$\text{Part Professeur} = (\text{Prix séance} \times \text{Séances/mois} \times \text{Élèves}) \times \frac{\text{Taux \%}}{100}$$
   *Exemple :* Cours à 25 TND / séance (100 TND / mois), taux à 70% ➔ 70 TND pour le professeur, 30 TND de marge centre par élève.

2. **Formule A — Part Fixe Administration (`fixed_center`)** :
   $$\text{Part Centre} = \text{Montant fixe administration} \times \text{Élèves}$$
   $$\text{Part Professeur} = \text{C.A. Brut} - \text{Part Centre}$$

3. **Formule B — Montant Fixe par Élève (`fixed_student_rate`)** :
   $$\text{Part Professeur} = \text{Montant fixe par élève} \times \text{Élèves}$$
   Le centre conserve l'intégralité du reliquat après déduction de la part fixe.

> **Règle de Protection Enseignante (Absorption des Remises)** :  
> Toute remise tarifaire accordée à une famille d'élèves (fratrie, besoin social) est **absorbée à 100% par l'administration**. Les honoraires des professeurs restent calculés sur le plein tarif brut contractuel.

---

## 6. Déploiement Cloud & Sécurité de Production

- **Backend** : Conteneurisé via Docker (`backend/Dockerfile`, Alpine Linux PHP 8.2-cli) et orchestré sur Render Web Service.
- **Base de Données** : TiDB Cloud Serverless (MySQL 8.0) situé dans la région Francfort (`gateway01.eu-central-1.prod.aws.tidbcloud.com:4000`), avec vérification de certificat CA système (`/etc/ssl/certs/ca-certificates.crt`).
- **Frontend** : Site statique distribué à haute disponibilité avec réécriture SPA (`/* ➔ /index.html`).
- **Sécurité des Sessions** : Double seuil d'expiration (1 heure en cas de fermeture du navigateur, 2 heures continues d'inactivité).
