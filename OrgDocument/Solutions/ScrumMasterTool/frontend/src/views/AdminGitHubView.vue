<script setup>
/**
 * @component AdminGitHubView
 * @description Admin page for GitHub accounts: add an account (organization or user) with its
 * access token, test the connection, replace the token, delete unused accounts, import the legacy
 * .env settings and review the audit log. The token is write-only — it is sent to the server once
 * and never displayed again (only the last four characters are shown).
 */
import { ref, onMounted } from 'vue'
import AdminTabs from '../components/AdminTabs.vue'
import { useProjectStore } from '../stores/projectStore.js'
import * as api from '../services/api.js'

const projectStore = useProjectStore()

const accounts = ref([])
const entries = ref([])
const loading = ref(false)
const busyId = ref(null)
const message = ref(null)
const showAudit = ref(false)

// Form (create when editingId is null)
const editingId = ref(null)
const formOpen = ref(false)
const saving = ref(false)
const form = ref(emptyForm())
const fieldErrors = ref({})

function emptyForm() {
  return { label: '', owner_login: '', token: '', graphql_url: '' }
}

const flash = (type, text) => {
  message.value = { type, text }
}

function errorText(err, fallback) {
  const data = err.response?.data
  if (data?.errors) return Object.values(data.errors).join(' ')
  return data?.error ?? fallback
}

async function load() {
  loading.value = true
  try {
    accounts.value = (await api.getGitHubAccounts()).accounts ?? []
    projectStore.fetchProjects() // an import can add a tracked project
  } finally {
    loading.value = false
  }
}

async function loadAudit() {
  entries.value = (await api.getAuditLog()).entries ?? []
}

function openCreate() {
  editingId.value = null
  form.value = emptyForm()
  fieldErrors.value = {}
  formOpen.value = true
}

function openEdit(account) {
  editingId.value = account.id
  form.value = { label: account.label, owner_login: account.owner_login, token: '', graphql_url: account.graphql_url }
  fieldErrors.value = {}
  formOpen.value = true
}

function closeForm() {
  form.value = emptyForm() // never keep a token in memory longer than needed
  formOpen.value = false
}

function validate() {
  const errs = {}
  if (!form.value.label.trim()) errs.label = 'Required.'
  if (editingId.value === null) {
    if (!/^[A-Za-z0-9][A-Za-z0-9-]{0,38}$/.test(form.value.owner_login.trim())) {
      errs.owner_login = 'Enter the GitHub organization or user login.'
    }
    if (form.value.token.trim().length < 20) errs.token = 'Paste a GitHub access token.'
  } else if (form.value.token.trim() !== '' && form.value.token.trim().length < 20) {
    errs.token = 'That does not look like a GitHub access token.'
  }
  return errs
}

async function save() {
  message.value = null
  fieldErrors.value = validate()
  if (Object.keys(fieldErrors.value).length) return

  saving.value = true
  try {
    const payload = { label: form.value.label.trim() }
    if (form.value.token.trim()) payload.token = form.value.token.trim()
    if (form.value.graphql_url.trim()) payload.graphql_url = form.value.graphql_url.trim()

    if (editingId.value === null) {
      payload.owner_login = form.value.owner_login.trim()
      const { account } = await api.createGitHubAccount(payload)
      flash(account.status === 'ok' ? 'success' : 'error', `Account saved — connection status: ${account.status}. ${account.status_message ?? ''}`)
    } else {
      const { account } = await api.updateGitHubAccount(editingId.value, payload)
      flash('success', `Account updated — connection status: ${account.status}.`)
    }
    closeForm()
    await load()
  } catch (err) {
    const status = err.response?.status
    if (status === 409) flash('error', errorText(err, 'This account already exists.'))
    else if (status === 422) fieldErrors.value = err.response?.data?.errors ?? {}
    else if (status === 503) flash('error', errorText(err, 'The server cannot store tokens (APP_KEY missing).'))
    else flash('error', errorText(err, 'Saving the account failed.'))
  } finally {
    saving.value = false
    form.value.token = ''
  }
}

async function test(account) {
  busyId.value = account.id
  message.value = null
  try {
    const { result } = await api.testGitHubAccount(account.id)
    flash(result.status === 'ok' ? 'success' : 'error', `${account.label}: ${result.message}`)
    await load()
  } catch (err) {
    flash('error', errorText(err, 'The connection test failed.'))
  } finally {
    busyId.value = null
  }
}

async function remove(account) {
  if (!window.confirm(`Delete the account "${account.label}"? Its stored token is removed.`)) return
  busyId.value = account.id
  message.value = null
  try {
    await api.deleteGitHubAccount(account.id)
    flash('success', `Account "${account.label}" deleted.`)
    await load()
  } catch (err) {
    flash('error', errorText(err, 'Deleting the account failed.'))
  } finally {
    busyId.value = null
  }
}

async function importEnv() {
  message.value = null
  try {
    const result = await api.importEnvAccount()
    flash('success', result.messages.join(' ') || 'Import complete.')
    await load()
  } catch (err) {
    const data = err.response?.data
    flash('error', data?.messages?.join(' ') ?? errorText(err, 'Import failed.'))
  }
}

async function toggleAudit() {
  showAudit.value = !showAudit.value
  if (showAudit.value) await loadAudit()
}

const when = (value) => (value ? new Date(String(value).replace(' ', 'T') + 'Z').toLocaleString() : '—')

onMounted(load)
</script>

<template>
  <div class="page">
    <header class="page-header">
      <h1>GitHub Accounts</h1>
      <button class="btn-primary" @click="openCreate">Add account</button>
    </header>
    <AdminTabs />

    <div v-if="message" :class="['msg-banner', message.type]" role="status">{{ message.text }}</div>

    <section class="card">
      <h2>Accounts</h2>
      <p class="hint">
        Access tokens are stored encrypted on the server and are never shown again. Required scopes:
        <code>read:project</code> (plus <code>read:org</code> for organizations).
      </p>

      <div v-if="loading" class="loading-bar" />

      <table v-if="accounts.length">
        <thead>
          <tr>
            <th>Label</th>
            <th>Owner</th>
            <th>Status</th>
            <th>Token</th>
            <th>Token expires</th>
            <th>Projects</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in accounts" :key="a.id">
            <td>{{ a.label }}</td>
            <td>{{ a.owner_login }} <span class="muted">({{ a.owner_type }})</span></td>
            <td>
              <span class="badge" :class="a.status">{{ a.status.replace('_', ' ') }}</span>
              <div v-if="a.status_message && a.status !== 'ok'" class="muted small">{{ a.status_message }}</div>
            </td>
            <td>{{ a.has_token ? '••••' + (a.token_hint ?? '') : 'none' }}</td>
            <td>{{ a.token_expires_at ? when(a.token_expires_at) : '—' }}</td>
            <td>{{ a.tracked_projects }}</td>
            <td class="actions">
              <button :disabled="busyId === a.id" @click="test(a)">Test</button>
              <button :disabled="busyId === a.id" @click="openEdit(a)">Edit</button>
              <button :disabled="busyId === a.id" class="danger" @click="remove(a)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else-if="!loading" class="empty">
        <p>No GitHub account is configured yet.</p>
        <p class="muted">
          Upgrading? If the server <code>.env</code> still contains <code>REPO_PAT</code>, import it:
        </p>
        <button class="btn-primary" @click="importEnv">Import from .env</button>
      </div>
    </section>

    <section v-if="formOpen" class="card">
      <h2>{{ editingId === null ? 'Add account' : 'Edit account' }}</h2>
      <form autocomplete="off" @submit.prevent="save" novalidate>
        <div class="field" :class="{ invalid: fieldErrors.label }">
          <label for="gh-label">Label *</label>
          <input id="gh-label" v-model="form.label" type="text" maxlength="100" />
          <span v-if="fieldErrors.label" class="field-error">{{ fieldErrors.label }}</span>
        </div>
        <div class="field" :class="{ invalid: fieldErrors.owner_login }">
          <label for="gh-owner">Organization or user login *</label>
          <input id="gh-owner" v-model="form.owner_login" type="text" :disabled="editingId !== null" />
          <span v-if="fieldErrors.owner_login" class="field-error">{{ fieldErrors.owner_login }}</span>
        </div>
        <div class="field" :class="{ invalid: fieldErrors.token }">
          <label for="gh-token">
            {{ editingId === null ? 'Access token *' : 'New access token (leave empty to keep the current one)' }}
          </label>
          <input id="gh-token" v-model="form.token" type="password" autocomplete="new-password" spellcheck="false" />
          <span v-if="fieldErrors.token" class="field-error">{{ fieldErrors.token }}</span>
        </div>
        <details>
          <summary>Advanced</summary>
          <div class="field" :class="{ invalid: fieldErrors.graphql_url }">
            <label for="gh-url">GraphQL endpoint (GitHub Enterprise only)</label>
            <input id="gh-url" v-model="form.graphql_url" type="url" placeholder="https://api.github.com/graphql" />
            <span v-if="fieldErrors.graphql_url" class="field-error">{{ fieldErrors.graphql_url }}</span>
          </div>
        </details>
        <div class="form-actions">
          <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button>
          <button type="button" @click="closeForm">Cancel</button>
        </div>
      </form>
    </section>

    <section class="card">
      <h2>
        Audit log
        <button class="link" @click="toggleAudit">{{ showAudit ? 'Hide' : 'Show' }}</button>
      </h2>
      <table v-if="showAudit && entries.length">
        <thead>
          <tr><th>When</th><th>Who</th><th>Action</th><th>Detail</th></tr>
        </thead>
        <tbody>
          <tr v-for="e in entries" :key="e.id">
            <td>{{ when(e.created_at) }}</td>
            <td>{{ e.actor_name ?? 'system' }}</td>
            <td>{{ e.action }}</td>
            <td class="small">{{ e.detail ? JSON.stringify(e.detail) : '' }}</td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="showAudit" class="empty">No entries yet.</p>
    </section>
  </div>
</template>

<style scoped>
.page { padding: 1.5rem; max-width: 1100px; margin: 0 auto; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
h1 { margin: 0; font-size: 1.4rem; color: #1a1a2e; }
h2 { font-size: 1rem; margin: 0 0 0.75rem; color: #333; display: flex; align-items: center; gap: 0.75rem; }
.hint, .muted { color: #777; font-size: 0.85rem; }
.small { font-size: 0.78rem; }
.loading-bar { height: 3px; background: repeating-linear-gradient(90deg, #4361ee 0%, #a8b8ff 50%, #4361ee 100%); background-size: 200%; animation: slide 1.2s linear infinite; border-radius: 2px; margin-bottom: 0.75rem; }
@keyframes slide { from { background-position: 0 } to { background-position: 200% } }
.card { background: #fff; border-radius: 8px; box-shadow: 0 1px 6px rgba(0,0,0,.08); padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; }
table { width: 100%; border-collapse: collapse; }
th { padding: 0.6rem 0.75rem; text-align: left; font-size: 0.82rem; font-weight: 700; color: #555; border-bottom: 2px solid #e9ecef; }
td { padding: 0.55rem 0.75rem; font-size: 0.88rem; border-top: 1px solid #f1f1f1; vertical-align: top; }
.actions { white-space: nowrap; text-align: right; }
.actions button { margin-left: 0.3rem; }
button { padding: 0.3rem 0.7rem; border: 1px solid #ccc; border-radius: 4px; background: #fff; cursor: pointer; font-size: 0.85rem; }
button:disabled { opacity: 0.5; cursor: not-allowed; }
button.danger { color: #b02a37; border-color: #e3a1a8; }
button.link { border: none; background: none; color: #4361ee; padding: 0; }
.btn-primary { background: #4361ee; color: #fff; border-color: #4361ee; padding: 0.5rem 1.1rem; }
.btn-primary:hover:not(:disabled) { background: #3451d1; }
.empty { text-align: center; color: #666; padding: 1.5rem; }
.badge { font-size: 0.75rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 999px; text-transform: capitalize; background: #e9ecef; color: #495057; }
.badge.ok { background: #d4edda; color: #155724; }
.badge.invalid, .badge.expired, .badge.needs_reentry, .badge.insufficient_scope, .badge.unreachable { background: #f8d7da; color: #721c24; }
.msg-banner { padding: 0.6rem 1rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.9rem; }
.msg-banner.success { background: #d4edda; color: #155724; }
.msg-banner.error { background: #f8d7da; color: #721c24; }
form { display: flex; flex-direction: column; gap: 0.75rem; max-width: 520px; }
.field { display: flex; flex-direction: column; gap: 0.25rem; margin-top: 0.5rem; }
.field label { font-size: 0.85rem; font-weight: 600; color: #444; }
.field input { padding: 0.5rem 0.65rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9rem; }
.field.invalid input { border-color: #dc3545; }
.field-error { font-size: 0.78rem; color: #dc3545; }
.form-actions { display: flex; gap: 0.5rem; }
summary { cursor: pointer; color: #555; font-size: 0.85rem; }
</style>
