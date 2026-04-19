# ADR-009-01: Return 404 (Not 403) for Non-Visible Supplier Profiles in `show()`

> **Status:** Accepted
> **Stage:** STAGE_09 — Suppliers
> **Date:** 2026-04-15
> **Deciders:** Architecture Guardian, speckit.plan

---

## Context

`SupplierController::show()` (`GET /api/v1/suppliers/{id}`) must serve both public visitors and
authenticated users. Supplier profiles have three verification statuses: `pending`, `verified`, and
`suspended`. Only `verified` profiles are visible to the public. Non-verified profiles are
accessible only to Admin and the owning Contractor.

When an unauthenticated visitor (or a Contractor who does not own the profile) requests a
non-verified supplier profile, the system must decide whether to return:

- **403 Forbidden** — "You cannot access this resource" (leaks that the resource exists)
- **404 Not Found** — "This resource does not exist" (prevents existence enumeration)

---

## Decision

**`SupplierController::show()` returns `404 RESOURCE_NOT_FOUND` for any supplier profile that is
not visible to the requesting actor.**

Concretely:

- The controller does **not** call `$this->authorize('view', $supplier)` in the `show()` method.
- Instead, `SupplierService::show(int $id, ?User $actor)` enforces visibility by applying
  `scopeVisibleTo($actor)` and throwing `ApiException::make(ApiErrorCode::RESOURCE_NOT_FOUND)`
  when the supplier does not exist or is not accessible to the actor.
- The `ApiException` with `RESOURCE_NOT_FOUND` maps to HTTP 404 via the global exception handler.

---

## Rationale

**Security:** Returning 403 reveals that a profile with the given ID exists but is restricted.
This enables an attacker to enumerate profile IDs and infer the platform's supplier population
size and ID ranges — a form of information disclosure:

```
GET /api/v1/suppliers/42  → 403 Forbidden   ← reveals ID 42 exists (but is not verified)
GET /api/v1/suppliers/43  → 404 Not Found   ← reveals ID 43 does not exist
```

Returning 404 for both non-existent and non-visible profiles eliminates this vector.

**Spec alignment:** `spec.md §US2` states: "Unverified suppliers visible only to Admin and the
owning Contractor." The natural (and secure) interpretation for all other actors is that the
resource does not exist from their perspective.

**Alternative rejected:** Using `Policy::view()` in the controller for `show()` would yield 403
(via `AuthorizationException`), leaking profile existence. Catching and re-mapping
`AuthorizationException` to 404 inside the controller was also considered but is more fragile than
a single service-level enforcement point.

---

## Consequences

### Positive

- Prevents supplier profile existence enumeration by unauthenticated users.
- Single enforcement point in `SupplierService::show()` — no try/catch in controller.
- Consistent with OWASP guidance on not leaking resource existence to unauthorized actors.

### Negative / Trade-offs

- `SupplierPolicy::view()` is defined but **not called** from `SupplierController::show()`.
  It is called from `SupplierController::index()` for per-item visibility filtering when
  constructing the listing response.
- Developers reviewing the Policy in isolation must understand that `show()` bypasses it.
  This is documented in `plan.md P2.4` and `P2.6` notes.

---

## Implementation Notes

- `SupplierService::show()` uses `SupplierRepository::findById()` and then checks visibility
  via `scopeVisibleTo($actor)` logic before returning the model.
- `SupplierController::show()` calls the service, not the policy, for the visibility check.
- `SupplierController::index()` still calls `authorize('viewAny', SupplierProfile::class)` for
  the listing endpoint (always true per policy), and the service applies `scopeVisibleTo($actor)`
  via the repository's `paginate()` method.

---

## Related

- `specs/runtime/009-suppliers/plan.md` — P2.4 (SupplierPolicy), P2.6 (SupplierController)
- `ADR-SUPPLIER-02` (inline in plan.md) — superseded by this formal ADR
- `AGENTS.md §Error Contract` — `RESOURCE_NOT_FOUND` code maps to HTTP 404
