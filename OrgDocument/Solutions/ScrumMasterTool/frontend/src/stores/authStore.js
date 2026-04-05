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
    async login(email, password) {
      await api.login(email, password)
      await this.fetchMe()
    },

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

    async fetchMe() {
      try {
        const data = await api.getMe()
        this.user = data.user ?? data
      } catch {
        // 401 means not logged in — not an error
        this.user = null
      }
    },

    clearAuth() {
      this.user = null
    },
  },
})
