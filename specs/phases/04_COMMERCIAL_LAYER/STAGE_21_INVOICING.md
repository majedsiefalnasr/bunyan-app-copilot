# STAGE_21 — Invoicing

> **Phase:** 04_COMMERCIAL_LAYER
> **Status:** NOT STARTED
> **Scope:** Invoice generation, VAT compliance, PDF export
> **Risk Level:** MEDIUM

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: MEDIUM

## Objective

Implement invoice generation with Saudi VAT compliance (ZATCA e-invoicing standards). Support automatic invoice generation from orders and manual invoice creation.

## Scope

### Backend

- Invoice model (belongs to order, customer, supplier)
- Invoice item model
- Invoice service (generate, send, mark paid, void)
- Invoice number generation (INV-YYYYMMDD-XXXX)
- VAT calculation (15% Saudi VAT)
- ZATCA QR code generation for e-invoicing compliance
- PDF generation service
- Auto-generate invoice on order completion

### Frontend

- Invoice listing page
- Invoice detail page with print layout
- Invoice creation form (manual)
- Invoice PDF preview
- Invoice email sending

### API Endpoints

| Method | Route                      | Description             |
| ------ | -------------------------- | ----------------------- |
| GET    | /api/v1/invoices           | List invoices           |
| POST   | /api/v1/invoices           | Create invoice (manual) |
| GET    | /api/v1/invoices/{id}      | Get invoice details     |
| GET    | /api/v1/invoices/{id}/pdf  | Download PDF            |
| POST   | /api/v1/invoices/{id}/send | Send invoice by email   |
| PUT    | /api/v1/invoices/{id}/void | Void invoice            |

### Database Schema

| Table         | Columns                                                                                                                                                                                                     |
| ------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| invoices      | id, invoice_number, order_id, customer_id, supplier_id, subtotal, vat_amount, vat_percentage, total, status (draft/sent/paid/overdue/void), due_date, paid_at, zatca_qr_data, notes, created_at, updated_at |
| invoice_items | id, invoice_id, description_ar, description_en, quantity, unit_price, vat_rate, total_price                                                                                                                 |

## Dependencies

- **Upstream:** STAGE_19_ORDERS, STAGE_20_PAYMENTS
- **Downstream:** STAGE_27_REPORTS
