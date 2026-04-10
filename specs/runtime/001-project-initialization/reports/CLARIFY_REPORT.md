# CLARIFY_REPORT — Project Initialization

**Stage:** Project Initialization  
**Phase:** 01_PLATFORM_FOUNDATION  
**Branch:** spec/001-project-initialization  
**Completed:** 2026-04-10T00:00:00Z  

---

## Clarifications Summary

### Status: ✅ COMPLETE — 6 Critical Decisions Documented

All specification ambiguities resolved. Clarifications appended to spec.md with binding documentation.

---

## Clarification Decisions (6 Q&A)

### Q1 — RBAC: Which Routes Are Public vs Protected?

**Decision:** **DEFAULT-PROTECTED architecture**

- **Protected:** All `/api/v1/*` routes require `auth:sanctum` middleware
- **Public Exceptions:**
  - `POST /api/v1/auth/register` — new user signup
  - `POST /api/v1/auth/login` — authentication endpoint
  - `GET /api/v1/health` — service health check
- **Rationale:** Foundation stage establishes "secure by default" posture. Explicit public routes are exceptions, not the default.
- **Implementation:** Middleware groups in `routes/api.php`

---

### Q2 — Error Responses: What Exact Format for Validation Errors?

**Decision:** **Laravel standard validation error format**

**Single format across all layers:**

```json
{
  "success": false,
  "data": null,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

- **Per-field validation:** Array of error messages (supports multiple validation rule violations per field)
- **Applies to:** Form Requests, API validation, exception responses
- **Consistency:** Frontend and backend both validate independently using this format
- **Rationale:** Laravel convention + consistency with construction domain API standards

---

### Q3 — Docker Compose: MySQL/Redis/Node — What Mandatory vs Optional?

**Decision:** **MySQL mandatory; Redis recommended; Node optional**

| Service | Requirement | Justification |
|---------|-------------|---------------|
| **MySQL** | MANDATORY | Database required for all stages. Pre-configured in docker-compose.yml |
| **Redis** | RECOMMENDED | Caching is high-value for later stages; foundation enables it but doesn't require it for local dev |
| **Node** | OPTIONAL | Frontend can use `npm run dev` (built-in Nuxi dev server). Docker Node watcher is convenience, not requirement |

- **Default compose stack:** MySQL + Node watcher (most common flow)
- **Flexible alternative:** MySQL + local npm scripts (lighter for laptops)
- **Rationale:** Don't block foundation stage on Docker/Redis expertise; developers can add services incrementally

---

### Q4 — Nuxt UI: Which Components Are Essential for Foundation?

**Decision:** **Minimal 5-component proof-of-concept**

Essential components for foundation stage:

| Component | Purpose | Required |
|-----------|---------|----------|
| **UButton** | All CTAs, form actions | ✅ REQUIRED |
| **UCard** | Page containers, sections | ✅ REQUIRED |
| **UForm** | Login/register forms | ✅ REQUIRED |
| **UInput** | Text fields | ✅ REQUIRED |
| **ULayout** | Page structure (header, sidebar, main) | ✅ REQUIRED |

**Optional (defer to later stages):**
- UTable, UModal, UDropdown, UToast, UPagination, etc.

- **Rationale:** Foundation stage proves Nuxt UI integration; full component library adopted later
- **Prevents over-specification:** Keep initialization scope focused

---

### Q5 — RTL Support: Strict Adherence from Day 1 or Configured?

**Decision:** **Infrastructure configured now; content localization deferred**

**Foundation Stage (NOW):**
- ✅ Nuxt i18n module installed (ar-SA, en-US locales)
- ✅ Tailwind CSS logical properties active (no left/right in CSS)
- ✅ `dir="rtl"` attribute dynamically set based on locale
- ✅ RTL-aware components verified (no hardcoded absolute positioning)
- ✅ RTL testing template created

**Deferred to Content Stages (STAGE_31+):**
- Actual Arabic translations
- Content review for Arabic UX best practices
- Bidirectional text (Arabic + English mixed) testing
- Regional compliance (Egypt, Saudi Arabia, etc.)

- **Rationale:** Foundation proves technical RTL capability; content finalized during UI implementation stages
- **Prevents bloat:** Don't require translation of foundation code; focus on architecture

---

### Q6 — Testing: 100% Coverage Target or Pass Rate?

**Decision:** **0 failures required; 70% coverage for new code**

| Metric | Target | Scope |
|--------|--------|-------|
| **Test Pass Rate** | 100% | All tests must pass locally + CI |
| **Coverage for New Code** | ≥70% | Lines added in this stage |
| **Coverage Exclusions** | N/A | Migrations, seeders, built-in Laravel scaffolding |
| **Coverage Floor** | ≥50% | Existing Laravel code (not new) |

- **Rationale:** Foundation stage should establish quality baseline without over-specification
- **Practical:** Bootstrap code has inherent lower coverage (migrations, config); focus on business logic
- **Scalable:** Later stages inherit and improve coverage

---

## Supporting Checklists Generated

### 1. **security-checklist.md** — 60 Items
- **CRITICAL:** 20 items (Sanctum token security, RBAC enforcement, SQL injection prevention)
- **HIGH:** 24 items (CSRF protection, environment variable management, rate limiting)
- **MEDIUM:** 16 items (response headers, role enumeration)

**Focus Areas:**
- Authentication lifecycle (token generation, refresh, revocation)
- RBAC middleware scaffold (policy definitions, gate setup)
- SQL injection prevention (Eloquent queries only, no raw SQL outside repositories)
- XSS prevention (Vue templating safety, no `v-html` without sanitization)
- CORS configuration (explicit domain whitelist)

### 2. **performance-checklist.md** — 95 Items
- **CRITICAL:** 10 items (connection pool, N+1 query prevention, main bundle size)
- **HIGH:** 36 items (eager loading patterns, Redis setup, API SLA)
- **MEDIUM:** 42 items (database indexing, minification, lazy loading)
- **LOW:** 7 items (monitoring infrastructure, logging)

**Focus Areas:**
- Database: Connection pool size (min/max), query optimization, index strategy
- Frontend: Main bundle < 150KB (gzipped), lazy-load routes and components
- API: Response time SLA (P95 < 200ms), caching strategy
- Infrastructure: Docker resource limits (MySQL 2GB, Node 1GB)

### 3. **accessibility-checklist.md** — 95 Items
- **CRITICAL:** 18 items (semantic HTML, keyboard navigation, screen reader basics)
- **HIGH:** 43 items (ARIA labels, form associations, focus management, RTL)
- **MEDIUM:** 34 items (color contrast, loading indicators, text scaling)

**Focus Areas:**
- Semantic HTML structure (Vue component hierarchy)
- Keyboard navigation (Tab, Escape, arrow keys, focus management)
- Screen reader compatibility (landmark regions, heading hierarchy)
- RTL language support (Tailwind logical properties, direction-aware ARIA)
- Color contrast (WCAG 2.1 AA: 4.5:1 text, 3:1 UI components)
- Form accessibility (labels, error association, validation feedback)

---

## Governance Alignment

All clarifications and checklists validated against Bunyan architecture rules:

✅ **RBAC Non-Negotiable:** Default-protected routing enforced  
✅ **Error Contract Binding:** Standardized JSON format across layers  
✅ **No Unjustified Dependencies:** All checklists reference only approved tech stack  
✅ **Arabic/RTL First-Class:** Infrastructure in place, content localization planned  
✅ **Clean Architecture:** Service layer, repositories, policy-based authorization defined  
✅ **Testing Foundation:** 70% coverage baseline with 100% pass rate requirement

---

## Barriers to Planning Removed

| Barrier | Status |
|---------|--------|
| Ambiguous RBAC scope | ✅ RESOLVED — Default-protected |
| Error response inconsistency | ✅ RESOLVED — Single format |
| Docker complexity | ✅ RESOLVED — Flexible, optional services |
| Component bloat | ✅ RESOLVED — Minimal 5-component set |
| RTL regressions | ✅ RESOLVED — Infrastructure first, content later |
| Testing targets | ✅ RESOLVED — 70% new code, 100% pass rate |

---

## Next Steps

Specification is now **HARDENED** and ready for **Step 3 — Planning**.

All foundational decisions are documented and binding:
- Technical design can now proceed
- Resource planning can commence
- Task decomposition is unambiguous
- Acceptance criteria are testable

**Status:** CLARIFICATIONS LOCKED  
**Next Agent:** speckit.plan (architecture design generation)
