<template>
  <NavBar />
  <ErrorBanner />
  <router-view />
</template>

<script setup>
import ErrorBanner from './components/ErrorBanner.vue'
import NavBar from './components/NavBar.vue'
</script>
