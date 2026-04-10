# STAGE_18 — Quotations

> **Phase:** 04_COMMERCIAL_LAYER
> **Status:** NOT STARTED
> **Scope:** RFQ, supplier quotes, quote comparison
> **Risk Level:** MEDIUM

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: MEDIUM

## Objective

Implement Request for Quotation (RFQ) workflow allowing customers to request quotes from suppliers, and suppliers to submit competitive bids.

## Scope

### Backend

- RFQ model (request for quotation from customer)
- Quotation model (supplier response)
- RFQ service (create, send to suppliers, close)
- Quotation service (submit, revise, accept, reject)
- RFQ status: DRAFT → SENT → QUOTING → EVALUATION → AWARDED → CLOSED
- Auto-notification to relevant suppliers by category
- Quotation comparison service

### Frontend

- RFQ creation form (Customer)
- RFQ listing page (filterable by status)
- Quotation submission form (Supplier)
- Quote comparison table
- Award notification interface
- RFQ detail page with all received quotes

### API Endpoints

| Method | Route                                     | Description           |
| ------ | ----------------------------------------- | --------------------- |
| GET    | /api/v1/rfqs                              | List RFQs             |
| POST   | /api/v1/rfqs                              | Create RFQ            |
| GET    | /api/v1/rfqs/{id}                         | Get RFQ details       |
| POST   | /api/v1/rfqs/{id}/send                    | Send RFQ to suppliers |
| POST   | /api/v1/rfqs/{id}/quotations              | Submit quotation      |
| GET    | /api/v1/rfqs/{id}/quotations              | List quotations       |
| PUT    | /api/v1/rfqs/{id}/quotations/{qid}/accept | Accept quotation      |
| GET    | /api/v1/rfqs/{id}/compare                 | Compare quotations    |

### Database Schema

| Table           | Columns                                                                                                                             |
| --------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| rfqs            | id, project_id, created_by, title, description, status, delivery_deadline, response_deadline, created_at, updated_at                |
| rfq_items       | id, rfq_id, product_id, description, quantity, unit, specifications                                                                 |
| quotations      | id, rfq_id, supplier_id, total_price, delivery_days, notes, status (submitted/revised/accepted/rejected), valid_until, submitted_at |
| quotation_items | id, quotation_id, rfq_item_id, unit_price, total_price, notes                                                                       |

## Dependencies

- **Upstream:** STAGE_09_SUPPLIERS, STAGE_17_COST_ESTIMATOR
- **Downstream:** STAGE_19_ORDERS
