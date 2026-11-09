import { readonly, ref } from 'vue'

export type ToastType = 'success' | 'error' | 'warning' | 'info'

export interface ToastItem {
  id: number
  type: ToastType
  message: string
  paused: boolean
}

const DURATION = 4000
const MAX_VISIBLE = 3
const visible = ref<ToastItem[]>([])
const queue: ToastItem[] = []
const timers = new Map<number, ReturnType<typeof setTimeout>>()
const startedAt = new Map<number, number>()
const remaining = new Map<number, number>()
let nextId = 1
let leavingCount = 0

function startTimer(item: ToastItem, duration: number) {
  remaining.set(item.id, duration)
  startedAt.set(item.id, Date.now())
  timers.set(item.id, setTimeout(() => dismiss(item.id), duration))
}

function enqueue(type: ToastType, message: string) {
  const cleanMessage = message.trim()
  if (!cleanMessage) return
  if ([...visible.value, ...queue].some((item) => item.type === type && item.message === cleanMessage)) return

  const item: ToastItem = { id: nextId++, type, message: cleanMessage, paused: false }
  if (visible.value.length + leavingCount < MAX_VISIBLE) {
    visible.value = [...visible.value, item]
    startTimer(item, DURATION)
  } else {
    queue.push(item)
  }
}

function dismiss(id: number) {
  const timer = timers.get(id)
  if (timer) clearTimeout(timer)
  timers.delete(id)
  startedAt.delete(id)
  remaining.delete(id)
  const queuedIndex = queue.findIndex((item) => item.id === id)
  if (queuedIndex !== -1) queue.splice(queuedIndex, 1)
  if (visible.value.some((item) => item.id === id)) leavingCount++
  visible.value = visible.value.filter((item) => item.id !== id)
}

function pause(id: number) {
  const item = visible.value.find((toast) => toast.id === id)
  if (!item || item.paused) return
  const elapsed = Date.now() - (startedAt.get(id) ?? Date.now())
  const timeLeft = Math.max(0, (remaining.get(id) ?? DURATION) - elapsed)
  const timer = timers.get(id)
  if (timer) clearTimeout(timer)
  timers.delete(id)
  remaining.set(id, timeLeft)
  item.paused = true
}

function resume(id: number) {
  const item = visible.value.find((toast) => toast.id === id)
  if (!item || !item.paused) return
  item.paused = false
  startTimer(item, remaining.get(id) ?? DURATION)
}

function afterLeave() {
  leavingCount = Math.max(0, leavingCount - 1)
  while (visible.value.length + leavingCount < MAX_VISIBLE && queue.length > 0) {
    const item = queue.shift()!
    visible.value = [...visible.value, item]
    startTimer(item, DURATION)
  }
}

export const toastItems = readonly(visible)

export const toast = {
  success: (message: string) => enqueue('success', message),
  error: (message: string) => enqueue('error', message),
  warning: (message: string) => enqueue('warning', message),
  info: (message: string) => enqueue('info', message),
  dismiss,
  pause,
  resume,
  afterLeave,
  items: toastItems,
}
