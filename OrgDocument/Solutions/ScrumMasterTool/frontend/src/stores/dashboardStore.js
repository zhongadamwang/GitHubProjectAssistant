import { defineStore } from 'pinia'
import * as api from '../services/api.js'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    points: [],       // Array of { date, ideal, actual }
    iteration: null,  // Currently displayed iteration name
    loading: false,
    error: null,
    pollingTimer: null,
  }),

  getters: {
    // Sprint health based on latest data point
    health: (state) => {
      if (!state.points.length) return null
      const last = state.points[state.points.length - 1]
      if (last.actual <= last.ideal) return 'on-track'
      const pct = (last.actual - last.ideal) / (last.ideal || 1)
      return pct < 0.2 ? 'at-risk' : 'behind'
    },
  },

  actions: {
    async fetchBurndown(projectId, iteration = null) {
      if (this.loading) return
      this.loading = true
      this.error = null
      try {
        const data = await api.getBurndown(projectId, iteration)
        this.points = data.points ?? []
        this.iteration = data.iteration ?? null
      } catch (err) {
        this.error = err.message ?? 'Failed to load burndown data.'
      } finally {
        this.loading = false
      }
    },

    async refresh(projectId) {
      await this.fetchBurndown(projectId, this.iteration)
    },

    startPolling(projectId, intervalMs = 30000) {
      this.stopPolling()
      this.pollingTimer = setInterval(() => {
        if (!this.loading) {
          this.refresh(projectId)
        }
      }, intervalMs)
    },

    stopPolling() {
      if (this.pollingTimer !== null) {
        clearInterval(this.pollingTimer)
        this.pollingTimer = null
      }
    },
  },
})
