import { describe, expect, it } from 'vitest'
import { licenseStatus, suggestedExpiryDate } from './terms'

describe('car license terms', () => {
  it('suggests a calendar-year expiry and clamps leap days', () => {
    expect(suggestedExpiryDate('2026-11-16', 3)).toBe('2029-11-16')
    expect(suggestedExpiryDate('2024-02-29', 1)).toBe('2025-02-28')
  })

  it('classifies expiration at the 30-day boundary', () => {
    expect(licenseStatus('2026-12-16', '2026-11-16')).toBe('Expiring Soon')
    expect(licenseStatus('2026-12-17', '2026-11-16')).toBe('Active')
    expect(licenseStatus('2026-11-15', '2026-11-16')).toBe('Expired')
  })
})
