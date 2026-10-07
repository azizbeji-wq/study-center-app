import axios from 'axios';
import { checkSessionValidity, clearSession, getStoredToken } from './session';

const rawBaseUrl = ((import.meta as any).env?.VITE_API_URL || '').trim();
// Strip trailing slash and trailing '/api' since all components already use '/api/...'
const normalizedBaseUrl = rawBaseUrl.replace(/\/+$/, '').replace(/\/api$/, '');

const api = axios.create({
  baseURL: normalizedBaseUrl,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Interceptor to inject bearer token and verify session lifetime
api.interceptors.request.use((config) => {
  const isAuthRequest = config.url?.includes('/login') || config.url?.includes('/health');

  if (!isAuthRequest) {
    const status = checkSessionValidity();
    if (!status.isValid && (status.reason === 'EXPIRED_2H_OPEN' || status.reason === 'EXPIRED_1H_CLOSED')) {
      const msg = status.message || 'Votre session a expiré. Veuillez vous reconnecter.';
      clearSession(msg);
      window.location.reload();
      return Promise.reject(new Error(msg));
    }
  }

  const token = getStoredToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
}, (error) => {
  return Promise.reject(error);
});

// Interceptor to handle unauthorized errors
api.interceptors.response.use((response) => {
  return response;
}, (error) => {
  if (error.response && error.response.status === 401 && !error.config?.url?.includes('/login')) {
    clearSession('Votre session a expiré sur le serveur. Veuillez vous reconnecter.');
    window.location.reload();
  }
  return Promise.reject(error);
});

export default api;
