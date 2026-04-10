<script setup>
/**
 * @component BurndownChart
 * @description Renders a sprint burndown chart using Chart.js.
 * Displays two lines: the ideal linear burn-down and the actual remaining work
 * derived from daily snapshots. Destroys and recreates the Chart.js instance
 * whenever the `points` prop changes to avoid canvas reuse warnings.
 *
 * @prop {Array<{date: string, ideal: number, actual: number}>} points
 *   Ordered array of burndown data points (one per calendar day).
 */
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const props = defineProps({
  // Array of { date: 'YYYY-MM-DD', ideal: Number, actual: Number }
  points: {
    type: Array,
    default: () => [],
  },
})

const canvasRef = ref(null)
let chartInstance = null

function buildChart() {
  if (!canvasRef.value) return
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
  if (!props.points.length) return

  const labels = props.points.map((p) => {
    const d = new Date(p.date + 'T00:00:00')
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  })

  chartInstance = new Chart(canvasRef.value, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Ideal',
          data: props.points.map((p) => p.ideal),
          borderColor: '#4361ee',
          borderDash: [6, 3],
          pointRadius: 3,
          tension: 0.1,
          fill: false,
        },
        {
          label: 'Actual',
          data: props.points.map((p) => p.actual),
          borderColor: '#d62828',
          pointRadius: 3,
          tension: 0.1,
          fill: false,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)} h`,
          },
        },
      },
      scales: {
        x: { title: { display: true, text: 'Date' } },
        y: { title: { display: true, text: 'Hours remaining' }, beginAtZero: true },
      },
    },
  })
}

onMounted(buildChart)
watch(() => props.points, buildChart, { deep: true })
onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
})
</script>

<template>
  <div class="chart-wrapper">
    <canvas v-if="points.length" ref="canvasRef" />
    <p v-else class="empty">No burndown data available for this sprint.</p>
  </div>
</template>

<style scoped>
.chart-wrapper {
  position: relative;
  height: 300px;
  width: 100%;
}
.empty {
  text-align: center;
  color: #888;
  padding: 4rem 0;
}
</style>
