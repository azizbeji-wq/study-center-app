-- ==============================================================================
-- SCRIPT SQL : NETTOYAGE DES DONNÉES DE TEST DU CENTRE (STUDY CENTER)
-- OBJECTIF   : Vider toutes les données opérationnelles (élèves, profs, paiements...)
-- GARANTIE   : Préserve STRICTEMENT les comptes de connexion (users, tokens, sessions)
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Clôtures comptables & dépenses réelles
TRUNCATE TABLE `monthly_closings`;
TRUNCATE TABLE `expenses`;

-- 2. Journal d'audit et logs d'activités
TRUNCATE TABLE `payment_audit_logs`;
TRUNCATE TABLE `activity_logs`;

-- 3. Paiements et assiduité
TRUNCATE TABLE `payments`;
TRUNCATE TABLE `attendances`;

-- 4. Inscriptions et emplois du temps
TRUNCATE TABLE `student_subject_enrollments`;
TRUNCATE TABLE `schedule_items`;
TRUNCATE TABLE `school_calendar_imports`;

-- 5. Élèves et Garderie
TRUNCATE TABLE `students`;
TRUNCATE TABLE `daycare_children`;

-- 6. Enseignants et associations de matières
TRUNCATE TABLE `teacher_subject`;
TRUNCATE TABLE `teachers`;

-- Optionnel : Catalogues (décommenter si vous souhaitez aussi vider les salles, matières, écoles)
-- TRUNCATE TABLE `school_classes`;
-- TRUNCATE TABLE `schools`;
-- TRUNCATE TABLE `subjects`;
-- TRUNCATE TABLE `rooms`;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- TABLES STRICTEMENT CONSERVÉES (NE JAMAIS VIDER) :
--  - `users` (Tous les comptes directeur, secrétaire, etc.)
--  - `personal_access_tokens` (Tokens d'authentification API / Sanctum)
--  - `sessions` / `password_reset_tokens`
--  - `migrations`
-- ==============================================================================
