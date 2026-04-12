# ERROR_HANDLING Security Verification Checklist

> **Purpose:** Verify that security requirements are clearly specified, unambiguous, and complete.  
> **Scope:** Server-side error handling, logging security, authentication/authorization error distinction, rate limiting, and data protection.  
> **Total Items:** 21

---

## Security Requirements Clarity

- [ ] **CHK-SEC-001** — Specification explicitly forbids stack trace exposure to clients (Clarified: "unhandled exceptions return 500 with generic message (stack trace logged server-side only)")

- [ ] **CHK-SEC-002** — Exception handler response when in production mode vs. local/dev is defined with different visibility rules

- [ ] **CHK-SEC-003** — Sensitive data masking strategy (C6) specifies concrete masked patterns: passwords → `***`, tokens → `tok_****...`, card numbers → `****-****-1234`

- [ ] **CHK-SEC-004** — Sensitive fields registry mechanism specified (in spec and clarification C6: "Create `app/Support/SensitiveFields.php` with registry and masking functions")

- [ ] **CHK-SEC-005** — Request logging middleware explicitly strips sensitive fields BEFORE logging (not retroactively)

- [ ] **CHK-SEC-006** — Error codes are semantically distinct for different auth/authz scenarios (C4 clarifies auth vs. RBAC vs. role-specific errors: `AUTH_UNAUTHORIZED` vs. `RBAC_ROLE_DENIED`)

- [ ] **CHK-SEC-007** — RBAC authorization error codes do NOT expose role information in error messages

- [ ] **CHK-SEC-008** — Rate limiting response includes both `429` status code AND `Retry-After` header (C3 specifies: "Error response: 429 `RATE_LIMIT_EXCEEDED` with `Retry-After` header")

- [ ] **CHK-SEC-009** — Rate limiting thresholds are explicitly defined: Global 100 req/min; Auth/Payment 10 req/min (C3 clarifies concrete values)

- [ ] **CHK-SEC-010** — Rate limiting distinguishes between global + per-endpoint + per-user + per-IP rules (C2 specifies: "Global middleware: 100 req/min per user; Auth: 10 req/min per IP; Payment: 10 req/min per user")

---

## Sensitive Data Protection

- [ ] **CHK-SEC-011** — Password fields are explicitly NEVER logged (C6 clarifies: "Passwords → `***` (never log, hash if captured)")

- [ ] **CHK-SEC-012** — All token types are identified: API tokens, session tokens, CSRF tokens, JWT tokens (spec requirement from C6 sensitive fields registry)

- [ ] **CHK-SEC-013** — PII capturing rules defined to avoid log spam (C6: "email, phone, SSN → Capture once per user per day")

- [ ] **CHK-SEC-014** — Bank account number masking pattern specified (C6: `****-****-****-1234`)

- [ ] **CHK-SEC-015** — Payment card PII handling complies with cardholder data protection requirements (spec should reference PCI-DSS or equivalent storage constraints)

---

## Correlation ID Security

- [ ] **CHK-SEC-016** — Correlation ID prevents request replay attacks (requirement implicit but should be explicitly stated if intended)

- [ ] **CHK-SEC-017** — Correlation ID uniqueness guaranteed via UUID v4 or cryptographically secure equivalent (C8 clarifies: "UUID v4 if not in incoming request")

- [ ] **CHK-SEC-018** — Correlation ID does NOT expose server topology, user counts, or internal infrastructure info

- [ ] **CHK-SEC-019** — Correlation ID propagation strategy prevents injection attacks (incoming header validation rules should be specified)

---

## Error Message Security

- [ ] **CHK-SEC-020** — Error messages do not include SQL query fragments or database schema hints (spec requirement from C4 localization scope: "database query errors logged without exposing schema details")

- [ ] **CHK-SEC-021** — Error messages do not disclose file paths, system commands, or server OS information

---

## Summary

**Expected:** All 21 security requirements are unambiguous and actionable by developers.  
**Success:** Each requirement has a clear acceptance criterion tied to implementation verification.
