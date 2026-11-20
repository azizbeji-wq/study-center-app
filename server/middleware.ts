import type { NextFunction, Request, Response } from 'express';
import { hasPermission } from './helpers';
import { store, UserRecord } from './store';

export interface AuthedRequest extends Request {
  user?: UserRecord;
  token?: string;
}

export function authRequired(req: AuthedRequest, res: Response, next: NextFunction) {
  const header = req.headers.authorization || '';
  const token = header.startsWith('Bearer ') ? header.slice(7).trim() : '';
  if (!token) {
    return res.status(401).json({ message: 'Non authentifié.' });
  }

  const record = store.tokens.get(token);
  if (!record || record.expiresAt < Date.now()) {
    if (record) store.tokens.delete(token);
    return res.status(401).json({ message: 'Session expirée ou invalide.' });
  }

  const user = store.users.find((u) => u.id === record.userId);
  if (!user || !user.is_active) {
    return res.status(401).json({ message: 'Compte introuvable ou inactif.' });
  }

  req.user = user;
  req.token = token;
  next();
}

export function requireRole(...roles: string[]) {
  return (req: AuthedRequest, res: Response, next: NextFunction) => {
    if (!req.user || !roles.includes(req.user.role)) {
      return res.status(403).json({ message: 'Accès non autorisé pour ce rôle.' });
    }
    next();
  };
}

export function requirePermission(perm: string) {
  return (req: AuthedRequest, res: Response, next: NextFunction) => {
    if (!req.user || !hasPermission(req.user, perm)) {
      return res.status(403).json({ message: `Permission requise : ${perm}` });
    }
    next();
  };
}
