<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useProjectStore } from '../stores/projectStore.js'
import { useDashboardStore } from '../stores/dashboardStore.js'
import BurndownChart from '../components/BurndownChart.vue'
import SprintSelector from '../components/SprintSelector.vue'
import HealthBadge from '../components/HealthBadge.vue'

const projectStore = useProjectStore()
const dashStore = useDashboardStore()

const selectedProjectId = ref(null)
const selectedIteration = ref(null)

// Unique iterations derived from burndown points already loaded
const iterations = computed(() =>
  [...new Set(dashStore.points.map((p) => p.iteration).filter(Boolean))]
)

async function load() {
  await projectStore.fetchProjects()
  if (projectStore.projects.length) {
    selectedProjectId.value = projectStore.activeProjectId ?? projectStore.projects[0].id
    await dashStore.fetchBurndown(selectedProjectId.value, selectedIteration.value)
  }
}

watch(selectedIteration, async (iter) => {
  if (selectedProjectId.value) {
    await dashStore.fetchBurndown(selectedProjectId.value, iter)
  }
})

onMounted(async () => {
  await load()
  if (selectedProjectId.value) {
    dashStore.startPolling(selectedProjectId.value, 30000)
  }
})

onUnmounted(() => {
  dashStore.stopPolling()
})
</script>

<template>
  <div class="page">
    <header class="page-header">
      <h1>Dashboard</h1>
      <div class="controls">
        <select
          v-if="projectStore.projects.length > 1"
          v-model="selectedProjectId"
          @change="dashStore.fetchBurndown(selectedProjectId, selectedIteration)"
        >
          <option v-for="p in projectStore.projects" :key="p.id" :value="p.id">
            {{ p.title ?? p.name }}
          </option>
        </select>
        <SprintSelector v-model="selectedIteration" :iterations="iterations" />
        <HealthBadge :health="dashStore.health" />
      </div>
    </header>

    <div v-if="dashStore.loading" class="loading-bar" />

    <div v-if="dashStore.error" class="error-banner">{{ dashStore.error }}</div>

    <section class="chart-section">
      <BurndownChart :points="dashStore.points" />
    </section>
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
