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
export const login = (email, password) =>
  unwrap(instance.post('/auth/login', { email, password }))

export const logout = () =>
  unwrap(instance.post('/auth/logout'))

export const getMe = () =>
  unwrap(instance.get('/auth/me'))

// ── Projects ──────────────────────────────────────────────────────────────
export const getProjects = () =>
  unwrap(instance.get('/projects'))

export const getProject = (id) =>
  unwrap(instance.get(`/projects/${id}`))

// ── Issues ────────────────────────────────────────────────────────────────
export const getIssues = (projectId, params = {}) =>
  unwrap(instance.get(`/projects/${projectId}/issues`, { params }))

export const updateIssueTime = (issueId, data) =>
  unwrap(instance.put(`/issues/${issueId}/time`, data))

// ── Burndown ──────────────────────────────────────────────────────────────
export const getBurndown = (projectId, iteration = null) => {
  const params = iteration ? { iteration } : {}
  return unwrap(instance.get(`/projects/${projectId}/burndown`, { params }))
}

// ── Members ───────────────────────────────────────────────────────────────
export const getMembers = (projectId, iteration = null) => {
  const params = iteration ? { iteration } : {}
  return unwrap(instance.get(`/projects/${projectId}/members`, { params }))
}

// ── Sync ──────────────────────────────────────────────────────────────────
export const getSyncHistory = () =>
  unwrap(instance.get('/sync/history'))

export const triggerSync = () =>
  unwrap(instance.post('/sync/trigger'))

// ── Admin / Users ─────────────────────────────────────────────────────────
export const getUsers = () =>
  unwrap(instance.get('/admin/users'))

export const createUser = (data) =>
  unwrap(instance.post('/admin/users', data))

export default instance
