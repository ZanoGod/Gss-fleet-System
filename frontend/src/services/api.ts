import axios from 'axios';
import { getApiErrorMessage } from './apiErrors';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '',
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
});

api.interceptors.response.use(
  response => response,
  (error: unknown) => {
    if (axios.isAxiosError(error)) {
      error.userMessage = getApiErrorMessage(error);
    }
    return Promise.reject(error);
  },
);

export default api;