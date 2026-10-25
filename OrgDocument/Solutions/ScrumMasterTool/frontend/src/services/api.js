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
      // 502/503 with an API message (GitHub rejected the token, APP_KEY missing, …) is shown by the view itself
      const explained = [502, 503].includes(status) && typeof error.response.data?.error === 'string'
      if (!explained) dispatchAppError('Server error. Please try again later.')
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
 * GET /api/projects — list tracked projects (admins can include archived ones).
 * @param {{includeArchived?: boolean}} [opts]
 * @returns {Promise<{projects: Array<Object>}>}
 */
export const getProjects = ({ includeArchived = false } = {}) =>
  unwrap(instance.get('/projects', { params: includeArchived ? { include_archived: 1 } : {} }))

/**
 * GET /api/projects/:id — get a single project with issue counts.
 * @param {number} id  Local project ID.
 * @returns {Promise<{project: Object}>}
 */
export const getProject = (id) =>
  unwrap(instance.get(`/projects/${id}`))

// ── Issues ────────────────────────────────────────────────────────────────
/**
 * GET /api/projects/:projectId/issues — one page of issues (read-only; hours come from GitHub).
 * @param {number} projectId
 * @param {{page?: number, page_size?: number, sort?: string, dir?: 'asc'|'desc',
 *          assignee?: string, iteration?: string, status?: string}} [params]
 * @returns {Promise<{issues: Array<Object>, total: number,
 *   meta: {page: number, page_size: number, total: number, total_pages: number},
 *   totals: {estimated: number, remaining: number, actual: number},
 *   assignees: Array<string>}>}
 */
export const getIssues = (projectId, params = {}) =>
  unwrap(instance.get(`/projects/${projectId}/issues`, { params }))

/**
 * GET /api/projects/:projectId/iterations — sprint labels of a project, newest first.
 * @param {number} projectId
 * @returns {Promise<{iterations: Array<{name: string, start_date: string|null,
 *   duration_days: number|null, is_current: boolean, is_completed: boolean, has_data: boolean}>}>}
 */
export const getIterations = (projectId) =>
  unwrap(instance.get(`/projects/${projectId}/iterations`))

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
 * GET /api/sync/history — latest sync history records (20), optionally for one project.
 * @param {number} [projectId]  Local project id; omitted = all projects.
 * @returns {Promise<{data: Array<Object>}>}
 */
export const getSyncHistory = (projectId = 0) =>
  unwrap(instance.get('/sync/history', { params: projectId ? { project_id: projectId } : {} }))

/**
 * POST /api/projects/:id/sync — sync ONE project now (admin only).
 * @param {number} projectId
 * @returns {Promise<{status: string, issues_added: number, issues_updated: number, unchanged: number, errors: number}>}
 */
export const syncProject = (projectId) =>
  unwrap(instance.post(`/projects/${projectId}/sync`))

// ── Admin / GitHub accounts ───────────────────────────────────────────────
// The access token is only ever sent to the server (create / replace); no response contains it.

/** @returns {Promise<{accounts: Array<Object>}>} */
export const getGitHubAccounts = () =>
  unwrap(instance.get('/admin/github-accounts'))

/**
 * @param {{label: string, owner_login: string, token: string, graphql_url?: string}} data
 * @returns {Promise<{account: Object}>}
 */
export const createGitHubAccount = (data) =>
  unwrap(instance.post('/admin/github-accounts', data))

/**
 * @param {number} id
 * @param {{label?: string, graphql_url?: string, token?: string}} data  A token replaces the stored one.
 * @returns {Promise<{account: Object}>}
 */
export const updateGitHubAccount = (id, data) =>
  unwrap(instance.put(`/admin/github-accounts/${id}`, data))

/** @param {number} id */
export const deleteGitHubAccount = (id) =>
  unwrap(instance.delete(`/admin/github-accounts/${id}`))

/** @returns {Promise<{account: Object, result: {status: string, message: string}}>} */
export const testGitHubAccount = (id) =>
  unwrap(instance.post(`/admin/github-accounts/${id}/test`))

/** @returns {Promise<{projects: Array<Object>}>} Projects the account can see on GitHub. */
export const discoverProjects = (accountId) =>
  unwrap(instance.get(`/admin/github-accounts/${accountId}/projects`))

/** @returns {Promise<Object>} Result of moving the legacy .env settings into an account. */
export const importEnvAccount = () =>
  unwrap(instance.post('/admin/github-accounts/import-env'))

/** @returns {Promise<{entries: Array<Object>}>} */
export const getAuditLog = () =>
  unwrap(instance.get('/admin/audit-log'))

// ── Admin / Projects ──────────────────────────────────────────────────────
/**
 * @param {number} accountId
 * @param {number} number  GitHub project number.
 * @returns {Promise<{project: Object, warning?: string}>}
 */
export const trackProject = (accountId, number) =>
  unwrap(instance.post('/admin/projects', { account_id: accountId, number }))

/**
 * @param {number} id
 * @param {{field_mapping?: Object|null, is_tracked?: boolean}} data
 * @returns {Promise<{project: Object}>}
 */
export const updateProject = (id, data) =>
  unwrap(instance.put(`/admin/projects/${id}`, data))

/** Stop tracking (archive; data is kept). */
export const untrackProject = (id) =>
  unwrap(instance.delete(`/admin/projects/${id}`))

/** Permanently delete an archived project's data; `confirm` must equal the project name. */
export const deleteProjectData = (id, confirm) =>
  unwrap(instance.delete(`/admin/projects/${id}/data`, { data: { confirm } }))

/** @returns {Promise<{mapping: Object, fields: Array<Object>, ok: boolean}>} */
export const validateProjectFields = (id) =>
  unwrap(instance.post(`/admin/projects/${id}/validate-fields`))

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
