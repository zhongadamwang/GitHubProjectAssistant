import { defineStore } from 'pinia'
import * as api from '../services/api.js'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
  }),

  getters: {
    isAuthenticated: (state) => state.user !== null,
    isAdmin: (state) => state.user?.role === 'admin',
  },

  actions: {
    /**
     * Authenticate with the API and load the current user into state.
     * @param {string} email
     * @param {string} password
     * @returns {Promise<void>}
     */
    async login(email, password) {
      await api.login(email, password)
      await this.fetchMe()
    },

    /**
     * Log out the current user: calls the API logout endpoint, clears auth
     * state, and redirects to the login page.
     * @returns {Promise<void>}
     */
    async logout() {
      try {
        await api.logout()
      } catch {
        // Swallow — session may already be gone
      }
      this.clearAuth()
      const { default: router } = await import('../router/index.js')
      router.push('/login')
    },

    /**
     * Fetch the currently authenticated user from GET /api/auth/me and
     * populate `state.user`.  Silently sets `user` to null on 401.
     * @returns {Promise<void>}
     */
    async fetchMe() {
      try {
        const data = await api.getMe()
        this.user = data.user ?? data
      } catch {
        // 401 means not logged in — not an error
        this.user = null
      }
    },

    /**
     * Clear authentication state without calling the API.
     * Used by the Axios interceptor on 401 responses.
     */
    clearAuth() {
      this.user = null
    },
  },
})
