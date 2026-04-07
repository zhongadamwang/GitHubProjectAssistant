import axios from 'axios'

/**
 * Dispatch a global app-error event consumed by ErrorBanner.vue in App.vue.
 * Keeps Axios interceptors decoupled from the Vue component tree.
 */
function dispatchAppError(message) {
  window.dispatchEvent(new CustomEvent('app-error', { detail: { message } }))
}

const instance = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// 401 interceptor — auto-logout and redirect to login
// Network error interceptor — surface connection problems as banner messages
instance.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error.response?.status

    if (status === 401) {
      // Dynamically import to avoid circular dependency at module load time
      const { useAuthStore } = await import('../stores/authStore.js')
      const authStore = useAuthStore()
      authStore.clearAuth()

      const { default: router } = await import('../router/index.js')
      router.push('/login')
    } else if (status === 403) {
      dispatchAppError('You do not have permission to perform this action.')
    } else if (status === 404) {
      dispatchAppError('The requested resource was not found.')
    } else if (status >= 500) {
      dispatchAppError('Server error. Please try again later.')
    } else if (!error.response) {
      // Network error — no response received (offline, DNS failure, server down)
      dispatchAppError('Network error. Please check your connection and try again.')
    }

    return Promise.reject(error)
  }
)

// Helper to unwrap axios response data
const unwrap = (promise) => promise.then((r) => r.data)

// ── Auth ─────────────────────────────────────────────────────────────────
/**
 * POST /api/auth/login — start an authenticated session.
 * @param {string} email
 * @param {string} password
 * @returns {Promise<{user: Object}>}
 */
export const login = (email, password) =>
  unwrap(instance.post('/auth/login', { email, password }))

/**
 * POST /api/auth/logout — destroy the current session cookie.
 * @returns {Promise<{message: string}>}
 */
export const logout = () =>
  unwrap(instance.post('/auth/logout'))

/**
 * GET /api/auth/me — return the currently authenticated user.
 * @returns {Promise<{user: Object}>}
 */
export const getMe = () =>
  unwrap(instance.get('/auth/me'))

// ── Projects ──────────────────────────────────────────────────────────────
/**
 * GET /api/projects — list all synced projects.
 * @returns {Promise<{projects: Array<Object>}>}
 */
export const getProjects = () =>
  unwrap(instance.get('/projects'))

/**
 * GET /api/projects/:id — get a single project with issue counts.
 * @param {number} id  Local project ID.
 * @returns {Promise<{project: Object}>}
 */
export const getProject = (id) =>
  unwrap(instance.get(`/projects/${id}`))

// ── Issues ────────────────────────────────────────────────────────────────
/**
 * GET /api/projects/:projectId/issues — list issues with optional filters.
 * @param {number} projectId
 * @param {{assignee?: string, iteration?: string, status?: string}} [params]
 * @returns {Promise<{issues: Array<Object>, total: number}>}
 */
export const getIssues = (projectId, params = {}) =>
  unwrap(instance.get(`/projects/${projectId}/issues`, { params }))

/**
 * PUT /api/issues/:id/time — update time-tracking fields for an issue.
 * @param {number} issueId
 * @param {{estimated_time?: number, remaining_time?: number, actual_time?: number}} data
 * @returns {Promise<Object>}  Updated issue row.
 */
export const updateIssueTime = (issueId, data) =>
  unwrap(instance.put(`/issues/${issueId}/time`, data))

// ── Burndown ──────────────────────────────────────────────────────────────
/**
 * GET /api/projects/:projectId/burndown — fetch burndown chart data.
 * @param {number}      projectId
 * @param {string|null} [iteration=null]  Sprint name; null resolves the latest.
 * @returns {Promise<{project_id: number, iteration: string, points: Array<Object>}>}
 */
export const getBurndown = (projectId, iteration = null) => {
  const params = iteration ? { iteration } : {}
  return unwrap(instance.get(`/projects/${projectId}/burndown`, { params }))
}

// ── Members ───────────────────────────────────────────────────────────────
/**
 * GET /api/projects/:projectId/members — fetch member efficiency data.
 * @param {number}      projectId
 * @param {string|null} [iteration=null]  Sprint name; null = all iterations.
 * @returns {Promise<{project_id: number, iteration: string|null, members: Array<Object>, trend: Object}>}
 */
export const getMembers = (projectId, iteration = null) => {
  const params = iteration ? { iteration } : {}
  return unwrap(instance.get(`/projects/${projectId}/members`, { params }))
}

// ── Sync ──────────────────────────────────────────────────────────────────
/**
 * GET /api/sync/history — return the last 20 sync history records.
 * @returns {Promise<{data: Array<Object>}>}
 */
export const getSyncHistory = () =>
  unwrap(instance.get('/sync/history'))

/**
 * POST /api/sync/trigger — trigger a manual GitHub sync (admin only).
 * @returns {Promise<{status: string, issues_added: number, issues_updated: number, unchanged: number, errors: number, snapshot_file: string}>}
 */
export const triggerSync = () =>
  unwrap(instance.post('/sync/trigger'))

// ── Admin / Users ─────────────────────────────────────────────────────────
/**
 * GET /api/admin/users — list all registered users (admin only).
 * @returns {Promise<{users: Array<Object>}>}
 */
export const getUsers = () =>
  unwrap(instance.get('/admin/users'))

/**
 * POST /api/admin/users — create a new user account (admin only).
 * @param {{email: string, display_name: string, password: string, role: string, github_username?: string}} data
 * @returns {Promise<{user: Object}>}
 */
export const createUser = (data) =>
  unwrap(instance.post('/admin/users', data))

export default instance
