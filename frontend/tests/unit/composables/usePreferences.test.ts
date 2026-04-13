import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { computed as vueComputed, ref } from 'vue';

import { usePreferences } from '../../../composables/usePreferences';

// computed is a Nuxt auto-import used in usePreferences.ts return value
vi.stubGlobal('computed', vueComputed);

// Mock vue-i18n — explicitly imported by usePreferences.ts
const mockSetLocale = vi.fn();
const mockLocale = ref('ar');
vi.mock('vue-i18n', () => ({
  useI18n: () => ({ locale: mockLocale, setLocale: mockSetLocale }),
}));

// Mock useDirection — explicitly imported by usePreferences.ts
const mockToggleDirection = vi.fn();
const mockSetDirection = vi.fn();
const mockDirection = ref<'rtl' | 'ltr'>('rtl');
vi.mock('../../../composables/useDirection', () => ({
  useDirection: () => ({
    direction: mockDirection,
    toggle: mockToggleDirection,
    setDirection: mockSetDirection,
  }),
}));

// Stub useColorMode — Nuxt auto-import (no explicit import in usePreferences.ts)
const mockColorMode = { value: 'light' as string, preference: 'light' as string };
vi.stubGlobal('useColorMode', vi.fn().mockReturnValue(mockColorMode));

describe('usePreferences composable', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Reset mockColorMode state
    mockColorMode.value = 'light';
    mockColorMode.preference = 'light';
    // Reset direction
    mockDirection.value = 'rtl';
    // Reset locale
    mockLocale.value = 'ar';
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.stubGlobal('computed', vueComputed);
    vi.stubGlobal('useColorMode', vi.fn().mockReturnValue(mockColorMode));
  });

  // ── Direction delegation ─────────────────────────────────────────────

  it('exposes direction from useDirection()', () => {
    mockDirection.value = 'rtl';
    const { direction } = usePreferences();
    expect(direction.value).toBe('rtl');
  });

  it('toggleDirection delegates to useDirection().toggle()', () => {
    const { toggleDirection } = usePreferences();
    toggleDirection();
    expect(mockToggleDirection).toHaveBeenCalledOnce();
  });

  it('setDirection delegates to useDirection().setDirection()', () => {
    const { setDirection } = usePreferences();
    setDirection('ltr');
    expect(mockSetDirection).toHaveBeenCalledWith('ltr');
  });

  // ── Locale delegation ────────────────────────────────────────────────

  it('exposes locale from useI18n()', () => {
    mockLocale.value = 'en';
    const { locale } = usePreferences();
    expect(locale.value).toBe('en');
  });

  it('setLocale delegates to useI18n().setLocale()', () => {
    const { setLocale } = usePreferences();
    setLocale('en' as never);
    expect(mockSetLocale).toHaveBeenCalledWith('en');
  });

  // ── Color mode cycling ───────────────────────────────────────────────

  it('exposes colorMode computed from useColorMode().value', () => {
    mockColorMode.value = 'light';
    const { colorMode } = usePreferences();
    expect(colorMode.value).toBe('light');
  });

  it('toggleColorMode cycles from light → dark', () => {
    mockColorMode.value = 'light';
    const { toggleColorMode } = usePreferences();
    toggleColorMode();
    expect(mockColorMode.preference).toBe('dark');
  });

  it('toggleColorMode cycles from dark → system', () => {
    mockColorMode.value = 'dark';
    const { toggleColorMode } = usePreferences();
    toggleColorMode();
    expect(mockColorMode.preference).toBe('system');
  });

  it('toggleColorMode cycles from system → light', () => {
    mockColorMode.value = 'system';
    const { toggleColorMode } = usePreferences();
    toggleColorMode();
    expect(mockColorMode.preference).toBe('light');
  });
});
