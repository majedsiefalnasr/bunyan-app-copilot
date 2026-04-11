/**
 * Staged-file checks for pre-commit. Paths are relative to the repo root.
 * Backend PHP: Laravel Pint (fix staged files), then PHPStan on staged files only.
 * Frontend JS/TS/Vue: Prettier (format), then ESLint (lint).
 * @param {string[]} filenames
 */
function toFrontendPaths(filenames) {
  return filenames.map((f) => f.replace(/^frontend\//, ''));
}

function toBackendPaths(filenames) {
  return filenames.map((f) => f.replace(/^backend\//, ''));
}

function shellQuote(paths) {
  return paths.map((p) => JSON.stringify(p)).join(' ');
}

export default {
  'frontend/**/*.{json,css}': (filenames) => {
    if (filenames.length === 0) {
      return [];
    }
    const rel = toFrontendPaths(filenames);
    return [
      `cd frontend && npx prettier --write ${shellQuote(rel)}`,
      `git add ${shellQuote(filenames)}`,
    ];
  },
  'frontend/**/*.{vue,ts,js,mjs,cjs}': (filenames) => {
    if (filenames.length === 0) {
      return [];
    }
    const rel = toFrontendPaths(filenames);
    const quoted = shellQuote(rel);
    return [
      `cd frontend && npx prettier --write ${quoted}`,
      `cd frontend && npx eslint --max-warnings=0 --fix ${quoted}`,
      `git add ${shellQuote(filenames)}`,
    ];
  },
  'backend/**/*.php': (filenames) => {
    if (filenames.length === 0) {
      return [];
    }
    const rel = toBackendPaths(filenames);
    const quoted = shellQuote(rel);
    return [
      `cd backend && vendor/bin/pint ${quoted}`,
      `cd backend && vendor/bin/phpstan analyse --memory-limit=512M ${quoted}`,
      `git add ${shellQuote(filenames)}`,
    ];
  },
};
