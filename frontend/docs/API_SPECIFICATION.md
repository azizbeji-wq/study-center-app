# 📘 API Specification — Study Center

> **Version :** 2.0 — Mise à jour le 2026-11-16  
> **Base URL :** `http://localhost:8000/api`  
> **Authentification :** Laravel Sanctum (`Authorization: Bearer <token>`)  
> **Format :** JSON uniquement (`Content-Type: application/json`)

---

## Table des Matières

1. [Authentification](#1-authentification)
2. [Profil Utilisateur](#2-profil-utilisateur)
3. [Gestion des Utilisateurs (Directeur)](#3-gestion-des-utilisateurs-directeur)
4. [Élèves](#4-élèves)
5. [Enseignants](#5-enseignants)
6. [Matières](#6-matières)
7. [Groupes Pédagogiques](#7-groupes-pédagogiques)
8. [Paiements](#8-paiements)
9. [Présences (Absences)](#9-présences-absences)
10. [Crèche (Garderie)](#10-crèche-garderie)
11. [Emplois du Temps](#11-emplois-du-temps)
12. [Comptabilité & Finance](#12-comptabilité--finance)
13. [Portail Enseignant](#13-portail-enseignant)
14. [Écoles Primaires](#14-écoles-primaires)
15. [Calendrier Scolaire](#15-calendrier-scolaire)
16. [Recherche Globale](#16-recherche-globale)
17. [Guide d'Utilisation](#17-guide-dutilisation)
18. [Assistant IA](#18-assistant-ia)
19. [Tableau de Bord](#19-tableau-de-bord)
20. [Modèle de Permissions](#20-modèle-de-permissions)

---

## 1. Authentification

### `POST /api/login`
> **Public** — Throttle : 10 req/min

**Body :**
```json
{ "email": "director@study.com", "password": "secret" }
```
**Réponse 200 :**
```json
{
  "token": "1|abc123...",
  "user": { "id": 1, "name": "Directeur", "role": "director", "permissions": [] }
}
```
**Réponse 422 :** Credentials invalides

---

### `POST /api/logout`
> **Auth requis**

Révoque le token Sanctum courant. Réponse 200 : `{ "message": "Déconnecté." }`

---

## 2. Profil Utilisateur

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/profile` | Récupère le profil de l'utilisateur connecté | ✅ Tous |
| `PUT` | `/api/profile` | Modifie le nom / email | ✅ Tous |
| `PUT` | `/api/profile/password` | Change le mot de passe | ✅ Tous |

**Body `PUT /api/profile` :**
```json
{ "name": "Nouveau Nom", "email": "email@example.com" }
```

**Body `PUT /api/profile/password` :**
```json
{ "current_password": "ancien", "password": "nouveau", "password_confirmation": "nouveau" }
```

---

## 3. Gestion des Utilisateurs (Directeur)

> 🔒 **Réservé au rôle `director`**

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/users` | Liste tous les utilisateurs |
| `POST` | `/api/users` | Crée un compte utilisateur |
| `PUT` | `/api/users/{id}` | Modifie un utilisateur |
| `POST` | `/api/users/{id}/reset-password` | Réinitialise le mot de passe |
| `DELETE` | `/api/users/{id}` | Supprime un utilisateur |

**Body `POST /api/users` :**
```json
{
  "name": "Secrétaire Fatma",
  "email": "fatma@study.com",
  "password": "password123",
  "role": "secretary",
  "permissions": ["can_delete", "reports"]
}
```

**Rôles possibles :** `director`, `secretary`, `teacher`

---

## 4. Élèves

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/students` | Liste tous les élèves (avec matières & enseignants) | ✅ Tous |
| `POST` | `/api/students` | Inscrit un nouvel élève | ✅ Tous |
| `GET` | `/api/students/{id}` | Détail d'un élève | ✅ Tous |
| `PUT` | `/api/students/{id}` | Modifie un élève | ✅ Tous |
| `DELETE` | `/api/students/{id}` | Supprime un élève | 🔒 `can_delete` |

**Body `POST /api/students` :**
```json
{
  "name": "Ahmed Ben Ali",
  "phone": "55123456",
  "school": "École Pasteur",
  "level": "6ème",
  "subjects": [{ "subject_id": 1, "teacher_id": 2, "price_per_month": 60 }],
  "is_remise": false,
  "remise": 0,
  "discount_type": "percentage"
}
```

---

## 5. Enseignants

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/teachers` | Liste tous les enseignants (avec stats rémunération) | ✅ Tous |
| `POST` | `/api/teachers` | Ajoute un enseignant | ✅ Tous |
| `GET` | `/api/teachers/{id}` | Détail d'un enseignant | ✅ Tous |
| `PUT` | `/api/teachers/{id}` | Modifie un enseignant | ✅ Tous |
| `DELETE` | `/api/teachers/{id}` | Supprime un enseignant | 🔒 `can_delete` |
| `POST` | `/api/teachers/{id}/toggle-payout` | Bascule statut versement | 🔒 `director` |

**Types de rémunération (`remuneration_type`) :**
- `percentage` — Part en % du chiffre brut
- `fixed_center` — Montant fixe retenu par le centre par élève
- `fixed_per_student` — Tarif fixe par élève

---

## 6. Matières

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/subjects` | Liste toutes les matières | ✅ Tous |
| `GET` | `/api/subjects/{id}` | Détail d'une matière | ✅ Tous |
| `POST` | `/api/subjects` | Crée une matière | 🔒 `director` |
| `PUT` | `/api/subjects/{id}` | Modifie une matière | 🔒 `director` |
| `DELETE` | `/api/subjects/{id}` | Supprime une matière | 🔒 `can_delete` |

---

## 7. Groupes Pédagogiques

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/groups` | Liste tous les groupes | ✅ Tous |
| `POST` | `/api/groups` | Crée un groupe | ✅ Tous |
| `GET` | `/api/groups/{id}` | Détail d'un groupe | ✅ Tous |
| `PUT` | `/api/groups/{id}` | Modifie un groupe | ✅ Tous |
| `DELETE` | `/api/groups/{id}` | Supprime un groupe | ✅ Tous |
| `POST` | `/api/groups/{id}/students` | Rattache un élève | ✅ Tous |
| `DELETE` | `/api/groups/{id}/students/{studentId}` | Détache un élève | ✅ Tous |

---

## 8. Paiements

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/payments` | Liste tous les paiements | ✅ Tous |
| `POST` | `/api/payments` | Enregistre un paiement | ✅ Tous |
| `GET` | `/api/payments/{id}` | Détail d'un paiement | ✅ Tous |
| `PUT` | `/api/payments/{id}` | Modifie un paiement | ✅ Tous |
| `DELETE` | `/api/payments/{id}` | Supprime un paiement | 🔒 `can_delete` |
| `GET` | `/api/payment-dues` | Calcul des soldes impayés | ✅ Tous |
| `GET` | `/api/payments/extract-teachers` | Extrait de paiements enseignants | ✅ Tous |
| `GET` | `/api/payments/extract-students` | Extrait de paiements élèves | ✅ Tous |

**Body `POST /api/payments` :**
```json
{
  "student_name": "Ahmed Ben Ali",
  "amount": 120.00,
  "status": "paid",
  "date": "2025-11-15",
  "month": "novembre 2025",
  "payment_method": "especes",
  "student_id": 5,
  "teacher_id": 2,
  "sessions_paid": 4
}
```

**Statuts :** `paid`, `pending`  
**Méthodes de paiement :** `especes`, `virement`, `cheque`

---

## 9. Présences (Absences)

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/attendances` | Liste toutes les présences | ✅ Tous |
| `POST` | `/api/attendances` | Enregistre une présence | ✅ Tous |
| `GET` | `/api/attendances/{id}` | Détail d'une présence | ✅ Tous |
| `PUT` | `/api/attendances/{id}` | Modifie une présence | ✅ Tous |
| `DELETE` | `/api/attendances/{id}` | Supprime une présence | 🔒 `can_delete` |
| `GET` | `/api/attendances/summary/{studentId}` | Bilan présences d'un élève | ✅ Tous |
| `POST` | `/api/attendances/batch` | Saisie en lot (groupe) | ✅ Tous |

**Body `POST /api/attendances/batch` :**
```json
{
  "group_id": 1,
  "date": "2025-11-15",
  "records": [
    { "student_id": 5, "status": "present" },
    { "student_id": 6, "status": "absent" }
  ]
}
```

---

## 10. Crèche (Garderie)

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/daycare` | Liste les enfants en garderie | ✅ Tous |
| `POST` | `/api/daycare` | Inscrit un enfant | ✅ Tous |
| `GET` | `/api/daycare/{id}` | Détail d'un enfant | ✅ Tous |
| `PUT` | `/api/daycare/{id}` | Modifie un enfant | ✅ Tous |
| `DELETE` | `/api/daycare/{id}` | Supprime un enfant | 🔒 `can_delete` |

---

## 11. Emplois du Temps

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/schedules` | Liste les créneaux horaires | ✅ Tous |
| `POST` | `/api/schedules` | Crée un créneau | ✅ Tous |
| `GET` | `/api/schedules/{id}` | Détail d'un créneau | ✅ Tous |
| `PUT` | `/api/schedules/{id}` | Modifie un créneau | ✅ Tous |
| `DELETE` | `/api/schedules/{id}` | Supprime un créneau | ✅ Tous |

---

## 12. Comptabilité & Finance

> 🔒 **Permission `reports` requise**

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/accounting/summary` | Résumé financier mensuel |
| `GET` | `/api/accounting/expenses` | Liste des dépenses |
| `POST` | `/api/accounting/expenses` | Ajoute une dépense |
| `PUT` | `/api/accounting/expenses/{id}` | Modifie une dépense |
| `DELETE` | `/api/accounting/expenses/{id}` | Supprime une dépense |
| `POST` | `/api/accounting/expenses/generate-recurring` | Génère les dépenses récurrentes du mois |
| `GET` | `/api/accounting/audit-logs` | Journal d'audit financier |
| `GET` | `/api/accounting/closings` | Liste des clôtures mensuelles |
| `POST` | `/api/accounting/close-month` | Clôture le mois courant |
| `POST` | `/api/accounting/reopen-month` | Réouvre une clôture |
| `GET` | `/api/accounting/teachers/{id}/payslip` | Fiche de paie d'un enseignant |

**Catégories de dépenses :** `loyer`, `électricité`, `fournitures`, `entretien`, `publicité`, `internet_tel`, `salaires`, `autres`

---

## 13. Portail Enseignant

> 🔒 **Rôle `teacher`, `director` ou `secretary`**  
> Préfixe : `/api/teacher/`

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/teacher/profile` | Profil enseignant (rémunération, matières, stats) |
| `GET` | `/api/teacher/schedule` | Emploi du temps personnel |
| `GET` | `/api/teacher/groups` | Groupes gérés par l'enseignant |
| `GET` | `/api/teacher/groups/{id}/students` | Élèves d'un groupe |
| `POST` | `/api/teacher/attendance/batch` | Saisie de présences en lot |
| `GET` | `/api/teacher/attendances` | Historique des présences saisies |

---

## 14. Écoles Primaires

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/schools` | Liste les écoles | ✅ Tous |
| `POST` | `/api/schools` | Crée une école | ✅ Tous |
| `PUT` | `/api/schools/{id}` | Modifie une école | ✅ Tous |
| `DELETE` | `/api/schools/{id}` | Supprime une école | ✅ Tous |
| `GET` | `/api/schools/{id}/classes` | Classes d'une école | ✅ Tous |

---

## 15. Calendrier Scolaire

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/school-calendar-imports` | Liste les documents importés | ✅ Tous |
| `POST` | `/api/school-calendar-imports` | Importe un PDF/image | ✅ Tous |
| `GET` | `/api/school-calendar-imports/{id}/file` | Télécharge le fichier | ✅ Tous |
| `POST` | `/api/school-calendar-imports/{id}` | Met à jour les métadonnées | ✅ Tous |
| `DELETE` | `/api/school-calendar-imports/{id}` | Supprime un document | ✅ Tous |

---

## 16. Recherche Globale

### `GET /api/search?q={terme}`
> **Auth requis**

Recherche dans les élèves et enseignants. Retourne :
```json
{
  "students": [{ "id": 1, "name": "Ahmed", "level": "6ème" }],
  "teachers": [{ "id": 2, "name": "Prof. Sara", "subject": "Maths" }]
}
```

---

## 17. Guide d'Utilisation

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/guide/progress` | Progression des étapes du guide | ✅ Tous |
| `POST` | `/api/guide/progress/{key}` | Marque une étape comme complète | ✅ Tous |
| `DELETE` | `/api/guide/progress/{key}` | Annule la complétion d'une étape | ✅ Tous |

---

## 18. Assistant IA

> **Auth requis** — Throttle : 20 req/min

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `POST` | `/api/ai/ask` | Envoie une question au chatbot Gemini |
| `POST` | `/api/ai/chat` | Alias de `/api/ai/ask` |

**Body :**
```json
{ "message": "Comment créer un élève ?", "history": [] }
```

---

## 19. Tableau de Bord

### `GET /api/stats`
> **Auth requis**

Retourne les statistiques globales pour le tableau de bord :
```json
{
  "students_count": 42,
  "teachers_count": 8,
  "payments_this_month": 3200,
  "pending_payments": 5,
  "attendance_rate": 92.5
}
```

---

## 20. Modèle de Permissions

### Rôles

| Rôle | Accès |
|------|-------|
| `director` | Accès total à tout le système |
| `secretary` | Accès à la gestion quotidienne (élèves, paiements, présences) |
| `teacher` | Portail enseignant uniquement |

### Permissions Granulaires (attribuables à un secretary)

| Permission | Description | Endpoints concernés |
|------------|-------------|---------------------|
| `can_delete` | Suppression d'enregistrements | `DELETE /students`, `DELETE /teachers`, `DELETE /payments`, `DELETE /attendances`, `DELETE /daycare`, `DELETE /subjects` |
| `reports` | Module comptabilité complet | Tous les `/accounting/*` |

### Codes d'erreur HTTP

| Code | Signification |
|------|--------------|
| `200` | Succès (lecture/mise à jour) |
| `201` | Ressource créée |
| `204` | Succès sans contenu (logout) |
| `401` | Non authentifié (token absent/invalide) |
| `403` | Non autorisé (rôle/permission insuffisant) |
| `404` | Ressource introuvable |
| `422` | Erreur de validation — retourne `{ "errors": { "field": ["message"] } }` |
| `429` | Trop de requêtes (throttle) |
| `500` | Erreur serveur interne |
