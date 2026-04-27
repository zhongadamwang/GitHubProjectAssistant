import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  // Base path for subdirectory deployment (match APP_BASE_PATH in .env)
  // For local dev, use '/' - for production build with subdirectory, use '/website_98511c15/'
//  base: process.env.VITE_BASE_PATH || '/',
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
