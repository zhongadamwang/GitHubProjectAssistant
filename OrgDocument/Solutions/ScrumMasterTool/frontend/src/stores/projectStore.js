import { defineStore } from 'pinia'
import * as api from '../services/api.js'

/** Page sizes offered by the Issues view (must match the API whitelist). */
export const PAGE_SIZES = [10, 25, 50, 100]
const DEFAULT_PAGE_SIZE = 25
const PAGE_SIZE_KEY = 'scrum.issues.pageSize'

function loadPageSize() {
  try {
    const size = Number(localStorage.getItem(PAGE_SIZE_KEY))
    return PAGE_SIZES.includes(size) ? size : DEFAULT_PAGE_SIZE
  } catch {
    return DEFAULT_PAGE_SIZE
  }
}

// Only the response to the most recent issues request is applied
let issuesRequestSeq = 0

export const useProjectStore = defineStore('project', {
  state: () => ({
    projects: [],
    activeProjectId: null,

    // Iterations (sprints) of the active project: [{ name, is_current, ... }]
    iterations: [],

    // Issues — one server-side page at a time
    issues: [],
    issuesMeta: { page: 1, page_size: loadPageSize(), total: 0, total_pages: 1 },
    issuesTotals: { estimated: 0, remaining: 0, actual: 0 },
    assignees: [],
    issuesLoading: false,
    issuesError: null,

    // Members / efficiency
    members: [],
    membersLoading: false,
    membersError: null,

    // Filters, sort and paging (applied by the API)
    filterAssignee: '',
    filterIteration: '',
    filterStatus: 'all',
    sortKey: 'id',
    sortDir: 'asc',
    page: 1,
    pageSize: loadPageSize(),

    // Polling
    issuesPollingTimer: null,
  }),

  getters: {
    iterationNames: (state) => state.iterations.map((i) => i.name),

    currentIteration: (state) => state.iterations.find((i) => i.is_current)?.name ?? null,
  },

  actions: {
    /**
     * Fetch all projects from the API and populate `state.projects`.
     * Automatically sets `activeProjectId` to the first project if none is set.
     * @returns {Promise<void>}
     */
    async fetchProjects() {
      try {
        const data = await api.getProjects()
        this.projects = data.projects ?? data
        if (this.projects.length && !this.activeProjectId) {
          this.activeProjectId = this.projects[0].id
        }
      } catch {
        // Error surfaced via Axios interceptor → ErrorBanner
      }
    },

    /**
     * Fetch the iteration (sprint) labels of a project, newest first.
     * @param {number} projectId
     * @returns {Promise<void>}
     */
    async fetchIterations(projectId) {
      try {
        const data = await api.getIterations(projectId)
        this.iterations = data.iterations ?? []
      } catch {
        this.iterations = []
      }
    },

    /**
     * Fetch the current page of issues using the stored filters, sort and paging.
     * A newer request supersedes an older one still in flight.
     * @param {number} projectId
     * @returns {Promise<void>}
     */
    async fetchIssues(projectId) {
      const seq = ++issuesRequestSeq
      this.issuesLoading = true
      this.issuesError = null

      const params = {
        page: this.page,
        page_size: this.pageSize,
        sort: this.sortKey,
        dir: this.sortDir,
      }
      if (this.filterAssignee) params.assignee = this.filterAssignee
      if (this.filterIteration) params.iteration = this.filterIteration
      if (this.filterStatus !== 'all') params.status = this.filterStatus

      try {
        const data = await api.getIssues(projectId, params)
        if (seq !== issuesRequestSeq) return
        this.issues = data.issues ?? []
        this.issuesMeta = data.meta ?? this.issuesMeta
        this.issuesTotals = data.totals ?? this.issuesTotals
        this.assignees = data.assignees ?? this.assignees
        this.page = this.issuesMeta.page
      } catch (err) {
        if (seq !== issuesRequestSeq) return
        this.issuesError = err.message ?? 'Failed to load issues.'
      } finally {
        if (seq === issuesRequestSeq) this.issuesLoading = false
      }
    },

    /**
     * Change one filter (assignee | iteration | status), return to page 1 and reload.
     * @param {number} projectId
     * @param {'filterAssignee'|'filterIteration'|'filterStatus'} key
     * @param {string} value
     * @returns {Promise<void>}
     */
    async applyFilter(projectId, key, value) {
      this[key] = value
      this.page = 1
      await this.fetchIssues(projectId)
    },

    /**
     * Toggle sort direction if the same key is selected; otherwise sort ascending
     * by the new key. Returns to page 1 and reloads.
     * @param {number} projectId
     * @param {string} key  API sort column.
     * @returns {Promise<void>}
     */
    async setSort(projectId, key) {
      if (this.sortKey === key) {
        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortKey = key
        this.sortDir = 'asc'
      }
      this.page = 1
      await this.fetchIssues(projectId)
    },

    /**
     * @param {number} projectId
     * @param {number} page  1-based page number.
     * @returns {Promise<void>}
     */
    async setPage(projectId, page) {
      this.page = page
      await this.fetchIssues(projectId)
    },

    /**
     * Change the page size (remembered in localStorage), return to page 1 and reload.
     * @param {number} projectId
     * @param {number} size  One of PAGE_SIZES.
     * @returns {Promise<void>}
     */
    async setPageSize(projectId, size) {
      this.pageSize = size
      this.page = 1
      try {
        localStorage.setItem(PAGE_SIZE_KEY, String(size))
      } catch {
        // Storage unavailable — the choice just isn't remembered
      }
      await this.fetchIssues(projectId)
    },

    /**
     * Fetch member efficiency records for a project, optionally scoped to an iteration.
     * @param {number}      projectId
     * @param {string|null} [iteration=null]  Sprint name; null = all iterations.
     * @returns {Promise<void>}
     */
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

    /**
     * Start polling the current issues page at the given interval.
     * Clears any existing timer before starting a new one.
     * @param {number} projectId
     * @param {number} [intervalMs=60000]
     */
    startPolling(projectId, intervalMs = 60000) {
      this.stopPolling()
      this.issuesPollingTimer = setInterval(() => {
        if (!this.issuesLoading) {
          this.fetchIssues(projectId)
        }
      }, intervalMs)
    },

    /**
     * Stop the active issues polling timer.
     */
    stopPolling() {
      if (this.issuesPollingTimer !== null) {
        clearInterval(this.issuesPollingTimer)
        this.issuesPollingTimer = null
      }
    },
  },
})
