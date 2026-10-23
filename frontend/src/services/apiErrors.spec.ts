import { AxiosError, type AxiosResponse } from 'axios'
import { describe, expect, it } from 'vitest'
import { API_ERROR_MESSAGES, getApiErrorCategory, getApiErrorMessage } from './apiErrors'

function responseError(status: number, data: unknown): AxiosError {
  const response = { status, data, headers: {}, config: {}, statusText: '' } as AxiosResponse
  return { isAxiosError: true, response, config: {} } as AxiosError
}

describe('API error messages', () => {
  it('distinguishes unavailable database from generic server errors', () => {
    expect(getApiErrorMessage(responseError(503, { code: 'DATABASE_UNAVAILABLE' }))).toBe(API_ERROR_MESSAGES.database)
    expect(getApiErrorMessage(responseError(500, { message: 'SQL details and stack trace' }))).toBe(API_ERROR_MESSAGES.server)
  })

  it('shows invalid credential and field validation messages from the API', () => {
    expect(getApiErrorMessage(responseError(422, { errors: { login: ['Incorrect username or password.'] } }))).toBe(API_ERROR_MESSAGES.invalidCredentials)
    expect(getApiErrorMessage(responseError(422, { errors: { email: ['The email field is required.'] } }))).toBe('The email field is required.')
  })

  it('distinguishes a missing session from an expired existing session', () => {
    const error = responseError(401, { code: 'UNAUTHENTICATED' })
    expect(getApiErrorCategory(error)).toBe('unauthorized')
    expect(getApiErrorMessage(error)).toBe(API_ERROR_MESSAGES.unauthorized)
    expect(getApiErrorMessage(error, { existingSession: true })).toBe(API_ERROR_MESSAGES.sessionExpired)
  })

  it('normalizes offline and csrf errors', () => {
    expect(getApiErrorMessage(new AxiosError('Network Error'))).toBe(API_ERROR_MESSAGES.offline)
    expect(getApiErrorMessage(responseError(419, { code: 'CSRF_INVALID' }))).toBe(API_ERROR_MESSAGES.csrf)
  })
})
