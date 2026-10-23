import axios, { type AxiosError } from 'axios'

export const API_ERROR_MESSAGES = {
  database: 'Database connection unavailable. Please contact the administrator.',
  invalidCredentials: 'Incorrect username or password.',
  offline: 'Cannot connect to the server. Please try again later.',
  sessionExpired: 'Your session has expired. Please log in again.',
  csrf: 'Your session is invalid or expired. Please refresh and try again.',
  server: 'An unexpected server error occurred. Please try again later.',
  validation: 'Please check the entered details.',
  unauthorized: 'Authentication is required to continue.',
  forbidden: 'You are not authorized to perform this action.',
  notFound: 'The requested resource could not be found.',
  rateLimited: 'Too many requests. Please wait and try again.',
  request: 'The request could not be completed. Please try again.',
} as const

type ApiErrorPayload = {
  code?: string
  message?: string
  errors?: Record<string, string[] | string>
  error?: { code?: string; message?: string; errors?: Record<string, string[] | string> }
}

export type ApiErrorCategory = 'database' | 'offline' | 'session' | 'csrf' | 'validation' | 'server' | 'unauthorized' | 'forbidden' | 'not-found' | 'rate-limited' | 'request'

export function getApiErrorCode(error: unknown): string | undefined {
  if (!axios.isAxiosError<ApiErrorPayload>(error)) return undefined
  const payload = error.response?.data
  return payload?.code || payload?.error?.code
}

function payloadOf(error: AxiosError<ApiErrorPayload>): ApiErrorPayload | undefined {
  return error.response?.data
}

function firstValidationMessage(payload?: ApiErrorPayload): string | undefined {
  const errors = payload?.errors || payload?.error?.errors
  if (!errors) return undefined
  for (const value of Object.values(errors)) {
    const message = Array.isArray(value) ? value.find(item => typeof item === 'string' && item.trim()) : value
    if (typeof message === 'string' && message.trim()) return message
  }
  return undefined
}

export function getApiErrorCategory(error: unknown, options: { existingSession?: boolean } = {}): ApiErrorCategory {
  if (!axios.isAxiosError(error)) return 'server'
  if (!error.response) return 'offline'

  const status = error.response.status
  const code = getApiErrorCode(error)
  if (code === 'DATABASE_UNAVAILABLE') return 'database'
  if (status === 401) return options.existingSession ? 'session' : 'unauthorized'
  if (status === 419 || code === 'CSRF_INVALID') return 'csrf'
  if (status === 422 || code === 'VALIDATION_ERROR') return 'validation'
  if (status === 403) return 'forbidden'
  if (status === 404) return 'not-found'
  if (status === 429) return 'rate-limited'
  if (status >= 500) return 'server'
  return 'request'
}

export function getApiErrorMessage(error: unknown, options: { existingSession?: boolean } = {}): string {
  if (!axios.isAxiosError<ApiErrorPayload>(error)) return API_ERROR_MESSAGES.server
  if (!error.response) return API_ERROR_MESSAGES.offline

  const category = getApiErrorCategory(error, options)
  if (category === 'validation') return firstValidationMessage(payloadOf(error)) || API_ERROR_MESSAGES.validation

  return {
    database: API_ERROR_MESSAGES.database,
    offline: API_ERROR_MESSAGES.offline,
    session: API_ERROR_MESSAGES.sessionExpired,
    csrf: API_ERROR_MESSAGES.csrf,
    server: API_ERROR_MESSAGES.server,
    unauthorized: API_ERROR_MESSAGES.unauthorized,
    forbidden: API_ERROR_MESSAGES.forbidden,
    'not-found': API_ERROR_MESSAGES.notFound,
    'rate-limited': API_ERROR_MESSAGES.rateLimited,
    request: API_ERROR_MESSAGES.request,
  }[category]
}

declare module 'axios' {
  interface AxiosError<T = unknown, D = any> {
    userMessage?: string
  }
}
