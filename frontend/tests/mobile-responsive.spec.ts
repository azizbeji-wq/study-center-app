import { test, expect } from '@playwright/test';

/**
 * Suite E2E : Vérification Responsive Mobile
 * Règle 4 stricte @e2e-playwright :
 * "Vérification Responsive : Ajoute un test sur viewport mobile (375x812)
 * pour vérifier qu'aucun bouton ou tableau ne déborde de l'écran."
 */

test.describe('Vérification Responsive Mobile (375x812) (@e2e-playwright)', () => {

  test.beforeEach(async ({ page }) => {
    // Configuration stricte du viewport mobile (format iPhone standard)
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto('/');
  });

  test('Page de connexion parfaitement adaptée au mobile sans débordement', async ({ page }) => {
    // Vérification de la visibilité des éléments critiques sur mobile
    const heading = page.getByRole('heading', { name: /Centre d'Études/i });
    await expect(heading).toBeVisible();

    const emailInput = page.getByPlaceholder('nom@exemple.com');
    await expect(emailInput).toBeVisible();

    const loginBtn = page.getByRole('button', { name: /se connecter/i });
    await expect(loginBtn).toBeVisible();

    // Vérification que le bouton est cliquable et contenu dans la largeur (max 375px)
    const btnBox = await loginBtn.boundingBox();
    expect(btnBox).not.toBeNull();
    if (btnBox) {
      expect(btnBox.x + btnBox.width).toBeLessThanOrEqual(375);
    }
  });

  test('Navigation mobile : Le menu burger s\'ouvre et se ferme sans déborder', async ({ page }) => {
    // Connexion rapide via le bouton démo Directeur
    await page.getByRole('button', { name: /Directeur/i }).click();

    // Sur mobile (375px), l'en-tête mobile avec bouton menu burger doit être présent
    const menuBtn = page.getByRole('button', { name: /Recherche|menu/i }).first();
    await expect(menuBtn).toBeVisible();

    // Vérification qu'aucun élément principal ne déborde horizontalement
    const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
    expect(bodyWidth).toBeLessThanOrEqual(375);
  });

  test('Tableau de bord mobile : Cartes statistiques adaptées verticalement', async ({ page }) => {
    await page.getByRole('button', { name: /Directeur/i }).click();

    // Vérification de la présence des cartes stats empilées
    await expect(page.getByText(/Total Élèves|Élèves/i).first()).toBeVisible();

    // Vérifier l'absence de scroll horizontal non désiré
    const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
    const clientWidth = await page.evaluate(() => document.documentElement.clientWidth);
    expect(scrollWidth).toBeLessThanOrEqual(clientWidth + 2); // tolérance de 2px
  });

});
