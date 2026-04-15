# Accessibility Checklist — STAGE_07_CATEGORIES

**Purpose**: Unit test for requirement quality and completeness of accessibility controls.  
**Generated**: 2026-04-15  
**Spec Reference**: specs/runtime/007-categories/spec.md

---

## RTL Layout Support (NFR-005, i18n-governance, bootstrap-ui-system skills)

- [ ] **CHK-A11Y-001**: HTML root element has dir="rtl" and lang="ar" attributes for Arabic
  - **Requirement**: NFR-005 "Frontend components must support Arabic (RTL) layouts"
  - **Validation**: Inspect page markup: `<html dir="rtl" lang="ar">` or dynamic via Nuxt config
  - **Example**:
    ```typescript
    // nuxt.config.ts
    export default defineNuxtConfig({
      app: {
        head: {
          htmlAttrs: { dir: 'rtl', lang: 'ar' },
        },
      },
    });
    ```

- [ ] **CHK-A11Y-002**: Tailwind logical properties used for spacing/alignment (not left/right)
  - **Requirement**: bootstrap-ui-system RTL support pattern
  - **Validation**: Component styles use `ms-` (margin-start), `ps-` (padding-start), `start-0` instead of `ml-`, `pl-`, `left-0`
  - **Example**:
    ```vue
    <div class="flex gap-4 ms-4"><!-- Margin-start handles RTL automatically -->
      <span>محتوى</span>
    </div>
    ```

- [ ] **CHK-A11Y-003**: Category names render correctly in RTL context (no text direction override)
  - **Requirement**: NFR-006 "text inputs must accept Arabic Unicode"
  - **Validation**: Arabic category names (e.g., "مواد بناء") display in RTL direction without manual override; English names in parent context switch to LTR
  - **Example**:
    ```vue
    <div>
      <p>{{ category.name_ar }}</p> <!-- Renders RTL automatically -->
      <p>{{ category.name_en }}</p> <!-- Renders LTR automatically -->
    </div>
    ```

- [ ] **CHK-A11Y-004**: Form inputs support Arabic text entry (no input restrictions)
  - **Requirement**: NFR-006 "All text inputs must accept Arabic Unicode"
  - **Validation**: name_ar input field accepts Unicode characters; type "أسمنت" and value persists correctly in v-model
  - **Example**: UInput component from Nuxt UI supports unicode; no maxlength or pattern restrictions on Arabic text

- [ ] **CHK-A11Y-005**: Category breadcrumb component reverses order dynamically based on lang
  - **Requirement**: FR-018 "RTL-aware layout"
  - **Validation**: Breadcrumb in Arabic reads RTL (Root → Parent → Child); breadcrumb in English reads LTR (Child → Parent → Root)
  - **Example**:
    ```vue
    <template>
      <div class="flex gap-2">
        <template v-for="(ancestor, i) in breadcrumb" :key="i">
          <span v-if="i > 0" class="text-gray-400">/</span>
          <a :href="`/categories/${ancestor.slug}`">{{ getLocalizedName(ancestor) }}</a>
        </template>
      </div>
    </template>
    ```

---

## Semantic HTML (Web Accessibility Best Practices)

- [ ] **CHK-A11Y-006**: Category tree uses native `<ul>` and `<li>` for hierarchy
  - **Requirement**: Semantic HTML for screen readers to understand hierarchy
  - **Validation**: Tree component renders `<ul>` with `role="tree"` (or native tree semantics via Nuxt UI UTree)
  - **Example**:
    ```vue
    <ul role="tree">
      <li role="treeitem">
        <span @click="toggle">📁 مواد بناء</span>
        <ul v-if="isExpanded">
          <li role="treeitem">
            <span>📦 أسمنت</span>
          </li>
        </ul>
      </li>
    </ul>
    ```

- [ ] **CHK-A11Y-007**: Form labels associated with inputs via `for` attribute or v-model binding
  - **Requirement**: WCAG 2.1 Level A - Form Labels
  - **Validation**: CategoryFormModal labels use `<label for="name_ar">` or UForm with proper label binding
  - **Example**:
    ```vue
    <UFormField label="اسم الفئة بالعربية" name="name_ar">
      <UInput v-model="state.name_ar" placeholder="مثال: مواد بناء" />
    </UFormField>
    ```

- [ ] **CHK-A11Y-008**: Input errors use `aria-invalid` and `aria-describedby`
  - **Requirement**: WCAG 2.1 Level A - Form Error Identification
  - **Validation**: Invalid input has `aria-invalid="true"` and `aria-describedby="error-name_ar"`; error message has matching `id`
  - **Example**:
    ```vue
    <input
      v-model="state.name_ar"
      :aria-invalid="errors.name_ar ? 'true' : 'false'"
      aria-describedby="error-name_ar"
    />
    <div id="error-name_ar" v-if="errors.name_ar" class="text-red-600">
      {{ errors.name_ar }}
    </div>
    ```

- [ ] **CHK-A11Y-009**: Buttons have descriptive text (not generic "Edit", "Delete")
  - **Requirement**: WCAG 2.1 Level A - Link Text
  - **Validation**: Buttons labeled "تحرير الفئة" (Edit Category), "حذف الفئة" (Delete Category), not just icons
  - **Example**:
    ```vue
    <UButton label="تحرير مواد بناء" icon="lucide-edit" @click="editCategory" />
    ```

- [ ] **CHK-A11Y-010**: Tree expand/collapse buttons use aria-expanded
  - **Requirement**: WCAG 2.1 Level A - Button State
  - **Validation**: Expand button has `aria-expanded="true"` or `"false"` to indicate state to screen readers
  - **Example**:
    ```vue
    <button :aria-expanded="isExpanded" @click="toggle" class="flex items-center">
      <span class="text-lg">{{ isExpanded ? '▼' : '▶' }}</span>
      <span>{{ category.name_ar }}</span>
    </button>
    ```

---

## Keyboard Navigation (WCAG 2.1 Level A, bootstrap-ui-system)

- [ ] **CHK-A11Y-011**: Category tree navigable via keyboard (Arrow Up/Down, Enter, Space)
  - **Requirement**: Request mentions "keyboard navigation on tree"
  - **Validation**: Playwright test: Tab to tree item → Arrow Down moves to next item → Arrow Right expands children → Enter/Space toggles expansion
  - **Example**:
    ```typescript
    // Vitest + @testing-library/vue
    const tree = screen.getByRole('tree');
    userEvent.tab();
    await userEvent.keyboard('{ArrowDown}');
    expect(screen.getByRole('treeitem', { name: /أسمنت/ })).toBeFocused();
    ```

- [ ] **CHK-A11Y-012**: Tree expand/collapse via keyboard (Arrow Right to expand, Arrow Left to collapse)
  - **Requirement**: Keyboard accessibility for tree operations
  - **Validation**: Focus on tree item → press Arrow Right → children become visible and focusable
  - **Example**: Tree component listens to keydown events and toggles expansion

- [ ] **CHK-A11Y-013**: Form inputs have visible focus indicator (not removed by CSS)
  - **Requirement**: WCAG 2.1 Level A - Focus Visible
  - **Validation**: Tab through CategoryFormModal inputs; each input shows clear focus outline or highlight
  - **Example**: Tailwind class `focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500`

- [ ] **CHK-A11Y-014**: CategorySelector dropdown navigable via keyboard (Type to search, Arrow keys to select, Enter to confirm)
  - **Requirement**: FR-019 "keyboard navigation on dropdown"
  - **Validation**: Playwright test: Focus dropdown → type "cement" → result filters → Arrow Down selects first result → Enter confirms
  - **Example**: Nuxt UI USelectMenu component handles keyboard natively; custom implementation uses @keydown listeners

- [ ] **CHK-A11Y-015**: Modal dialogs (Create/Edit) trap focus and prevent tab outside
  - **Requirement**: WCAG 2.1 Level A - Focus Management
  - **Validation**: CategoryFormModal opened → Tab cycles through form controls → Shift+Tab cycles backward → Tab at last control loops to first
  - **Example**: Nuxt UI UModal handles focus trap automatically; custom dialogs use focus-trap package

- [ ] **CHK-A11Y-016**: Escape key closes modals and returns focus to trigger button
  - **Requirement**: WCAG 2.1 Level A - Modal Behavior
  - **Validation**: CategoryFormModal open → press Escape → modal closes → focus returns to "Create Category" button
  - **Example**:
    ```vue
    <UModal @keydown.escape="closeModal()" />
    ```

- [ ] **CHK-A11Y-017**: Skip navigation link or focus management for keyboard users
  - **Requirement**: WCAG 2.1 Level A - Bypass Blocks
  - **Validation**: (If applicable) Page has "Skip to main content" link or tree is first focusable element
  - **Example**: AdminCategories page has skip link: `<a href="#tree" class="sr-only">Skip to categories</a>`

---

## ARIA Labels and Roles (WCAG 2.1 Level A)

- [ ] **CHK-A11Y-018**: Tree items have aria-label or descriptive text content
  - **Requirement**: Request mentions "ARIA labels for tree items"
  - **Validation**: Each tree node has clear, descriptive label (e.g., "Concrete category, 5 children, expandable")
  - **Example**:
    ```vue
    <li role="treeitem" :aria-label="`${category.name_ar}, ${category.children.length} items`">
      {{ category.name_ar }}
    </li>
    ```

- [ ] **CHK-A11Y-019**: Parent/child relationship indicated via aria-owns or nested structure
  - **Requirement**: Screen readers understand tree hierarchy
  - **Validation**: Parent node has `aria-owns="child-id"` or nested `<ul>` under `<li>` indicates ownership
  - **Example**: Nested `<ul>` structure inherently conveys parent-child relationship

- [ ] **CHK-A11Y-020**: Category icons have decorative or semantic ARIA treatment
  - **Requirement**: Icon accessibility; spec mentions icons per category
  - **Validation**: Icon tags have `aria-hidden="true"` if decorative, or `aria-label` if semantic
  - **Example**:
    ```vue
    <UIcon name="lucide-box" aria-hidden="true" />
    <!-- OR if meaningful: -->
    <span role="img" aria-label="Building materials category">🏗️</span>
    ```

- [ ] **CHK-A11Y-021**: Dropdown/selector has proper ARIA combobox attributes
  - **Requirement**: FR-019 CategorySelector accessibility
  - **Validation**: CategorySelector has `role="combobox"`, `aria-expanded`, `aria-controls`, `aria-owns`
  - **Example**:
    ```vue
    <div role="combobox" aria-expanded="isOpen" aria-controls="category-listbox">
      <input type="text" />
      <ul id="category-listbox" role="listbox">
        <li role="option">...</li>
      </ul>
    </div>
    ```

- [ ] **CHK-A11Y-022**: Error messages are announced to screen readers (role="alert" or aria-live)
  - **Requirement**: WCAG 2.1 Level A - Status Messages
  - **Validation**: Validation error appears with `role="alert"` or `aria-live="polite"`; screen reader announces it immediately
  - **Example**:
    ```vue
    <div v-if="error" role="alert" class="text-red-600">
      {{ error.message }}
    </div>
    ```

- [ ] **CHK-A11Y-023**: Search input in CategorySelector has aria-label or associated label
  - **Requirement**: FR-019 search capability should be accessible
  - **Validation**: Search input has `aria-label="Search categories"` or `<label>Search</label>`
  - **Example**:
    ```vue
    <input
      type="text"
      placeholder="ابحث عن فئة"
      aria-label="Search categories"
      @input="filterCategories"
    />
    ```

---

## Color and Contrast (WCAG 2.1 Level AA)

- [ ] **CHK-A11Y-024**: Text-to-background contrast ratio ≥ 4.5:1 for normal text
  - **Requirement**: WCAG 2.1 Level AA - Color Contrast
  - **Validation**: All text in category display meets 4.5:1 contrast ratio (e.g., #171717 text on white background = ~20:1, meets standard)
  - **Example**: Use contrast checker tool on category cards and form inputs

- [ ] **CHK-A11Y-025**: Focus indicators have ≥ 3:1 contrast with background
  - **Requirement**: WCAG 2.1 Level AA - Focus Visible Contrast
  - **Validation**: Focus ring color contrasts 3:1 with element background (e.g., blue focus on white)
  - **Example**: Tailwind `ring-blue-500` on white = meets standard

- [ ] **CHK-A11Y-026**: Color alone is not used to convey meaning (e.g., "red = error" has text label too)
  - **Requirement**: WCAG 2.1 Level A - Use of Color
  - **Validation**: Form errors not shown only by red background; error icon + text label used
  - **Example**:
    ```vue
    <div v-if="error" class="flex items-center gap-2">
      <UIcon name="lucide-alert-circle" class="text-red-600" />
      <span class="text-sm text-red-600">{{ error.message }}</span>
    </div>
    ```

---

## Responsive and Zoom Support (WCAG 2.1 Level AA)

- [ ] **CHK-A11Y-027**: Category page zooms to 200% without horizontal scrolling
  - **Requirement**: WCAG 2.1 Level AA - Reflow
  - **Validation**: Playwright test: zoom to 200% → tree and forms still readable; no horizontal scroll at 200%
  - **Example**:
    ```typescript
    await page.goto('/admin/categories');
    await page.evaluate(() => (document.body.style.zoom = '200%'));
    const hasHorizontalScroll = await page.evaluate(
      () => document.body.scrollWidth > window.innerWidth
    );
    expect(hasHorizontalScroll).toBe(false);
    ```

- [ ] **CHK-A11Y-028**: Category names wrap gracefully (no text truncation without mechanism to reveal)
  - **Requirement**: NFR-006 "Long Arabic names should wrap or truncate gracefully"
  - **Validation**: Long Arabic category name (e.g., "تجهيزات كهربائية ومدخلات توزيع الطاقة") displays on multiple lines; not hard-truncated
  - **Example**: CSS `word-wrap: break-word` or `word-break: break-word`; avoid `max-width` without `overflow-wrap`

- [ ] **CHK-A11Y-029**: Meta viewport tag prevents user-disabled zoom (not set to user-scalable=no)
  - **Requirement**: WCAG 2.1 Level AA - Reflow
  - **Validation**: `<meta name="viewport" content="width=device-width, initial-scale=1">` (no user-scalable=no)
  - **Example**: Nuxt default setup in app.html or nuxt.config includes proper viewport

---

## Testing and Validation (a11y best practices)

- [ ] **CHK-A11Y-030**: Automated a11y tests run in CI (axe-core or similar)
  - **Requirement**: WCAG 2.1 compliance validation
  - **Validation**: tests/ includes Vitest test with axe-matchers: `expect(element).toHaveNoViolations()`
  - **Example**:

    ```typescript
    import { axe, toHaveNoViolations } from 'jest-axe';
    expect.extend(toHaveNoViolations);

    test('category tree has no a11y violations', async () => {
      const { container } = render(CategoryTree);
      const results = await axe(container);
      expect(results).toHaveNoViolations();
    });
    ```

- [ ] **CHK-A11Y-031**: Manual screen reader testing completed (NVDA, JAWS, or VoiceOver)
  - **Requirement**: Real-world a11y validation
  - **Validation**: QA checklist includes manual test with screen reader; tree navigation, form interactions verified
  - **Example**: Test Steps: VoiceOver on Mac → navigate tree with VO+Arrow Keys → verify category names announced → form submission verified

- [ ] **CHK-A11Y-032**: Keyboard-only navigation tested end-to-end
  - **Requirement**: WCAG 2.1 Level A - Keyboard Accessible
  - **Validation**: QA/Playwright e2e test: Focus category page → navigate tree via Tab+Arrow Keys → open form → fill inputs → submit (no mouse)
  - **Example**:
    ```typescript
    test('category admin page is keyboard accessible', async ({ page }) => {
      await page.goto('/admin/categories');
      await page.keyboard.press('Tab'); // Focus tree
      await page.keyboard.press('ArrowDown'); // Navigate
      await page.keyboard.press('Enter'); // Edit
      // ... form navigation
    });
    ```

---

## Text Alternatives and Context

- [ ] **CHK-A11Y-033**: Icon-only buttons have aria-label or title attribute
  - **Requirement**: WCAG 2.1 Level A - Non-Text Content
  - **Validation**: Edit/Delete icon buttons have `aria-label="Edit category"` or `title="Edit"`
  - **Example**:
    ```vue
    <button :aria-label="`Edit ${category.name_ar}`">
      <UIcon name="lucide-edit" />
    </button>
    ```

- [ ] **CHK-A11Y-034**: Breadcrumb separators are meaningful (not just visual `/`)
  - **Requirement**: FR-018 breadcrumb accessibility
  - **Validation**: Breadcrumb separators have `aria-hidden="true"` (purely decorative) or have semantic meaning
  - **Example**:
    ```vue
    <nav aria-label="Breadcrumb">
      <ol>
        <li><a href="#/">Home</a></li>
        <li aria-hidden="true">/</li>
        <li><a href="#/building-materials">Building Materials</a></li>
      </ol>
    </nav>
    ```

- [ ] **CHK-A11Y-035**: Form helper text (e.g., "Max 100 characters") associated via aria-describedby
  - **Requirement**: WCAG 2.1 Level A - Help and Instructions
  - **Validation**: Input has `aria-describedby="help-name_ar"`; helper text div has matching `id`
  - **Example**:
    ```vue
    <UInput v-model="state.name_ar" aria-describedby="help-name_ar" maxlength="100" />
    <p id="help-name_ar" class="text-sm text-gray-500">
      Max 100 characters
    </p>
    ```

---

## Internationalization and Localization (i18n-governance skill)

- [ ] **CHK-A11Y-036**: All form labels, buttons, and messages are localized (Arabic and English)
  - **Requirement**: NFR-005 "Components must support Arabic (RTL) and English (LTR) layouts"
  - **Validation**: All text keys exist in i18n files (locales/ar.json, locales/en.json); no hardcoded English
  - **Example**:
    ```json
    // locales/ar.json
    {
      "categories": {
        "create": "إنشاء فئة",
        "edit": "تحرير الفئة",
        "delete": "حذف الفئة"
      }
    }
    ```

- [ ] **CHK-A11Y-037**: Validation error messages localized
  - **Requirement**: error contract includes localization support
  - **Validation**: POST with invalid data returns error message in both Arabic and English (based on Accept-Language header or user preference)
  - **Example**:
    ```json
    {
      "error": {
        "message": "اسم الفئة مطلوب / Category name is required"
      }
    }
    ```

---

## Performance and Accessibility (Related)

- [ ] **CHK-A11Y-038**: Category tree loads without JavaScript disabled (graceful degradation)
  - **Requirement**: Progressive enhancement (desired, not strict WCAG)
  - **Validation**: (Optional) Tree renders as flat list if JavaScript disabled; functionality degraded but content accessible
  - **Example**: Server-rendered fallback list of categories via `<noscript>` or SSR rendering

- [ ] **CHK-A11Y-039**: Page title and headings describe purpose clearly
  - **Requirement**: WCAG 2.1 Level A - Page Title
  - **Validation**: AdminCategories page has `<title>إدارة الفئات | Bunyan</title>` (Arabic first); main heading is `<h1>إدارة فئات المنتجات</h1>`
  - **Example**: Nuxt component:
    ```typescript
    definePageMeta({
      title: 'Category Management | Bunyan',
    });
    ```
