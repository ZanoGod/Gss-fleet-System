import axios from 'axios'
import api from './api'
import { getApiErrorMessage } from './apiErrors'

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
  return getApiErrorMessage(error)
}
