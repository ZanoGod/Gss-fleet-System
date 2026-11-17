<script setup lang="ts">
import BaseLoading from '@/components/common/BaseLoading.vue'
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { toast } from '@/services/toast'
const router = useRouter()
const busy = ref(false),
  error = ref(''),
  success = ref('')
const form = reactive({
  name: '',
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
})
async function register() {
  if (busy.value) return
  busy.value = true
  error.value = ''
  success.value = ''
  try {
    await api.get('/sanctum/csrf-cookie')
    const response = await api.post<{ message?: string }>('/register', form)
    success.value = response.data.message ?? 'Registration successful. Your account is pending administrator approval.'
    await router.replace({ path: '/login', query: { registered: '1' } })
  } catch (e: any) {
    const errors = e?.response?.data?.errors
    error.value = errors
      ? Object.values(errors).flat().join(' ')
      : 'Unable to register. Please try again.'
    toast.error(error.value)
  } finally {
    busy.value = false
  }
}
</script>
<template>
  <main class="login-page">
    <div class="login-card">
      <div class="login-brand">
        <div class="brand-mark">GSS</div>
        <h1>Create account</h1>
        <p>Register for Fleet Management</p>
      </div>
      <div v-if="success" class="alert alert-success" role="status">{{ success }}</div>
      <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
      <form @submit.prevent="register">
        <div
          v-for="field in [
            {
              key: 'name',
              label: 'Full name',
              type: 'text',
              autocomplete: 'name',
              icon: 'bi-person',
            },
            {
              key: 'username',
              label: 'Username',
              type: 'text',
              autocomplete: 'username',
              icon: 'bi-person-badge',
            },
            {
              key: 'email',
              label: 'Email',
              type: 'email',
              autocomplete: 'email',
              icon: 'bi-envelope',
            },
            {
              key: 'password',
              label: 'Password (at least 6 characters)',
              type: 'password',
              autocomplete: 'new-password',
              icon: 'bi-lock',
            },
            {
              key: 'password_confirmation',
              label: 'Confirm password',
              type: 'password',
              autocomplete: 'new-password',
              icon: 'bi-lock',
            },
          ]"
          :key="field.key"
          class="mb-3"
        >
          <label :for="field.key" class="field-label">{{ field.label }}</label
          ><div class="input-wrap">
            <i :class="['bi', field.icon, 'input-icon']" aria-hidden="true"></i>
            <input
              :id="field.key"
              v-model="form[field.key as keyof typeof form]"
              class="input-modern"
              :type="field.type"
              :autocomplete="field.autocomplete"
              required
            />
          </div>
        </div>
        <button class="btn-gold login-submit" type="submit" :disabled="busy || !!success" :aria-busy="busy">
          <BaseLoading v-if="busy" label="Registering…" /><template v-else>Register</template>
        </button>
      </form>
      <p class="mt-3 text-center">
        Already registered? <RouterLink to="/login">Sign in</RouterLink>
      </p>
    </div>
  </main>
</template>
