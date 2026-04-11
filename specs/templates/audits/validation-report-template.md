# Validation Report — {{STAGE_NAME}}

> **Phase:** {{PHASE_NAME}} > **Generated:** {{ISO_TIMESTAMP}}

## Test Results

### Backend (PHPUnit)

| Suite   | Tests | Passed | Failed | Skipped |
| ------- | ----- | ------ | ------ | ------- |
| Unit    |       |        |        |         |
| Feature |       |        |        |         |

### Frontend (Vitest)

| Suite      | Tests | Passed | Failed | Skipped |
| ---------- | ----- | ------ | ------ | ------- |
| Components |       |        |        |         |
| Stores     |       |        |        |         |
| Utils      |       |        |        |         |

## Lint Results

| Tool         | Status  | Issues |
| ------------ | ------- | ------ |
| Laravel Pint | ✅ / ❌ |        |
| ESLint       | ✅ / ❌ |        |

## Static Analysis

| Tool           | Status  | Issues |
| -------------- | ------- | ------ |
| PHPStan        | ✅ / ❌ |        |
| Nuxt Typecheck | ✅ / ❌ |        |

## Migration Validation

```
php artisan migrate --pretend
```

| Migration | Status  |
| --------- | ------- |
|           | ✅ / ❌ |

## Overall Verdict

**Status:** PASS / FAIL
