# Category System - Testing Guide

**Date**: 2026-04-15  
**Stage**: STAGE_07_CATEGORIES  
**Testing Scope**: Unit tests, Feature tests, E2E tests, Performance tests, Accessibility tests

---

## Overview

This guide documents manual and automated testing strategies for the Category Hierarchy system, covering:

- **Unit Tests** — Component logic and service methods
- **Feature Tests** — API endpoint behavior and database operations
- **E2E Tests** — User workflows from UI to API
- **Performance Tests** — Tree rendering, API response times
- **Accessibility Tests** — WCAG 2.1 AA compliance
- **Manual Test Scenarios** — Key user journeys

---

## Running Automated Tests

### Backend Tests (Laravel/PHPUnit)

```bash
# Run all backend tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/CategoryMigrationTest.php

# Run specific test method
php artisan test --filter=test_migration_creates_categories_table_with_correct_schema

# Run tests in parallel
php artisan test --parallel
```

Expected: **50+ tests** with **85%+ coverage**

### Frontend Unit Tests (Vitest)

```bash
# Run all unit tests
npm run test

# Run with coverage
npm run test:coverage

# Run specific test file
npm run test tests/unit/components/categories/CategoryTree.test.ts

# Watch mode for development
npm run test -- --watch
```

Expected: **15-20 tests** passing

### E2E Tests (Playwright)

```bash
# Run all E2E tests
npm run test:e2e

# Run specific test file
npm run test:e2e category-create.spec.ts

# Run in headed browser (see browser)
npm run test:e2e --headed

# Run with debug
npm run test:e2e --debug

# Generate HTML report
npm run test:e2e
# Then open: frontend/playwright-report/index.html
```

Expected: **6+ E2E scenarios** covering all major workflows

### Performance Tests

```bash
# Run performance tests
npm run test:e2e performance/category-performance.spec.ts

# Baseline metrics will be logged to console
```

Expected: Tree render <500ms, Selector open <1s, Form submit <2s

### Accessibility Tests

```bash
# Run a11y tests
npm run test:e2e a11y/category-a11y.spec.ts
```

Expected: WCAG AA compliance, no keyboard traps, proper labels

---

## Manual Test Scenarios

### Scenario 1: Create Top-Level Category

**Steps:**
1. Login as Admin
2. Navigate to /admin/categories
3. Click "Add Category"
4. Fill: name_ar = "مواد البناء", name_en = "Building Materials"
5. Click Save

**Expected:**
- Modal closes
- Success toast appears
- Category appears in tree at root level
- sort_order = (max existing + 1)
- is_active = true

**Database Check:**
```sql
SELECT * FROM categories WHERE name_en = 'Building Materials';
-- Verify: parent_id IS NULL, version = 0, deleted_at IS NULL
```

---

### Scenario 2: Create Nested Category

**Steps:**
1. Create parent: "Electrical Materials"
2. Click "+" on parent row
3. Fill: name_ar = "الأسلاك", name_en = "Wires"
4. Verify parent_id dropdown shows "Electrical Materials"
5. Save

**Expected:**
- Child appears indented under parent in tree
- parent_id = parent.id
- sort_order increments from siblings
- GET /api/v1/categories/{parent_id} returns children array

**Database Check:**
```sql
SELECT id, name_en, parent_id, sort_order FROM categories 
WHERE parent_id IS NOT NULL 
ORDER BY parent_id, sort_order;
```

---

### Scenario 3: Reorder Categories

**Steps:**
1. Create parent with 3 children (sort_order: 0, 1, 2)
2. Drag child(2) to position(0)
3. Drop

**Expected:**
- Children reorder: sort_order becomes [1, 2, 0] or [0, 1, 2] (recalculated)
- API call PUT /api/v1/categories/{id}/reorder succeeds
- No gaps in sort_order
- version incremented

**API Check:**
```bash
PUT /api/v1/categories/{id}/reorder
Body: { "newSortOrder": 0, "version": 1 }
Expected: 200 OK, version=2 in response
```

---

### Scenario 4: Move to Different Parent

**Steps:**
1. Create parent1 with child
2. Create parent2
3. Drag child from parent1 tree to parent2
4. Drop

**Expected:**
- Child removed from parent1's tree
- Child appears under parent2
- parent_id changed from parent1.id to parent2.id
- API call PUT /api/v1/categories/{id}/move
- Children (if any) move with parent

**Terminal Check:**
```bash
# Verify before move
curl -H "Authorization: Bearer $TOKEN" \
  "http://localhost/api/v1/categories?parent_id=1"

# Verify after move
curl -H "Authorization: Bearer $TOKEN" \
  "http://localhost/api/v1/categories?parent_id=2"
```

---

### Scenario 5: Edit Category Details

**Steps:**
1. Click edit icon on any category
2. Change name_en to "Updated Name"
3. Toggle is_active checkbox
4. Save

**Expected:**
- name_en updated
- is_active status changes
- slug remains immutable
- version incremented
- updated_at timestamp changes

**Concurrency Test:**
- Open same category in 2 browser tabs
- Edit Tab A: name_en = "Edit A", version = 1 → Success, version becomes 2
- Tab B still has version = 1
- Edit Tab B: name_en = "Edit B", version = 1 → Fails with 409 CONFLICT_ERROR

---

### Scenario 6: Soft Delete with Children

**Steps:**
1. Create parent with 2 children
2. Delete parent (click delete icon)
3. Confirm deletion

**Expected:**
- Parent deleted_at filled (soft delete)
- Parent hidden from tree (default queries)
- Children remain (orphaned, not cascaded)
- Breadcrumb no longer shows parent

**Admin "Show Deleted" Toggle:**
- Before toggle: deleted categories hidden
- After toggle: deleted categories visible, grayed out
- withTrashed() scope includes deleted_at IS NOT NULL

---

### Scenario 7: Prevent Circular References

**Steps:**
1. Create: GrandParent → Parent → Child hierarchy
2. Try to edit GrandParent: parent_id = Child.id
3. Save

**Expected:**
- API returns 422 VALIDATION_ERROR
- Error code: WORKFLOW_INVALID_TRANSITION
- Error message: mentions circular reference or similar
- GrandParent unchanged

---

### Scenario 8: Non-Admin RBAC Enforcement

**Steps:**
1. Login as Customer (non-admin)
2. Try to access /admin/categories

**Expected:**
- 403 Forbidden or redirect to dashboard
- Cannot see admin page

**API Test:**
```bash
# Without admin token
curl "http://localhost/api/v1/categories" \
  -H "Authorization: Bearer $CUSTOMER_TOKEN" \
  -X POST \
  -d '{"name_ar": "فئة", "name_en": "Category"}'

# Expected: 403 RBAC_ROLE_DENIED
```

---

### Scenario 9: Form Validation Errors

**Steps:**
1. Open create form
2. Leave name_ar blank
3. Try to save

**Expected:**
- Form validation runs client-side (VeeValidate)
- Error message: "مطلوب" (Required) below name_ar input
- Submit button disabled or click blocked

**Server Validation (if client validation skipped):**
- POST fails with 422 VALIDATION_ERROR
- error.details.name_ar array with error message

---

### Scenario 10: Keyboard Navigation

**Steps:**
1. On categories page
2. Press Tab to focus tree
3. Use Arrow keys to navigate nodes
4. Press Enter to expand/collapse
5. Press Delete to delete selected node

**Expected:**
- All navigation keyboard-accessible
- Focus visible (outline or highlight)
- No keyboard traps
- Screen reader announces actions

---

## Test Data Setup

### SQL Seed for Manual Testing

```sql
-- Category tree for manual testing
INSERT INTO categories (name_ar, name_en, parent_id, slug, sort_order, is_active, created_at, updated_at) VALUES
('مواد البناء', 'Building Materials', NULL, 'building-materials', 0, 1, NOW(), NOW()),
('الكهربائيات', 'Electrical', NULL, 'electrical', 1, 1, NOW(), NOW()),
('السباكة', 'Plumbing', NULL, 'plumbing', 2, 1, NOW(), NOW()),

-- Children of Building Materials
('الأسمنت', 'Cement', 1, 'cement', 0, 1, NOW(), NOW()),
('الحديد', 'Steel', 1, 'steel', 1, 1, NOW(), NOW()),
('الرمل', 'Sand', 1, 'sand', 2, 1, NOW(), NOW()),

-- Children of Electrical
('الأسلاك', 'Wires', 2, 'wires', 0, 1, NOW(), NOW()),
('المفاتيح', 'Switches', 2, 'switches', 1, 1, NOW(), NOW()),

-- Children of Plumbing
('الأنابيب', 'Pipes', 3, 'pipes', 0, 1, NOW(), NOW()),
('الصنابير', 'Taps', 3, 'taps', 1, 1, NOW(), NOW());
```

### SQLite In-Memory for PHPUnit

```php
// Automatically handled by phpunit.xml:
// <php>
//     <env name="DB_CONNECTION" value="sqlite"/>
//     <env name="DB_DATABASE" value=":memory:"/>
// </php>

// Each test runs with fresh data:
$this->artisan('migrate');
$this->seed(CategorySeeder::class);
```

---

## Performance Baselines

| Metric | Target | Status |
|--------|--------|--------|
| API Tree Query (<50 categories) | <100ms | ✓ |
| API Tree Query (1000 categories) | <500ms | ✓ |
| Tree Component Render | <500ms | ✓ |
| Selector Dropdown Open | <1s | ✓ |
| Form Submission & API | <2s | ✓ |
| Memory Usage (100 categories) | <50MB | ✓ |

---

## Accessibility Checklist

- [ ] All buttons have accessible labels (aria-label or text)
- [ ] All form fields have associated `<label>` or aria-label
- [ ] Color contrast ratios ≥4.5:1 (WCAG AA)
- [ ] Keyboard navigation works (Tab, Arrow, Enter, Delete)
- [ ] Focus indicators visible
- [ ] No keyboard traps
- [ ] Breadcrumb links navigable
- [ ] Tree expands/collapses via keyboard
- [ ] Error messages announced
- [ ] RTL layout renders correctly
- [ ] Screen reader announces dynamic updates (aria-live)
- [ ] No images without alt text
- [ ] Heading hierarchy correct

---

## Common Issues & Fixes

### Issue: Tests fail with "Model does not exist"

**Fix:**
```bash
php artisan migrate:fresh --seed
# Then retry tests
```

### Issue: E2E tests timeout on Playwright

**Fix:**
```bash
# Increase timeout in playwright.config.ts:
webServer: {
  command: 'npm run dev',
  timeout: 120000, // Increase from 60000
}
```

### Issue: "Cannot find module useCategories"

**Fix:**
- Ensure `frontend/composables/useCategories.ts` exists
- Run `npm run build` to generate types
- Restart IDE TypeScript server

### Issue: 409 Conflict always on update

**Fix:**
- Verify you're using latest version from GET response
- Version field must match exactly
- Refresh data before editing in concurrent scenarios

---

## CI/CD Integration

### GitHub Actions

```yaml
# .github/workflows/categories-tests.yml
name: Category Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Backend Tests
        run: php artisan test --coverage
      - name: Frontend Tests
        run: npm run test
      - name: E2E Tests
        run: npm run test:e2e
```

---

## Test Coverage Target

| Layer | Target | Achieved |
|-------|--------|----------|
| Backend Controllers | 90% | ✓ |
| Backend Services | 90% | ✓ |
| Backend Repositories | 85% | ✓ |
| Frontend Components | 80% | ✓ |
| **Overall** | **85%** | ✓ |

---

## Regression Test Suite

Run these before deployment:

```bash
# All automated tests
composer run test && npm run test && npm run test:e2e

# Lint & type checking
composer run lint && npm run lint && npm run typecheck

# Build check
npm run build
```

---

## Cleanup & Teardown

**After Manual Testing:**

```bash
# Reset database
php artisan migrate:fresh --seed

# Clear test cookies
# (Browser dev tools → Application → Clear site data)

# Restart servers
# Backend: Ctrl+C, then php artisan serve
# Frontend: Ctrl+C, then npm run dev
```

---

## Support & Questions

- API Documentation: `/backend/storage/api-docs/`
- Code Examples: See feature tests in `backend/tests/Feature/`
- Component Stories: See `frontend/components/categories/`
