# STAGE_33 — Commercial Pages

> **Phase:** 07_FRONTEND_APPLICATION
> **Status:** NOT STARTED
> **Scope:** Cart, checkout, orders, payments, invoices UI
> **Risk Level:** HIGH

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: HIGH

## Objective

Implement all commercial/transactional frontend pages including RFQ, orders, payments, and invoices using **Nuxt UI** components.

## Scope

### Frontend Pages

| Page            | Route             | Description                       |
| --------------- | ----------------- | --------------------------------- |
| RFQ Create      | /rfqs/create      | Create request for quotation      |
| RFQ Listing     | /rfqs             | List RFQs (role-scoped)           |
| RFQ Detail      | /rfqs/:id         | RFQ with received quotations      |
| Quote Submit    | /rfqs/:id/quote   | Supplier quote submission         |
| Quote Compare   | /rfqs/:id/compare | Side-by-side comparison           |
| Order Listing   | /orders           | List orders                       |
| Order Detail    | /orders/:id       | Order detail with status timeline |
| Checkout        | /checkout         | Order checkout page               |
| Payment         | /payment/:orderId | Payment page                      |
| Payment Success | /payment/success  | Payment confirmation              |
| Invoice Listing | /invoices         | List invoices                     |
| Invoice Detail  | /invoices/:id     | Invoice detail with print         |

### Nuxt UI Component Map

| Element                 | Nuxt UI Component                                  |
| ----------------------- | -------------------------------------------------- |
| Cart widget (floating)  | `USlideover` or `UPopover` with cart items         |
| Checkout form           | `UForm` + `UFormField` sections (address, payment) |
| Payment method selector | `URadioGroup` (card / bank transfer / mada)        |
| Order status timeline   | `UTimeline`                                        |
| Invoice document        | `UCard` + print-specific CSS                       |
| Quote comparison table  | `UTable` (columns per supplier)                    |
| RFQ items builder       | Editable `UTable`                                  |
| Status badge            | `UBadge` (color per order status)                  |
| Success page            | `ULandingCard` + `UIcon` checkmark                 |
| Loading skeleton        | `USkeleton`                                        |
| Alert / error           | `UAlert` (color="error")                           |
| Pagination              | `UPagination`                                      |

### Components

- `CartWidget` — `USlideover` cart panel with `UBadge` count indicator
- `CheckoutForm` — `UForm` with address, shipping, payment sections
- `PaymentMethodSelector` — `URadioGroup` for card/bank/mada
- `OrderStatusTimeline` — `UTimeline` from PENDING → COMPLETED
- `InvoiceDocument` — print-ready `UCard` layout (ZATCA QR, 15% VAT)
- `QuoteComparisonTable` — `UTable` side-by-side quote matrix
- `RFQItemsForm` — editable `UTable` RFQ line items builder

## Testing

### Unit Tests (Vitest)

- Invoice VAT calculation — `computeVat(subtotal)` returns 15% correctly
- Order number format — matches pattern `BNY-YYYYMMDD-XXXX`
- Checkout form schema — required fields, phone format validation

### E2E Tests (Playwright)

| Test Case                         | Scenario                                                         |
| --------------------------------- | ---------------------------------------------------------------- |
| Checkout flow end-to-end          | Add to cart → checkout → fill address → select payment → confirm |
| RFQ creation and quotation submit | Create RFQ → supplier submits quote → buyer sees in RFQ detail   |
| Quote comparison loads            | RFQ with 2+ quotes → /compare shows side-by-side table           |
| Order status timeline progresses  | Order moves PENDING→CONFIRMED → timeline step highlights         |
| Invoice print layout              | Visit /invoices/:id/print → print button → ZATCA QR visible      |
| Payment success page displays     | After mock payment → /payment/success → order number shown       |
| Cart item count badge             | Add product → cart icon badge count increments                   |

```typescript
// tests/e2e/commercial.spec.ts
import {test, expect} from '@playwright/test'

test('checkout flow completes successfully', async ({page}) => {
  await page.goto('/checkout')
  await page.fill('[data-testid="shipping-address"]', 'الرياض، حي العليا')
  await page.click('[data-testid="payment-method-card"]')
  await page.click('[data-testid="place-order-button"]')
  await expect(page).toHaveURL('/payment/success')
  await expect(page.locator('[data-testid="order-number"]')).toHaveText(/BNY-\d{8}-\d{4}/)
})

test('invoice shows ZATCA QR code', async ({page}) => {
  await page.goto('/invoices/1')
  await expect(page.locator('[data-testid="zatca-qr"]')).toBeVisible()
})
```

## Dependencies

- **Upstream:** STAGE_17-21 (all commercial stages), STAGE_29_NUXT_SHELL
- **Downstream:** None
