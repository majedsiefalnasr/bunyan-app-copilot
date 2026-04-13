import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useBreadcrumb } from '../../../composables/useBreadcrumb';

// Stub Nuxt globals before any module-level code in useBreadcrumb.ts runs.
// useState is called at MODULE LEVEL, so it must be available at import time.
// vi.hoisted runs before all imports — this is the only safe approach for
// module-level Nuxt auto-imports.
const sharedManualBreadcrumbs = vi.hoisted(() => {
  let _value: unknown = null;
  const mockRef = {
    get value() {
      return _value;
    },
    set value(v: unknown) {
      _value = v;
    },
  };

  (globalThis as Record<string, unknown>).useState = (_key: string, _init?: () => unknown) =>
    mockRef;
  // Minimal computed stub — re-evaluates getter on every .value access (no caching needed for unit tests)
  (globalThis as Record<string, unknown>).computed = (fn: () => unknown) => ({
    get value() {
      return fn();
    },
  });

  return mockRef;
});

// Mock vue-router — explicit import in useBreadcrumb.ts
const mockRouteMeta: Record<string, unknown> = {};
vi.mock('vue-router', () => ({
  useRoute: () => ({ meta: mockRouteMeta }),
}));

describe('useBreadcrumb composable', () => {
  beforeEach(() => {
    // Reset shared manual breadcrumbs state between tests
    sharedManualBreadcrumbs.value = null;
    // Reset route meta key used by this composable
    mockRouteMeta.breadcrumb = undefined;
  });

  it('returns empty array when no manual breadcrumbs and no route meta', () => {
    const { breadcrumbs } = useBreadcrumb();
    expect(breadcrumbs.value).toEqual([]);
  });

  it('returns route.meta.breadcrumb when set and no manual override', () => {
    const items = [
      { label: 'Home', to: '/' },
      { label: 'Projects', to: '/projects' },
    ];
    mockRouteMeta.breadcrumb = items;

    const { breadcrumbs } = useBreadcrumb();
    expect(breadcrumbs.value).toEqual(items);
  });

  it('setBreadcrumbs overrides route.meta.breadcrumb', () => {
    mockRouteMeta.breadcrumb = [{ label: 'Home', to: '/' }];
    const manualItems = [{ label: 'Override', to: '/custom' }];

    const { breadcrumbs, setBreadcrumbs } = useBreadcrumb();
    setBreadcrumbs(manualItems);

    expect(breadcrumbs.value).toEqual(manualItems);
  });

  it('clearBreadcrumbs falls back to route.meta.breadcrumb', () => {
    const routeItems = [{ label: 'Dashboard', to: '/dashboard' }];
    mockRouteMeta.breadcrumb = routeItems;

    const { breadcrumbs, setBreadcrumbs, clearBreadcrumbs } = useBreadcrumb();
    setBreadcrumbs([{ label: 'Manual', to: '/manual' }]);
    expect(breadcrumbs.value).toEqual([{ label: 'Manual', to: '/manual' }]);

    clearBreadcrumbs();
    expect(breadcrumbs.value).toEqual(routeItems);
  });

  it('clearBreadcrumbs returns empty array when no route meta', () => {
    const { breadcrumbs, setBreadcrumbs, clearBreadcrumbs } = useBreadcrumb();
    setBreadcrumbs([{ label: 'Set', to: '/set' }]);
    clearBreadcrumbs();
    expect(breadcrumbs.value).toEqual([]);
  });

  it('manual breadcrumbs are shared across multiple useBreadcrumb() instances', () => {
    const instance1 = useBreadcrumb();
    const instance2 = useBreadcrumb();

    instance1.setBreadcrumbs([{ label: 'Shared', to: '/shared' }]);

    // Both instances see the same value since _manualBreadcrumbs is module-scoped
    expect(instance2.breadcrumbs.value).toEqual([{ label: 'Shared', to: '/shared' }]);
  });

  it('route.meta.breadcrumb is ignored when it is not an array', () => {
    mockRouteMeta.breadcrumb = 'not-an-array';
    const { breadcrumbs } = useBreadcrumb();
    expect(breadcrumbs.value).toEqual([]);
  });
});
