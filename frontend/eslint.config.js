import { createConfigForNuxt } from '@nuxt/eslint-config/flat';

/**
 * ESLint 9+ flat config. Replaces legacy `.eslintrc.json`.
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
);
