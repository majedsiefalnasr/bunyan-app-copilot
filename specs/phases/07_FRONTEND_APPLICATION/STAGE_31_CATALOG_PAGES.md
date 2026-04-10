# STAGE_31 — Catalog Pages

> **Phase:** 07_FRONTEND_APPLICATION
> **Status:** NOT STARTED
> **Scope:** Product browsing, search, filtering, category pages
> **Risk Level:** MEDIUM

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: MEDIUM

## Objective

Implement all catalog-related frontend pages for browsing products, categories, and suppliers using **Nuxt UI** components.

## Scope

### Frontend Pages

| Page               | Route             | Description                             |
| ------------------ | ----------------- | --------------------------------------- |
| Category Listing   | /categories       | Browse categories (grid with icons)     |
| Category Detail    | /categories/:slug | Products in category                    |
| Product Listing    | /products         | All products with filters               |
| Product Detail     | /products/:slug   | Product info, images, pricing, supplier |
| Supplier Directory | /suppliers        | Browse suppliers                        |
| Supplier Profile   | /suppliers/:id    | Supplier info, products, ratings        |
| Search Results     | /search           | Search results with faceted filters     |

### Nuxt UI Component Map

| Element                  | Nuxt UI Component                              |
| ------------------------ | ---------------------------------------------- |
| Product card             | `UCard` with image slot + `UBadge` for stock   |
| Filter sidebar           | `UAccordion` + `UCheckbox` groups              |
| Price range filter       | `URange` (dual-handle)                         |
| Search bar               | `UInput` (icon="i-heroicons-magnifying-glass") |
| Autocomplete suggestions | `UCommandPalette`                              |
| Pagination               | `UPagination`                                  |
| Sort dropdown            | `USelect`                                      |
| Grid/list toggle         | `UButtonGroup` + `UToggle`                     |
| Image gallery            | Custom carousel using `UCarousel`              |
| Pricing tiers            | `UTable`                                       |
| Supplier rating          | `UIcon` stars (custom)                         |
| Empty state              | `ULandingCard` / empty slot with `UIcon`       |
| Skeleton loading         | `USkeleton` card grid                          |

### Components

- `ProductCard` — `UCard`-based grid/list card with stock badge
- `ProductFilter` — `UAccordion` sidebar with `UCheckbox` + `URange`
- `ProductImageGallery` — `UCarousel` with zoom lightbox
- `PricingTierTable` — `UTable` for bulk pricing display
- `SupplierCard` — `UCard` for supplier listing
- `SearchBar` — `UInput` + `UCommandPalette` autocomplete
- `CategoryGrid` — grid of `UCard` tiles with icons

## Testing

### Unit Tests (Vitest)

- Filter composable — `useProductFilter`: price range, checkbox selections build correct query params
- Search composable — debounce timing, empty state logic

### E2E Tests (Playwright)

| Test Case                 | Scenario                                                        |
| ------------------------- | --------------------------------------------------------------- |
| Category grid loads       | Visit /categories → at least 1 category card visible            |
| Product listing paginates | Visit /products → navigate to page 2 → different products shown |
| Filter by category        | Check category filter → products filtered → URL updates         |
| Filter by price range     | Set price range slider → products filtered                      |
| Product detail page loads | Click product → detail page with images, price, supplier        |
| Search autocomplete       | Type in search bar → suggestions dropdown appears               |
| Supplier profile loads    | Visit /suppliers/:id → products listed with ratings             |
| RTL catalog layout        | All cards right-to-left, Arabic text alignment correct          |

```typescript
// tests/e2e/catalog.spec.ts
import {test, expect} from '@playwright/test'

test('product listing paginates correctly', async ({page}) => {
  await page.goto('/products')
  const firstPageItems = await page.locator('[data-testid="product-card"]').count()
  await page.click('[data-testid="pagination-next"]')
  await expect(page).toHaveURL(/page=2/)
  const secondPageItems = await page.locator('[data-testid="product-card"]').count()
  expect(secondPageItems).toBeGreaterThan(0)
})
```

## Dependencies

- **Upstream:** STAGE_07_CATEGORIES, STAGE_08_PRODUCTS, STAGE_09_SUPPLIERS, STAGE_29_NUXT_SHELL
- **Downstream:** STAGE_33_COMMERCIAL_PAGES
