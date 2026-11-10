import { onMounted, onUnmounted, ref } from 'vue'

// Give the form placeholder a brief visible state even when defaults are local.
export function useCreateFormSkeleton(enabled = true) {
  const showing = ref(enabled)
  let timer: ReturnType<typeof setTimeout> | undefined

  onMounted(() => {
    if (enabled) timer = setTimeout(() => (showing.value = false), 220)
  })
  onUnmounted(() => clearTimeout(timer))

  return showing
}
