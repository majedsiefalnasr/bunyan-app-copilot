# STAGE_16 — Document Management

> **Phase:** 03_PROJECT_MANAGEMENT
> **Status:** NOT STARTED
> **Scope:** Project documents, file uploads, version tracking
> **Risk Level:** LOW

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: LOW

## Objective

Implement document management for projects. Support file uploads, categorization, version tracking, and access control.

## Scope

### Backend

- Document model (polymorphic — project, task, etc.)
- Document category enum (Blueprint, Contract, Permit, Invoice, Photo, Report, Other)
- Document service (upload, download, version, delete)
- File storage abstraction (local/S3)
- Document access control (project-scoped)
- Document version model

### Frontend

- Document browser component (within project)
- File upload dropzone component
- Document preview (PDF, images)
- Document version history
- Document category filter

### API Endpoints

| Method | Route                           | Description           |
| ------ | ------------------------------- | --------------------- |
| GET    | /api/v1/projects/{id}/documents | List documents        |
| POST   | /api/v1/projects/{id}/documents | Upload document       |
| GET    | /api/v1/documents/{id}          | Get document metadata |
| GET    | /api/v1/documents/{id}/download | Download file         |
| DELETE | /api/v1/documents/{id}          | Delete document       |
| GET    | /api/v1/documents/{id}/versions | List versions         |

### Database Schema

| Table             | Columns                                                                                                                                                                   |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| documents         | id, documentable_type, documentable_id, category, title, original_filename, storage_path, mime_type, size_bytes, version, uploaded_by, created_at, updated_at, deleted_at |
| document_versions | id, document_id, version, storage_path, size_bytes, uploaded_by, created_at                                                                                               |

## Dependencies

- **Upstream:** STAGE_12_PROJECTS
- **Downstream:** None (standalone utility)
