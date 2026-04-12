# Security Checklist — NUXT_SHELL (Stage 029)

> **Purpose:** Unit tests for security requirements quality. Each item evaluates whether the spec or implementation spec _defines_ and _constrains_ the security behavior — not whether code works.
>
> **Domain:** Nuxt.js 3 + Nuxt UI + Vue 3 TypeScript + Laravel Sanctum SPA cookie auth
> **Stage:** STAGE_29_NUXT_SHELL
> **Created:** 2026-04-12

---

## SEC001 — Sanctum Cookie Architecture: XSS Risk Documented

**Category:** Token Storage / XSS

Is the Sanctum authentication token stored exclusively via an `httpOnly` cookie managed by Laravel (not JavaScript-accessible), and is the risk profile of non-httpOnly storage documented if deviated from?

- The spec Security Audit (Flag 1) identifies a contradiction: `useApi` initially described attaching `Authorization: Bearer {token}` from Pinia, which would require a JS-readable token. The resolution requires switching to `credentials: 'include'` with no manual header.
- **Requirement quality check:** Is the final decided storage strategy stated unambiguously in a single authoritative location (spec NFR or `useApi` composable spec)?
- **Risk register check:** If the cookie is NOT `httpOnly` (accessible to JS), is the XSS surface area explicitly documented with a mitigation decision?

---

## SEC002 — `useApi` Does Not Attach Bearer Token from JavaScript State

**Category:** Token Handling

Does the spec explicitly prohibit `useApi` from reading a token from `useAuthStore` and manually appending `Authorization: Bearer {token}` to request headers?

- With Sanctum SPA cookie auth, the browser sends the auth cookie automatically. Any JS-side token read and header attachment exposes the token to JavaScript, making it vulnerable to XSS.
- **Requirement quality check:** Is `credentials: 'include'` the stated and only mechanism for session propagation in `useApi`? Is it stated that `Authorization` header is omitted entirely?

---

## SEC003 — No Token in `useAuthStore` Public Surface

**Category:** Token Exposure / Store Design

Is it specified that `useAuthStore` does NOT expose a `token` property on its public surface?

- Per the Security Audit Flag 1 resolution, the token must not be in Pinia's reactive state at all (no `token`, `authToken`, or similar field).
- **Requirement quality check:** Is the public interface of `useAuthStore` (user, isAuthenticated, role, permissions) defined with `token` explicitly excluded?

---

## SEC004 — No Token in URL Parameters

**Category:** Token Exposure / URL Leakage

Does the spec prohibit any route that passes an auth token, session identifier, or sensitive credential as a URL query parameter or path segment?

- Tokens in URLs appear in browser history, server access logs, referrer headers, and analytics.
- **Requirement quality check:** Is this prohibition stated in the `useApi` spec, auth middleware spec, or security NFR?

---

## SEC005 — No `console.log` of Auth State or Token

**Category:** Token Exposure / Debug Leakage

Does the spec forbid `console.log`, `console.debug`, or `console.info` calls that output token values, user credentials, or sensitive auth state?

- Debug logging of tokens is a common developer mistake that becomes a security issue in production.
- **Requirement quality check:** Is this prohibition explicit in the composable specs (`useAuth`, `useApi`) or in the project engineering rules referenced from this spec?

---

## SEC006 — Auth Middleware on All Protected Routes (Server-Side Enforcement Documented)

**Category:** RBAC / Route Authorization

Is it specified that `auth.ts` middleware covers ALL authenticated routes, and that role checks in `role.ts` are never the _only_ authorization mechanism (i.e., backend APIs enforce their own RBAC independently)?

- Client-side middleware is a UI guard only. The spec must acknowledge that backend authorization is the actual enforcement layer.
- **Requirement quality check:** Does the middleware section state that frontend middleware is a UX redirect — not a security boundary — and that backend APIs perform their own role checks?

---

## SEC007 — `role.ts` Middleware: Route Meta Convention Defined

**Category:** RBAC / Middleware Specification

Does the spec define the exact mechanism by which routes declare their required role (e.g., `route.meta.requiredRole: 'admin'`)?

- Per Security Audit Flag 3, the `role.ts` middleware is listed in the delivery map but has no implementation sketch or meta convention defined.
- **Requirement quality check:** Is a code sketch or data contract provided for `role.ts` that matches the `auth.ts` level of detail? Is the `requiredRole` meta field documented?

---

## SEC008 — `role.ts` Middleware Redirect Target Per Role Defined

**Category:** RBAC / Navigation

When `role.ts` redirects an unauthorized user, is the redirect target for each role explicitly defined in the spec?

- Without a defined redirect target, implementations may redirect all roles to the same page (e.g., `/dashboard`) which may itself be role-restricted, creating a redirect loop.
- **Requirement quality check:** Is a role-to-dashboard URL map defined (e.g., admin → `/admin`, customer → `/customer`) as the canonical redirect target for unauthorized role redirects?

---

## SEC009 — Toast Messages: No Internal Error Codes Exposed to UI

**Category:** Information Disclosure / Toast Security

Does the spec prohibit exposing raw API error codes (e.g., `RBAC_ROLE_DENIED`, `AUTH_TOKEN_EXPIRED`), stack traces, or `error.details` field names in toast notifications visible to the user?

- The `useNotification` composable must present only user-friendly, localized messages — never machine-readable internal codes.
- **Requirement quality check:** Is `useNotification` specified to accept only a localized string `msg` parameter? Is there a call-site rule that the caller must translate the error before passing to `notifyError()`?

---

## SEC010 — `error.vue`: No Stack Trace Exposure

**Category:** Information Disclosure / Error Boundary

Does the spec explicitly state that `error.vue` must NOT render stack traces, system paths, raw exception messages, or HTTP 5xx body content from the API?

- The spec mentions "no stack trace exposed" in US15 but this needs to be a hard constraint on the error page implementation spec.
- **Requirement quality check:** Is there a clear rule that `error.vue` only displays content from the platform's approved `common.error_generic` and `common.error_not_found` i18n keys?

---

## SEC011 — CSRF Protection for SPA Cookie Auth

**Category:** CSRF

Does the spec address CSRF protection requirements when using Sanctum's SPA cookie-based authentication?

- SPA cookie auth with Sanctum requires the frontend to first call `GET /sanctum/csrf-cookie` to obtain an `XSRF-TOKEN` cookie. Subsequent mutation requests (POST, PUT, DELETE) must include this token.
- **Requirement quality check:** Is the CSRF cookie initialization call specified as part of `useApi` setup or in a Nuxt plugin? Is `XSRF-TOKEN` cookie handling documented?

---

## SEC012 — No `v-html` With User-Generated Content

**Category:** XSS / Template Safety

Does the spec explicitly prohibit the use of `v-html` in any shell component that renders dynamic user content (nav labels, breadcrumbs, toast messages, error messages)?

- `v-html` bypasses Vue's XSS escaping. Even translated strings passed through `v-html` are vulnerable if translation values are user-controlled.
- **Requirement quality check:** Is there a blanket prohibition on `v-html` in the shell components spec? Does it distinguish between static i18n strings (lower risk) and user-derived content (prohibited)?

---

## SEC013 — No `eval()` in Shell Plugins or Composables

**Category:** XSS / Code Execution

Does the spec or referenced engineering rules explicitly prohibit `eval()`, `new Function()`, `setTimeout(string)`, and `setInterval(string)` in all shell JavaScript?

- **Requirement quality check:** Is this constraint stated in the security NFR of this spec or in an upstream engineering rules document (`AI_ENGINEERING_RULES.md`) that this spec references?

---

## SEC014 — 401 Response Handling Does Not Expose Server Internals

**Category:** Information Disclosure / 401 Handling

Does the `useApi` 401 handling spec ensure that the redirect to `/auth/login` does not leak the reason for the 401 (e.g., expired token, revoked token, wrong role) to the user or URL?

- Per the error contract, 401 errors must not expose role information or token state.
- **Requirement quality check:** Is `useApi`'s 401 path specified to clear auth state silently and redirect with no error detail passed as a query parameter or stored state?

---

## SEC015 — Direction and Dark Mode Preferences: No Sensitive Data in `localStorage`

**Category:** Data Storage Safety

Does the spec confirm that `usePreferences` (from Clarification A2) only writes non-sensitive UI preferences (`bunyan_direction`, `bunyan_locale`, color mode) to `localStorage` — and never writes auth tokens, user IDs, permission lists, or role identifiers?

- Even "harmless" data like role names in `localStorage` can be used for client-side privilege escalation attempts or fingerprinting.
- **Requirement quality check:** Is the permitted `localStorage` key list explicitly enumerated in the `usePreferences` spec?

---

## SEC016 — Navigation Config: No Role Leakage via Cross-Role Route Visibility

**Category:** Information Disclosure / Navigation Security

Does the spec confirm that `NAV_ITEMS_BY_ROLE` is filtered at the data layer so that no role can infer the existence of routes belonging to other roles?

- Per Clarification A4, each role sees ONLY their own items. This is a requirements quality check on whether that constraint is testable in the navigation config unit tests.
- **Requirement quality check:** Does the unit test spec for `navigation.spec.ts` include a "no cross-role leakage" assertion — verifying that role A's nav does not contain any routes from role B's nav?

---

## SEC017 — `useAuth` Logout: Complete State Teardown Specified

**Category:** Session Termination

Does the logout flow in `useAuth` specify that ALL of the following are cleared on logout: Pinia auth store state, any cookies writable by JS, any `localStorage` keys holding auth-adjacent data, and that the API call to `DELETE /api/auth/logout` is made before redirect?

- Incomplete teardown (e.g., forgetting to clear the UI store or breadcrumb state that contains user-identifiable route paths) can leak session context.
- **Requirement quality check:** Is the logout sequence listed as an ordered set of steps in the spec?

---

_Total items: 17_
