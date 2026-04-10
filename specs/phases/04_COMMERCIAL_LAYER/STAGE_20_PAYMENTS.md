# STAGE_20 — Payments

> **Phase:** 04_COMMERCIAL_LAYER
> **Status:** NOT STARTED
> **Scope:** Payment processing, gateway integration, payment tracking
> **Risk Level:** HIGH

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: HIGH

## Objective

Implement payment processing with Saudi payment gateway integration (e.g., Moyasar, HyperPay, or Tap). Support multiple payment methods and transaction tracking.

## Scope

### Backend

- Payment model (polymorphic — order, invoice)
- Transaction model (individual payment attempts)
- Payment service (initiate, capture, refund)
- Payment gateway adapter (abstraction layer)
- Webhook handler for payment status notifications
- Payment verification middleware
- Payment receipt generation

### Frontend

- Payment checkout page
- Payment method selector (credit card, bank transfer, mada)
- Payment confirmation page
- Payment history page
- Payment receipt download

### API Endpoints

| Method | Route                         | Description             |
| ------ | ----------------------------- | ----------------------- |
| POST   | /api/v1/payments/initiate     | Initiate payment        |
| GET    | /api/v1/payments/{id}         | Get payment details     |
| POST   | /api/v1/payments/{id}/capture | Capture payment         |
| POST   | /api/v1/payments/{id}/refund  | Refund payment          |
| POST   | /api/v1/webhooks/payment      | Payment gateway webhook |
| GET    | /api/v1/payments/history      | Payment history         |

### Database Schema

| Table        | Columns                                                                                                                                                                                           |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| payments     | id, payable_type, payable_id, amount, currency (SAR), method (card/bank/mada), status (pending/processing/completed/failed/refunded), gateway_reference, paid_by, paid_at, created_at, updated_at |
| transactions | id, payment_id, type (charge/refund/void), amount, status, gateway_id, gateway_response, created_at                                                                                               |

## Dependencies

- **Upstream:** STAGE_19_ORDERS, STAGE_14_WORKFLOW_ENGINE
- **Downstream:** STAGE_21_INVOICING
