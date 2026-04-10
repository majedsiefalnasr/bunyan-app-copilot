# STAGE_24 — Media Library

> **Phase:** 05_COMMUNICATION_AND_MEDIA
> **Status:** NOT STARTED
> **Scope:** Media uploads, image processing, galleries
> **Risk Level:** LOW

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: LOW

## Objective

Implement centralized media library for managing images, videos, and files across the platform.

## Scope

### Backend

- Media model (polymorphic mediable)
- Media service (upload, resize, thumbnail, delete)
- Image processing (thumbnails, watermark if needed)
- Storage driver abstraction (local/S3)
- Media collection model (galleries/albums)
- Upload validation (file size, type, dimensions)
- Temporary upload with cleanup job

### Frontend

- Media library browser component
- Image upload with drag-and-drop
- Image cropper component
- Gallery/lightbox viewer
- Media picker modal (reusable across features)

### API Endpoints

| Method | Route                | Description             |
| ------ | -------------------- | ----------------------- |
| POST   | /api/v1/media/upload | Upload media            |
| GET    | /api/v1/media/{id}   | Get media details       |
| DELETE | /api/v1/media/{id}   | Delete media            |
| GET    | /api/v1/media        | List media (filterable) |

### Database Schema

| Table | Columns                                                                                                                                                                                                |
| ----- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| media | id, mediable_type, mediable_id, collection, filename, original_filename, mime_type, disk, path, size_bytes, dimensions_json, alt_text_ar, alt_text_en, sort_order, uploaded_by, created_at, updated_at |

## Dependencies

- **Upstream:** STAGE_06_API_FOUNDATION
- **Downstream:** STAGE_08_PRODUCTS, STAGE_16_DOCUMENT_MANAGEMENT
