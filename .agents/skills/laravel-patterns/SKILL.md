---
name: laravel-patterns
description: Laravel service pattern, repository pattern, conventions
---

# Laravel Patterns — Bunyan

## Directory Structure

```
backend/
├── app/
│   ├── Enums/              # PHP enums (UserRole, ProjectStatus, etc.)
│   ├── Http/
│   │   ├── Controllers/    # Thin controllers — delegate to services
│   │   ├── Middleware/      # Auth, RBAC, rate limiting
│   │   ├── Requests/       # Form request validation
│   │   └── Resources/      # API resource transformers
│   ├── Models/             # Eloquent models
│   ├── Repositories/       # Database query layer
│   ├── Services/           # Business logic layer
│   ├── Events/             # Domain events
│   ├── Listeners/          # Event handlers
│   ├── Jobs/               # Queue jobs (idempotent)
│   ├── Notifications/      # User notifications
│   └── Policies/           # Authorization policies
├── database/
│   ├── migrations/         # Forward-only migrations
│   ├── seeders/            # Development data
│   └── factories/          # Test factories
├── routes/
│   ├── api.php             # API routes (versioned)
│   └── web.php             # Web routes (if any)
├── config/                 # Configuration files
├── tests/
│   ├── Unit/               # Unit tests
│   └── Feature/            # Integration/feature tests
└── resources/
    └── lang/               # Translation files (ar, en)
```

## Service Pattern

```php
class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly PhaseRepository $phaseRepository,
    ) {}

    public function createProject(User $user, CreateProjectData $data): Project
    {
        // Business logic validation
        if ($user->role !== UserRole::Customer) {
            throw new UnauthorizedException('Only customers can create projects');
        }

        return DB::transaction(function () use ($user, $data) {
            $project = $this->projectRepository->create([
                'name' => $data->name,
                'description' => $data->description,
                'customer_id' => $user->id,
                'status' => ProjectStatus::Draft,
                'budget' => $data->budget,
                'location' => $data->location,
            ]);

            event(new ProjectCreated($project));

            return $project;
        });
    }
}
```

## Controller Pattern

```php
class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    public function store(CreateProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->createProject(
            $request->user(),
            CreateProjectData::from($request->validated()),
        );

        return response()->json([
            'success' => true,
            'data' => new ProjectResource($project),
            'error' => null,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $projects = $this->projectService->listProjects(
            $request->user(),
            $request->only(['status', 'search', 'per_page']),
        );

        return response()->json([
            'success' => true,
            'data' => ProjectResource::collection($projects),
            'error' => null,
        ]);
    }
}
```

## Form Request Pattern

```php
class CreateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === UserRole::Customer;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'budget' => ['required', 'numeric', 'min:0'],
            'location' => ['required', 'string', 'max:500'],
            'start_date' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المشروع مطلوب',
            'budget.required' => 'الميزانية مطلوبة',
            'budget.min' => 'الميزانية يجب أن تكون أكبر من صفر',
            'location.required' => 'الموقع مطلوب',
        ];
    }
}
```

## API Resource Pattern

```php
class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'budget' => $this->budget,
            'location' => $this->location,
            'start_date' => $this->start_date?->toISOString(),
            'end_date' => $this->end_date?->toISOString(),
            'customer' => new UserResource($this->whenLoaded('customer')),
            'contractor' => new UserResource($this->whenLoaded('contractor')),
            'phases_count' => $this->whenCounted('phases'),
            'tasks_count' => $this->whenCounted('tasks'),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

## Routing Pattern (Versioned)

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/register', [AuthController::class, 'register']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Projects (role-based access via policies)
        Route::apiResource('projects', ProjectController::class);
        Route::apiResource('projects.phases', PhaseController::class);
        Route::apiResource('projects.phases.tasks', TaskController::class);

        // E-Commerce
        Route::apiResource('products', ProductController::class);
        Route::apiResource('orders', OrderController::class);
        Route::post('orders/{order}/checkout', [OrderController::class, 'checkout']);
    });
});
```
