<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useProjectStore } from '../stores/projectStore.js'
import IssueTimeEditor from '../components/IssueTimeEditor.vue'

const store = useProjectStore()
const projectId = computed(() => store.activeProjectId)

onMounted(async () => {
  if (!store.projects.length) await store.fetchProjects()
  if (projectId.value) {
    await store.fetchIssues(projectId.value)
    store.startPolling(projectId.value, 60000)
  }
})

onUnmounted(() => store.stopPolling())

// Re-fetch immediately after a successful save
function onSaved() {
  if (projectId.value) store.fetchIssues(projectId.value)
}

const sortIndicator = (key) => {
  if (store.sortKey !== key) return ''
  return store.sortDir === 'asc' ? ' ▲' : ' ▼'
}
</script>

<template>
  <div class="page">
    <header class="page-header">
      <h1>Issues</h1>
      <div v-if="store.issuesLoading" class="loading-badge">Refreshing…</div>
    </header>

    <!-- Filters -->
    <div class="filters">
      <label>
        Assignee
        <select v-model="store.filterAssignee">
          <option value="">All</option>
          <option v-for="a in store.uniqueAssignees" :key="a" :value="a">{{ a }}</option>
        </select>
      </label>
      <label>
        Sprint
        <select v-model="store.filterIteration">
          <option value="">All</option>
          <option v-for="it in store.uniqueIterations" :key="it" :value="it">{{ it }}</option>
        </select>
      </label>
      <label>
        Status
        <select v-model="store.filterStatus">
          <option value="all">All</option>
          <option value="open">Open</option>
          <option value="closed">Closed</option>
        </select>
      </label>
    </div>

    <div v-if="store.issuesError" class="error-banner">{{ store.issuesError }}</div>

    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th @click="store.setSort('number')">#{{ sortIndicator('number') }}</th>
            <th @click="store.setSort('title')">Title{{ sortIndicator('title') }}</th>
            <th @click="store.setSort('assignee')">Assignee{{ sortIndicator('assignee') }}</th>
            <th @click="store.setSort('state')">Status{{ sortIndicator('state') }}</th>
            <th @click="store.setSort('iteration')">Sprint{{ sortIndicator('iteration') }}</th>
            <th>Est / Rem / Act (h)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="issue in store.filteredIssues" :key="issue.id">
            <td>{{ issue.number }}</td>
            <td class="title-cell">{{ issue.title }}</td>
            <td>{{ issue.assignee ?? '—' }}</td>
            <td>
              <span class="status-badge" :class="issue.state === 'CLOSED' ? 'closed' : 'open'">
                {{ issue.state === 'CLOSED' ? 'Closed' : 'Open' }}
              </span>
            </td>
            <td>{{ issue.iteration ?? '—' }}</td>
            <td><IssueTimeEditor :issue="issue" @saved="onSaved" /></td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="5" class="totals-label">Totals</td>
            <td class="totals-values">
              {{ store.totals.estimated.toFixed(1) }} /
              {{ store.totals.remaining.toFixed(1) }} /
              {{ store.totals.actual.toFixed(1) }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>

<style scoped>
.page { padding: 1.5rem; max-width: 1200px; margin: 0 auto; }
.page-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
h1 { margin: 0; font-size: 1.4rem; color: #1a1a2e; }
.loading-badge {
  font-size: 0.8rem;
  background: #e9f0ff;
  color: #4361ee;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
}
.filters {
  display: flex;
  gap: 1.25rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}
.filters label {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #444;
}
.filters select {
  padding: 0.3rem 0.5rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 0.85rem;
}
.error-banner {
  background: #f8d7da; color: #721c24;
  padding: 0.6rem 1rem; border-radius: 4px; margin-bottom: 1rem;
}
.table-wrapper { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 6px rgba(0,0,0,.08); }
thead tr { background: #f8f9fa; }
th {
  padding: 0.65rem 0.75rem;
  text-align: left;
  font-size: 0.82rem;
  font-weight: 700;
  color: #555;
  cursor: pointer;
  user-select: none;
  white-space: nowrap;
}
th:hover { background: #eef0ff; }
td { padding: 0.55rem 0.75rem; font-size: 0.88rem; border-top: 1px solid #f1f1f1; }
.title-cell { max-width: 280px; }
.status-badge {
  font-size: 0.75rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 999px;
}
.status-badge.open   { background: #d4edda; color: #155724; }
.status-badge.closed { background: #e9ecef; color: #495057; }
tfoot td { font-weight: 700; background: #f8f9fa; }
.totals-label { color: #555; }
.totals-values { font-family: monospace; }
</style>
