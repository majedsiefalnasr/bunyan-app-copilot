// frontend/composables/useDirection.ts
import { useI18n } from 'vue-i18n';
import type { Direction } from '../types/index';

const STORAGE_KEY = 'bunyan_direction';
const OVERRIDE_KEY = 'bunyan_direction_manual';

export function useDirection() {
  const { locale } = useI18n();

  // Initialize direction: stored value first, then locale-derived default
  const initialDirection = (): Direction => {
    if (import.meta.client) {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored === 'rtl' || stored === 'ltr') return stored;
    }
    return locale.value === 'en' ? 'ltr' : 'rtl';
  };

  const direction = ref<Direction>(initialDirection());
  const hasManualOverride = ref<boolean>(
    import.meta.client ? localStorage.getItem(OVERRIDE_KEY) === 'true' : false
  );

  function applyDirection(dir: Direction) {
    direction.value = dir;
    if (import.meta.client) {
      document.documentElement.dir = dir;
      localStorage.setItem(STORAGE_KEY, dir);
    }
  }

  function setDirection(dir: Direction) {
    hasManualOverride.value = true;
    if (import.meta.client) {
      localStorage.setItem(OVERRIDE_KEY, 'true');
    }
    applyDirection(dir);
  }

  function toggle() {
    setDirection(direction.value === 'rtl' ? 'ltr' : 'rtl');
  }

  // Auto-sync with locale changes only if no manual override
  watch(locale, (newLocale) => {
    if (!hasManualOverride.value) {
      applyDirection(newLocale === 'en' ? 'ltr' : 'rtl');
    }
  });

  return {
    direction: readonly(direction),
    hasManualOverride: readonly(hasManualOverride),
    setDirection,
    toggle,
  };
}
