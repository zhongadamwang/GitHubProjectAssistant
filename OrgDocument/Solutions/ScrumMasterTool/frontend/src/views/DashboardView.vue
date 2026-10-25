<script setup>
/**
 * @component DashboardView
 * @description Main sprint dashboard. Displays the burndown chart, sprint health
 * badge, and a sprint selector for the active project. Polls burndown data every
 * 30 seconds via dashboardStore. When the selected project or iteration changes,
 * the store is refreshed and polling restarted.
 */
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useProjectStore } from '../stores/projectStore.js'
import { useDashboardStore } from '../stores/dashboardStore.js'
import BurndownChart from '../components/BurndownChart.vue'
import SprintSelector from '../components/SprintSelector.vue'
import HealthBadge from '../components/HealthBadge.vue'
import NoProjectNotice from '../components/NoProjectNotice.vue'

const projectStore = useProjectStore()
const dashStore = useDashboardStore()

const selectedIteration = ref(null)
const projectId = computed(() => projectStore.activeProjectId)

// Sprint labels of the active project (from GET /api/projects/{id}/iterations)
const iterations = computed(() => projectStore.iterationNames)

/**
 * Load the active project: fetch its sprint list, preselect the current sprint (falling back
 * to "Latest"), load that burndown, and (re)start polling for this project.
 */
async function loadProject(id) {
  dashStore.stopPolling()
  dashStore.points = []
  dashStore.iteration = null
  if (!id) return
  await projectStore.fetchIterations(id)
  if (id !== projectId.value) return // switched again while loading
  selectedIteration.value = projectStore.currentIteration
  await dashStore.fetchBurndown(id, selectedIteration.value)
  if (id === projectId.value) dashStore.startPolling(id, 30000)
}

async function onIterationChange(iteration) {
  selectedIteration.value = iteration
  await dashStore.fetchBurndown(projectId.value, iteration)
}

watch(projectId, (id) => loadProject(id))

onMounted(async () => {
  if (!projectStore.projectsLoaded) await projectStore.fetchProjects()
  await loadProject(projectId.value)
})

onUnmounted(() => {
  dashStore.stopPolling()
})
</script>

<template>
  <div class="page">
    <header class="page-header">
      <h1>Dashboard<span v-if="projectStore.activeProject" class="project-name"> — {{ projectStore.activeProject.name }}</span></h1>
      <div class="controls">
        <SprintSelector
          :model-value="selectedIteration"
          :iterations="iterations"
          :current="projectStore.currentIteration"
          @update:model-value="onIterationChange"
        />
        <HealthBadge :health="dashStore.health" />
      </div>
    </header>

    <NoProjectNotice v-if="projectStore.projectsLoaded && !projectId" />

    <template v-else>
    <div v-if="dashStore.loading" class="loading-bar" />

    <div v-if="dashStore.error" class="error-banner">{{ dashStore.error }}</div>

    <section class="chart-section">
      <BurndownChart :points="dashStore.points" />
    </section>
    </template>
  </div>
</template>

<style scoped>
.page { padding: 1.5rem; max-width: 1100px; margin: 0 auto; }
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}
h1 { margin: 0; font-size: 1.4rem; color: #1a1a2e; }
.project-name { font-weight: 400; color: #555; }
.controls { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
select {
  padding: 0.4rem 0.6rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 0.9rem;
}
.loading-bar {
  height: 3px;
  background: repeating-linear-gradient(90deg, #4361ee 0%, #a8b8ff 50%, #4361ee 100%);
  background-size: 200%;
  animation: slide 1.2s linear infinite;
  border-radius: 2px;
  margin-bottom: 0.75rem;
}
@keyframes slide { from { background-position: 0 } to { background-position: 200% } }
.error-banner {
  background: #f8d7da;
  color: #721c24;
  padding: 0.6rem 1rem;
  border-radius: 4px;
  margin-bottom: 1rem;
}
.chart-section {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 6px rgba(0,0,0,.08);
  padding: 1.25rem 1.5rem;
}
</style>
