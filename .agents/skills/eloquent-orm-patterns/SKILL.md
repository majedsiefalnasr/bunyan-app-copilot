---
name: eloquent-orm-patterns
description: Eloquent ORM schema, query, and repository patterns
---

# Eloquent ORM Patterns — Bunyan

## Schema Definition Patterns

### Model with Relationships
```php
class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'customer_id', 'contractor_id',
        'supervising_architect_id', 'status', 'start_date', 'end_date',
        'budget', 'location',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'status' => ProjectStatus::class,
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(Phase::class);
    }

    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(Task::class, Phase::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProjectStatus::Active);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::Customer => $query->where('customer_id', $user->id),
            UserRole::Contractor => $query->where('contractor_id', $user->id),
            UserRole::SupervisingArchitect => $query->where('supervising_architect_id', $user->id),
            UserRole::FieldEngineer => $query->whereHas('tasks', fn ($q) => $q->where('assigned_to', $user->id)),
            UserRole::Admin => $query,
        };
    }
}
```

## Repository Pattern

```php
class ProjectRepository
{
    public function __construct(
        private readonly Project $model,
    ) {}

    public function findById(int $id): ?Project
    {
        return $this->model->find($id);
    }

    public function findByIdOrFail(int $id): Project
    {
        return $this->model->findOrFail($id);
    }

    public function listForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->model
            ->forUser($user)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Project
    {
        return $this->model->create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);
        return $project->fresh();
    }
}
```

## Query Optimization Rules

1. **Always eager load** relationships used in responses:
   ```php
   $project->load(['customer', 'contractor', 'phases.tasks']);
   ```

2. **Use `select()`** to limit columns:
   ```php
   Project::select(['id', 'name', 'status', 'customer_id'])->get();
   ```

3. **Avoid N+1** — use `withCount()` for counts:
   ```php
   Project::withCount('tasks')->get();
   ```

4. **Use chunking** for bulk operations:
   ```php
   Project::where('status', 'completed')->chunk(100, function ($projects) {
       // Process batch
   });
   ```

5. **Index foreign keys** — always add index on FK columns in migrations.

## Enum Pattern

```php
enum ProjectStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Active => 'نشط',
            self::OnHold => 'معلق',
            self::Completed => 'مكتمل',
            self::Cancelled => 'ملغى',
        };
    }
}
```
