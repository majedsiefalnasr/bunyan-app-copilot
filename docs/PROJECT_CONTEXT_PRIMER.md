# Bunyan بنيان — Project Context Primer

## Platform Identity

**Bunyan (بنيان)** is a full-stack Arabic construction services and building materials marketplace platform. The name means "structure" or "edifice" in Arabic, reflecting the platform's mission to build bridges between construction industry stakeholders.

## Mission

Connect customers (عملاء), contractors (مقاولين), supervising architects (مهندسين مشرفين), and field engineers (مهندسين ميدانيين) through a unified digital platform that manages construction projects end-to-end — from project creation through phase management, task execution, reporting, and materials procurement.

## Tech Stack

| Layer        | Technology                     |
| ------------ | ------------------------------ |
| Backend      | Laravel (PHP 8.3+)             |
| Database     | MySQL 8.x (utf8mb4)            |
| Cache/Queue  | Redis                          |
| ORM          | Eloquent                       |
| Auth         | Laravel Sanctum                |
| Frontend     | Nuxt.js 3 (Vue 3 + TypeScript) |
| UI Framework | Bootstrap 5 RTL                |
| State        | Pinia                          |
| Testing (BE) | PHPUnit / Pest                 |
| Testing (FE) | Vitest                         |
| API          | RESTful (versioned /api/v1/)   |

## Domain Model

### Users & Roles

- **Customer (العميل)**: Creates projects, approves phases, manages budget
- **Contractor (المقاول)**: Assigned to projects, manages phases, creates tasks
- **Supervising Architect (المهندس المشرف)**: Oversees quality, approves tasks and phases
- **Field Engineer (المهندس الميداني)**: Executes tasks on-site, uploads reports
- **Admin (الإدارة)**: Full system access, user management, platform configuration

### Core Entities

- **Project (مشروع)**: Top-level entity. Owned by customer, assigned to contractor.
- **Phase (مرحلة)**: A project is divided into phases (foundation, structure, finishing, etc.)
- **Task (مهمة)**: Each phase contains tasks assigned to field engineers
- **Report (تقرير)**: Progress reports, inspection reports, uploaded per task/phase
- **Workflow Config**: State machine definitions for project/phase/task lifecycle

### E-Commerce Entities

- **Product (منتج)**: Building materials listed by suppliers
- **Category (تصنيف)**: Product categories (cement, steel, wood, etc.)
- **Cart (سلة)**: Shopping cart per user
- **Order (طلب)**: Purchase order with payment tracking
- **Transaction (معاملة مالية)**: Payment records

## Architecture Principles

1. **Clean Architecture**: Controllers → Services → Repositories → Models
2. **RBAC Everywhere**: Every route, every action — role-checked
3. **Arabic-First**: RTL layout, Arabic validation messages, bilingual support
4. **Workflow Engine**: State machines govern all entity lifecycles
5. **Audit Trail**: All significant actions logged
6. **Financial Safety**: All monetary operations in DB transactions

## Project Structure

```
bunyan-app/
├── backend/              # Laravel application
│   ├── app/
│   │   ├── Enums/        # PHP enums (UserRole, ProjectStatus, etc.)
│   │   ├── Http/         # Controllers, Middleware, Requests, Resources
│   │   ├── Models/       # Eloquent models
│   │   ├── Services/     # Business logic
│   │   ├── Repositories/ # Database queries
│   │   ├── Policies/     # Authorization policies
│   │   ├── Events/       # Domain events
│   │   ├── Jobs/         # Queue jobs
│   │   └── Notifications/
│   ├── database/
│   │   ├── migrations/   # Forward-only migrations
│   │   ├── seeders/
│   │   └── factories/
│   ├── routes/
│   │   └── api.php       # Versioned API routes
│   ├── config/
│   ├── tests/
│   │   ├── Unit/
│   │   └── Feature/
│   └── resources/lang/   # ar/ + en/ translations
├── frontend/             # Nuxt.js 3 application
│   ├── pages/
│   ├── components/
│   ├── composables/
│   ├── stores/
│   ├── layouts/
│   ├── middleware/
│   ├── plugins/
│   ├── locales/          # i18n JSON files
│   └── assets/
├── docs/                 # Documentation
│   ├── ai/               # AI governance
│   ├── architecture/     # ADRs, architecture map
│   └── api/              # API documentation
├── .agents/              # AI workflow
│   ├── agents/           # Agent definitions
│   ├── skills/           # Skill definitions
│   └── prompts/          # Agent prompts
└── specs/                # SpecKit specifications
```
