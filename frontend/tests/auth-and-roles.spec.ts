import { test, expect } from '@playwright/test';

/**
 * Suite E2E : Authentification & Contrôle d'accès par Rôle
 * Règles @e2e-playwright :
 * 1. Locators robustes (getByRole, getByPlaceholder, getByText)
 * 2. Parcours critiques : Login, Logout, Rôles (Directeur, Secrétaire, Enseignant), Mauvais mdp
 * 3. Zéro waitForTimeout : assertions auto-retrying
 */

test.describe('Authentification & Gestion des Rôles (@e2e-playwright)', () => {

  test.beforeEach(async ({ page }) => {
    // Navigation vers la page d'accueil / login
    await page.goto('/');
  });

  test('Affichage initial de la page de connexion avec logo et formulaire', async ({ page }) => {
    // Vérification en-tête et logo
    await expect(page.getByRole('heading', { name: /Centre d'Études/i })).toBeVisible();
    await expect(page.getByText(/Direction Financière, Pédagogie & Administration/i)).toBeVisible();

    // Vérification des champs requis
    await expect(page.getByPlaceholder('nom@exemple.com')).toBeVisible();
    await expect(page.getByPlaceholder('••••••••')).toBeVisible();
    await expect(page.getByRole('button', { name: /se connecter/i })).toBeVisible();

    // Vérification de la présence des boutons de démonstration rapide
    await expect(page.getByRole('button', { name: /Directeur/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /Secrétaire/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /Enseignant/i })).toBeVisible();
  });

  test('Tentative de connexion avec identifiants erronés affiche un message d\'erreur', async ({ page }) => {
    await page.getByPlaceholder('nom@exemple.com').fill('inconnu@exemple.com');
    await page.getByPlaceholder('••••••••').fill('mauvaismotdepasse');
    await page.getByRole('button', { name: /se connecter/i }).click();

    // Assertion réactive sur le message d'erreur
    await expect(page.getByText(/introuvable|invalide|incorrect/i)).toBeVisible();
  });

  test('Connexion Directeur via formulaire classique puis Déconnexion', async ({ page }) => {
    await page.getByPlaceholder('nom@exemple.com').fill('director@studycenter.com');
    await page.getByPlaceholder('••••••••').fill('director123');
    await page.getByRole('button', { name: /se connecter/i }).click();

    // Vérification arrivée sur le Dashboard Directeur
    await expect(page.getByText(/Espace Directeur/i)).toBeVisible();
    await expect(page.getByRole('button', { name: /Tableau de bord/i })).toBeVisible();

    // Déconnexion
    const logoutBtn = page.getByRole('button', { name: /Déconnexion/i });
    await expect(logoutBtn).toBeVisible();
    await logoutBtn.click();

    // Retour sur la page de login
    await expect(page.getByRole('heading', { name: /Centre d'Études/i })).toBeVisible();
    await expect(page.getByPlaceholder('nom@exemple.com')).toBeVisible();
  });

  test('Connexion rapide 1-clic pour le rôle Secrétaire', async ({ page }) => {
    // Clic sur le bouton démo Secrétaire
    await page.getByRole('button', { name: /Secrétaire/i }).click();

    // Vérification de l'interface Secrétaire
    await expect(page.getByText(/Espace Secrétariat|Secrétaire/i)).toBeVisible();
    await expect(page.getByRole('button', { name: /Élèves/i })).toBeVisible();
  });

  test('Connexion rapide 1-clic pour le rôle Enseignant', async ({ page }) => {
    // Clic sur le bouton démo Enseignant
    await page.getByRole('button', { name: /Enseignant/i }).click();

    // Vérification de l'arrivée sur l'espace Enseignant
    await expect(page.getByText(/Espace Enseignant|Mes cours|Professeur/i)).toBeVisible();
  });

});
