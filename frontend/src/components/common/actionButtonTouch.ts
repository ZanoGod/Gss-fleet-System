// Some touch browsers defer :active until the tap ends. Start feedback on contact.
export function enableActionButtonTouchFeedback() {
  let pressed: Element | undefined
  let pointerId: number | undefined

  function clear(event?: PointerEvent) {
    if (event && event.pointerId !== pointerId) return
    pressed?.removeAttribute('data-touch-pressed')
    pressed = undefined
    pointerId = undefined
  }

  document.addEventListener('pointerdown', (event) => {
    if (event.pointerType !== 'touch' || !event.isPrimary || !(event.target instanceof Element))
      return
    clear()
    pressed = event.target.closest('.btn.gss-button:not(:disabled)') ?? undefined
    if (pressed) {
      pointerId = event.pointerId
      pressed.setAttribute('data-touch-pressed', '')
    }
  })
  document.addEventListener('pointerup', clear, true)
  document.addEventListener('pointercancel', clear, true)
  window.addEventListener('blur', () => clear())
}
