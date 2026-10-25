<script setup>
/**
 * @component AdminTabs
 * @description Sub-navigation shared by the admin pages (users, GitHub accounts, projects).
 */
</script>

<template>
  <nav class="tabs" aria-label="Admin sections">
    <router-link to="/admin">Users</router-link>
    <router-link to="/admin/github">GitHub accounts</router-link>
    <router-link to="/admin/projects">Projects</router-link>
  </nav>
</template>

<style scoped>
.tabs { display: flex; gap: 0.25rem; margin-bottom: 1.25rem; border-bottom: 2px solid #e9ecef; }
.tabs a {
  padding: 0.5rem 1rem;
  color: #555;
  text-decoration: none;
  font-weight: 600;
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
}
.tabs a.router-link-exact-active { color: #4361ee; border-bottom-color: #4361ee; }
</style>
