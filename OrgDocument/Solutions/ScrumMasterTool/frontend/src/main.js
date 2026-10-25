import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router/index.js'
import { useAuthStore } from './stores/authStore.js'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)

// Restore session state before installing the router: installing it triggers the
// initial navigation, whose guard needs to know whether the user is authenticated.
const authStore = useAuthStore()
authStore.fetchMe().finally(() => {
  app.use(router)
  app.mount('#app')
})
