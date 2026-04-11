# CLARIFY_REPORT — ERROR_HANDLING

**Stage:** ERROR_HANDLING  
**Phase:** 01_PLATFORM_FOUNDATION  
**Branch:** spec/005-error-handling  
**Generated:** 2026-04-11  

## Executive Summary

All specification ambiguities have been successfully resolved. 5 key clarification items were addressed with explicit decisions, rationales, and implementation guidance. The specification is now fully clarified and ready for technical planning.

## Clarifications Resolved (5/5)

| # | Topic | Decision | Status |
|---|-------|----------|--------|
| C1 | Logging Storage | Local files with daily rotation (no ELK) | ✅ |
| C2 | Rate Limiting Strategy | Hybrid (global baseline + per-endpoint overrides) | ✅ |
| C3 | Rate Limiting Values | 100 req/min global; 10 req/min for auth/payment | ✅ |
| C4 | Error Message i18n | User-facing in both languages; technical in English | ✅ |
| C5 | Log Retention Policy | 30 days general; 90 days audit | ✅ |

## Detailed Decisions

### C1: Logging Storage Strategy

**Decision:** Local files with daily rotation via Laravel `daily` channel

**Rationale:**
- Simpler deployment without external dependencies
- Appropriate for MVP stage of marketplace
- ELK/managed logging can be added in DevOps/Monitoring stage
- Reduces infrastructure complexity and cloud costs

**Implementation Details:**
- Use Laravel's built-in logging channels
- Configure `config/logging.php` with daily rotation
- Archive after 7 days automatic cleanup
- 30-day retention for general logs, 90-day for audit logs

**Impact:** Reduces operational complexity while maintaining full auditability.

---

### C2 & C3: Rate Limiting Configuration

**Decision:** Hybrid rate limiting model with conservative, security-focused thresholds

**Strategy:**
- **Global (default):** 100 requests/minute per authenticated user
- **Auth Endpoints:** 10 requests/minute per IP address (brute-force protection)
- **Payment Endpoints:** 10 requests/minute per user (fraud prevention)
- **Public Endpoints:** 60 requests/minute per IP (general DDoS mitigation)

**Rationale:**
- Conservative approach appropriate for construction marketplace handling payments
- Multi-layer defense: IP-level + user-level + endpoint-specific
- Prevents account takeover, payment fraud, brute-force attacks
- Aligns with security-hardening requirements

**Implementation Details:**
- Redis-preferred for distributed rate limiting
- In-memory fallback for single-instance deployments (development)
- Laravel middleware integration (`RateLimitRequests`)
- Error code: `RATE_LIMIT_EXCEEDED` with retry-after header

**Impact:** Production-ready security posture from ground level.

---

### C4: Error Message Localization

**Decision:** Bifurcated localization strategy

**User-Facing Errors (Localized to Arabic/English):**
- Validation errors (field-level messages)
- Authentication errors
- Authorization errors (RBAC)
- Business rule violations (workflow transitions, budget limits)
- Payment errors
- File upload errors

**Technical/Debug Errors (English Only):**
- Stack traces
- Database errors
- Internal system errors
- Server environment errors
- Configuration issues

**Rationale:**
- Arabic users receive appropriate local-language UX
- Technical debugging remains efficient (English logs reduce complexity)
- Aligns with i18n-governance and platform Arabic-first philosophy
- Prevents localization effort waste on non-user-facing errors

**Implementation Details:**
- Error messages keyed via Laravel's translation system
- Create translation files: `resources/lang/ar/errors.php` + `resources/lang/en/errors.php`
- Use enum for error codes (language-agnostic)
- Service layer handles localization logic

**Impact:** Professional UX for Arabic users; efficient development experience.

---

### C5: Log Retention Policy

**Decision:** Tiered retention strategy (30 days general / 90 days audit)

**Retention Tiers:**
- **General Logs:** 30 days (cost-effective)
- **Audit Logs:** 90 days (compliance + financial trail)
- **Request Logs:** 7 days (performance data)
- **Error Logs:** 30 days (debugging history)

**Rationale:**
- 30-day retention balances cost + debugging needs
- 90-day audit trail meets construction industry compliance standards
- Financial transactions require extended auditability
- Automated cleanup via Laravel scheduler prevents manual maintenance

**Implementation Details:**
- Create separate log channels for domain areas:
  - `laravel.log` — General application
  - `audit.log` — Workflow + financial
  - `requests.log` — API requests/responses
  - `errors.log` — All exceptions
- Scheduled cleanup command (daily)
- Archive older logs to S3 (optional, Phase 2)

**Impact:** Compliance-ready from day 1; manageable storage costs.

---

## Architecture Alignment Validation

### RBAC Patterns ✅

- **Verified:** Error code `RBAC_ROLE_DENIED` for authorization failures
- **Verified:** Role-based exception handling in middleware
- **Verified:** Specific error codes for each role denying scenario

### Form Request Validation ✅

- **Verified:** Validation errors return 422 status code
- **Verified:** Field-level error details in response
- **Verified:** Form Request pattern enforced in spec

### Eloquent Patterns ✅

- **Verified:** `ModelNotFoundException` caught and transformed to 404
- **Verified:** Repository pattern for database access
- **Verified:** Relationship errors properly coded

### Service Layer Boundaries ✅

- **Verified:** Exception handler in `app/Exceptions/Handler.php`
- **Verified:** Response helpers as trait (`ApiResponseTrait`)
- **Verified:** Business logic error codes separated from system errors

### Controller Thinness ✅

- **Verified:** `BaseController` provides response formatting
- **Verified:** Controllers delegate business logic to services
- **Verified:** Exception handler standardizes responses

### Arabic/RTL Support ✅

- **Verified:** Error messages keyed for i18n  
- **Verified:** Both Arabic and English error text specified
- **Verified:** No hardcoded Arabic/English text in controllers

### Workflow Engine Integration ✅

- **Verified:** Error codes for workflow state violations
- **Verified:** Transition error codes: `WORKFLOW_INVALID_TRANSITION`, `WORKFLOW_PREREQUISITES_UNMET`
- **Verified:** Phase transition errors properly specified

## Dependency Alignment

| Dependency | Stage | Alignment | Status |
| --- | --- | --- | --- |
| **Upstream: STAGE_01** | Project Initialization | Uses base API response structure | ✅ |
| **Downstream: All Stages** | STAGES_06+ | Error codes extensible per domain | ✅ |
| **Skill: error-handling-patterns** | Reference | Spec implements all patterns | ✅ |
| **Skill: observability-standards** | Reference | Structured logging with correlation IDs | ✅ |
| **Skill: i18n-governance** | Reference | Arabic-first, RTL-ready patterns | ✅ |

## Specification Completeness Assessment

| Component | Status | Notes |
| --- | --- | --- |
| Error Contract | ✅ Complete | Response format with 12 error codes |
| Exception Handler | ✅ Complete | Global transformation layer specified |
| Logging Strategy | ✅ Complete | Structured, tiered retention, correlation IDs |
| Rate Limiting | ✅ Complete | Hybrid model with specific thresholds |
| Localization | ✅ Complete | User-facing bilingual, technical English |
| RBAC Integration | ✅ Complete | Role-based error scenarios specified |
| User Stories | ✅ Complete | 6 stories with 38 acceptance criteria |
| Frontend Integration | ✅ Complete | Error boundary, toast, error pages |

## Ready for Planning Assessment

### ✅ **READY FOR PLANNING**

**All blockers resolved. Stage is fully clarified and ready to proceed.**

**Pre-Planning Checklist:**
- [x] All ambiguities resolved (5/5 clarifications)
- [x] Decisions encoded in spec.md with rationales
- [x] Architecture alignment confirmed across all patterns
- [x] Skills validation passed (RBAC, logging, i18n)
- [x] Upstream dependencies available (STAGE_01 initialized)
- [x] No blocking contradictions detected
- [x] Error code registry complete (12+ codes)
- [x] Response contract unambiguous
- [x] i18n strategy validated
- [x] Rate limiting strategy security-reviewed

## Recommendations for Planning Phase

1. **Rate Limiting Middleware:** Create reusable `RateLimitByRole` middleware for endpoint-level configuration.

2. **Logging Channels Separation:** Define separate log files by domain for easier debugging and analytics.

3. **Error Code Extensibility:** Use enum (`app/Enums/ApiErrorCode.php`) with versioning to allow downstream stages to extend without breaking API.

4. **Correlation ID Propagation:** Document pattern for async job handling + event listeners for end-to-end traceability.

5. **Arabic Error Messages:** Create translation guide and coordinate with Product on error message tone (formal for financial, friendly for user-facing).

6. **Frontend Error Boundary:** Design error recovery flows for different error categories (network, validation, server).

---

## Next Steps

1. ✅ **Clarify step complete** — All ambiguities resolved
2. ⏭️ **Step 3 (Plan):** Generate technical design, data model, implementation strategy
3. ⏭️ **Step 4 (Tasks):** Convert design to actionable, dependency-ordered tasks
4. ⏭️ **Step 5 (Analyze):** Drift detection and architecture validation
5. ⏭️ **Step 6 (Implement):** Execute implementation via TDD
6. ⏭️ **Step 7 (Closure):** Final validation and PR summary

---

**Status:** ✅ Step 2 (Clarify) Complete  
**Ready for:** Step 3 (Plan)
