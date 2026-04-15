# Security Checklist — STAGE_09 Suppliers

> **Spec:** `specs/runtime/009-suppliers/spec.md`
> **Stage:** STAGE_09 — Suppliers
> **Phase:** 02_CATALOG_AND_INVENTORY
> **Last Updated:** 2026-04-15

Use this checklist before marking the stage as COMPLETE. Every item must be checked or explicitly
marked N/A with a justification. Security items are **never N/A** without a written rationale.

---

## Authorization (Policy Guards)

- [ ] `PUT /suppliers/{id}/verify` protected by `SupplierPolicy::verify()` — Admin only
- [ ] `PUT /suppliers/{id}/suspend` protected by `SupplierPolicy::suspend()` — Admin only
- [ ] `DELETE /suppliers/{id}` (soft delete) protected by `SupplierPolicy::delete()` — Admin only
- [ ] All three admin-only methods call `$this->authorize()` (or equivalent) **before** any service call
- [ ] Policy `verify()`, `suspend()`, and `delete()` do NOT fall through to a default `true` on unknown roles
- [ ] Policy registered in `AuthServiceProvider` (or auto-discovered via policy naming convention)

---

## Contractor Self-Service Boundary

- [ ] `SupplierPolicy::update()` verifies `$supplier->user_id === $user->id` OR `$user->isAdmin()`
- [ ] Contractor passing a foreign `supplier_id` in PUT request receives `AUTH_UNAUTHORIZED` (403), not `RESOURCE_NOT_FOUND`
- [ ] No contractor can escalate to admin actions by guessing another supplier's ID
- [ ] `SupplierPolicy::create()` verifies contractor role AND absence of existing profile before allowing creation

---

## Verification Status Immutability

- [ ] `verification_status` is NOT present in `StoreSupplierRequest::rules()`
- [ ] `verification_status` is NOT present in `UpdateSupplierRequest::rules()`
- [ ] Service layer `update()` method explicitly does NOT pass `verification_status` to repository
- [ ] If `verification_status` is included in PUT request body it is silently ignored (not applied, not error)
- [ ] Mutation of `verification_status` is only possible via `verify()` and `suspend()` service methods

---

## Error Code Consistency (RBAC / Auth)

- [ ] Wrong-role access returns `RBAC_ROLE_DENIED` with HTTP 403 (not 401)
- [ ] Own-resource violation returns `AUTH_UNAUTHORIZED` with HTTP 403 (not 401)
- [ ] 401 responses are reserved exclusively for unauthenticated requests (missing/expired token)
- [ ] Error responses for auth failures do NOT leak role information (e.g., do not say "admin-only endpoint")
- [ ] Error response format follows unified contract: `{ success: false, data: null, error: { code, message, details: null } }`

---

## SQL Injection Prevention

- [ ] No raw SQL or `DB::statement()` calls in `SupplierRepository`
- [ ] All filter parameters (`city`, `district`, `search`, `verification_status`) use Eloquent query builder methods (`where()`, `like`, scopes)
- [ ] `search` filter uses parameterized LIKE (`->where('company_name_ar', 'like', '%' . $term . '%')`) — not string concatenation
- [ ] `verification_status` filter validates against the `SupplierVerificationStatus` enum before applying to query
- [ ] No user-supplied values interpolated directly into query strings anywhere in repository or service

---

## Mass Assignment Protection

- [ ] `$fillable` on `SupplierProfile` explicitly excludes: `verified_at`, `verified_by`, `rating_avg`, `total_ratings`, `user_id`
- [ ] `$fillable` does NOT use `$guarded = []` (open guard) — explicit allowlist only
- [ ] `user_id` assigned only via `$data['user_id'] = $actor->id` in service (not from request input)
- [ ] `rating_avg` and `total_ratings` only updated via dedicated aggregation method (stub in this stage)
- [ ] Model does NOT use `$guarded = ['id']` or similar partial guard that would expose sensitive fields

---

## Input Validation

- [ ] `logo` validated with `url` rule — prevents arbitrary freeform strings being stored as logo URL
- [ ] `website` validated with `url` rule — prevents non-URL injection in website field
- [ ] `commercial_reg` validated with `max:100` — prevents oversized payloads causing DB/index issues
- [ ] `company_name_ar` and `company_name_en` validated with `max:255` — prevents column overflow
- [ ] `description_ar` and `description_en` validated with `max:2000` — prevents TEXT column abuse
- [ ] `address` validated with `max:500` — prevents runaway address strings
- [ ] `tax_number` validated with `max:50` — prevents oversized input
- [ ] `phone` validated with `/^05\d{8}$/` regex — rejects non-Saudi, non-mobile formats
- [ ] `per_page` query parameter validated/capped at 100 — prevents excessive DB reads

---

## Rate Limiting

- [ ] `POST /api/v1/suppliers` (registration endpoint) has rate limiting applied
- [ ] Rate limit applies per-user or per-IP to prevent bulk profile creation attempts
- [ ] Rate limiter defined in `RouteServiceProvider` or route middleware (not ad-hoc in controller)
- [ ] `RATE_LIMIT_EXCEEDED` (429) returned when limit is exceeded, following error contract

---

## Authentication Requirements

- [ ] All write endpoints (`POST`, `PUT /update`, `PUT /verify`, `PUT /suspend`) require `auth:sanctum` middleware
- [ ] Unauthenticated requests to write endpoints return 401 (`AUTH_TOKEN_EXPIRED` or missing token)
- [ ] Read endpoints (`GET /suppliers`, `GET /suppliers/{id}`, `GET /suppliers/{id}/products`) intentionally public — documented in route file comment
- [ ] Sanctum token not logged or exposed in any server-side log output
- [ ] Token scope and expiry handled by Sanctum defaults (no custom override that weakens token security)

---

## Soft Delete Security

- [ ] Soft-deleted supplier profiles are excluded from ALL queries including admin queries
- [ ] `SoftDeletes` trait ensures `->withTrashed()` is never called implicitly in any repository method
- [ ] Repository `findById()` does NOT use `withTrashed()` — hard delete is permanent invisibility
- [ ] Admin uses `suspend` (not delete) to hide a supplier from public; soft delete is irreversible via API
- [ ] Deleted supplier's products are NOT accessible via `/suppliers/{id}/products` (cascade via soft delete scoping)

---

## Enumeration Prevention

- [ ] Non-existent supplier ID returns `RESOURCE_NOT_FOUND` — same response for deleted and never-existed IDs
- [ ] Non-verified supplier returns `RESOURCE_NOT_FOUND` for unauthorized callers (not "forbidden") to prevent enumeration
- [ ] Error messages for not-found cases do NOT distinguish "deleted" vs "never existed" vs "unauthorized"

---
