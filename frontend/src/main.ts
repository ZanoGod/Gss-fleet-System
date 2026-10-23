import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap-icons/font/bootstrap-icons.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import api from '@/services/api'
import { getApiErrorMessage } from '@/services/apiErrors'
import { useAuthStore } from '@/stores/auth'

const app = createApp(App)
app.use(createPinia())
const auth = useAuthStore()
api.interceptors.response.use(response => response, async error => {
  if (error.response?.status === 401 && auth.isAuthenticated && !['/login', '/logout'].includes(error.config?.url)) {
    auth.clearAuth()
    await router.replace({ path: '/login', query: { reason: 'expired' } })
  }
  return Promise.reject(error)
})
app.use(router)
void router.isReady().then(() => app.mount('#app'))
// Renew only in response to actual interaction, never an unattended polling loop.
let lastCheck = 0
for (const event of ['pointerdown', 'keydown']) {
  window.addEventListener(event, event => {
    if (!event.isTrusted || !auth.isAuthenticated || document.hidden || Date.now() - lastCheck < 60000) return
    lastCheck = Date.now()
    void auth.getUser().catch(() => { /* A transient outage does not claim logout. */ })
  }, { passive: true })
}
