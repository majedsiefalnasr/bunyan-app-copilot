# STAGE_23 — Messaging

> **Phase:** 05_COMMUNICATION_AND_MEDIA
> **Status:** NOT STARTED
> **Scope:** In-app messaging, conversations, project chat
> **Risk Level:** MEDIUM

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: MEDIUM

## Objective

Implement in-app messaging system for direct communication between platform users (customer-supplier, team members).

## Scope

### Backend

- Conversation model (between 2+ users, optionally project-scoped)
- Message model (belongs to conversation)
- Messaging service (create conversation, send message, list)
- Message read receipts
- File attachment support in messages
- Real-time broadcasting (Laravel Echo + Pusher/Soketi)

### Frontend

- Messaging inbox page
- Conversation view with message thread
- New conversation composer
- Message notification badge
- File attachment in messages

### API Endpoints

| Method | Route                               | Description         |
| ------ | ----------------------------------- | ------------------- |
| GET    | /api/v1/conversations               | List conversations  |
| POST   | /api/v1/conversations               | Create conversation |
| GET    | /api/v1/conversations/{id}/messages | List messages       |
| POST   | /api/v1/conversations/{id}/messages | Send message        |
| PUT    | /api/v1/conversations/{id}/read     | Mark as read        |

### Database Schema

| Table                     | Columns                                                                                                     |
| ------------------------- | ----------------------------------------------------------------------------------------------------------- |
| conversations             | id, project_id, title, type (direct/group), created_at, updated_at                                          |
| conversation_participants | id, conversation_id, user_id, last_read_at, joined_at                                                       |
| messages                  | id, conversation_id, sender_id, body, type (text/file), attachment_path, created_at, updated_at, deleted_at |

## Dependencies

- **Upstream:** STAGE_06_API_FOUNDATION
- **Downstream:** None
