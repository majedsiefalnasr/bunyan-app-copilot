# Testing Guide — Suppliers

> **Phase:** 02_CATALOG_AND_INVENTORY | **Generated:** 2026-04-19T09:00:00Z

## Prerequisites

```bash
# Backend
cd backend
composer install
php artisan migrate:fresh --seed

# Frontend
cd frontend
npm install
```

## Running Tests

### Backend Unit Tests

```bash
cd backend
php artisan test --filter=SupplierServiceTest
```

### Backend Feature Tests

```bash
cd backend
php artisan test --filter=SupplierControllerTest
php artisan test --filter=SupplierVerificationWorkflowTest
```

### All Backend Tests

```bash
cd backend
php artisan test
```

### Frontend Tests

```bash
cd frontend
npm run test -- --run
# Filter supplier tests
npm run test -- --run --reporter=verbose 2>&1 | grep -i supplier
```

### Static Analysis

```bash
# Backend
cd backend && vendor/bin/phpstan analyse --memory-limit=1G
cd backend && vendor/bin/pint --test

# Frontend
cd frontend && npx nuxi typecheck
cd frontend && npx eslint . --max-warnings=0
```

### Migration Validation

```bash
cd backend
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan migrate --pretend
```

## Manual Test Scenarios

### Scenario 1 — Contractor Creates Supplier Profile

**Preconditions:**

- User exists with `contractor` role
- No existing supplier profile for this user

**Steps:**

1. Authenticate as contractor: `POST /api/v1/auth/login` with contractor credentials
2. Create supplier profile: `POST /api/v1/suppliers` with valid payload:
   ```json
   {
     "company_name_ar": "شركة الإنشاءات",
     "company_name_en": "Construction Co",
     "commercial_reg": "CR-12345",
     "phone": "0512345678",
     "city": "Riyadh",
     "tax_number": "TAX-001"
   }
   ```
3. Verify response includes `verification_status: "pending"`

**Expected Result:**

- HTTP 201 Created
- Response body: `{ "success": true, "data": { "id": ..., "verification_status": "pending" }, "error": null }`

### Scenario 2 — Contractor Cannot Create Duplicate Profile

**Preconditions:**

- Contractor already has a supplier profile

**Steps:**

1. Authenticate as contractor
2. Attempt `POST /api/v1/suppliers` again

**Expected Result:**

- HTTP 409 Conflict
- Response body: `{ "success": false, "data": null, "error": { "code": "CONFLICT_ERROR", "message": "..." } }`

### Scenario 3 — Admin Verifies Supplier

**Preconditions:**

- Supplier profile exists with `verification_status: "pending"`
- Admin user available

**Steps:**

1. Authenticate as admin
2. Call `POST /api/v1/suppliers/{id}/verify` with body:
   ```json
   { "notes": "Documents verified successfully" }
   ```
3. Check supplier profile updated

**Expected Result:**

- HTTP 200 OK
- `verification_status` changed to `"verified"`

### Scenario 4 — Admin Suspends Verified Supplier

**Preconditions:**

- Supplier profile with `verification_status: "verified"`

**Steps:**

1. Authenticate as admin
2. Call `POST /api/v1/suppliers/{id}/suspend` with body:
   ```json
   { "reason": "Policy violation" }
   ```

**Expected Result:**

- HTTP 200 OK
- `verification_status` changed to `"suspended"`

### Scenario 5 — Non-Owner Cannot Update Supplier Profile

**Preconditions:**

- Contractor A owns supplier profile
- Contractor B is a different user

**Steps:**

1. Authenticate as Contractor B
2. Attempt `PUT /api/v1/suppliers/{id}` where `{id}` belongs to Contractor A

**Expected Result:**

- HTTP 404 Not Found (ADR-009-01: existence enumeration prevention)
- NOT 403 Forbidden

### Scenario 6 — Non-Admin Cannot Verify Supplier

**Steps:**

1. Authenticate as contractor
2. Attempt `POST /api/v1/suppliers/{id}/verify`

**Expected Result:**

- HTTP 403 Forbidden
- `{ "error": { "code": "AUTH_UNAUTHORIZED" } }`

### Scenario 7 — Public Supplier Listing (No Auth)

**Steps:**

1. Call `GET /api/v1/suppliers` without authentication
2. Filter by city: `GET /api/v1/suppliers?city=Riyadh`
3. Filter by status: `GET /api/v1/suppliers?status=verified`
4. Search: `GET /api/v1/suppliers?search=construction`

**Expected Result:**

- HTTP 200 OK
- Paginated response with only `verified` suppliers visible publicly

### Scenario 8 — Frontend Admin Page

**Steps:**

1. Log in as admin in the frontend application
2. Navigate to `/admin/suppliers`
3. Verify the supplier table loads with columns: Company (AR), City, Phone, Status, Actions
4. Test verify button on a pending supplier
5. Test suspend button on a verified supplier

**Expected Result:**

- Table renders correctly in RTL Arabic layout
- Verify/Suspend actions update status inline without page reload

### Scenario 9 — Frontend Contractor Profile Page

**Steps:**

1. Log in as contractor
2. Navigate to `/dashboard/supplier/profile`
3. If no profile: form renders → fill and submit → profile created
4. If profile exists: view mode shown → click Edit → form renders with existing data → update → saved

**Expected Result:**

- Form validates required fields (company_name_ar, commercial_reg, phone, city)
- Success toast shown after save
- Verification status badge displayed correctly

## API Test Endpoints

| Method | Endpoint                         | Auth Required | Expected Status |
| ------ | -------------------------------- | ------------- | --------------- |
| GET    | `/api/v1/suppliers`              | None (public) | 200             |
| GET    | `/api/v1/suppliers/{id}`         | None (public) | 200 / 404       |
| POST   | `/api/v1/suppliers`              | contractor    | 201 / 409       |
| PUT    | `/api/v1/suppliers/{id}`         | owner         | 200 / 404       |
| POST   | `/api/v1/suppliers/{id}/verify`  | admin         | 200 / 403       |
| POST   | `/api/v1/suppliers/{id}/suspend` | admin         | 200 / 403       |

## Common Issues

| Issue                        | Cause                               | Fix                                                          |
| ---------------------------- | ----------------------------------- | ------------------------------------------------------------ |
| 404 on verify/suspend        | Wrong supplier ID or user not admin | Ensure admin role + correct ID                               |
| 409 on create                | Duplicate `commercial_reg`          | Use unique commercial registration number                    |
| 422 on create                | Missing required fields             | Provide `company_name_ar`, `commercial_reg`, `phone`, `city` |
| Frontend table blank         | API not running or wrong base URL   | Check `NUXT_PUBLIC_API_BASE` env var                         |
| TypeScript error in template | Wrong import alias                  | Use `~~/types/supplier` not `~/types/supplier`               |
