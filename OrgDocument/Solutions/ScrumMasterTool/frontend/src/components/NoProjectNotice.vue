<script setup>
/**
 * @component NoProjectNotice
 * @description Empty state shown by project-scoped views when no project is tracked yet.
 * Admins get a link to the setup pages; members are told to ask an admin.
 */
import { useAuthStore } from '../stores/authStore.js'

const authStore = useAuthStore()
</script>

<template>
  <section class="notice" role="status">
    <h2>No project to show yet</h2>
    <template v-if="authStore.isAdmin">
      <p>Connect a GitHub account and choose the projects to track.</p>
      <router-link class="cta" to="/admin/github">Set up GitHub</router-link>
    </template>
    <p v-else>No GitHub project has been set up yet. Please ask an administrator to add one.</p>
  </section>
</template>

<style scoped>
.notice {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 6px rgba(0, 0, 0, 0.08);
  padding: 2rem;
  text-align: center;
  color: #444;
}
h2 { margin: 0 0 0.5rem; font-size: 1.1rem; color: #1a1a2e; }
.cta {
  display: inline-block;
  margin-top: 0.5rem;
  padding: 0.5rem 1.1rem;
  background: #4361ee;
  color: #fff;
  border-radius: 4px;
  text-decoration: none;
  font-weight: 600;
}
</style>
