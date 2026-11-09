import { reactive } from 'vue'

export function usePendingAction() {
  const active = reactive(new Set<string>())
  const pending = (key: string) => active.has(key)

  async function run(key: string, action: () => Promise<void>) {
    if (pending(key)) return
    active.add(key)
    try {
      await action()
    } finally {
      active.delete(key)
    }
  }

  return { pending, run }
}
