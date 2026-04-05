import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router/index.js'
import { useAuthStore } from './stores/authStore.js'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Restore session state before mounting to avoid flash-of-unauthenticated-content
const authStore = useAuthStore()
authStore.fetchMe().finally(() => {
  app.mount('#app')
})
