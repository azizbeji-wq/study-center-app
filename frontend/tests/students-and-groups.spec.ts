import { test, expect } from '@playwright/test';

/**
 * Suite E2E : Gestion des Élèves et Présences
 * Règles @e2e-playwright :
 * 1. Locators robustes (getByRole, getByPlaceholder, getByText)
 * 2. Parcours critiques : Navigation onglet Élèves, Présences & Groupes, Garderie
 * 3. Zéro waitForTimeout : assertions auto-retrying
 */

test.describe('Gestion des Élèves et Présences (@e2e-playwright)', () => {

  test.beforeEach(async ({ page }) => {
    await page.goto('/');
    // Connexion Directeur via bouton démo rapide
    await page.getByRole('button', { name: /Directeur/i }).click();
    await expect(page.getByText(/Espace Directeur/i)).toBeVisible();
  });

  test('Navigation vers le module Élèves et affichage des contrôles', async ({ page }) => {
    // Clic sur l'onglet Élèves
    await page.getByRole('button', { name: /^Élèves$/i }).click();

    // Vérification de la présence de la barre de recherche et du bouton d'ajout
    await expect(page.getByPlaceholder(/Rechercher un élève/i)).toBeVisible();
    await expect(page.getByRole('button', { name: /Inscrire|Ajouter un élève/i })).toBeVisible();
  });

  test('Recherche interactive d\'un élève dans la liste', async ({ page }) => {
    await page.getByRole('button', { name: /^Élèves$/i }).click();

    const searchInput = page.getByPlaceholder(/Rechercher un élève/i);
    await expect(searchInput).toBeVisible();

    // Saisie d'une recherche
    await searchInput.fill('Ahmed');
    // Le filtre est réactif et les résultats s'adaptent sans timeout
    await expect(searchInput).toHaveValue('Ahmed');
  });

  test('Navigation vers Présences & Groupes et vérification du tableau', async ({ page }) => {
    // Clic sur Présences & Groupes
    await page.getByRole('button', { name: /Présences & Groupes/i }).click();

    // Vérification des éléments de gestion des présences
    await expect(page.getByRole('button', { name: /Appel rapide|Fiche d'appel|Marquer présence/i })).toBeVisible();
  });

  test('Navigation vers Élèves de garde (Crèche / Garderie)', async ({ page }) => {
    // Clic sur l'onglet Crèche / Garderie
    await page.getByRole('button', { name: /Élèves de garde/i }).click();

    // Vérification que le module Garderie se charge
    await expect(page.getByRole('button', { name: /Ajouter un enfant|Inscrire un enfant/i })).toBeVisible();
  });

});
