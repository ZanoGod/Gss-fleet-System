import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

export default defineConfig({
  plugins: [vue(), vueDevTools()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: '127.0.0.1',
    port: 5173,
    proxy: {
      '^/(api|sanctum)(/|$)': {
        target: process.env.DEV_BACKEND_URL || 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
      // Vue Router owns GET /login; only its POST request belongs to Laravel.
      '/login': {
        target: process.env.DEV_BACKEND_URL || 'http://127.0.0.1:8000',
        changeOrigin: true,
        bypass: request => request.method === 'GET' ? '/index.html' : undefined,
      },
      '/register': {
        target: process.env.DEV_BACKEND_URL || 'http://127.0.0.1:8000',
        changeOrigin: true,
        bypass: request => request.method === 'GET' ? '/index.html' : undefined,
      },
      '/logout': {
        target: process.env.DEV_BACKEND_URL || 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
})
