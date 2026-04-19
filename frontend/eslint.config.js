import { createConfigForNuxt } from '@nuxt/eslint-config/flat';
import eslintConfigPrettier from 'eslint-config-prettier/flat';

/**
 * ESLint 9+ flat config. Replaces legacy `.eslintrc.json`.
 * `eslint-config-prettier` is appended last so ESLint --fix does not rewrite style in ways
 * that Prettier will undo (and vice versa). Use `npm run lint:fix` for a stable Prettier → ESLint order.
 * @see https://eslint.nuxt.com/packages/module
 */
const nuxtConfig = createConfigForNuxt(
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

// Test files need more relaxed rules
const testFilesConfig = {
  files: ['tests/**'],
  rules: {
    '@typescript-eslint/no-explicit-any': 'off',
    '@typescript-eslint/no-unused-vars': 'off',
    '@typescript-eslint/no-unused-expressions': 'off',
    'prefer-const': 'off',
  },
};

export default nuxtConfig
  .prepend({
    ignores: ['playwright-report/**', 'test-results/**', '.nuxt/**', '.output/**'],
  })
  .append(testFilesConfig)
  .append(eslintConfigPrettier);
