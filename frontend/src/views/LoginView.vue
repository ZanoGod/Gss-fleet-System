<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authError } from '@/services/auth'
import { API_ERROR_MESSAGES } from '@/services/apiErrors'
import { useAuthStore } from '@/stores/auth'
const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const username = ref('')
const password = ref('')
const showPassword = ref(false)
const submitting = ref(false)
const error = ref(route.query.reason === 'expired' ? API_ERROR_MESSAGES.sessionExpired : route.query.reason === 'database' ? API_ERROR_MESSAGES.database : route.query.reason === 'server' ? API_ERROR_MESSAGES.server : route.query.reason === 'unavailable' ? API_ERROR_MESSAGES.offline : '')
async function signIn() {
  error.value = ''
  submitting.value = true
  try {
    await auth.login(username.value.trim(), password.value)
    await router.replace('/dashboard')
  } catch (e) {
    error.value = authError(e)
  } finally { submitting.value = false }
}
</script>
<template>
  <main class="login-page">
    <div class="login-card">
      <div class="login-brand">
        <div class="brand-mark">GSS</div>
        <h1>Fleet Management</h1>
        <p>Sign in to the admin panel</p>
      </div>
      <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
      <form @submit.prevent="signIn">
        <div class="mb-4">
          <label for="username" class="field-label">Username or email</label>
          <div class="input-wrap"><i class="bi bi-person input-icon" aria-hidden="true"></i>
            <input id="username" v-model="username" class="input-modern" name="username" type="text" placeholder="Enter your username or email" autocomplete="username" required autofocus />
          </div>
        </div>
        <div class="mb-4">
          <label for="password" class="field-label">Password</label>
          <div class="input-wrap"><i class="bi bi-lock input-icon" aria-hidden="true"></i>
            <input id="password" v-model="password" class="input-modern" name="password" :type="showPassword ? 'text' : 'password'" placeholder="Enter your password" autocomplete="current-password" required />
            <button type="button" class="toggle-password" :aria-label="showPassword ? 'Hide password' : 'Show password'" @click="showPassword = !showPassword"><i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i></button>
          </div>
        </div>
        <button class="btn-gold login-submit" :disabled="submitting" type="submit"><span v-if="submitting" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>{{ submitting ? 'Signing in…' : 'Secure Login' }}</button>
      </form>
    </div>
  </main>
</template>
