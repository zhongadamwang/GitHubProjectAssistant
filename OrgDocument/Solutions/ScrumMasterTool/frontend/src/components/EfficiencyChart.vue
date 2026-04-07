<script setup>
/**
 * @component EfficiencyChart
 * @description Renders a grouped bar chart comparing estimated vs. actual hours
 * per team member using Chart.js. Each member gets two adjacent bars so the
 * estimation accuracy ratio (actual / estimated) is visually apparent.
 *
 * @prop {Array<{member: string, estimated: number, actual: number}>} members
 *   Per-member efficiency records from the `/api/projects/{id}/members` endpoint.
 */
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const props = defineProps({
  // Array of { login, estimated, actual }
  members:

const canvasRef = ref(null)
let chartInstance = null

function buildChart() {
  if (!canvasRef.value) return
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
  if (!props.members.length) return

  chartInstance = new Chart(canvasRef.value, {
    type: 'bar',
    data: {
      labels: props.members.map((m) => m.login),
      datasets: [
        {
          label: 'Estimated (h)',
          data: props.members.map((m) => m.estimated ?? 0),
          backgroundColor: 'rgba(67, 97, 238, 0.7)',
        },
        {
          label: 'Actual (h)',
          data: props.members.map((m) => m.actual ?? 0),
          backgroundColor: 'rgba(255, 140, 0, 0.7)',
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'top' } },
      scales: {
        x: { title: { display: true, text: 'Member' } },
        y: { title: { display: true, text: 'Hours' }, beginAtZero: true },
      },
    },
  })
}

onMounted(buildChart)
watch(() => props.members, buildChart, { deep: true })
onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
})
</script>

<template>
  <div class="chart-wrapper">
    <canvas v-if="members.length" ref="canvasRef" />
    <p v-else class="empty">No efficiency data available.</p>
  </div>
</template>

<style scoped>
.chart-wrapper {
  position: relative;
  height: 280px;
  width: 100%;
}
.empty {
  text-align: center;
  color: #888;
  padding: 3rem 0;
}
</style>
