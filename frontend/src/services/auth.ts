import axios from 'axios'
import api from './api'

export interface User {
  id: number
  name: string
  username?: string | null
  email: string
}

export async function login(identifier: string, password: string): Promise<User> {
  await api.get('/sanctum/csrf-cookie')
  const response = await api.post<{ user: User }>('/login', {
    login: identifier,
    password,
  })
  return response.data.user
}

export async function getUser(): Promise<User | null> {
  try {
    const response = await api.get<{ user: User }>('/api/user')
    return response.data.user
  } catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 401) return null
    throw error
  }
}

export async function logout(): Promise<void> {
  // A stale CSRF token is refreshed once; no other failed mutations are retried.
  try { await api.post('/logout') }
  catch (error) {
    if (axios.isAxiosError(error) && error.response?.status === 401) return
    if (!axios.isAxiosError(error) || error.response?.status !== 419) throw error
    await api.get('/sanctum/csrf-cookie')
    try { await api.post('/logout') }
    catch (retryError) {
      if (!axios.isAxiosError(retryError) || retryError.response?.status !== 401) throw retryError
    }
  }
}

export function authError(error: unknown): string {
  if (!axios.isAxiosError(error)) return 'Something went wrong. Please try again.'
  if (!error.response) return 'Cannot connect to the server. Please check your connection.'
  const status = error.response.status
  if (status === 401) return 'Incorrect username or password.'
  if (status === 419) return 'Your session has expired. Please sign in again.'
  if (status === 429) return 'Too many sign-in attempts. Please wait a minute and try again.'
  if (status === 422) {
    const messages = Object.values(error.response.data?.errors || {}).flat()
    return typeof messages[0] === 'string' ? messages[0] : 'Please check the entered details.'
  }
  return 'Something went wrong on the server. Please try again.'
}
