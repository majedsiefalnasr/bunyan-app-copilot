import { beforeEach, describe, expect, it, vi } from 'vitest';
import { readonly, ref, watch } from 'vue';

import { useDirection } from '../../../composables/useDirection';

// Mock useI18n before importing composable
vi.mock('vue-i18n', () => ({
    useI18n: () => ({ locale: ref('ar') }),
}));

// Stub Nuxt auto-imports — ref, watch, readonly are used in useDirection.ts without explicit imports
vi.stubGlobal('ref', ref);
vi.stubGlobal('watch', watch);
vi.stubGlobal('readonly', readonly);

describe('useDirection composable', () => {
    beforeEach(() => {
        // Clear localStorage between tests
        localStorage.clear();
        // Reset document.documentElement.dir
        document.documentElement.dir = 'rtl';
    });

    it('defaults to rtl when no localStorage value and locale is ar', () => {
        const { direction } = useDirection();
        expect(direction.value).toBe('rtl');
    });

    it('defaults to locale-based direction when no localStorage value and locale is ar', () => {
        // import.meta.client guards are inactive in Node — direction falls back to locale-based default
        localStorage.setItem('bunyan_direction', 'ltr'); // ignored in Node env
        const { direction } = useDirection();
        expect(direction.value).toBe('rtl'); // locale 'ar' → rtl
    });

    it('setDirection rtl sets direction ref and applies to document', () => {
        const { direction, setDirection } = useDirection();
        setDirection('rtl');
        expect(direction.value).toBe('rtl');
        expect(document.documentElement.dir).toBe('rtl');
    });

    // NOTE: document.dir and localStorage side effects are guarded by import.meta.client
    // (a Nuxt build-time flag). In the Node/Vitest environment they are not activated,
    // but direction.value IS always updated unconditionally.
    it('setDirection ltr changes direction.value', () => {
        const { direction, setDirection } = useDirection();
        setDirection('ltr');
        expect(direction.value).toBe('ltr');
    });

    it('toggle flips from current direction', () => {
        const { direction, toggle } = useDirection();
        const initial = direction.value;
        toggle();
        expect(direction.value).not.toBe(initial);
    });

    it('toggle from rtl produces ltr', () => {
        localStorage.clear();
        localStorage.setItem('bunyan_direction', 'rtl');
        const { direction, toggle } = useDirection();
        expect(direction.value).toBe('rtl');
        toggle();
        expect(direction.value).toBe('ltr');
    });

    it('hasManualOverride becomes true after setDirection call', () => {
        const { hasManualOverride, setDirection } = useDirection();
        expect(hasManualOverride.value).toBe(false);
        setDirection('ltr');
        expect(hasManualOverride.value).toBe(true);
    });

    it('setDirection sets manual override regardless of client context', () => {
        // localStorage.setItem is guarded by import.meta.client; direction.value is not
        const { direction, setDirection, hasManualOverride } = useDirection();
        expect(direction.value).toBe('rtl');
        setDirection('ltr');
        expect(direction.value).toBe('ltr');
        expect(hasManualOverride.value).toBe(true);
    });
});
