// src/app/utils/session.ts

export const SESSION_KEYS = {
  TOKEN: 'token',
  USER: 'user',
  LOGIN_TIME: 'login_time',
  LAST_HEARTBEAT: 'last_heartbeat',
  NOTICE: 'session_notice',
} as const;

// 1 heure en millisecondes : délai d'expiration si le site est fermé / rouvert depuis la dernière connexion
export const TIMEOUT_CLOSED_MS = 60 * 60 * 1000; // 1h (3600s)

// 2 heures en millisecondes : délai maximal si le site reste ouvert en continu dans le navigateur
export const TIMEOUT_OPEN_MS = 2 * 60 * 60 * 1000; // 2h (7200s)

// Seuil d'interruption du heartbeat (ex: 2 minutes pour tolérer les micro-pauses ou onglets en arrière-plan)
// Au-delà de ce seuil sans heartbeat, on considère que le site a été fermé / mis en veille
export const HEARTBEAT_GAP_THRESHOLD_MS = 2 * 60 * 1000; // 2 minutes

export interface SessionStatus {
  isValid: boolean;
  reason?: 'EXPIRED_2H_OPEN' | 'EXPIRED_1H_CLOSED' | 'NO_TOKEN' | 'NO_TIMESTAMP';
  message?: string;
}

/**
 * Enregistre une nouvelle session après une authentification réussie
 */
export function setSession(token: string, user: any, loginTimeMs?: number): void {
  const now = Date.now();
  const actualLoginTime = loginTimeMs && loginTimeMs > 0 ? loginTimeMs : now;

  localStorage.setItem(SESSION_KEYS.TOKEN, token);
  localStorage.setItem(SESSION_KEYS.USER, typeof user === 'string' ? user : JSON.stringify(user));
  localStorage.setItem(SESSION_KEYS.LOGIN_TIME, String(actualLoginTime));
  localStorage.setItem(SESSION_KEYS.LAST_HEARTBEAT, String(now));
}

/**
 * Supprime la session actuelle et enregistre optionnellement un message d'information
 */
export function clearSession(noticeMessage?: string): void {
  localStorage.removeItem(SESSION_KEYS.TOKEN);
  localStorage.removeItem(SESSION_KEYS.USER);
  localStorage.removeItem(SESSION_KEYS.LOGIN_TIME);
  localStorage.removeItem(SESSION_KEYS.LAST_HEARTBEAT);

  if (noticeMessage) {
    sessionStorage.setItem(SESSION_KEYS.NOTICE, noticeMessage);
  }
}

/**
 * Récupère et supprime le message d'information stocké en sessionStorage (consommation unique)
 */
export function consumeNotice(): string | null {
  const notice = sessionStorage.getItem(SESSION_KEYS.NOTICE);
  if (notice) {
    sessionStorage.removeItem(SESSION_KEYS.NOTICE);
  }
  return notice;
}

/**
 * Définit un message d'information temporaire
 */
export function setNotice(noticeMessage: string): void {
  sessionStorage.setItem(SESSION_KEYS.NOTICE, noticeMessage);
}

/**
 * Récupère le token d'authentification actuel
 */
export function getStoredToken(): string | null {
  return localStorage.getItem(SESSION_KEYS.TOKEN);
}

/**
 * Récupère l'utilisateur stocké
 */
export function getStoredUser(): any | null {
  const userStr = localStorage.getItem(SESSION_KEYS.USER);
  if (!userStr) return null;
  try {
    return JSON.parse(userStr);
  } catch {
    return null;
  }
}

/**
 * Met à jour le timestamp de présence active dans le navigateur
 */
export function recordHeartbeat(): void {
  localStorage.setItem(SESSION_KEYS.LAST_HEARTBEAT, String(Date.now()));
}

/**
 * Vérifie la validité de la session selon les 2 règles :
 * 1. Expiration après 1h depuis la dernière connexion si le site a été fermé / rouvert.
 * 2. Déconnexion automatique après 2h si le site reste ouvert dans le navigateur.
 */
export function checkSessionValidity(): SessionStatus {
  const token = localStorage.getItem(SESSION_KEYS.TOKEN);
  const userStr = localStorage.getItem(SESSION_KEYS.USER);

  if (!token || !userStr) {
    return { isValid: false, reason: 'NO_TOKEN' };
  }

  const loginTimeStr = localStorage.getItem(SESSION_KEYS.LOGIN_TIME);
  const now = Date.now();

  // Si pas de timestamp de connexion, on l'initialise à l'instant présent pour éviter une déconnexion intempestive
  if (!loginTimeStr) {
    localStorage.setItem(SESSION_KEYS.LOGIN_TIME, String(now));
    localStorage.setItem(SESSION_KEYS.LAST_HEARTBEAT, String(now));
    return { isValid: true };
  }

  const loginTime = Number(loginTimeStr) || now;
  const lastHeartbeatStr = localStorage.getItem(SESSION_KEYS.LAST_HEARTBEAT);
  const lastHeartbeat = Number(lastHeartbeatStr) || loginTime;

  const totalElapsed = now - loginTime;
  const timeSinceLastHeartbeat = now - lastHeartbeat;

  // RÈGLE 2 : Si le site est resté ouvert dans le navigateur, déconnexion obligatoire après 2 heures
  if (totalElapsed >= TIMEOUT_OPEN_MS) {
    return {
      isValid: false,
      reason: 'EXPIRED_2H_OPEN',
      message: 'Votre session de 2 heures a expiré. Veuillez vous reconnecter.',
    };
  }

  // RÈGLE 1 : Si le site a été fermé ou mis en veille (heartbeat interrompu > 2 minutes)
  // ET qu'il s'est écoulé au moins 1 heure depuis la connexion
  if (timeSinceLastHeartbeat > HEARTBEAT_GAP_THRESHOLD_MS && totalElapsed >= TIMEOUT_CLOSED_MS) {
    return {
      isValid: false,
      reason: 'EXPIRED_1H_CLOSED',
      message: "Votre session a expiré (plus d'1 heure depuis votre dernière connexion). Veuillez vous reconnecter.",
    };
  }

  // Session valide : on rafraîchit le heartbeat
  recordHeartbeat();
  return { isValid: true };
}

/**
 * Lance le moniteur d'activité et de session en tâche de fond.
 * Vérifie périodiquement la validité de la session et écoute les événements de navigation.
 * Retourne une fonction de nettoyage pour stopper le moniteur.
 */
export function startSessionMonitor(onExpired: (message: string) => void): () => void {
  let isRunning = true;
  let lastThrottleActivity = 0;

  const performCheck = () => {
    if (!isRunning) return;
    const status = checkSessionValidity();
    if (!status.isValid) {
      if (status.reason === 'EXPIRED_2H_OPEN' || status.reason === 'EXPIRED_1H_CLOSED') {
        const msg = status.message || 'Session expirée. Veuillez vous reconnecter.';
        clearSession(msg);
        onExpired(msg);
      }
    }
  };

  // 1. Vérification périodique toutes les 5 secondes
  const intervalId = window.setInterval(performCheck, 5000);

  // 2. Vérification immédiate lors du retour sur l'onglet (sortie de veille / switch d'onglet)
  const handleVisibilityChange = () => {
    if (document.visibilityState === 'visible') {
      performCheck();
    }
  };
  document.addEventListener('visibilitychange', handleVisibilityChange);

  // 3. Mise à jour du heartbeat lors des interactions utilisateur (throttled à 10s)
  const handleUserActivity = () => {
    const now = Date.now();
    if (now - lastThrottleActivity > 10000) {
      lastThrottleActivity = now;
      recordHeartbeat();
    }
  };

  window.addEventListener('click', handleUserActivity, { passive: true });
  window.addEventListener('keydown', handleUserActivity, { passive: true });
  window.addEventListener('touchstart', handleUserActivity, { passive: true });

  // 4. Synchronisation multi-onglets : si un autre onglet s'est déconnecté
  const handleStorageChange = (e: StorageEvent) => {
    if (e.key === SESSION_KEYS.TOKEN && !e.newValue) {
      onExpired('Déconnexion effectuée.');
    }
  };
  window.addEventListener('storage', handleStorageChange);

  // Fonction de nettoyage
  return () => {
    isRunning = false;
    window.clearInterval(intervalId);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    window.removeEventListener('click', handleUserActivity);
    window.removeEventListener('keydown', handleUserActivity);
    window.removeEventListener('touchstart', handleUserActivity);
    window.removeEventListener('storage', handleStorageChange);
  };
}
