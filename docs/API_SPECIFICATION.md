# Spécification Technique de l'API REST — Study Center API

> **Norme de Référence pour Intégrations Frontend & Audits Techniques**  
> **Framework** : Laravel 12 (PHP 8.2+) • **Authentification** : Laravel Sanctum  
> **Base URL Locale** : `http://127.0.0.1:8000/api`  
> **Base URL Production** : `https://study-center-api.onrender.com/api`

---

## 1. Conventions Générales & Format des Échanges

### 1.1 Headers HTTP Requis
Pour toute requête adressée à un endpoint authentifié, le client doit obligatoirement transmettre :
```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <SANCTUM_PERSONAL_ACCESS_TOKEN>
```

### 1.2 Codes de Statut Standardisés
| Code HTTP | Libellé | Signification & Cas d'Usage |
|---|---|---|
| `200 OK` | Succès | Requête traitée avec succès (GET, PUT standard, DELETE idempotent). |
| `201 Created` | Ressource Créée | Nouvel enregistrement inséré en base (POST). |
| `401 Unauthorized` | Non Authentifié | Token absent, invalide, révoqué ou session expirée. |
| `403 Forbidden` | Droits Insuffisants | Rôle ou permission manquante (ex: suppression tentée par Secrétaire). |
| `404 Not Found` | Introuvable | Identifiant de ressource inexistant ou supprimé logiquement. |
| `422 Unprocessable` | Erreur de Validation | Corps de requête non conforme aux règles FormRequest (retourne `errors: {}`). |
| `429 Too Many Requests` | Limite Dépassée | Rate limiting atteint (ex: max 10 tentatives/min sur `/login`). |

---

## 2. Authentification & Profil Utilisateur

### `POST /login` (Public, limité à 10 req/min)
Authentifie l'utilisateur et délivre un jeton porteur personnel Sanctum.

**Payload JSON :**
```json
{
  "email": "director@studycenter.com",
  "password": "director123"
}
```

**Réponse `200 OK` :**
```json
{
  "token": "1|eXamPLeTokEnSanctumHash987654321",
  "user": {
    "id": 1,
    "name": "Directeur",
    "email": "director@studycenter.com",
    "role": "director",
    "permissions": ["all"]
  }
}
```

---

### `POST /logout` (auth:sanctum)
Révoque immédiatement le jeton d'accès actuellement utilisé.

**Réponse `200 OK` :**
```json
{
  "message": "Déconnexion réussie."
}
```

---

### `GET /profile` (auth:sanctum)
Renvoie les détails du profil de l'utilisateur connecté.

**Réponse `200 OK` :**
```json
{
  "id": 1,
  "name": "Directeur",
  "email": "director@studycenter.com",
  "role": "director",
  "phone": "+216 20 000 000",
  "is_active": true
}
```

---

## 3. Guide Intégré & Progression Utilisateur

### `GET /guide/progress` (auth:sanctum)
Récupère les clés des tâches du guide déjà validées par l'utilisateur connecté, ainsi que la détection automatique des prérequis en base de données pour le Directeur.

**Réponse `200 OK` :**
```json
{
  "completed": [
    "director_subjects",
    "director_teachers"
  ],
  "starting_checks": {
    "has_subjects": true,
    "has_teachers": true,
    "has_rates": true,
    "has_students": true,
    "has_attendance": false
  }
}
```

---

### `POST /guide/progress/{key}` (auth:sanctum)
Marque une tâche du guide comme terminée de façon idempotente.

**Clés autorisées :**
`director_subjects`, `director_teachers`, `director_rates`, `director_students`, `director_attendance`, `teacher_sessions`, `teacher_attendance`, `tour_students`, `tour_attendance`, `tour_payments`, `tour_schedule`, `tour_teachers`, `tour_teacher_schedule`, `tour_teacher_attendance`.

**Réponse `200 OK` :**
```json
{
  "status": "marked",
  "key": "director_attendance",
  "completed_at": "2026-11-13T10:00:00.000000Z"
}
```

---

### `DELETE /guide/progress/{key}` (auth:sanctum)
Réinitialise une étape du guide pour l'utilisateur connecté.

**Réponse `200 OK` :**
```json
{
  "status": "unmarked",
  "key": "director_attendance"
}
```

---

## 4. Recherche Globale Instantanée

### `GET /search?q={query}` (auth:sanctum)
Recherche unifiée dans les élèves (par nom, niveau, groupe, téléphone) et les enseignants. Filtre automatiquement les données selon le rôle de l'utilisateur (un enseignant ne reçoit que les élèves de ses propres groupes).

**Réponse `200 OK` :**
```json
{
  "students": [
    {
      "id": 12,
      "name": "Yassine Ben Salem",
      "level": "Baccalauréat Sciences Expérimentales",
      "group_name": "BAC-SC1",
      "phone": "+216 98 111 222"
    }
  ],
  "teachers": [
    {
      "id": 1,
      "name": "Professeur Mohamed",
      "subject": "Mathématiques, Physique",
      "phone": "+216 20 000 000"
    }
  ]
}
```

---

## 5. Moteur de Créances & Règlements

### `GET /payment-dues` (auth:sanctum)
Exécute le moteur de calcul différentiel des séances dues (Séances Présent/Retard $-$ Séances déjà acquittées) et les mois de garderie impayés.

**Réponse `200 OK` :**
```json
{
  "students": [
    {
      "key": "s-12-t-1",
      "studentId": 12,
      "name": "Yassine Ben Salem",
      "teacherId": 1,
      "teacherName": "Professeur Mohamed",
      "subject": "Mathématiques",
      "sessionsDue": 8,
      "sessionsPaid": 4,
      "sessionsUnpaid": 4,
      "sessionPrice": 25.00,
      "amountDue": 100.00,
      "status": "pending"
    }
  ],
  "daycare": [],
  "totals": {
    "pending": 100.00,
    "pendingCount": 1,
    "overdue": 0.00,
    "overdueCount": 0
  }
}
```

---

### `POST /payments` (auth:sanctum)
Enregistre un règlement en espèces ou chèque, applique la transaction atomique et génère le numéro de reçu officiel.

**Payload JSON :**
```json
{
  "student_id": 12,
  "teacher_id": 1,
  "amount": 100.00,
  "sessions_paid": 4,
  "month": "Novembre 2026",
  "payment_method": "cash",
  "notes": "Règlement reçu du parent"
}
```

**Réponse `201 Created` :**
```json
{
  "id": 45,
  "receipt_number": "REC-00045",
  "student_id": 12,
  "amount": "100.00",
  "status": "paid",
  "created_at": "2026-11-13T10:05:00.000000Z"
}
```

---

## 6. Pointage des Présences (Batch Attendance)

### `POST /attendances/batch` (auth:sanctum)
Valide atomiquement la feuille d'appel pour un groupe d'élèves lors d'une date de cours donnée.

**Payload JSON :**
```json
{
  "session_date": "2026-11-13",
  "group_name": "BAC-SC1",
  "subject_name": "Mathématiques",
  "teacher_id": 1,
  "attendances": [
    {
      "student_id": 12,
      "status": "present",
      "notes": null
    },
    {
      "student_id": 14,
      "status": "absent",
      "notes": "Non excusé"
    }
  ]
}
```

**Réponse `200 OK` :**
```json
{
  "success": true,
  "saved_count": 2,
  "session_date": "2026-11-13"
}
```

---

## 7. Portail Enseignant (`/api/teacher/*`)

Tous les endpoints de ce groupe sont accessibles aux utilisateurs disposant du rôle `teacher` ou du double rôle `director` / `secretary` :

- `GET /api/teacher/profile` : Profil de l'enseignant, nombre de cours par semaine et statut de rémunération.
- `GET /api/teacher/schedule` : Emploi du temps personnalisé de l'enseignant connecté.
- `GET /api/teacher/groups` : Groupes assignés à l'enseignant connecté.
- `GET /api/teacher/groups/{id}/students` : Liste nominative des élèves composant le groupe.
- `POST /api/teacher/attendance/batch` : Saisie sécurisée de la feuille d'appel par le professeur.

---

## 8. Clôtures de Caisse & Comptabilité (Directeur)

Accessible sous protection `middleware('permission:reports')` et `middleware('role:director')` :

- `GET /api/accounting/summary` : Bilan mensuel consolidé (recettes, honoraires professeurs versés, charges fixes, bénéfice net).
- `GET /api/accounting/expenses` : Liste des dépenses du centre.
- `POST /api/accounting/expenses` : Enregistrement d'une dépense (loyer, matériel, eau, électricité).
- `POST /api/accounting/close-month` : Scelle définitivement la caisse du mois sélectionné avec archivage comptable.

