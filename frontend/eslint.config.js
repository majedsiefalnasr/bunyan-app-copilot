import { createConfigForNuxt } from '@nuxt/eslint-config/flat';
import eslintConfigPrettier from 'eslint-config-prettier/flat';

/**
 * ESLint 9+ flat config. Replaces legacy `.eslintrc.json`.
 * `eslint-config-prettier` is appended last so ESLint --fix does not rewrite style in ways
 * that Prettier will undo (and vice versa). Use `npm run lint:fix` for a stable Prettier → ESLint order.
 * @see https://eslint.nuxt.com/packages/module
 */
export default createConfigForNuxt(
    {
        features: {
            // Match previous setup: rely on Prettier for formatting; avoid noisy stylistic rules in CI.
            stylistic: false,
        },
    },
    {
        rules: {
            'vue/multi-word-component-names': 'off',
            // Vue 3 / Nuxt 3 allow multiple template roots.
            'vue/no-multiple-template-root': 'off',
        },
    }
)
    .prepend({
        ignores: ['playwright-report/**', 'test-results/**', '.nuxt/**', '.output/**'],
    })
    .append(eslintConfigPrettier);
