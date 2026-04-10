<script setup>
/**
 * @component SyncView
 * @description GitHub sync management view. Shows sync history and — for admin
 * users — provides a manual trigger button. Uses SyncStatus component to show
 * the age/status of the last run.
 */
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/authStore.js'
import SyncStatus from '../components/SyncStatus.vue'
import * as api from '../services/api.js'

const authStore = useAuthStore()

const history = ref([])
const loading = ref(false)
const triggerLoading = ref(false)
const triggerMsg = ref(null)

const latestSync = computed(() => history.value[0] ?? null)

async function fetchHistory() {
  loading.value = true
  try {
    const data = await api.getSyncHistory()
    history.value = (data.history ?? data).slice(0, 20)
  } catch {
    // silently fail — status component handles unknowns
  } finally {
    loading.value = false
  }
}

async function handleTrigger() {
  triggerLoading.value = true
  triggerMsg.value = null
  try {
    await api.triggerSync()
    triggerMsg.value = { type: 'success', text: 'Sync triggered successfully.' }
    await fetchHistory()
  } catch (err) {
    triggerMsg.value = {
      type: 'error',
      text: err.response?.data?.error ?? 'Sync trigger failed.',
    }
  } finally {
    triggerLoading.value = false
    setTimeout(() => { triggerMsg.value = null }, 4000)
  }
}

onMounted(fetchHistory)
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
        <button
          v-if="authStore.isAdmin"
          :disabled="triggerLoading"
          class="btn-sync"
          @click="handleTrigger"
        >
          <span v-if="triggerLoading">Syncing…</span>
          <span v-else>Sync Now</span>
        </button>
      </div>
    </header>

    <div v-if="triggerMsg" :class="['msg-banner', triggerMsg.type]">
      {{ triggerMsg.text }}
    </div>

    <section class="summary" v-if="latestSync">
      <dl>
        <dt>Last Sync</dt>
        <dd>{{ new Date(latestSync.synced_at).toLocaleString() }}</dd>
        <dt>GraphQL Points Used</dt>
        <dd>{{ latestSync.points_used ?? '—' }}</dd>
        <dt>Result</dt>
        <dd>{{ latestSync.issues_added }} added · {{ latestSync.issues_updated }} updated · {{ latestSync.issues_removed }} removed</dd>
      </dl>
    </section>

    <div v-if="loading" class="loading-bar" />

    <section class="table-section">
      <h2>History</h2>
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
            <td>{{ new Date(row.synced_at).toLocaleString() }}</td>
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
  </div>
</template>

<style scoped>
.page { padding: 1.5rem; max-width: 1000px; margin: 0 auto; }
.page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.25rem; }
h1 { margin: 0; font-size: 1.4rem; color: #1a1a2e; }
h2 { font-size: 1rem; margin: 0 0 0.75rem; color: #333; }
.header-right { display: flex; align-items: center; gap: 0.75rem; }
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
.status-badge.failed, .status-badge.error { background: #f8d7da; color: #721c24; }
.empty { text-align: center; color: #888; padding: 2rem; }
</style>
