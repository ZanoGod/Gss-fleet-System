<script setup lang="ts">
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";
import api from "@/services/api";
import { useAuthStore } from "@/stores/auth";
const router = useRouter(),
  auth = useAuthStore(),
  busy = ref(false),
  error = ref("");
const form = reactive({
  name: "",
  username: "",
  email: "",
  password: "",
  password_confirmation: "",
});
async function register() {
  busy.value = true;
  error.value = "";
  try {
    await api.get("/sanctum/csrf-cookie");
    await api.post("/register", form);
    await auth.getUser();
    await router.replace("/dashboard");
  } catch (e: any) {
    const errors = e?.response?.data?.errors;
    error.value = errors
      ? Object.values(errors).flat().join(" ")
      : "Unable to register. Please try again.";
  } finally {
    busy.value = false;
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
      <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
      <form @submit.prevent="register">
        <div
          v-for="field in [
            { key: 'name', label: 'Full name', type: 'text', autocomplete: 'name' },
            { key: 'username', label: 'Username', type: 'text', autocomplete: 'username' },
            { key: 'email', label: 'Email', type: 'email', autocomplete: 'email' },
            {
              key: 'password',
              label: 'Password (at least 6 characters)',
              type: 'password',
              autocomplete: 'new-password',
            },
            {
              key: 'password_confirmation',
              label: 'Confirm password',
              type: 'password',
              autocomplete: 'new-password',
            },
          ]"
          :key="field.key"
          class="mb-3"
        >
          <label :for="field.key" class="field-label">{{ field.label }}</label
          ><input
            :id="field.key"
            v-model="form[field.key as keyof typeof form]"
            class="input-modern"
            :type="field.type"
            :autocomplete="field.autocomplete"
            required
          />
        </div>
        <button class="btn-gold login-submit" :disabled="busy">
          {{ busy ? "Creating account…" : "Register" }}
        </button>
      </form>
      <p class="mt-3 text-center">
        Already registered? <RouterLink to="/login">Sign in</RouterLink>
      </p>
    </div>
  </main>
</template>
