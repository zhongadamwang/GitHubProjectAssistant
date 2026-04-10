<script setup>
/**
 * @component LoginView
 * @description Unauthenticated login form. Submits credentials to the API via
 * the authStore.login action. On success redirects to the page the user was
 * trying to access (from `route.query.redirect`) or to the dashboard.
 */
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/authStore.js'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')

async function handleSubmit() {
  if (loading.value) return
  errorMessage.value = ''
  loading.value = true
  try {
    await authStore.login(email.value, password.value)
    const redirect = route.query.redirect || '/'
    router.push(redirect)
  } catch (err) {
    if (err.response?.status === 401) {
      errorMessage.value = 'Invalid email or password.'
    } else {
      errorMessage.value = 'Login failed — please try again.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-wrapper">
    <div class="login-card">
      <h1>Scrum Master Tool</h1>
      <h2>Sign In</h2>

      <form @submit.prevent="handleSubmit" novalidate>
        <div class="field">
          <label for="email">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            autocomplete="email"
            placeholder="admin@example.com"
            required
          />
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            autocomplete="current-password"
            placeholder="••••••••"
            required
            @keydown.enter="handleSubmit"
          />
        </div>

        <p v-if="errorMessage" class="error-msg" role="alert">{{ errorMessage }}</p>

        <button type="submit" :disabled="loading" class="btn-primary">
          <span v-if="loading">Signing in…</span>
          <span v-else>Sign In</span>
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.login-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0f2f5;
}

.login-card {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  padding: 2.5rem 2rem;
  width: 100%;
  max-width: 380px;
}

h1 {
  font-size: 1.1rem;
  color: #555;
  margin: 0 0 0.25rem;
  text-align: center;
}

h2 {
  font-size: 1.6rem;
  margin: 0 0 1.5rem;
  text-align: center;
  color: #1a1a2e;
}

.field {
  margin-bottom: 1rem;
}

label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 0.35rem;
  color: #444;
}

input {
  width: 100%;
  padding: 0.6rem 0.75rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 0.95rem;
  box-sizing: border-box;
  transition: border-color 0.15s;
}

input:focus {
  outline: none;
  border-color: #4361ee;
}

.error-msg {
  color: #d62828;
  font-size: 0.85rem;
  margin: 0 0 0.75rem;
}

.btn-primary {
  width: 100%;
  padding: 0.7rem;
  background: #4361ee;
  color: #fff;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.15s;
  margin-top: 0.5rem;
}

.btn-primary:hover:not(:disabled) {
  background: #3451d1;
}

.btn-primary:disabled {
  background: #9aaaf5;
  cursor: not-allowed;
}
</style>
