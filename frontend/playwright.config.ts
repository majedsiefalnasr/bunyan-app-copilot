import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : 2,
    reporter: 'html',
    expect: {
        timeout: 10000,
    },
    use: {
        baseURL: 'http://localhost:3001',
        trace: 'on-first-retry',
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],

    webServer: {
        command: 'npm run dev -- --port 3001',
        url: 'http://localhost:3001/ar',
        reuseExistingServer: !process.env.CI,
        timeout: 120000,
    },
});
