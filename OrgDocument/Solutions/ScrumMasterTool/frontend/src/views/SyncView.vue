<script setup>
/**
 * @component SyncView
 * @description GitHub sync management. Lists the tracked projects with their last sync result,
 * shows the sync history of the active project and — for admins — syncs ONE project at a time
 * ("Sync Now" per project; the cron job syncs all of them).
 */
import { ref, computed, watch, onMounted } from 'vue'
import { useAuthStore } from '../stores/authStore.js'
import { useProjectStore } from '../stores/projectStore.js'
import SyncStatus from '../components/SyncStatus.vue'
import NoProjectNotice from '../components/NoProjectNotice.vue'
import * as api from '../services/api.js'

const authStore = useAuthStore()
const projectStore = useProjectStore()

const history = ref([])
const loading = ref(false)
const syncingId = ref(null)
const triggerMsg = ref(null)

const projectId = computed(() => projectStore.activeProjectId)
const latestSync = computed(() => history.value[0] ?? null)

// Timestamps from the API are UTC without a zone marker
const when = (value) => (value ? new Date(String(value).replace(' ', 'T') + 'Z').toLocaleString() : '—')

async function fetchHistory() {
  const id = projectId.value
  if (!id) {
    history.value = []
    return
  }
  loading.value = true
  try {
    const data = await api.getSyncHistory(id)
    if (id === projectId.value) history.value = (data.data ?? data.history ?? data).slice(0, 20)
  } catch {
    // silently fail — status component handles unknowns
  } finally {
    loading.value = false
  }
}

async function handleSync(project) {
  syncingId.value = project.id
  triggerMsg.value = null
  try {
    const r = await api.syncProject(project.id)
    triggerMsg.value = {
      type: 'success',
      text: `${project.name}: synced — ${r.issues_added ?? 0} added, ${r.issues_updated ?? 0} updated.`,
    }
  } catch (err) {
    triggerMsg.value = {
      type: 'error',
      text: `${project.name}: ${err.response?.data?.error ?? 'Sync failed.'}`,
    }
  } finally {
    syncingId.value = null
    await Promise.all([projectStore.fetchProjects(), fetchHistory()])
    setTimeout(() => { triggerMsg.value = null }, 6000)
  }
}

watch(projectId, fetchHistory)

onMounted(async () => {
  await projectStore.fetchProjects()
  await fetchHistory()
})
</script>

<template>
  <div class="page">
    <header class="page-header">
      <h1>Sync Status</h1>
      <div class="header-right">
        <SyncStatus
          :last-sync-at="latestSync?.synced_at ?? null"
          :last-status="latestSync?.status ?? null"
        />
      </div>
    </header>

    <NoProjectNotice v-if="projectStore.projectsLoaded && !projectStore.projects.length" />

    <template v-else>
    <div v-if="triggerMsg" :class="['msg-banner', triggerMsg.type]">
      {{ triggerMsg.text }}
    </div>

    <section class="table-section projects">
      <h2>Projects</h2>
      <table>
        <thead>
          <tr>
            <th>Project</th>
            <th>Account</th>
            <th>Last sync</th>
            <th>Result</th>
            <th v-if="authStore.isAdmin"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="p in projectStore.projects"
            :key="p.id"
            :class="{ active: p.id === projectId }"
          >
            <td>
              <a href="#" @click.prevent="projectStore.selectProject(p.id)">{{ p.name }}</a>
            </td>
            <td>
              {{ p.account_label ?? 'Legacy .env' }}
              <span v-if="p.account_status && p.account_status !== 'ok'" class="status-badge failed">{{ p.account_status }}</span>
            </td>
            <td>{{ when(p.last_sync_at) }}</td>
            <td>
              <span v-if="p.last_sync_status" class="status-badge" :class="p.last_sync_status">{{ p.last_sync_status.replace('_', ' ') }}</span>
              <span v-else>—</span>
            </td>
            <td v-if="authStore.isAdmin" class="actions">
              <button
                :disabled="syncingId !== null"
                class="btn-sync"
                @click="handleSync(p)"
              >
                <span v-if="syncingId === p.id">Syncing…</span>
                <span v-else>Sync Now</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="summary" v-if="latestSync">
      <dl>
        <dt>Last Sync</dt>
        <dd>{{ when(latestSync.synced_at) }}</dd>
        <dt>GraphQL Points Used</dt>
        <dd>{{ latestSync.points_used ?? '—' }}</dd>
        <dt>Result</dt>
        <dd>{{ latestSync.issues_added }} added · {{ latestSync.issues_updated }} updated · {{ latestSync.issues_removed }} removed</dd>
      </dl>
    </section>

    <div v-if="loading" class="loading-bar" />

    <section class="table-section">
      <h2>History<span v-if="projectStore.activeProject"> — {{ projectStore.activeProject.name }}</span></h2>
      <table v-if="history.length">
        <thead>
          <tr>
            <th>Date / Time</th>
            <th>Added</th>
            <th>Updated</th>
            <th>Removed</th>
            <th>Points</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in history" :key="row.id">
            <td>{{ when(row.synced_at) }}</td>
            <td>{{ row.issues_added }}</td>
            <td>{{ row.issues_updated }}</td>
            <td>{{ row.issues_removed }}</td>
            <td>{{ row.points_used ?? '—' }}</td>
            <td>
              <span class="status-badge" :class="row.status">{{ row.status }}</span>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else class="empty">No sync history yet.</p>
    </section>
    </template>
  </div>
</template>

<style scoped>
.page { padding: 1.5rem; max-width: 1000px; margin: 0 auto; }
.page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.25rem; }
h1 { margin: 0; font-size: 1.4rem; color: #1a1a2e; }
h2 { font-size: 1rem; margin: 0 0 0.75rem; color: #333; }
.header-right { display: flex; align-items: center; gap: 0.75rem; }
.projects { margin-bottom: 1.25rem; }
.projects tr.active td { background: #f4f6ff; }
.actions { text-align: right; }
.btn-sync {
  padding: 0.45rem 1rem;
  background: #4361ee; color: #fff;
  border: none; border-radius: 4px; font-size: 0.9rem; cursor: pointer;
}
.btn-sync:disabled { background: #9aaaf5; cursor: not-allowed; }
.btn-sync:hover:not(:disabled) { background: #3451d1; }
.msg-banner { padding: 0.6rem 1rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.9rem; }
.msg-banner.success { background: #d4edda; color: #155724; }
.msg-banner.error   { background: #f8d7da; color: #721c24; }
.summary { background: #fff; border-radius: 8px; box-shadow: 0 1px 6px rgba(0,0,0,.08); padding: 1rem 1.5rem; margin-bottom: 1.25rem; }
dl { display: grid; grid-template-columns: max-content 1fr; gap: 0.4rem 1rem; }
dt { font-weight: 700; font-size: 0.85rem; color: #555; }
dd { font-size: 0.9rem; margin: 0; }
.loading-bar { height: 3px; background: repeating-linear-gradient(90deg, #4361ee 0%, #a8b8ff 50%, #4361ee 100%); background-size: 200%; animation: slide 1.2s linear infinite; border-radius: 2px; margin-bottom: 0.75rem; }
@keyframes slide { from { background-position: 0 } to { background-position: 200% } }
.table-section { background: #fff; border-radius: 8px; box-shadow: 0 1px 6px rgba(0,0,0,.08); padding: 1.25rem 1.5rem; }
table { width: 100%; border-collapse: collapse; }
th { padding: 0.6rem 0.75rem; text-align: left; font-size: 0.82rem; font-weight: 700; color: #555; border-bottom: 2px solid #e9ecef; }
td { padding: 0.55rem 0.75rem; font-size: 0.88rem; border-top: 1px solid #f1f1f1; }
.status-badge { font-size: 0.75rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 999px; text-transform: capitalize; }
.status-badge.success, .status-badge.ok { background: #d4edda; color: #155724; }
.status-badge.failed, .status-badge.error, .status-badge.needs_attention, .status-badge.needs_reentry, .status-badge.invalid, .status-badge.expired { background: #f8d7da; color: #721c24; }
.status-badge.partial, .status-badge.rate_limited { background: #fff3cd; color: #856404; }
.empty { text-align: center; color: #888; padding: 2rem; }
</style>
