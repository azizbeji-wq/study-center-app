/**
 * Utilitaire d'impression multi-pages isolé pour application web (SPA).
 * Résout le problème de page blanche et le problème de tronquage à une seule page.
 */

export function printIsolatedElement(elementIdOrEl: string | HTMLElement, title = "Impression"): void {
  const el = typeof elementIdOrEl === 'string' ? document.getElementById(elementIdOrEl) : elementIdOrEl;

  if (!el) {
    console.warn(`[printIsolatedElement] Élément non trouvé:`, elementIdOrEl);
    window.print();
    return;
  }

  // 1. Récupérer ou créer l'iframe d'impression avec dimensions réelles mais en arrière-plan
  let iframe = document.getElementById('spa-isolated-print-iframe') as HTMLIFrameElement | null;
  if (!iframe) {
    iframe = document.createElement('iframe');
    iframe.id = 'spa-isolated-print-iframe';
    iframe.style.position = 'fixed';
    iframe.style.top = '0';
    iframe.style.left = '0';
    iframe.style.width = '1024px';
    iframe.style.height = '768px';
    iframe.style.border = '0';
    iframe.style.zIndex = '-99999';
    iframe.style.opacity = '0.01';
    iframe.style.pointerEvents = 'none';
    iframe.setAttribute('aria-hidden', 'true');
    document.body.appendChild(iframe);
  }

  const iframeDoc = iframe.contentWindow?.document;
  if (!iframeDoc || !iframe.contentWindow) {
    console.warn('[printIsolatedElement] Impossible d\'accéder à l\'iframe, repli sur window.print()');
    window.print();
    return;
  }

  // 2. Extraire toutes les feuilles de style de la page principale (Tailwind, classes custom)
  let stylesHtml = '';
  document.querySelectorAll('style, link[rel="stylesheet"]').forEach((node) => {
    stylesHtml += node.outerHTML;
  });

  // 3. Règles CSS d'impression strictes garantissant la visibilité totale et la pagination propre
  const customPrintStyles = `
    @page {
      size: A4 portrait;
      margin: 12mm 10mm 12mm 10mm;
    }
    *, *::before, *::after {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    html, body {
      background-color: #ffffff !important;
      background: #ffffff !important;
      color: #111827 !important;
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      height: auto !important;
      min-height: auto !important;
      overflow: visible !important;
      font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    /* FORCER la visibilité de TOUS les éléments à l'intérieur de l'iframe */
    html, body, body * {
      visibility: visible !important;
    }

    .print-content-wrapper {
      width: 100% !important;
      max-width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
      background: #ffffff !important;
      visibility: visible !important;
    }

    /* Masquer uniquement les boutons d'action d'impression s'il en reste */
    .no-print, .no-print *, [class*="print:hidden"] {
      display: none !important;
      visibility: hidden !important;
    }

    /* Empêcher les coupures au milieu d'un bloc ou tableau */
    .avoid-break {
      break-inside: avoid !important;
      page-break-inside: avoid !important;
    }
    .break-before-page {
      break-before: page !important;
      page-break-before: always !important;
    }
    h1, h2, h3, h4 {
      break-after: avoid !important;
      page-break-after: avoid !important;
    }
    table {
      width: 100% !important;
      border-collapse: collapse !important;
      page-break-inside: auto;
    }
    tr {
      break-inside: avoid !important;
      page-break-inside: avoid !important;
    }
    thead {
      display: table-header-group !important;
    }
    tfoot {
      display: table-footer-group !important;
    }
  `;

  // 4. Utiliser outerHTML pour conserver l'ID d'origine (ex: #financial-report-printable)
  const contentHtml = el.outerHTML;

  // 5. Injecter le document HTML autonome dans l'iframe
  iframeDoc.open();
  iframeDoc.write(`<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>${title}</title>
  ${stylesHtml}
  <style>
    ${customPrintStyles}
  </style>
</head>
<body>
  <div class="print-content-wrapper">
    ${contentHtml}
  </div>
</body>
</html>`);
  iframeDoc.close();

  // 6. Attendre que le navigateur compose la page (layout + fonts) avant d'ouvrir la boîte d'impression
  setTimeout(() => {
    try {
      iframe.contentWindow?.focus();
      iframe.contentWindow?.print();
    } catch (err) {
      console.error('[printIsolatedElement] Erreur impression iframe:', err);
      window.print();
    }
  }, 400);
}
