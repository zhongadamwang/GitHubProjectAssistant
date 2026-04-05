<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/authStore.js'
import * as api from '../services/api.js'

const authStore = useAuthStore()

const users = ref([])
const loading = ref(false)
const formLoading = ref(false)
const formError = ref(null)
const formSuccess = ref(false)

const form = ref({
  email: '',
  display_name: '',
  password: '',
  role: 'member',
  github_username: '',
})

const fieldErrors = ref({})

function validateForm() {
  const errs = {}
  if (!form.value.email) errs.email = 'Required.'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) errs.email = 'Invalid email format.'
  if (!form.value.display_name) errs.display_name = 'Required.'
  if (!form.value.password) errs.password = 'Required.'
  else if (form.value.password.length < 8) errs.password = 'Minimum 8 characters.'
  return errs
}

async function fetchUsers() {
  loading.value = true
  try {
    const data = await api.getUsers()
    users.value = data.users ?? data
  } finally {
    loading.value = false
  }
}

async function handleCreate() {
  formError.value = null
  formSuccess.value = false
  fieldErrors.value = validateForm()
  if (Object.keys(fieldErrors.value).length) return

  formLoading.value = true
  try {
    const created = await api.createUser({ ...form.value })
    users.value.push(created.user ?? created)
    formSuccess.value = true
    form.value = { email: '', display_name: '', password: '', role: 'member', github_username: '' }
  } catch (err) {
    if (err.response?.status === 409) {
      formError.value = 'Email already exists.'
    } else if (err.response?.status === 422) {
      formError.value = err.response?.data?.error ?? 'Validation error.'
    } else {
      formError.value = 'Failed to create user — please try again.'
    }
  } finally {
    formLoading.value = false
    if (formSuccess.value) setTimeout(() => { formSuccess.value = false }, 3000)
  }
}

onMounted(fetchUsers)
</script>

<template>
  <div class="page">
    <header class="page-header">
      <h1>User Management</h1>
    </header>

    <div v-if="loading" class="loading-bar" />

    <!-- Users table -->
    <section class="card">
      <h2>Users</h2>
      <table v-if="users.length">
        <thead>
          <tr>
            <th>Email</th>
            <th>Display Name</th>
            <th>Role</th>
            <th>GitHub Username</th>
            <th>Last Login</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id">
            <td>{{ u.email }}</td>
            <td>{{ u.display_name }}</td>
            <td>
              <span class="role-badge" :class="u.role">{{ u.role }}</span>
            </td>
            <td>{{ u.github_username ?? '—' }}</td>
            <td>{{ u.last_login_at ? new Date(u.last_login_at).toLocaleString() : 'Never' }}</td>
          </tr>
        </tbody>
      </table>
      <p v-else class="empty">No users found.</p>
    </section>

    <!-- Add User form -->
    <section class="card">
      <h2>Add User</h2>

      <div v-if="formSuccess" class="msg-banner success">User created successfully.</div>
      <div v-if="formError" class="msg-banner error">{{ formError }}</div>

      <form @submit.prevent="handleCreate" novalidate>
        <div class="field" :class="{ invalid: fieldErrors.email }">
          <label>Email *</label>
          <input v-model="form.email" type="email" />
          <span v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email }}</span>
        </div>
        <div class="field" :class="{ invalid: fieldErrors.display_name }">
          <label>Display Name *</label>
          <input v-model="form.display_name" type="text" />
          <span v-if="fieldErrors.display_name" class="field-error">{{ fieldErrors.display_name }}</span>
        </div>
        <div class="field" :class="{ invalid: fieldErrors.password }">
          <label>Password *</label>
          <input v-model="form.password" type="password" />
          <span v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</span>
        </div>
        <div class="field">
          <label>Role</label>
          <select v-model="form.role">
            <option value="member">Member</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="field">
          <label>GitHub Username</label>
          <input v-model="form.github_username" type="text" />
        </div>
        <button type="submit" :disabled="formLoading" class="btn-primary">
          <span v-if="formLoading">Creating…</span>
          <span v-else>Create User</span>
        </button>
      </form>
    </section>
  </div>
</template>

<style scoped>
.page { padding: 1.5rem; max-width: 900px; margin: 0 auto; }
.page-header { margin-bottom: 1.25rem; }
h1 { margin: 0; font-size: 1.4rem; color: #1a1a2e; }
h2 { font-size: 1rem; margin: 0 0 1rem; color: #333; }
.loading-bar { height: 3px; background: repeating-linear-gradient(90deg, #4361ee 0%, #a8b8ff 50%, #4361ee 100%); background-size: 200%; animation: slide 1.2s linear infinite; border-radius: 2px; margin-bottom: 0.75rem; }
@keyframes slide { from { background-position: 0 } to { background-position: 200% } }
.card { background: #fff; border-radius: 8px; box-shadow: 0 1px 6px rgba(0,0,0,.08); padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; }
table { width: 100%; border-collapse: collapse; }
th { padding: 0.6rem 0.75rem; text-align: left; font-size: 0.82rem; font-weight: 700; color: #555; border-bottom: 2px solid #e9ecef; }
td { padding: 0.55rem 0.75rem; font-size: 0.88rem; border-top: 1px solid #f1f1f1; }
.role-badge { font-size: 0.75rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 999px; text-transform: capitalize; }
.role-badge.admin  { background: #cce5ff; color: #004085; }
.role-badge.member { background: #e9ecef; color: #495057; }
.empty { text-align: center; color: #888; padding: 2rem; }
.msg-banner { padding: 0.6rem 1rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.9rem; }
.msg-banner.success { background: #d4edda; color: #155724; }
.msg-banner.error   { background: #f8d7da; color: #721c24; }
form { display: flex; flex-direction: column; gap: 0.75rem; max-width: 480px; }
.field { display: flex; flex-direction: column; gap: 0.25rem; }
.field label { font-size: 0.85rem; font-weight: 600; color: #444; }
.field input, .field select { padding: 0.5rem 0.65rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9rem; }
.field.invalid input, .field.invalid select { border-color: #dc3545; }
.field-error { font-size: 0.78rem; color: #dc3545; }
.btn-primary { padding: 0.6rem 1.25rem; background: #4361ee; color: #fff; border: none; border-radius: 4px; font-size: 0.95rem; cursor: pointer; align-self: flex-start; margin-top: 0.25rem; }
.btn-primary:hover:not(:disabled) { background: #3451d1; }
.btn-primary:disabled { background: #9aaaf5; cursor: not-allowed; }
</style>
