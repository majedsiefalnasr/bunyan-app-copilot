// frontend/composables/usePreferences.ts
// Facade composable: re-exports direction, locale, and colorMode controls.
// Adds NO new logic — pure re-export delegation.
import { useI18n } from 'vue-i18n';
import { useDirection } from './useDirection';

export function usePreferences() {
    const { direction, toggle: toggleDirection, setDirection } = useDirection();
    const { locale, setLocale } = useI18n();
    const colorMode = useColorMode();

    function toggleColorMode() {
        const cycle: Array<'light' | 'dark' | 'system'> = ['light', 'dark', 'system'];
        const currentIndex = cycle.indexOf(colorMode.value as 'light' | 'dark' | 'system');
        const next = cycle[(currentIndex + 1) % cycle.length];
        if (next) colorMode.preference = next;
    }

    return {
        direction,
        toggleDirection,
        setDirection,
        locale,
        setLocale,
        colorMode: computed(() => colorMode.value),
        toggleColorMode,
    };
}
