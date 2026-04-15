# Accessibility / Internationalisation Requirements Quality Checklist — STAGE_06: API Foundation

> **Spec:** `specs/runtime/006-api-foundation/spec.md`
> **Date:** 2026-04-14
> **Stage:** API Foundation (01_PLATFORM_FOUNDATION)
> **Purpose:** Validate that i18n and language accessibility requirements are precisely and completely specified for this stage — not that the translations are correct.

---

## Arabic and English Error Message Translations

- [ ] **CHK051** [I18N] Is there a requirement specifying the exact translation key naming convention for all new error messages introduced in this stage (e.g., `errors.rate_limit_exceeded`, `errors.auth_unauthorized`) so that keys in `lang/ar/` and `lang/en/` are consistent and discoverable?
- [ ] **CHK052** [I18N] Is there a requirement that the translation keys in `lang/ar/errors.php` and `lang/en/errors.php` are explicitly listed as deliverables for this stage, rather than relying on implementers to infer the required keys from code?
- [ ] **CHK053** [I18N] Is there a requirement specifying how the backend determines the response language — e.g., parsing the `Accept-Language` HTTP header — and what the fallback language is (Arabic or English) when the header is absent or contains an unsupported locale?
- [ ] **CHK054** [I18N] Is there a requirement that `lang/ar/` translation files introduced in this stage undergo Arabic language review (by a native speaker or designated reviewer) before the stage is marked complete, to ensure grammatical accuracy beyond machine translation?
- [ ] **CHK055** [I18N] Is there a requirement that `BaseApiController::error()` and `ApiResponseTrait::error()` use `__('errors.error_code_key')` (Laravel translation helper) rather than hardcoded English strings, ensuring translations are applied uniformly?

---

## Rate Limit Error Message Localisation

- [ ] **CHK056** [I18N-RATE-LIMIT] Is the Arabic translation string for the `RATE_LIMIT_EXCEEDED` error code's `message` field explicitly defined in the spec (or referenced to a translation file entry), rather than left for the implementer to author?
- [ ] **CHK057** [I18N-RATE-LIMIT] Is there a requirement that the rate-limit error message body includes a human-readable retry time indication (e.g., "يرجى المحاولة مرة أخرى بعد X ثانية" / "Please retry after X seconds") sourced from the `Retry-After` header value, supporting Arabic locale?
- [ ] **CHK058** [I18N-RATE-LIMIT] Is there a requirement that rate-limit error messages from the `ThrottleRequestsException` handler (FR-049) are processed through the same i18n translation pipeline as other `ApiResponseTrait::error()` calls, rather than using Laravel's default throttle message?

---

## Health Check Response Language (English-Only Exception)

- [ ] **CHK059** [I18N-HEALTH] Is the use of English-only values for `/api/health` response fields (`status`, `environment`) formally documented as an explicit, intentional exception to NFR-017 (all user-facing error messages must have Arabic translations)?
- [ ] **CHK060** [I18N-HEALTH] Is `/api/health` formally classified in the spec as an infrastructure/operational endpoint (not user-facing), providing the justification for why its English-only field values are out of scope for the Arabic i18n mandate?
- [ ] **CHK061** [I18N-HEALTH] Is there a requirement that the `/api/health` endpoint does NOT respect the `Accept-Language` header — i.e., that its response shape is fixed and does not attempt localisation — to avoid ambiguity about its i18n behavior?

---

## API Documentation Interface Language

- [ ] **CHK062** [I18N-DOCS] Is there a requirement defining the display language of the Swagger UI interface (currently English by default via l5-swagger) and whether offering Arabic language support in the UI is in scope, out of scope, or deferred for this stage?
- [ ] **CHK063** [I18N-DOCS] Is there a requirement specifying the language in which `@OA\*` annotation descriptions, parameter descriptions, and response descriptions should be authored — English is standard for OpenAPI tooling, but this should be explicitly decided given the Arabic-first platform identity?
- [ ] **CHK064** [I18N-DOCS] Is there a requirement that the Swagger UI title, description, and any developer-facing text in `OpenApiAnnotations.php` match the platform's official English name (`Bunyan API`) rather than the Arabic name, to ensure compatibility with standard tooling?
