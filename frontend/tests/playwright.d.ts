// Playwright test type extensions
import type { Page } from '@playwright/test';

declare module '@playwright/test' {
  interface PlaywrightTestArgs {
    _page?: Page;
  }
}
