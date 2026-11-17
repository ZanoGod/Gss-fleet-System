export function suggestedExpiryDate(issueDate: string, years: number): string {
  if (!issueDate || !Number.isInteger(Number(years)) || Number(years) < 1) return ''
  const [rawYear, rawMonth, rawDay] = issueDate.split('-')
  const year = Number(rawYear),
    month = Number(rawMonth),
    day = Number(rawDay)
  if (
    !Number.isInteger(year) ||
    !Number.isInteger(month) ||
    !Number.isInteger(day) ||
    month < 1 ||
    month > 12 ||
    day < 1 ||
    day > 31
  )
    return ''
  const targetYear = year + Number(years)
  const targetDay = Math.min(day, new Date(Date.UTC(targetYear, month, 0)).getUTCDate())
  return `${targetYear}-${String(month).padStart(2, '0')}-${String(targetDay).padStart(2, '0')}`
}

export function licenseStatus(
  expiryDate: string,
  today: string,
): 'Active' | 'Expiring Soon' | 'Expired' {
  const days = Math.floor(
    (Date.parse(`${expiryDate}T00:00:00Z`) - Date.parse(`${today}T00:00:00Z`)) / 86_400_000,
  )
  if (days < 0) return 'Expired'
  return days <= 30 ? 'Expiring Soon' : 'Active'
}
