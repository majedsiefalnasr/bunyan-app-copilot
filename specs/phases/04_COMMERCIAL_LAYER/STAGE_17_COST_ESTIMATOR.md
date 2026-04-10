# STAGE_17 — Cost Estimator

> **Phase:** 04_COMMERCIAL_LAYER
> **Status:** NOT STARTED
> **Scope:** Cost estimation tool, BOQ, material calculations
> **Risk Level:** HIGH

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: HIGH

## Objective

Implement the cost estimation tool for construction projects. Support Bill of Quantities (BOQ), material cost calculations, and labor estimates.

## Scope

### Backend

- Estimate model (belongs to project)
- Estimate line item model (material, labor, overhead)
- Estimate service (create, calculate, approve, export)
- BOQ template model (reusable BOQ templates)
- Cost calculation engine (quantity × unit price + markup)
- Estimate comparison (compare multiple estimates)
- Export to PDF/Excel

### Frontend

- Cost estimator page (within project)
- BOQ builder interface (add/edit line items)
- Material selector (search from product catalog)
- Cost summary dashboard
- Estimate comparison view
- PDF export button

### API Endpoints

| Method | Route                                 | Description          |
| ------ | ------------------------------------- | -------------------- |
| GET    | /api/v1/projects/{id}/estimates       | List estimates       |
| POST   | /api/v1/projects/{id}/estimates       | Create estimate      |
| GET    | /api/v1/estimates/{id}                | Get estimate details |
| PUT    | /api/v1/estimates/{id}                | Update estimate      |
| POST   | /api/v1/estimates/{id}/calculate      | Recalculate totals   |
| GET    | /api/v1/estimates/{id}/export         | Export to PDF        |
| POST   | /api/v1/estimates/{id}/items          | Add line item        |
| PUT    | /api/v1/estimates/{id}/items/{itemId} | Update line item     |
| DELETE | /api/v1/estimates/{id}/items/{itemId} | Remove line item     |

### Database Schema

| Table          | Columns                                                                                                                                                                                                                    |
| -------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| estimates      | id, project_id, title, description, status (draft/submitted/approved/rejected), total_materials, total_labor, total_overhead, grand_total, markup_percentage, approved_by, approved_at, created_by, created_at, updated_at |
| estimate_items | id, estimate_id, product_id, description_ar, description_en, category (material/labor/overhead), quantity, unit, unit_price, total_price, sort_order                                                                       |
| boq_templates  | id, name_ar, name_en, project_type, items_json, created_by, created_at, updated_at                                                                                                                                         |

## Dependencies

- **Upstream:** STAGE_08_PRODUCTS, STAGE_11_PRICING, STAGE_12_PROJECTS
- **Downstream:** STAGE_18_QUOTATIONS
