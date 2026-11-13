/** Arrow keys move focus; Enter/Space retain native button/link activation. */
export function navigateSegments(event: KeyboardEvent) {
  if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return
  const container = event.currentTarget as HTMLElement
  const items = Array.from(
    container.querySelectorAll<HTMLElement>('button:not(:disabled), a[href]'),
  )
  const index = items.indexOf(event.target as HTMLElement)
  if (index < 0 || !items.length) return
  event.preventDefault()
  const next =
    event.key === 'Home'
      ? 0
      : event.key === 'End'
        ? items.length - 1
        : (index + (event.key === 'ArrowRight' ? 1 : -1) + items.length) % items.length
  items[next]?.focus()
}
