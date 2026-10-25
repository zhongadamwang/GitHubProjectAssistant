<script setup>
/**
 * @component MembersView
 * @description Member efficiency view. Shows per-member estimated vs. actual
 * hour comparisons for the active project, optionally scoped to a sprint.
 * Includes trend charts (EfficiencyChart) and an iteration selector.
 */
import { ref, computed, watch, onMounted } from 'vue'
import { useProjectStore } from '../stores/projectStore.js'
import EfficiencyChart from '../components/EfficiencyChart.vue'
import NoProjectNotice from '../components/NoProjectNotice.vue'

const store = useProjectStore()
const selectedIteration = ref(null)

const projectId = computed(() => store.activeProjectId)

async function load(iteration = null) {
  if (!store.projectsLoaded) await store.fetchProjects()
  const id = projectId.value
  if (id) {
    // The sprint dropdown comes from the project's iteration list
    if (!store.iterations.length) await store.fetchIterations(id)
    if (id === projectId.value) await store.fetchMembers(id, iteration)
  }
}

// Switching project resets the sprint choice (sprints differ per project)
watch(projectId, () => {
  selectedIteration.value = null
  load()
})

onMounted(() => load())

// Ratio helpers
function ratio(m) {
  if (!m.estimated) return null
  return m.actual / m.estimated
}

function ratioClass(m) {
  const r = ratio(m)
  if (r === null) return 'na'
  if (r < 0.9) return 'over'
  if (r > 1.1) return 'under'
  return 'accurate'
}

function ratioLabel(m) {
  const r = ratio(m)
  if (r === null) return 'N/A'
  return r.toFixed(2)
}
</script>

<template>
  <div class="page">
    <header class="page-header">
      <h1>Member Efficiency<span v-if="store.activeProject" class="project-name"> — {{ store.activeProject.name }}</span></h1>
      <div class="controls">
        <label>
          Sprint
          <select
            v-model="selectedIteration"
            @change="load(selectedIteration)"
          >
            <option :value="null">All Time</option>
            <option v-for="it in store.iterationNames" :key="it" :value="it">{{ it }}</option>
          </select>
        </label>
      </div>
    </header>

    <NoProjectNotice v-if="store.projectsLoaded && !projectId" />

    <template v-else>
    <div v-if="store.membersLoading" class="loading-bar" />
    <div v-if="store.membersError" class="error-banner">{{ store.membersError }}</div>

    <section class="chart-section">
      <EfficiencyChart :members="store.members" />
    </section>

    <section class="table-section">
      <table v-if="store.members.length">
        <thead>
          <tr>
            <th>Member</th>
            <th>Estimated (h)</th>
            <th>Actual (h)</th>
            <th>Accuracy Ratio</th>
            <th>Issues</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in store.members" :key="m.member">
            <td>{{ m.member }}</td>
            <td>{{ (m.estimated ?? 0).toFixed(1) }}</td>
            <td>{{ (m.actual ?? 0).toFixed(1) }}</td>
            <td>
              <span class="ratio-badge" :class="ratioClass(m)">
                {{ ratioLabel(m) }}
              </span>
            </td>
            <td>{{ m.issues_count ?? 0 }}</td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="!store.membersLoading" class="empty">No efficiency data available.</p>
    </section>
    </template>
  </div>
</template>

<style scoped>
.page { padding: 1.5rem; max-width: 1100px; margin: 0 auto; }
.page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.25rem; }
h1 { margin: 0; font-size: 1.4rem; color: #1a1a2e; }
.controls label { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 600; color: #444; }
.controls select { padding: 0.4rem 0.6rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9rem; }
.loading-bar { height: 3px; background: repeating-linear-gradient(90deg, #4361ee 0%, #a8b8ff 50%, #4361ee 100%); background-size: 200%; animation: slide 1.2s linear infinite; border-radius: 2px; margin-bottom: 0.75rem; }
@keyframes slide { from { background-position: 0 } to { background-position: 200% } }
.error-banner { background: #f8d7da; color: #721c24; padding: 0.6rem 1rem; border-radius: 4px; margin-bottom: 1rem; }
.chart-section, .table-section { background: #fff; border-radius: 8px; box-shadow: 0 1px 6px rgba(0,0,0,.08); padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; }
table { width: 100%; border-collapse: collapse; }
th { padding: 0.6rem 0.75rem; text-align: left; font-size: 0.82rem; font-weight: 700; color: #555; border-bottom: 2px solid #e9ecef; }
td { padding: 0.55rem 0.75rem; font-size: 0.88rem; border-top: 1px solid #f1f1f1; }
.ratio-badge { font-size: 0.8rem; font-weight: 700; padding: 0.15rem 0.55rem; border-radius: 999px; }
.ratio-badge.accurate { background: #d4edda; color: #155724; }
.ratio-badge.over     { background: #cce5ff; color: #004085; }
.ratio-badge.under    { background: #f8d7da; color: #721c24; }
.ratio-badge.na       { background: #e9ecef; color: #6c757d; }
.empty { text-align: center; color: #888; padding: 2rem; }
.project-name { font-weight: 400; color: #555; }
</style>
