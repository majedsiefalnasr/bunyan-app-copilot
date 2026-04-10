# Tasks — {{STAGE_NAME}}

> **Phase:** {{PHASE_NAME}}
> **Based on:** `specs/runtime/{{STAGE_DIR_NAME}}/plan.md`
> **Created:** {{ISO_TIMESTAMP}}
> **Total Tasks:** {{TASKS_TOTAL}}

## Legend

- `T001` — Sequential task ID in execution order
- `[P]` — Parallelizable task (can run concurrently with other `[P]` tasks in same group)
- `[US1]` — User story reference
- `- [ ]` — Incomplete | `- [X]` — Complete

## Setup & Foundation

- [ ] T001 [Description with exact file path]
- [ ] T002 [Description with exact file path]

## Database & Models

- [ ] T003 [Create migration: database/migrations/YYYY_MM_DD_create_xxx_table.php]
- [ ] T004 [Create Eloquent model: app/Models/Xxx.php]

## Backend — Service Layer

- [ ] T005 [Create service: app/Services/XxxService.php]
- [ ] T006 [Create repository: app/Repositories/XxxRepository.php]

## Backend — API Layer

- [ ] T007 [Create form request: app/Http/Requests/XxxRequest.php]
- [ ] T008 [Create controller: app/Http/Controllers/Api/XxxController.php]
- [ ] T009 [Create API resource: app/Http/Resources/XxxResource.php]
- [ ] T010 [Register routes: routes/api.php]

## Backend — Middleware & Auth

- [ ] T011 [Create/update middleware: app/Http/Middleware/Xxx.php]

## Frontend — State Management

- [ ] T012 [Create Pinia store: stores/xxx.ts]

## Frontend — Pages

- [ ] T013 [Create page: pages/xxx/index.vue]

## Frontend — Components

- [ ] T014 [P] [Create component: components/xxx/XxxCard.vue]
- [ ] T015 [P] [Create component: components/xxx/XxxForm.vue]

## Testing

- [ ] T016 [P] [Unit tests: tests/Unit/Services/XxxServiceTest.php]
- [ ] T017 [P] [Feature tests: tests/Feature/Api/XxxControllerTest.php]
- [ ] T018 [P] [Frontend tests: tests/components/XxxCard.test.ts]

## Documentation & Cleanup

- [ ] T019 [Update API documentation]
- [ ] T020 [Update README if needed]
