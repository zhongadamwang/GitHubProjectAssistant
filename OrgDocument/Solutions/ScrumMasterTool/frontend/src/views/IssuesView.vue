<script setup>
/**
 * @component IssuesView
 * @description Read-only, server-side paginated issue list for the active project.
 * Estimated / remaining / actual hours are synced from GitHub and cannot be edited
 * here. Filtering, sorting and paging are applied by the API through projectStore;
 * the current page is re-fetched every 60 seconds.
 */
import { computed, onMounted, onUnmounted } from 'vue'
import { useProjectStore, PAGE_SIZES } from '../stores/projectStore.js'
import PaginationBar from '../components/PaginationBar.vue'

const store = useProjectStore()
const projectId = computed(() => store.activeProjectId)

onMounted(async () => {
  if (!store.projects.length) await store.fetchProjects()
  if (projectId.value) {
    await Promise.all([store.fetchIterations(projectId.value), store.fetchIssues(projectId.value)])
    store.startPolling(projectId.value, 60000)
  }
})

onUnmounted(() => store.stopPolling())

const sortIndicator = (key) => {
  if (store.sortKey !== key) return ''
  return store.sortDir === 'asc' ? ' ▲' : ' ▼'
}

const hours = (value) => Number(value ?? 0).toFixed(1)
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
        <select
          :value="store.filterAssignee"
          @change="store.applyFilter(projectId, 'filterAssignee', $event.target.value)"
        >
          <option value="">All</option>
          <option v-for="a in store.assignees" :key="a" :value="a">{{ a }}</option>
        </select>
      </label>

      <label>
        Sprint
        <select
          :value="store.filterIteration"
          @change="store.applyFilter(projectId, 'filterIteration', $event.target.value)"
        >
          <option value="">All</option>
          <option v-for="it in store.iterationNames" :key="it" :value="it">{{ it }}</option>
        </select>
      </label>

      <label>
        Status
        <select
          :value="store.filterStatus"
          @change="store.applyFilter(projectId, 'filterStatus', $event.target.value)"
        >
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
            <th @click="store.setSort(projectId, 'id')"># {{ sortIndicator('id') }}</th>
            <th @click="store.setSort(projectId, 'title')">Title{{ sortIndicator('title') }}</th>
            <th @click="store.setSort(projectId, 'assignee')">Assignee{{ sortIndicator('assignee') }}</th>
            <th @click="store.setSort(projectId, 'status')">Status{{ sortIndicator('status') }}</th>
            <th @click="store.setSort(projectId, 'iteration')">Sprint{{ sortIndicator('iteration') }}</th>
            <th class="num" @click="store.setSort(projectId, 'estimated_time')">Est (h){{ sortIndicator('estimated_time') }}</th>
            <th class="num" @click="store.setSort(projectId, 'remaining_time')">Rem (h){{ sortIndicator('remaining_time') }}</th>
            <th class="num" @click="store.setSort(projectId, 'actual_time')">Act (h){{ sortIndicator('actual_time') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="issue in store.issues" :key="issue.id">
            <td>{{ issue.id }}</td>
            <td class="title-cell">{{ issue.title }}</td>
            <td>{{ issue.assignee ?? '—' }}</td>
            <td>
              <span class="status-badge" :class="issue.status === 'closed' ? 'closed' : 'open'">
                {{ issue.status === 'closed' ? 'Closed' : 'Open' }}
              </span>
            </td>
            <td>{{ issue.iteration ?? '—' }}</td>
            <td class="num">{{ hours(issue.estimated_time) }}</td>
            <td class="num">{{ hours(issue.remaining_time) }}</td>
            <td class="num">{{ hours(issue.actual_time) }}</td>
          </tr>
          <tr v-if="!store.issues.length && !store.issuesLoading">
            <td colspan="8" class="empty">No issues match the current filters.</td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="5" class="totals-label">Totals (all {{ store.issuesMeta.total }} matching issues)</td>
            <td class="num">{{ hours(store.issuesTotals.estimated) }}</td>
            <td class="num">{{ hours(store.issuesTotals.remaining) }}</td>
            <td class="num">{{ hours(store.issuesTotals.actual) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>

    <PaginationBar
      :page="store.issuesMeta.page"
      :page-size="store.issuesMeta.page_size"
      :total="store.issuesMeta.total"
      :total-pages="store.issuesMeta.total_pages"
      :page-sizes="PAGE_SIZES"
      @update:page="store.setPage(projectId, $event)"
      @update:page-size="store.setPageSize(projectId, $event)"
    />
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
.num { text-align: right; font-variant-numeric: tabular-nums; }
.title-cell { max-width: 280px; }
.empty { text-align: center; color: #888; padding: 2rem 0; }
.status-badge {
  font-size: 0.75rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 999px;
}
.status-badge.open   { background: #d4edda; color: #155724; }
.status-badge.closed { background: #e9ecef; color: #495057; }
tfoot td { font-weight: 700; background: #f8f9fa; }
.totals-label { color: #555; }
</style>
