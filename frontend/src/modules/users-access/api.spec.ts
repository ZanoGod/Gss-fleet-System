import { describe, expect, it } from 'vitest'
import { normalizeUserPage } from './api'

const row = {
  id: 8, name: 'Test User', username: 'test-user', email: 'test@example.test', role_id: 1,
  role: { id: 1, name: 'Administrator', slug: 'administrator' }, is_active: true, approved_at: '2026-11-17T00:00:00Z',
  created_at: '2026-11-17T00:00:00Z', updated_at: '2026-11-17T00:00:00Z',
}

describe('normalizeUserPage', () => {
  it('reads Laravel paginator fields at the response root', () => {
    expect(normalizeUserPage({ data: [row], current_page: 2, last_page: 4, per_page: 10, total: 34 }, 1)).toEqual({
      data: [row], meta: { current_page: 2, last_page: 4, per_page: 10, total: 34 },
    })
  })

  it('continues to read Laravel resource collection metadata', () => {
    expect(normalizeUserPage({ data: [row], meta: { current_page: 3, last_page: 5, per_page: 10, total: 48 } })).toEqual({
      data: [row], meta: { current_page: 3, last_page: 5, per_page: 10, total: 48 },
    })
  })

  it('returns an empty, valid page when the response has no records', () => {
    expect(normalizeUserPage({ data: [], current_page: 1, last_page: 1, per_page: 10, total: 0 })).toEqual({
      data: [], meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
    })
  })
})
