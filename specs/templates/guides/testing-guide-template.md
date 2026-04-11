# Testing Guide — {{STAGE_NAME}}

> **Phase:** {{PHASE_NAME}} > **Generated:** {{ISO_TIMESTAMP}}

## Prerequisites

```bash
# Backend
composer install
php artisan migrate:fresh --seed

# Frontend
npm install
```

## Running Tests

### Backend Unit Tests

```bash
php artisan test --testsuite=Unit --filter={{TestFilterPattern}}
```

### Backend Feature Tests

```bash
php artisan test --testsuite=Feature --filter={{TestFilterPattern}}
```

### Frontend Tests

```bash
npm run test -- --filter={{TestFilterPattern}}
```

### All Tests

```bash
# Backend
php artisan test

# Frontend
npm run test
```

## Manual Test Scenarios

### Scenario 1 — [Description]

**Preconditions:**

- [Setup required]

**Steps:**

1. [Step 1]
2. [Step 2]
3. [Step 3]

**Expected Result:**

- [Expected outcome]

### Scenario 2 — [Description]

**Preconditions:**

- [Setup required]

**Steps:**

1. [Step 1]
2. [Step 2]

**Expected Result:**

- [Expected outcome]

## API Test Endpoints

| Method | Endpoint | Auth | Expected Status |
| ------ | -------- | ---- | --------------- |
|        |          |      |                 |

## Common Issues

| Issue | Cause | Fix |
| ----- | ----- | --- |
|       |       |     |
