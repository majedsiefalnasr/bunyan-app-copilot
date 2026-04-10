import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import nuxt from 'nuxt/config'
import { fileURLToPath } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '#app': fileURLToPath(new URL('./.nuxt', import.meta.url)),
      '#build': fileURLToPath(new URL('./.nuxt', import.meta.url)),
      '~': fileURLToPath(new URL('./', import.meta.url)),
      '@': fileURLToPath(new URL('./', import.meta.url)),
    },
  },
  test: {
    environment: 'jsdom',
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      exclude: [
        'node_modules/',
        '.nuxt/',
      ],
    },
  },
})
