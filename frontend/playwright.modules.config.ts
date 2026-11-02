import { defineConfig, devices } from '@playwright/test'
export default defineConfig({
  testDir: './e2e',
  testMatch: ['fleet-modules-live.spec.ts', 'drivers-live.spec.ts'],
  workers: 1,
  timeout: 60000,
  reporter: [['list']],
  use: {
    ...devices['Desktop Chrome'],
    baseURL: process.env.GSS_LIVE_QA_URL || 'http://127.0.0.1:5174',
    headless: true,
    trace: 'retain-on-failure',
  },
})
