# ERROR_HANDLING API Contract Compliance Verification Checklist

> **Purpose:** Verify that the API error contract is complete, unambiguous, and consistently applied across all endpoints.  
> **Scope:** Response format, error codes, HTTP status codes, field-level details, correlation ID placement, and rate limiting headers.  
> **Total Items:** 22

---

## Response Format Specification

- [ ] **CHK-API-001** — Response format contract specifies all required fields: `success`, `data`, `error` (spec defines in "Error Contract Specification")

- [ ] **CHK-API-002** — `success` field is always present in all responses (both success and error)

- [ ] **CHK-API-003** — `data` field is null on error responses (spec: "Null on errors")

- [ ] **CHK-API-004** — `error` field is null on success responses (spec: "Null on success")

- [ ] **CHK-API-005** — Error object has exactly three sub-fields defined: `code`, `message`, `details` (spec confirms: "within `error` object")

- [ ] **CHK-API-006** — `error.code` is machine-readable string, not numeric (spec: "Machine-readable error code")

- [ ] **CHK-API-007** — `error.message` is human-readable and localized (C4 clarifies user-facing messages translated)

- [ ] **CHK-API-008** — `error.details` is optional and only populated for validation errors (spec: "Field-level details (validation errors only)")

---

## Error Code Registry Completeness

- [ ] **CHK-API-009** — All 12 error codes are documented with code, HTTP status, description, and example scenario (spec lists table with all codes)

- [ ] **CHK-API-010** — Error code `AUTH_INVALID_CREDENTIALS` — 401 status is specified (error registry table)

- [ ] **CHK-API-011** — Error code `AUTH_TOKEN_EXPIRED` — 401 status is specified (error registry table)

- [ ] **CHK-API-012** — Error code `AUTH_UNAUTHORIZED` — 403 status is specified (error registry table; C4 distinguishes from RBAC_ROLE_DENIED)

- [ ] **CHK-API-013** — Error code `RBAC_ROLE_DENIED` — 403 status is specified (distinct from AUTH_UNAUTHORIZED per C4)

- [ ] **CHK-API-014** — Error code `RESOURCE_NOT_FOUND` — 404 status is specified (error registry table)

- [ ] **CHK-API-015** — Error code `VALIDATION_ERROR` — 422 status is specified (error registry table)

- [ ] **CHK-API-016** — Error code `WORKFLOW_INVALID_TRANSITION` — 422 status is specified (error registry table)

- [ ] **CHK-API-017** — Error code `WORKFLOW_PREREQUISITES_UNMET` — 422 status is specified (error registry table)

- [ ] **CHK-API-018** — Error code `PAYMENT_FAILED` — 422 status is specified (error registry table)

- [ ] **CHK-API-019** — Error code `CONFLICT_ERROR` — 409 status is specified (error registry table)

- [ ] **CHK-API-020** — Error code `RATE_LIMIT_EXCEEDED` — 429 status is specified (error registry table; C3 requires Retry-After header)

- [ ] **CHK-API-021** — Error code `SERVER_ERROR` — 500 status is specified (error registry table; no stack trace to client per spec)

---

## Field-Level Validation Details

- [ ] **CHK-API-022** — Validation error `error.details` format is specified as object with field names as keys and error message arrays as values (spec shows example: `"email": ["The email field is required."]`)

---

## Rate Limiting Headers

- [ ] **CHK-API-023** — Rate-limited error response includes `429` HTTP status code (C3 specifies)

- [ ] **CHK-API-024** — Rate-limited error response includes `Retry-After` header with retry delay in seconds (C3 specifies: "Error response: 429 `RATE_LIMIT_EXCEEDED` with `Retry-After` header")

- [ ] **CHK-API-025** — Rate-limited error response body includes `RATE_LIMIT_EXCEEDED` code in `error.code` field (consistency with contract)

---

## Correlation ID in Responses

- [ ] **CHK-API-026** — Correlation ID appears in HTTP response header `X-Correlation-ID` (C8 specifies: "HTTP Response Header: `X-Correlation-ID: 550e8400...`")

- [ ] **CHK-API-027** — Correlation ID appears in error message text for user visibility (C8: "Error Message Text: 'Internal Server Error with ID: 550e8400...'")

- [ ] **CHK-API-028** — Correlation ID optionally appears in `error.correlation_id` body field (C8: "Optional Response Body Field")

---

## Response Contract Stability

- [ ] **CHK-API-029** — Error codes are documented as stable (non-breaking) — new codes added; existing codes never change (spec NFR: "Error codes are stable (never change; deprecated via versioning)")

- [ ] **CHK-API-030** — Response format supports versioning strategy for future extensions (e.g., `error.version` or backward-compatible `error.[new_field]` additions)

---

## Summary

**Expected:** All 30 API contract requirements are unambiguous and allow API consumers to implement robust error handling.  
**Success:** API clients can reliably distinguish error types, retry appropriately, and display user-friendly messages without guessing or parsing message text.
