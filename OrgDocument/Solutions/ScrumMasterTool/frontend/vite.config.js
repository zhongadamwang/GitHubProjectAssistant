import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  // Base path defaults to '/' (root) which is correct for subdomain deployment
  base: process.env.VITE_BASE_PATH || '/',
  build: {
    // Output compiled assets into the PHP document root
    outDir: '../public/dist',
    emptyOutDir: true,
  },
  server: {
    port: 5173,
    proxy: {
      // Forward all /api/* requests to the PHP dev server
      '/api': {
        target: 'http://localhost:8080',
        changeOrigin: true,
      },
    },
  },
})
