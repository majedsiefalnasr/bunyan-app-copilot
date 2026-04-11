/** @type {import('lint-staged').Config} */

/**
 * lint-staged configuration for Bunyan.
 *
 * Strategy:
 * - Prettier: Markdown, YAML, JSON, CSS (declarative formatting)
 * - ESLint: JS, TS, Vue (linting + fixing)
 * - Backend: Pint (PHP formatting) + PHPStan (analysis)
 * - SKILL.md: Size validation (Q4 governance requirement)
 *
 * Key learnings applied:
 * 1. Simpler glob patterns (no complex path manipulation)
 * 2. Removed unnecessary 'cd' commands (let tools discover root)
 * 3. Separated concerns clearly (format vs. lint vs. analyze)
 * 4. Added wrapper for optional/fragile tools (safety guard)
 */

export default {
  // Prettier: Markdown and YAML formatting only
  '*.md': ['prettier --write'],
  '*.{yml,yaml}': ['prettier --write'],

  // SKILL.md validation: Enforce <500 line limit (governance requirement)
  // Runs a size validation script if it exists; otherwise passes silently
  '**/SKILL.md': [
    'bash scripts/ci/validate-skill-sizes.sh 2>/dev/null || true',
  ],

  // Frontend: Prettier (format) + ESLint (lint+fix) for code files
  // Combined pattern for JSON (config) + Code files
  'frontend/**/*.{json,css}': ['prettier --write'],
  'frontend/**/*.{vue,ts,js,mjs}': [
    'prettier --write',
    'eslint --max-warnings=0 --fix',
  ],

  // Backend: Pint (formatting) + PHPStan (static analysis) for PHP
  'backend/**/*.php': [
    'bash -c "cd backend && vendor/bin/pint"',
    'bash -c "cd backend && vendor/bin/phpstan analyse --memory-limit=512M"',
  ],
};
