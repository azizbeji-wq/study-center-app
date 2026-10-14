export const categoryColors: Record<string, string> = {
  loyer: '#4F46E5',        // Indigo
  électricité: '#F59E0B',  // Amber
  fournitures: '#10B981',  // Emerald
  entretien: '#06B6D4',    // Cyan
  publicité: '#EC4899',    // Pink
  internet_tel: '#8B5CF6', // Purple
  salaires: '#3B82F6',     // Blue
  autres: '#6B7280',       // Gray
};

export function getCategoryLabel(cat: string): string {
  const map: Record<string, string> = {
    loyer: 'Loyer',
    électricité: 'Électricité / Eau',
    fournitures: 'Fournitures & Copies',
    entretien: 'Entretien',
    publicité: 'Publicité & Marketing',
    internet_tel: 'Internet & Tel',
    salaires: 'Salaires Annexes',
    autres: 'Autres Dépenses',
  };
  return map[cat] || cat;
}

export function getCategoryBadgeClass(cat: string): string {
  const map: Record<string, string> = {
    loyer: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    électricité: 'bg-amber-50 text-amber-700 border-amber-200',
    fournitures: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    entretien: 'bg-cyan-50 text-cyan-700 border-cyan-200',
    publicité: 'bg-pink-50 text-pink-700 border-pink-200',
    internet_tel: 'bg-purple-50 text-purple-700 border-purple-200',
    salaires: 'bg-blue-50 text-blue-700 border-blue-200',
    autres: 'bg-gray-50 text-gray-700 border-gray-200',
  };
  return map[cat] || 'bg-gray-50 text-gray-700 border-gray-200';
}

export function formatAuditAction(act: string): string {
  const map: Record<string, string> = {
    created: 'Paiement Enregistré',
    updated: 'Modification',
    marked_paid: 'Validé Payé',
    deleted: 'Paiement Supprimé',
  };
  return map[act] || act;
}

export function getAuditActionBadge(act: string): string {
  const map: Record<string, string> = {
    created: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    updated: 'bg-blue-50 text-blue-700 border-blue-200',
    marked_paid: 'bg-purple-50 text-purple-700 border-purple-200',
    deleted: 'bg-red-50 text-red-700 border-red-200',
  };
  return map[act] || 'bg-gray-50 text-gray-700 border-gray-200';
}

export function formatLogDate(d: string): string {
  if (!d) return '—';
  try {
    const date = new Date(d);
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
  } catch {
    return d;
  }
}

export function formatDateTime(d: string): string {
  if (!d) return '—';
  try {
    const date = new Date(d);
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  } catch {
    return d;
  }
}
