import { defineStore } from 'pinia'
import * as api from '../services/api.js'

export const useProjectStore = defineStore('project', {
  state: () => ({
    projects: [],
    activeProjectId: null,

    // Issues
    issues: [],
    issuesLoading: false,
    issuesError: null,

    // Members / efficiency
    members: [],
    membersLoading: false,
    membersError: null,

    // Filters & sort
    filterAssignee: '',
    filterIteration: '',
    filterStatus: 'all',
    sortKey: 'number',
    sortDir: 'asc',

    // Polling
    issuesPollingTimer: null,
  }),

  getters: {
    filteredIssues: (state) => {
      let list = [...state.issues]

      if (state.filterAssignee) {
        list = list.filter((i) => i.assignee === state.filterAssignee)
      }
      if (state.filterIteration) {
        list = list.filter((i) => i.iteration === state.filterIteration)
      }
      if (state.filterStatus !== 'all') {
        list = list.filter((i) =>
          state.filterStatus === 'open' ? i.state !== 'CLOSED' : i.state === 'CLOSED'
        )
      }

      list.sort((a, b) => {
        const va = a[state.sortKey] ?? ''
        const vb = b[state.sortKey] ?? ''
        const cmp = typeof va === 'number' ? va - vb : String(va).localeCompare(String(vb))
        return state.sortDir === 'asc' ? cmp : -cmp
      })

      return list
    },

    uniqueAssignees: (state) =>
      [...new Set(state.issues.map((i) => i.assignee).filter(Boolean))].sort(),

    uniqueIterations: (state) =>
      [...new Set(state.issues.map((i) => i.iteration).filter(Boolean))].sort(),

    // Column totals
    totals: (state) => ({
      estimated: state.issues.reduce((s, i) => s + (i.estimated_hours ?? 0), 0),
      remaining: state.issues.reduce((s, i) => s + (i.remaining_hours ?? 0), 0),
      actual:    state.issues.reduce((s, i) => s + (i.actual_hours ?? 0), 0),
    }),
  },

  actions: {
    async fetchProjects() {
      try {
        const data = await api.getProjects()
        this.projects = data.projects ?? data
        if (this.projects.length && !this.activeProjectId) {
          this.activeProjectId = this.projects[0].id
        }
      } catch (err) {
        console.error('fetchProjects', err)
      }
    },

    async fetchIssues(projectId) {
      if (this.issuesLoading) return
      this.issuesLoading = true
      this.issuesError = null
      try {
        const data = await api.getIssues(projectId)
        this.issues = data.issues ?? data
      } catch (err) {
        this.issuesError = err.message ?? 'Failed to load issues.'
      } finally {
        this.issuesLoading = false
      }
    },

    async fetchMembers(projectId, iteration = null) {
      this.membersLoading = true
      this.membersError = null
      try {
        const data = await api.getMembers(projectId, iteration)
        this.members = data.members ?? data
      } catch (err) {
        this.membersError = err.message ?? 'Failed to load member data.'
      } finally {
        this.membersLoading = false
      }
    },

    // Optimistic update — reverts on error; emits nothing (caller handles event)
    async saveIssueTime(issueId, patch) {
      const idx = this.issues.findIndex((i) => i.id === issueId)
      if (idx === -1) return
      const prev = { ...this.issues[idx] }
      // Optimistic apply
      this.issues[idx] = { ...this.issues[idx], ...patch }
      try {
        await api.updateIssueTime(issueId, patch)
      } catch (err) {
        // Rollback
        this.issues[idx] = prev
        throw err
      }
    },

    setSort(key) {
      if (this.sortKey === key) {
        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortKey = key
        this.sortDir = 'asc'
      }
    },

    startPolling(projectId, intervalMs = 60000) {
      this.stopPolling()
      this.issuesPollingTimer = setInterval(() => {
        if (!this.issuesLoading) {
          this.fetchIssues(projectId)
        }
      }, intervalMs)
    },

    stopPolling() {
      if (this.issuesPollingTimer !== null) {
        clearInterval(this.issuesPollingTimer)
        this.issuesPollingTimer = null
      }
    },
  },
})
