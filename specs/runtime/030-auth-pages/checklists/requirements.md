# Auth Pages — Requirements Checklist

**Stage:** 07_FRONTEND_APPLICATION / STAGE_30  
**Specification:** `spec.md`  
**Version:** 1.0  
**Generated:** 2026-04-13

---

## 1. Functional Requirements

### 1.1 Login Page (`/auth/login`)

- [ ] Page renders at `/auth/login` with guest-only access
- [ ] Email input accepts valid email format
- [ ] Password input masks characters with show/hide toggle
- [ ] "Remember me" checkbox is optional and defaults to unchecked
- [ ] Form validates on blur (client-side VeeValidate)
- [ ] Submit button is disabled until form is valid
- [ ] Submit button shows loading spinner during API call
- [ ] Successful login stores token in localStorage + Pinia
- [ ] Successful login redirects to `/dashboard`
- [ ] Failed login displays red `UAlert` with error message
- [ ] Error message displayed in user's current language (ar/en)
- [ ] Forgot password link routes to `/auth/forgot-password`
- [ ] Register link routes to `/auth/register`
- [ ] Form auto-fills if user returns to page within session
- [ ] Logged-in users are redirected to `/dashboard`

### 1.2 Register Page (`/auth/register`)

**Step 1: Account Type Selection**

- [ ] `USteppers` component shows all 4 steps
- [ ] Account type radio group has 2 options (Customer/Contractor)
- [ ] Customer and Contractor icons/labels display correctly
- [ ] Next button validates step 1 and advances
- [ ] Back button is disabled on step 1

**Step 2: Personal Information**

- [ ] First name field validates (2-50 chars)
- [ ] Last name field validates (2-50 chars)
- [ ] Phone field validates phone number format
- [ ] ID number field validates (10-30 chars)
- [ ] All fields show Arabic error messages on invalid input
- [ ] Next button validates all fields before advancing
- [ ] Back button returns to step 1

**Step 3: Address Information**

- [ ] City dropdown populates with predefined Saudi cities
- [ ] District dropdown cascades based on selected city
- [ ] Address textarea accepts 5-200 characters
- [ ] Character count displayed below address field
- [ ] RTL layout applied to textarea (right-aligned)
- [ ] All fields support Arabic text input

**Step 4: Email & Password**

- [ ] Email field validates email format
- [ ] Password field validates regex (length, uppercase, number, symbol)
- [ ] Confirm password field checks match with password
- [ ] `UProgress` shows password strength (Red→Yellow→Green)
- [ ] Password strength label displays in correct language
- [ ] Submit button validates all 4 steps before submission
- [ ] Form displays validation errors inline per field

**Post-Submission**

- [ ] API call to `/api/v1/auth/register` with complete payload
- [ ] Success response triggers email send
- [ ] User redirected to `/auth/verify-email`
- [ ] Email pre-filled (read-only) on verify page
- [ ] Duplicate email shows error: "البريد الإلكتروني مسجل بالفعل"
- [ ] Network errors display with retry option

### 1.3 Forgot Password Page (`/auth/forgot-password`)

- [ ] Page renders at `/auth/forgot-password` with guest-only access
- [ ] Email input validates email format
- [ ] Submit button is disabled until email is valid
- [ ] API call to `/api/v1/auth/forgot-password` on submit
- [ ] Success shows green `UAlert`: "تحقق من بريدك الإلكتروني"
- [ ] Email not found shows generic message (for security): "تحقق من بريدك" (no email confirmation)
- [ ] Email backend service sends reset link with token
- [ ] Reset link routes to `/auth/reset-password?token=xyz`
- [ ] Link valid for 1 hour (configurable backend)
- [ ] Back to login link works correctly

### 1.4 Reset Password Page (`/auth/reset-password`)

- [ ] Page validates token on load from query string
- [ ] Invalid/expired token shows red `UAlert`
- [ ] Password field validates regex strength requirements
- [ ] Confirm password validates match with password
- [ ] `UProgress` indicates strength level
- [ ] Submit button disabled until both passwords valid and match
- [ ] API call to `/api/v1/auth/reset-password` with token + new password
- [ ] Success redirects to `/auth/login` with info toast
- [ ] Token expiry errors caught and displayed clearly
- [ ] Password change clears all active sessions (security best practice)

### 1.5 Email Verification Page (`/auth/verify-email`)

- [ ] Page renders at `/auth/verify-email` after registration
- [ ] Email display shows masked email (a\*\*\*\*@example.com for security)
- [ ] OTP input uses `UPinInput` with 6 digit boxes
- [ ] Auto-focus on first box upon page load
- [ ] Tab advances between boxes; Backspace deletes and goes back
- [ ] Full 6-digit code auto-submits on completion
- [ ] API call to `/api/v1/auth/verify-email` with 6-digit code
- [ ] Invalid code shows red `UAlert`: "الكود غير صالح"
- [ ] Expired code shows red `UAlert`: "انتهت صلاحية الكود"
- [ ] Success redirects to `/dashboard` with welcome message
- [ ] Resend code link triggers POST `/api/v1/auth/resend-verification-code`
- [ ] Resend button disabled during 60-second cooldown
- [ ] Countdown timer displays remaining time
- [ ] Change email link routes back to registration flow
- [ ] Code valid for 10 minutes (configurable backend)

### 1.6 Profile Page (`/profile`)

**Page Load**

- [ ] Requires auth middleware (logged-in only)
- [ ] Loads user data from Pinia store on initial render
- [ ] Fetches latest data from `/api/v1/user/profile` via `useAsyncData`
- [ ] Avatar displays user's profile picture or default avatar
- [ ] All form fields pre-populate with current user data

**Form Fields**

- [ ] First name accepts 2-50 characters
- [ ] Last name accepts 2-50 characters
- [ ] Phone accepts valid phone numbers (9-15 digits)
- [ ] City dropdown cascades (predefined Saudi cities)
- [ ] District dropdown depends on selected city
- [ ] Address textarea accepts 5-200 characters
- [ ] Language preference dropdown (Arabic/English)
- [ ] All fields support Arabic text input (RTL)

**Form Actions**

- [ ] Save button appears only when form is dirty (has changes)
- [ ] Save button disabled if form is invalid
- [ ] Cancel button reverts form to original state
- [ ] Save button shows loading spinner during API call
- [ ] Successful save shows green toast: "تم حفظ الملف الشخصي بنجاح"
- [ ] Successful save updates Pinia store
- [ ] Failed save shows red alert with field-level errors
- [ ] Change password button opens modal (see below)

**Change Password Modal**

- [ ] Modal triggered by "تغيير كلمة المرور" button
- [ ] Current password field validates requirement
- [ ] New password validates regex (length, uppercase, number, symbol)
- [ ] Confirm password validates match
- [ ] `UProgress` shows password strength
- [ ] Submit disabled until all fields valid
- [ ] API call to `/api/v1/user/change-password` on submit
- [ ] Success shows green toast + closes modal
- [ ] Current password incorrect shows error
- [ ] Modal can be closed by X button or Cancel button

**Avatar Upload**

- [ ] Click on avatar or "Change Photo" link opens file input
- [ ] Accepts JPEG, PNG, WebP (5MB max)
- [ ] Shows loading spinner during upload
- [ ] On success, avatar updates and shows new image
- [ ] On failure, shows red alert with message
- [ ] File size validation shown before upload

---

## 2. UI/UX Requirements

### 2.1 Layout & Spacing

- [ ] Auth pages use centered card layout (max-width: 28rem / 448px)
- [ ] Profile page uses sidebar layout with form on right
- [ ] All cards have shadow-as-border `0px 0px 0px 1px rgba(0,0,0,0.08)`
- [ ] Form fields have consistent vertical spacing (1rem between groups)
- [ ] Button group spacing: 0.5rem between buttons
- [ ] Page padding: 1rem on mobile, 2rem on desktop

### 2.2 Typography

- [ ] Page titles use Geist Sans, 24px, font-weight 600, tracking -0.96px
- [ ] Form labels use Geist Sans, 16px, font-weight 500
- [ ] Body text uses Geist Sans, 16px, font-weight 400
- [ ] Error messages use 14px, color #ef4444 (red)
- [ ] Placeholder text uses 16px, color #999999 (gray)
- [ ] All text supports Arabic (RTL) rendering

### 2.3 Color Scheme

- [ ] Background: `#ffffff` (pure white)
- [ ] Text: `#171717` (Vercel black, NOT `#000000`)
- [ ] Borders (shadow): `rgba(0,0,0,0.08)` (subtle gray shadow)
- [ ] Error text: `#ef4444` (red)
- [ ] Success text: `#22c55e` (green)
- [ ] Disabled button: `#d1d5db` (light gray)
- [ ] Placeholder: `#999999` (medium gray)
- [ ] Focus ring: `hsla(212, 100%, 48%, 1)` (blue)

### 2.4 Buttons

- [ ] Primary (colored): Background `#171717`, text `#ffffff`, 8px padding, 6px radius
- [ ] Secondary (outline): Border `0px 0px 0px 1px rgba(0,0,0,0.08)`, text `#171717`, 8px padding, 6px radius
- [ ] All buttons: 14px font, font-weight 500, cursor pointer
- [ ] Hover state: Background darkens slightly
- [ ] Disabled state: Opacity 50%
- [ ] Loading state: Shows spinner, button disabled
- [ ] Full-width on mobile forms

### 2.5 Input Fields

- [ ] Height: 40px (10 Tailwind units)
- [ ] Padding: 8px 12px
- [ ] Border: via shadow `0px 0px 0px 1px rgba(0,0,0,0.08)`
- [ ] Radius: 6px
- [ ] Focus: Blue focus ring `2px solid hsla(212, 100%, 48%, 1)`
- [ ] Placeholder: Color `#999999`
- [ ] RTL: `text-align: end` when in RTL mode
- [ ] Error state: Shadow color `#ef4444`

### 2.6 Forms

- [ ] Form labels above inputs (Arabic: right-aligned)
- [ ] Required indicator (\*) shown for all required fields
- [ ] Error messages displayed below field in red
- [ ] Form applies client-side validation on blur
- [ ] Form errors update on user input (clear on correction)
- [ ] VeeValidate + Zod used for all validation

---

## 3. Internationalization (i18n) Requirements

### 3.1 Arabic Support

- [ ] All UI text translated to Arabic in `locales/ar.json`
- [ ] All form labels in Arabic
- [ ] All error messages in Arabic
- [ ] All placeholder text in Arabic
- [ ] All button labels in Arabic
- [ ] All links and navigation in Arabic
- [ ] RTL layout applied (`dir="rtl"` on html)
- [ ] RTL-aware Tailwind logical properties (margin-inline, etc.)

### 3.2 English Support

- [ ] All UI text translated to English in `locales/en.json`
- [ ] Language switcher functional (if implemented)
- [ ] All form labels in English
- [ ] All error messages in English
- [ ] All placeholder text in English
- [ ] All button labels in English
- [ ] LTR layout applied when English selected

### 3.3 Translation Keys

- [ ] All translation keys use dot notation (e.g., `auth.login`)
- [ ] No hardcoded Arabic/English text in components
- [ ] Validation messages use i18n with dynamic field names
- [ ] Error codes mapped to i18n messages
- [ ] Date/time formatting uses locale (e.g., `Intl.DateTimeFormat`)
- [ ] Currency formatting uses locale (SAR / ر.س)

---

## 4. Validation Requirements

### 4.1 Email Validation

- [ ] Valid format: xxx@yyy.zzz
- [ ] Normalized to lowercase on submit
- [ ] Maximum 255 characters
- [ ] Check for duplicates on register (show error)

### 4.2 Password Validation

- [ ] Minimum 8 characters
- [ ] Maximum 128 characters
- [ ] At least 1 uppercase letter (A-Z)
- [ ] At least 1 number (0-9)
- [ ] At least 1 special character (!@#$%^&\*)
- [ ] Error messages guide user to add missing requirements
- [ ] Zod schema rejects weak passwords

### 4.3 Personal Information Validation

- [ ] First/Last name: 2-50 characters, letters + spaces allowed
- [ ] Phone: 9-15 digits, international format optional
- [ ] ID number: 10-30 characters
- [ ] City/District: Selected from dropdown (not free text)
- [ ] Address: 5-200 characters, UTF-8 (Arabic) supported

### 4.4 Real-Time Feedback

- [ ] Validation errors show on blur (not on change)
- [ ] Errors clear when field is corrected
- [ ] Password strength indicator updates on each keystroke
- [ ] Form fields highlight on error (red border)

---

## 5. Accessibility (A11y) Requirements

### 5.1 Color Contrast

- [ ] Text `#171717` on `#ffffff`: 21:1 contrast (WCAG AAA)
- [ ] Error text `#ef4444` on `#ffffff`: 4.5:1 contrast (WCAG AA)
- [ ] No information conveyed by color alone

### 5.2 Keyboard Navigation

- [ ] All interactive elements accessible via Tab key
- [ ] Tab order logical (left-to-right, top-to-bottom)
- [ ] No keyboard traps
- [ ] Enter key submits form
- [ ] Escape closes modals
- [ ] Focus visible at all times (blue ring)

### 5.3 Screen Reader Support

- [ ] Form labels associated with inputs via `for` attribute
- [ ] Error messages have `role="alert"`
- [ ] Form has `role="form"`
- [ ] Buttons have descriptive label text
- [ ] Links have descriptive link text (not "click here")
- [ ] OTP input boxes semantically grouped
- [ ] Page title clear and descriptive

### 5.4 Mobile & Touch

- [ ] Touch targets minimum 44px × 44px
- [ ] Input fields easily tappable on small screens
- [ ] No hover-only functionality
- [ ] Viewport meta tag set correctly
- [ ] Font size minimum 16px (prevents zoom on iOS)

---

## 6. Performance Requirements

### 6.1 Load Times

- [ ] Page load (DOMContentLoaded): < 2 seconds
- [ ] API response (login): < 1 second
- [ ] API response (register): < 1 second
- [ ] API response (profile fetch): < 500ms

### 6.2 Client-Side Performance

- [ ] Form validation: < 100ms (including Zod)
- [ ] Field-level errors update: < 50ms
- [ ] Component re-renders: < 200ms (Vue reactivity)
- [ ] Bundle size (auth pages): < 50KB additional

### 6.3 Optimization

- [ ] No unnecessary re-renders (Vue 3 Composition API)
- [ ] Lazy-load components where possible
- [ ] Use `<script setup>` for all components
- [ ] Debounce API calls on email uniqueness check
- [ ] Cache user profile data in Pinia

---

## 7. Security Requirements

### 7.1 Authentication Flow

- [ ] Tokens stored in localStorage (not cookies, for SPA)
- [ ] Token included in Authorization header for API calls
- [ ] 401 unauthorized redirects to `/auth/login`
- [ ] 403 forbidden shows error message (not redirect)
- [ ] Password never logged or stored in localStorage

### 7.2 Form Security

- [ ] CSRF protection via Laravel Sanctum
- [ ] Input sanitization on backend (never trust client)
- [ ] No sensitive data in URL query strings
- [ ] Passwords masked in input fields
- [ ] Token validated server-side before password reset

### 7.3 Error Handling

- [ ] No stack traces exposed to users
- [ ] No database errors shown to users
- [ ] Generic error message for "email not found" (don't confirm user exists)
- [ ] Rate limiting on login/register attempts
- [ ] Rate limiting on password reset requests

---

## 8. Responsive Design Requirements

### 8.1 Mobile (375px - 480px)

- [ ] Single-column layout
- [ ] Full-width form cards (with padding)
- [ ] Touch-friendly buttons (min 44px height)
- [ ] Font sizes readable without zoom
- [ ] OTP input boxes stack properly or use horizontal scroll
- [ ] No horizontal overflow

### 8.2 Tablet (481px - 768px)

- [ ] Form cards centered with max-width
- [ ] 2-column forms where appropriate (profile page)
- [ ] Sidebar visible (profile layout)
- [ ] All UI elements proportionally spaced

### 8.3 Desktop (769px+)

- [ ] All layouts as specified
- [ ] Max-width containers applied
- [ ] Sidebar full height
- [ ] Optimal spacing and typography

---

## 9. Integration Requirements

### 9.1 API Integration

- [ ] All endpoints tested with actual backend
- [ ] Error responses follow standard error contract
- [ ] Token refresh working (if implemented)
- [ ] CORS properly configured on backend
- [ ] SSL/TLS certificate valid

### 9.2 State Management (Pinia)

- [ ] Auth store manages token + user data
- [ ] Auth store provides getters: `isAuthenticated`, `userRole`
- [ ] Auth store actions: `login`, `register`, `logout`, `updateProfile`
- [ ] Store persists to localStorage
- [ ] Store initializes from localStorage on app startup

### 9.3 Navigation & Routing

- [ ] `guest` middleware prevents logged-in users from auth pages
- [ ] `auth` middleware prevents guests from profile page
- [ ] `role` middleware enforces RBAC on protected routes
- [ ] Redirect logic: login success → `/dashboard`, logout → `/auth/login`
- [ ] Direct URLs to auth pages redirect if logged in

---

## 10. Testing Requirements

### 10.1 Unit Tests (Vitest)

- [ ] Zod schemas tested for all validation rules
- [ ] Auth store actions tested (login, logout, setToken)
- [ ] Auth store getters tested (isAuthenticated, userRole)
- [ ] Password strength calculation tested
- [ ] All composables tested (useAuth, usePasswordToggle, etc.)
- [ ] Test coverage: > 80% for schemas + stores

### 10.2 E2E Tests (Playwright)

- [ ] Login with valid credentials → redirect to /dashboard
- [ ] Login with invalid credentials → show error alert
- [ ] Register multi-step flow complete and successful
- [ ] Forgot password sends email and enables reset
- [ ] Reset password with valid token changes password
- [ ] Email verification with OTP works end-to-end
- [ ] Profile page loads user data and allows edits
- [ ] Profile change password modal works
- [ ] RTL layout verified (dir="rtl" on html)
- [ ] All form validations work
- [ ] All error messages display correctly
- [ ] Responsive design tested at 375px, 768px, 1200px
- [ ] Test coverage: All happy paths + critical error paths

### 10.3 Accessibility Testing

- [ ] Lighthouse accessibility score: > 90
- [ ] WAVE tool reports no errors or contrast issues
- [ ] Keyboard navigation tested (Tab, Shift+Tab, Enter, Escape)
- [ ] Screen reader tested (NVDA, JAWS, or VoiceOver)
- [ ] Color contrast verified with Colour Contrast Analyser
- [ ] Mobile accessibility tested on real devices

### 10.4 Cross-Browser Testing

- [ ] Chrome/Chromium (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

---

## 11. Code Quality Requirements

### 11.1 Style & Linting

- [ ] ESLint passes with no errors
- [ ] Prettier formatting applied consistently
- [ ] TypeScript strict mode: no `any` types
- [ ] No console.logs in production code
- [ ] No commented-out code in final PR

### 11.2 Naming Conventions

- [ ] Components: PascalCase (AuthCard.vue)
- [ ] Files: kebab-case (forgot-password.vue)
- [ ] Variables/functions: camelCase
- [ ] Constants: UPPER_SNAKE_CASE
- [ ] i18n keys: dot notation (auth.login)

### 11.3 Documentation

- [ ] Components have JSDoc comments (if needed)
- [ ] Complex logic explained with comments
- [ ] Composables documented with usage examples
- [ ] Store actions documented with parameters
- [ ] README.md updated with auth pages info

---

## 12. Deployment Requirements

### 12.1 Build & Bundle

- [ ] `npm run build` completes without errors
- [ ] No build warnings
- [ ] Bundle size < 5MB total (frontend project, all pages)
- [ ] Tree-shaking removes unused code
- [ ] Source maps generated for debugging

### 12.2 Environment Variables

- [ ] API_BASE_URL configured per environment
- [ ] No hardcoded URLs in code
- [ ] `.env.example` updated with required vars
- [ ] No secrets committed to repository

### 12.3 CI/CD Pipeline

- [ ] GitHub Actions workflow passes (lint + test)
- [ ] All tests pass before merge
- [ ] Code coverage maintained > 80%
- [ ] No security vulnerabilities (npm audit)

---

## Checklist Metadata

| Field            | Value                                   |
| ---------------- | --------------------------------------- |
| **Total Items**  | 150+ requirements                       |
| **Categories**   | 12 (Functional, UI/UX, i18n, etc.)      |
| **Priority**     | All items required for stage completion |
| **Owner**        | QA / Development team                   |
| **Next Review**  | After implementation complete           |
| **Last Updated** | 2026-04-13                              |

---

## Sign-Off

**Generated by:** SpecKit Specify Step  
**For:** STAGE_30_AUTH_PAGES (Frontend Auth Pages)  
**Phase:** 07_FRONTEND_APPLICATION

This checklist represents the comprehensive quality gates for the Auth Pages stage. All items must pass before the stage is considered complete.
