<script setup>
/**
 * @component NavBar
 * @description Top navigation shown to authenticated users: links to every view
 * (Admin only for admins), the signed-in user's name and a logout button.
 */
import { useAuthStore } from '../stores/authStore.js'

const authStore = useAuthStore()
</script>

<template>
  <nav v-if="authStore.isAuthenticated" class="navbar">
    <div class="links">
      <router-link to="/">Dashboard</router-link>
      <router-link to="/issues">Issues</router-link>
      <router-link to="/members">Members</router-link>
      <router-link to="/sync">Sync</router-link>
      <router-link v-if="authStore.isAdmin" to="/admin">Admin</router-link>
    </div>
    <div class="user">
      <span>{{ authStore.user?.display_name ?? authStore.user?.email }}</span>
      <button type="button" @click="authStore.logout()">Log out</button>
    </div>
  </nav>
</template>

<style scoped>
.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.6rem 2rem;
  background: #1a1a2e;
  color: #fff;
}
.links {
  display: flex;
  gap: 1.25rem;
}
.links a {
  color: #cfd3ff;
  text-decoration: none;
  font-weight: 600;
}
.links a.router-link-exact-active {
  color: #fff;
  border-bottom: 2px solid #4361ee;
}
.user {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.9rem;
}
.user button {
  background: transparent;
  color: #fff;
  border: 1px solid #fff;
  border-radius: 4px;
  padding: 0.25rem 0.75rem;
  cursor: pointer;
}
</style>
