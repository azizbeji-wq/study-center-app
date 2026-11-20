import { test, expect } from '@playwright/test';

/**
 * Suite E2E : Paiements & Comptabilité
 * Règles @e2e-playwright :
 * 1. Locators robustes (getByRole, getByPlaceholder, getByText)
 * 2. Parcours critiques : Suivi des soldes impayés, Historique, Module Comptabilité
 * 3. Zéro waitForTimeout : assertions auto-retrying
 */

test.describe('Paiements & Module Comptabilité (@e2e-playwright)', () => {

  test.beforeEach(async ({ page }) => {
    await page.goto('/');
    // Connexion Directeur
    await page.getByRole('button', { name: /Directeur/i }).click();
    await expect(page.getByText(/Espace Directeur/i)).toBeVisible();
  });

  test('Navigation vers Paiements : Affichage des soldes à régler et statistiques', async ({ page }) => {
    // Clic sur l'onglet Paiements
    await page.getByRole('button', { name: /^Paiements$/i }).click();

    // Vérification de la présence des sous-onglets ou filtres de paiement
    await expect(page.getByRole('button', { name: /Soldes à régler|Impayés/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /Historique des paiements|Historique/i })).toBeVisible();
  });

  test('Bascule entre les soldes et l\'historique des encaissements', async ({ page }) => {
    await page.getByRole('button', { name: /^Paiements$/i }).click();

    // Clic sur l'onglet Historique
    const historyTab = page.getByRole('button', { name: /Historique des paiements|Historique/i });
    await historyTab.click();

    // Vérification que le tableau ou filtre d'historique s'affiche
    await expect(page.getByPlaceholder(/Rechercher par élève|Rechercher un paiement/i)).toBeVisible();
  });

  test('Navigation vers le module Comptabilité réservé au Directeur', async ({ page }) => {
    // Clic sur l'onglet Comptabilité
    await page.getByRole('button', { name: /Comptabilité/i }).click();

    // Vérification des indicateurs financiers (Dépenses, Recettes, Clôtures)
    await expect(page.getByRole('button', { name: /Ajouter une dépense|Nouvelle dépense/i })).toBeVisible();
    await expect(page.getByText(/Bilan financier|Vue d'ensemble|Total dépenses/i)).toBeVisible();
  });

});
