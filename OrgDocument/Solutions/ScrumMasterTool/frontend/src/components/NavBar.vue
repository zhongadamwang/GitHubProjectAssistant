<script setup>
/**
 * @component NavBar
 * @description Top navigation shown to authenticated users: links to every view
 * (Admin only for admins), the signed-in user's name and a logout button.
 */
import { watch } from 'vue'
import { useAuthStore } from '../stores/authStore.js'
import { useProjectStore } from '../stores/projectStore.js'

const authStore = useAuthStore()
const projectStore = useProjectStore()

// Load the tracked projects once the user is signed in (also after a page reload)
watch(
  () => authStore.isAuthenticated,
  (signedIn) => {
    if (signedIn) projectStore.fetchProjects()
  },
  { immediate: true },
)

const onSelect = (event) => projectStore.selectProject(Number(event.target.value))
</script>

<template>
  <nav v-if="authStore.isAuthenticated" class="navbar">
    <div class="links">
      <router-link to="/">Dashboard</router-link>
      <router-link to="/issues">Issues</router-link>
      <router-link to="/members">Members</router-link>
      <router-link to="/sync">Sync</router-link>
      <router-link v-if="authStore.isAdmin" to="/admin" :class="{ 'router-link-exact-active': $route.path.startsWith('/admin') }">Admin</router-link>
    </div>
    <div class="user">
      <label v-if="projectStore.projects.length" class="project-switcher">
        <span class="sr-only">Project</span>
        <select aria-label="Project" :value="projectStore.activeProjectId" @change="onSelect">
          <option v-for="p in projectStore.projects" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
      </label>
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
.project-switcher select {
  max-width: 260px;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  border: 1px solid #4361ee;
  background: #24244a;
  color: #fff;
  font-size: 0.9rem;
}
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
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
