<script setup>
/**
 * @component AdminProjectsView
 * @description Admin page for choosing which GitHub projects are tracked. Discover the projects
 * an account can see, track or untrack them (untracking keeps the data), edit the per-project
 * field mapping, validate it against GitHub, and permanently delete an archived project's data.
 */
import { ref, computed, onMounted } from 'vue'
import AdminTabs from '../components/AdminTabs.vue'
import { useProjectStore } from '../stores/projectStore.js'
import * as api from '../services/api.js'

const projectStore = useProjectStore()

const ROLES = [
  { key: 'estimated', label: 'Estimate hours', placeholder: 'Estimate Hours' },
  { key: 'remaining', label: 'Remaining hours', placeholder: 'Remains Hours' },
  { key: 'actual', label: 'Actual hours', placeholder: 'Actual Hours' },
  { key: 'iteration', label: 'Sprint (iteration)', placeholder: 'Iteration' },
]

const projects = ref([])
const accounts = ref([])
const loading = ref(false)
const message = ref(null)
const warning = ref(null)
const busyId = ref(null)

// Discovery
const discoverAccountId = ref(null)
const discovered = ref([])
const discovering = ref(false)

// Mapping editor
const editingId = ref(null)
const mapping = ref({})
const validation = ref(null)

const trackedCount = computed(() => projects.value.filter((p) => p.is_tracked).length)

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
    const [p, a] = await Promise.all([api.getProjects({ includeArchived: true }), api.getGitHubAccounts()])
    projects.value = p.projects ?? []
    accounts.value = a.accounts ?? []
    if (!discoverAccountId.value && accounts.value.length) discoverAccountId.value = accounts.value[0].id
    projectStore.fetchProjects() // keep the project switcher in step with tracking changes
  } finally {
    loading.value = false
  }
}

async function discover() {
  if (!discoverAccountId.value) return
  discovering.value = true
  message.value = null
  discovered.value = []
  try {
    discovered.value = (await api.discoverProjects(discoverAccountId.value)).projects ?? []
    if (!discovered.value.length) flash('error', 'This account has no visible projects.')
  } catch (err) {
    flash('error', errorText(err, 'Could not list the projects of this account.'))
  } finally {
    discovering.value = false
  }
}

async function track(candidate) {
  busyId.value = candidate.id
  message.value = null
  try {
    const result = await api.trackProject(discoverAccountId.value, candidate.number)
    warning.value = result.warning ?? null
    flash('success', `"${result.project.name}" is now tracked. Run a sync to import its issues.`)
    await Promise.all([load(), discover()])
  } catch (err) {
    flash('error', errorText(err, 'Tracking the project failed.'))
  } finally {
    busyId.value = null
  }
}

async function setTracked(project, tracked) {
  busyId.value = project.id
  message.value = null
  try {
    await api.updateProject(project.id, { is_tracked: tracked })
    flash('success', tracked ? `"${project.name}" is tracked again.` : `"${project.name}" is archived — its data is kept.`)
    await load()
  } catch (err) {
    flash('error', errorText(err, 'Updating the project failed.'))
  } finally {
    busyId.value = null
  }
}

async function deleteData(project) {
  const typed = window.prompt(
    `This permanently deletes all synced issues, burndown history and sprints of "${project.name}".\n\nType the project name to confirm:`,
  )
  if (typed === null) return
  busyId.value = project.id
  message.value = null
  try {
    await api.deleteProjectData(project.id, typed)
    flash('success', `All data of "${project.name}" was deleted.`)
    await load()
  } catch (err) {
    flash('error', errorText(err, 'Deleting the data failed.'))
  } finally {
    busyId.value = null
  }
}

function openMapping(project) {
  editingId.value = project.id
  mapping.value = { ...(project.field_mapping ?? {}) }
  validation.value = null
}

function closeMapping() {
  editingId.value = null
  validation.value = null
}

async function saveMapping(reset = false) {
  busyId.value = editingId.value
  message.value = null
  try {
    await api.updateProject(editingId.value, { field_mapping: reset ? null : { ...mapping.value } })
    flash('success', reset ? 'Field names reset to the defaults.' : 'Field mapping saved. It applies from the next sync.')
    if (reset) mapping.value = {}
    await load()
  } catch (err) {
    flash('error', errorText(err, 'Saving the field mapping failed.'))
  } finally {
    busyId.value = null
  }
}

async function validateMapping() {
  busyId.value = editingId.value
  message.value = null
  try {
    // Validate what is stored; save first so the check reflects the form
    await api.updateProject(editingId.value, { field_mapping: { ...mapping.value } })
    validation.value = await api.validateProjectFields(editingId.value)
    await load()
  } catch (err) {
    flash('error', errorText(err, 'Validating the fields failed.'))
  } finally {
    busyId.value = null
  }
}

const when = (value) => (value ? new Date(String(value).replace(' ', 'T') + 'Z').toLocaleString() : '—')

onMounted(load)
</script>

<template>
  <div class="page">
    <header class="page-header">
      <h1>Projects</h1>
    </header>
    <AdminTabs />

    <div v-if="message" :class="['msg-banner', message.type]" role="status">{{ message.text }}</div>
    <div v-if="warning || trackedCount > 10" class="msg-banner warn" role="alert">
      {{ warning ?? 'More than 10 projects are tracked.' }}
    </div>

    <section class="card">
      <h2>Tracked projects <span class="muted">({{ trackedCount }})</span></h2>
      <p class="hint">
        Supported size: up to 10 projects of about 1,000 issues each. All signed-in users can see all tracked projects.
      </p>
      <div v-if="loading" class="loading-bar" />

      <table v-if="projects.length">
        <thead>
          <tr>
            <th>Project</th>
            <th>Account</th>
            <th>State</th>
            <th>Last sync</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <template v-for="p in projects" :key="p.id">
            <tr>
              <td>{{ p.name }} <span class="muted">{{ p.github_owner }} #{{ p.project_number }}</span></td>
              <td>{{ p.account_label ?? 'Legacy .env' }}</td>
              <td>
                <span class="badge" :class="p.is_tracked ? 'ok' : 'archived'">{{ p.is_tracked ? 'tracked' : 'archived' }}</span>
              </td>
              <td>
                {{ when(p.last_sync_at) }}
                <span v-if="p.last_sync_status" class="badge" :class="p.last_sync_status">{{ p.last_sync_status.replace('_', ' ') }}</span>
              </td>
              <td class="actions">
                <button :disabled="busyId === p.id" @click="editingId === p.id ? closeMapping() : openMapping(p)">Fields</button>
                <button v-if="p.is_tracked" :disabled="busyId === p.id" @click="setTracked(p, false)">Untrack</button>
                <template v-else>
                  <button :disabled="busyId === p.id" @click="setTracked(p, true)">Track again</button>
                  <button :disabled="busyId === p.id" class="danger" @click="deleteData(p)">Delete data</button>
                </template>
              </td>
            </tr>
            <tr v-if="editingId === p.id" class="editor">
              <td colspan="5">
                <h3>Field names in this GitHub project</h3>
                <p class="hint">Leave a field empty to use the default shown as placeholder. Hours fields must be Number fields; the sprint must be an Iteration field.</p>
                <div class="mapping-grid">
                  <label v-for="r in ROLES" :key="r.key">
                    {{ r.label }}
                    <input v-model="mapping[r.key]" type="text" :placeholder="r.placeholder" maxlength="100" />
                  </label>
                </div>
                <div class="form-actions">
                  <button class="btn-primary" :disabled="busyId === p.id" @click="saveMapping(false)">Save</button>
                  <button :disabled="busyId === p.id" @click="validateMapping">Save and validate against GitHub</button>
                  <button :disabled="busyId === p.id" @click="saveMapping(true)">Reset to defaults</button>
                  <button @click="closeMapping">Close</button>
                </div>
                <ul v-if="validation" class="validation">
                  <li v-for="f in validation.fields" :key="f.role" :class="f.status">
                    <strong>{{ f.name }}</strong> —
                    <span v-if="f.status === 'found'">found ({{ f.data_type }})</span>
                    <span v-else-if="f.status === 'wrong_type'">wrong type: {{ f.data_type }}, expected {{ f.expected_type }}</span>
                    <span v-else>not found in the GitHub project</span>
                  </li>
                </ul>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
      <p v-else-if="!loading" class="empty">No project is tracked yet. Add one below.</p>
    </section>

    <section class="card">
      <h2>Add a project</h2>
      <p v-if="!accounts.length" class="empty">
        Add a GitHub account first: <router-link to="/admin/github">GitHub accounts</router-link>.
      </p>
      <template v-else>
        <div class="discover-bar">
          <label>
            Account
            <select v-model="discoverAccountId">
              <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.label }} ({{ a.owner_login }})</option>
            </select>
          </label>
          <button class="btn-primary" :disabled="discovering" @click="discover">
            {{ discovering ? 'Loading…' : 'Discover projects' }}
          </button>
        </div>

        <table v-if="discovered.length">
          <thead><tr><th>#</th><th>Title</th><th>State</th><th></th></tr></thead>
          <tbody>
            <tr v-for="d in discovered" :key="d.id">
              <td>{{ d.number }}</td>
              <td>{{ d.title }} <span v-if="d.closed" class="muted">(closed)</span></td>
              <td>{{ d.tracked ? 'tracked' : d.archived ? 'archived' : '—' }}</td>
              <td class="actions">
                <button
                  v-if="!d.tracked"
                  class="btn-primary"
                  :disabled="busyId === d.id"
                  @click="track(d)"
                >
                  {{ d.archived ? 'Track again' : 'Track' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </template>
    </section>
  </div>
</template>

<style scoped>
.page { padding: 1.5rem; max-width: 1100px; margin: 0 auto; }
.page-header { margin-bottom: 1rem; }
h1 { margin: 0; font-size: 1.4rem; color: #1a1a2e; }
h2 { font-size: 1rem; margin: 0 0 0.5rem; color: #333; }
h3 { font-size: 0.9rem; margin: 0 0 0.25rem; }
.hint, .muted { color: #777; font-size: 0.85rem; }
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
.btn-primary { background: #4361ee; color: #fff; border-color: #4361ee; padding: 0.4rem 0.9rem; }
.empty { text-align: center; color: #666; padding: 1.25rem; }
.badge { font-size: 0.75rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 999px; text-transform: capitalize; background: #e9ecef; color: #495057; margin-left: 0.4rem; }
.badge.ok { background: #d4edda; color: #155724; }
.badge.failed, .badge.needs_attention { background: #f8d7da; color: #721c24; }
.badge.partial, .badge.rate_limited { background: #fff3cd; color: #856404; }
.msg-banner { padding: 0.6rem 1rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.9rem; }
.msg-banner.success { background: #d4edda; color: #155724; }
.msg-banner.error { background: #f8d7da; color: #721c24; }
.msg-banner.warn { background: #fff3cd; color: #856404; }
.editor td { background: #f8f9ff; }
.mapping-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem; margin: 0.5rem 0; }
.mapping-grid label { display: flex; flex-direction: column; gap: 0.2rem; font-size: 0.85rem; font-weight: 600; color: #444; }
.mapping-grid input { padding: 0.45rem 0.6rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9rem; font-weight: 400; }
.form-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.validation { list-style: none; padding: 0; margin: 0.75rem 0 0; font-size: 0.88rem; }
.validation li { padding: 0.2rem 0; }
.validation li.found { color: #155724; }
.validation li.missing, .validation li.wrong_type { color: #721c24; }
.discover-bar { display: flex; align-items: flex-end; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
.discover-bar label { display: flex; flex-direction: column; font-size: 0.85rem; font-weight: 600; color: #444; gap: 0.2rem; }
.discover-bar select { padding: 0.4rem 0.6rem; border: 1px solid #ccc; border-radius: 4px; }
</style>
