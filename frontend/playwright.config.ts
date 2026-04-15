import { defineConfig, devices } from '@playwright/test';

const ci = !!process.env.CI;
const isTruthy = (value: string | undefined) => {
  const normalized = value?.trim().toLowerCase();

  return normalized === '1' || normalized === 'true' || normalized === 'yes' || normalized === 'on';
};

const serverHost = process.env.PW_SERVER_HOST || '127.0.0.1';
const serverPort = Number(process.env.PW_SERVER_PORT || '3001');
const serverURL = process.env.PW_SERVER_URL || `http://${serverHost}:${serverPort}`;
/**
 * Keep browser traffic on `localhost` by default because some existing specs
 * hardcode cookie URLs against that host. The webServer health check can still
 * use IPv4 loopback to avoid `localhost` / `::1` mismatches on some machines.
 */
const baseURL = process.env.PW_BASE_URL || `http://localhost:${serverPort}`;
const devServerCommand =
  process.env.PW_WEB_SERVER_COMMAND ||
  `${ci ? 'npm run preview' : 'npm run dev'} -- --host ${serverHost} --port ${serverPort}`;

const headless = !isTruthy(process.env.PW_HEADED);
const browsers = (process.env.PW_BROWSERS || '').trim().toLowerCase();

const defaultProjects = [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }];
const allProjects = [
  { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
];

export default defineConfig({
  testDir: './tests/e2e',
  // One shared Nuxt server is more reliable than parallel workers fighting HMR.
  fullyParallel: false,
  workers: 1,

  forbidOnly: ci,
  retries: ci ? 2 : 0,
  globalTimeout: ci ? 15 * 60 * 1000 : 0,
  reporter: ci ? [['dot'], ['html', { open: 'never' }]] : [['list'], ['html', { open: 'never' }]],
  timeout: ci ? 45_000 : 30_000,
  expect: {
    timeout: 10_000,
  },
  use: {
    baseURL,
    headless,
    actionTimeout: 10_000,
    navigationTimeout: 15_000,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },

  // Fast by default: keep local runs on Chromium unless cross-browser is requested.
  projects: browsers === 'all' ? allProjects : defaultProjects,

  webServer: {
    command: devServerCommand,
    url: serverURL,
    reuseExistingServer: !ci,
    timeout: ci ? 180_000 : 120_000,
    stdout: 'ignore',
    stderr: ci ? 'ignore' : 'pipe',
    env: {
      ...process.env,
      PLAYWRIGHT_TEST: '1',
    },
  },
});
