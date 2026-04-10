# STAGE_22 — Notifications

> **Phase:** 05_COMMUNICATION_AND_MEDIA
> **Status:** NOT STARTED
> **Scope:** Push, email, SMS, in-app notifications
> **Risk Level:** MEDIUM

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: MEDIUM

## Objective

Implement multi-channel notification system supporting in-app, email, SMS, and push notifications.

## Scope

### Backend

- Notification model (polymorphic notifiable)
- Notification channels: database (in-app), email, SMS
- Notification service (send, mark read, preferences)
- Notification event listeners (order placed, task assigned, approval needed, etc.)
- User notification preferences model
- Email templates (Arabic/English)
- Queue-based notification dispatch (Redis)

### Frontend

- Notification bell with unread count
- Notification dropdown panel
- Notification settings page
- Notification history page

### API Endpoints

| Method | Route                              | Description                    |
| ------ | ---------------------------------- | ------------------------------ |
| GET    | /api/v1/notifications              | List notifications (paginated) |
| PUT    | /api/v1/notifications/{id}/read    | Mark as read                   |
| PUT    | /api/v1/notifications/read-all     | Mark all as read               |
| GET    | /api/v1/notifications/unread-count | Get unread count               |
| GET    | /api/v1/notification-preferences   | Get preferences                |
| PUT    | /api/v1/notification-preferences   | Update preferences             |

### Database Schema

| Table                    | Columns                                                                                    |
| ------------------------ | ------------------------------------------------------------------------------------------ |
| notifications            | id (uuid), type, notifiable_type, notifiable_id, data (json), channel, read_at, created_at |
| notification_preferences | id, user_id, type, email_enabled, sms_enabled, push_enabled                                |

## Dependencies

- **Upstream:** STAGE_06_API_FOUNDATION
- **Downstream:** Used by all feature modules
