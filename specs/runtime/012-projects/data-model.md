# Data Model — Projects (المشاريع)

> **Phase:** 03_PROJECT_MANAGEMENT > **Stage:** STAGE_12_PROJECTS
> **Created:** 2026-04-19

---

## Entity Relationship Diagram

```
User (owner) ──1:N──► Project ──1:N──► ProjectPhase
```

---

## Table: `projects`

| Column           | Type                                                      | Constraints                      | Notes                           |
| ---------------- | --------------------------------------------------------- | -------------------------------- | ------------------------------- |
| id               | BIGINT UNSIGNED                                           | PK, AUTO_INCREMENT               |                                 |
| owner_id         | BIGINT UNSIGNED                                           | FK → users.id, NOT NULL, INDEX   | Must be Customer-role user      |
| name_ar          | VARCHAR(255)                                              | NOT NULL                         | Arabic project name             |
| name_en          | VARCHAR(255)                                              | NOT NULL                         | English project name            |
| description      | TEXT                                                      | NULLABLE                         | Bilingual description           |
| city             | VARCHAR(100)                                              | NOT NULL, INDEX                  | City name for filtering         |
| district         | VARCHAR(100)                                              | NULLABLE                         | District within city            |
| location_lat     | DECIMAL(10,7)                                             | NULLABLE                         | Latitude (-90 to 90)            |
| location_lng     | DECIMAL(10,7)                                             | NULLABLE                         | Longitude (-180 to 180)         |
| status           | ENUM(draft,planning,in_progress,on_hold,completed,closed) | NOT NULL, DEFAULT 'draft', INDEX | Validated state machine         |
| type             | ENUM(residential,commercial,infrastructure)               | NOT NULL, INDEX                  | Project classification          |
| budget_estimated | DECIMAL(15,2)                                             | NULLABLE                         | Must be > 0 if provided         |
| budget_actual    | DECIMAL(15,2)                                             | NULLABLE, DEFAULT 0.00           | Not user-editable in this stage |
| start_date       | DATE                                                      | NULLABLE                         | Project planned start           |
| end_date         | DATE                                                      | NULLABLE                         | Project planned end             |
| created_at       | TIMESTAMP                                                 | Laravel default                  |                                 |
| updated_at       | TIMESTAMP                                                 | Laravel default                  | Used for optimistic locking     |
| deleted_at       | TIMESTAMP                                                 | NULLABLE                         | Soft-delete                     |

### Indexes

| Index Name                | Columns    | Type   | Purpose               |
| ------------------------- | ---------- | ------ | --------------------- |
| projects_owner_id_index   | owner_id   | B-TREE | Owner-scoped queries  |
| projects_status_index     | status     | B-TREE | Status filtering      |
| projects_type_index       | type       | B-TREE | Type filtering        |
| projects_city_index       | city       | B-TREE | City filtering        |
| projects_deleted_at_index | deleted_at | B-TREE | Soft-delete exclusion |

### Foreign Keys

| FK Name                   | Column   | References | On Delete |
| ------------------------- | -------- | ---------- | --------- |
| projects_owner_id_foreign | owner_id | users(id)  | CASCADE   |

---

## Table: `project_phases`

| Column                | Type                                | Constraints                        | Notes                        |
| --------------------- | ----------------------------------- | ---------------------------------- | ---------------------------- |
| id                    | BIGINT UNSIGNED                     | PK, AUTO_INCREMENT                 |                              |
| project_id            | BIGINT UNSIGNED                     | FK → projects.id, NOT NULL, INDEX  | Parent project               |
| name_ar               | VARCHAR(255)                        | NOT NULL                           | Arabic phase name            |
| name_en               | VARCHAR(255)                        | NOT NULL                           | English phase name           |
| sort_order            | INTEGER UNSIGNED                    | NOT NULL, DEFAULT 0                | Display ordering             |
| status                | ENUM(pending,in_progress,completed) | NOT NULL, DEFAULT 'pending'        | Simple enum, no machine      |
| start_date            | DATE                                | NULLABLE                           | Must be within project range |
| end_date              | DATE                                | NULLABLE                           | Must be within project range |
| completion_percentage | TINYINT UNSIGNED                    | NOT NULL, DEFAULT 0, CHECK (0–100) | 0–100 range enforced         |
| created_at            | TIMESTAMP                           | Laravel default                    |                              |
| updated_at            | TIMESTAMP                           | Laravel default                    |                              |

### Indexes

| Index Name                        | Columns                | Type      | Purpose                 |
| --------------------------------- | ---------------------- | --------- | ----------------------- |
| project_phases_project_id_index   | project_id             | B-TREE    | Phase lookup by project |
| project_phases_project_sort_index | project_id, sort_order | COMPOSITE | Ordered phase listing   |

### Foreign Keys

| FK Name                           | Column     | References   | On Delete |
| --------------------------------- | ---------- | ------------ | --------- |
| project_phases_project_id_foreign | project_id | projects(id) | CASCADE   |

---

## Eloquent Models

### Project

```php
namespace App\Models;

class Project extends BaseModel
{
    // Inherits: SoftDeletes, HasFactory, $guarded = []

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'type' => ProjectType::class,
            'budget_estimated' => 'decimal:2',
            'budget_actual' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    // ── Relationships ──

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class)->orderBy('sort_order');
    }

    // ── Scopes ──

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::ADMIN => $query,
            UserRole::CUSTOMER => $query->where('owner_id', $user->id),
            // Stub: Contractor/Architect/Engineer return empty until STAGE_15
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function scopeStatus(Builder $query, ProjectStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeType(Builder $query, ProjectType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    // ── Helpers ──

    public function isEditable(): bool
    {
        return $this->status !== ProjectStatus::CLOSED;
    }
}
```

### ProjectPhase

```php
namespace App\Models;

class ProjectPhase extends BaseModel
{
    // No SoftDeletes on phases (hard-delete per spec)
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => PhaseStatus::class,
            'sort_order' => 'integer',
            'completion_percentage' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
```

**Note on ProjectPhase:** Does NOT extend `BaseModel` because `BaseModel` includes `SoftDeletes` by default. Phases use hard-delete per spec. Instead, extends `Model` directly with `HasFactory` and manual `$guarded = []`.

---

## Enums

### ProjectStatus

```php
namespace App\Enums;

enum ProjectStatus: string
{
    case DRAFT = 'draft';
    case PLANNING = 'planning';
    case IN_PROGRESS = 'in_progress';
    case ON_HOLD = 'on_hold';
    case COMPLETED = 'completed';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'مسودة',
            self::PLANNING => 'تخطيط',
            self::IN_PROGRESS => 'قيد التنفيذ',
            self::ON_HOLD => 'متوقف',
            self::COMPLETED => 'مكتمل',
            self::CLOSED => 'مغلق',
        };
    }

    /** @return ProjectStatus[] */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PLANNING],
            self::PLANNING => [self::IN_PROGRESS],
            self::IN_PROGRESS => [self::ON_HOLD, self::COMPLETED],
            self::ON_HOLD => [self::IN_PROGRESS],
            self::COMPLETED => [self::CLOSED],
            self::CLOSED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
```

### ProjectType

```php
namespace App\Enums;

enum ProjectType: string
{
    case RESIDENTIAL = 'residential';
    case COMMERCIAL = 'commercial';
    case INFRASTRUCTURE = 'infrastructure';

    public function label(): string
    {
        return match ($this) {
            self::RESIDENTIAL => 'سكني',
            self::COMMERCIAL => 'تجاري',
            self::INFRASTRUCTURE => 'بنية تحتية',
        };
    }
}
```

### PhaseStatus

```php
namespace App\Enums;

enum PhaseStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::IN_PROGRESS => 'قيد التنفيذ',
            self::COMPLETED => 'مكتمل',
        };
    }
}
```

---

## Migration Files

### Migration 1: `create_projects_table`

```
Filename: YYYY_MM_DD_HHMMSS_create_projects_table.php
```

Creates `projects` table with all columns, indexes, and foreign keys as defined above.

### Migration 2: `create_project_phases_table`

```
Filename: YYYY_MM_DD_HHMMSS_create_project_phases_table.php
```

Creates `project_phases` table with all columns, indexes, and foreign keys. Depends on `projects` table existing.

---

## Data Integrity Rules

1. `owner_id` must reference a user with `role = 'customer'` (validated in Form Request, not FK constraint)
2. `status` transitions validated by `ProjectStatus::canTransitionTo()` in service layer
3. Phase dates must be contained within project date range (NULL project dates skip the check)
4. `completion_percentage` constrained 0–100 via DB CHECK and validation rule
5. `budget_estimated` must be > 0 if provided (nullable, but positive when set)
6. Soft-deleted projects excluded from all non-Admin queries via `scopeForUser()`
7. CLOSED projects are immutable (checked in service before any update)
