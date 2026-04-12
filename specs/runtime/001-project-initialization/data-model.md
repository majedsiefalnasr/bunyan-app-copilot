# Data Model — Project Initialization

> **Phase:** 01_PLATFORM_FOUNDATION  
> **Purpose:** Define database schema, Eloquent models, and type definitions  
> **Created:** 2026-04-10T00:00:00Z

---

## Overview

STAGE_01 establishes the foundational data model: the `users` table with role-based access control support. Future stages will extend this with projects, tasks, products, orders, and relationships.

---

## Database Schema

### 1.1 Users Table

**Location:** `backend/database/migrations/{timestamp}_create_users_table.php`

```sql
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,

  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,

  `role` ENUM(
    'customer',
    'contractor',
    'supervising_architect',
    'field_engineer',
    'admin'
  ) NOT NULL DEFAULT 'customer',

  `email_verified_at` TIMESTAMP NULL,
  `remember_token` VARCHAR(100) NULL,

  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columns Explained:**

| Column              | Type            | Constraints                  | Purpose                                     |
| ------------------- | --------------- | ---------------------------- | ------------------------------------------- |
| `id`                | BIGINT UNSIGNED | PK, auto-increment           | Unique user identifier                      |
| `name`              | VARCHAR(255)    | NOT NULL                     | User full name (supports Arabic characters) |
| `email`             | VARCHAR(255)    | UNIQUE, NOT NULL             | Unique email for login                      |
| `password`          | VARCHAR(255)    | NOT NULL                     | Bcrypt hash (never plaintext)               |
| `role`              | ENUM            | NOT NULL, DEFAULT='customer' | RBAC role classification                    |
| `email_verified_at` | TIMESTAMP       | NULL                         | Email verification timestamp (future)       |
| `remember_token`    | VARCHAR(100)    | NULL                         | Session token (future)                      |
| `created_at`        | TIMESTAMP       | NOT NULL                     | Record creation time (UTC)                  |
| `updated_at`        | TIMESTAMP       | NOT NULL                     | Record last modification time (UTC)         |

**Indexes:**

- `idx_email`: Optimize login queries (`WHERE email = ?`)
- `idx_role`: Optimize role-based filtering (`WHERE role = 'admin'`)
- `idx_created_at`: Optimize date-range queries

### 1.2 Personal Access Tokens Table

**Location:** Created by Sanctum (`vendor:publish`)

```sql
CREATE TABLE `personal_access_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,

  `tokenable_type` VARCHAR(255) NOT NULL,
  `tokenable_id` BIGINT UNSIGNED NOT NULL,

  `name` VARCHAR(255) NOT NULL,
  `token` VARCHAR(80) NOT NULL UNIQUE,
  `abilities` LONGTEXT NULL,

  `last_used_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,

  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_tokenable` (`tokenable_type`, `tokenable_id`),
  INDEX `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Purpose:**

- Stores API access tokens for Sanctum authentication
- `token` column: The bearer token (sent by client in `Authorization: Bearer` header)
- `abilities`: JSON array of token-specific permissions (future)
- `expires_at`: Token expiry (future)

---

## Eloquent Models

### 2.1 User Model

**Location:** `backend/app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'users';
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // ============ Accessors ============

    public function getInitialsAttribute(): string
    {
        $names = explode(' ', $this->name);
        return strtoupper(
            $names[0][0] . (isset($names[1]) ? $names[1][0] : '')
        );
    }

    // ============ Scopes ============

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    // ============ Methods ============

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isContractor(): bool
    {
        return $this->role === 'contractor';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isArchitect(): bool
    {
        return $this->role === 'supervising_architect';
    }

    public function isFieldEngineer(): bool
    {
        return $this->role === 'field_engineer';
    }
}
```

### 2.2 Model Relationships (Future)

```php
// In STAGE_12 (Projects), the User model will extend with:

public function projects()
{
    return $this->hasMany(Project::class);
}

public function managedTeams()
{
    return $this->hasMany(Team::class, 'manager_id');
}

public function assignments()
{
    return $this->hasManyThrough(Assignment::class, Team::class);
}
```

---

## Type Definitions (TypeScript)

### 3.1 Frontend Types

**Location:** `frontend/types/user.ts`

```typescript
export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

export type UserRole =
  | "customer"
  | "contractor"
  | "supervising_architect"
  | "field_engineer"
  | "admin";

export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface AuthResponse {
  success: boolean;
  data: {
    user: User;
    token: string;
  };
  message: string;
  errors: Record<string, string[]>;
}

export interface ApiResponse<T> {
  success: boolean;
  data: T | null;
  message: string;
  errors: Record<string, string[]>;
}

export const USER_ROLES: Record<UserRole, { ar: string; en: string }> = {
  customer: { ar: "العميل", en: "Customer" },
  contractor: { ar: "المقاول", en: "Contractor" },
  supervising_architect: { ar: "المهندس المشرف", en: "Supervising Architect" },
  field_engineer: { ar: "المهندس الميداني", en: "Field Engineer" },
  admin: { ar: "الإدارة", en: "Admin" },
};
```

### 3.2 Backend Form Requests

**Location:** `backend/app/Http/Requests/`

```php
// app/Http/Requests/RegisterRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Register is public
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email:rfc,dns',
                Rule::unique('users', 'email'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',  // password_confirmation must match
                'regex:/[A-Z]/',  // At least one uppercase
                'regex:/[0-9]/',  // At least one digit
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must contain uppercase and digits.',
        ];
    }
}

// app/Http/Requests/LoginRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Login is public
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
```

---

## API Resources (Response Formatting)

### 4.1 User Resource

**Location:** `backend/app/Http/Resources/UserResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'initials' => $this->initials,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### 4.2 Base Response Wrapper

**Location:** `backend/app/Http/Resources/BaseResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    protected static function response(
        $data,
        bool $success = true,
        string $message = '',
        int $statusCode = 200
    ) {
        return response()->json([
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'errors' => [],
        ], $statusCode);
    }
}
```

---

## Enums

### 5.1 UserRole Enum

**Location:** `backend/app/Enums/UserRole.php`

```php
<?php

namespace App\Enums;

enum UserRole: string
{
    case CUSTOMER = 'customer';
    case CONTRACTOR = 'contractor';
    case SUPERVISING_ARCHITECT = 'supervising_architect';
    case FIELD_ENGINEER = 'field_engineer';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match($this) {
            self::CUSTOMER => 'Customer',
            self::CONTRACTOR => 'Contractor',
            self::SUPERVISING_ARCHITECT => 'Supervising Architect',
            self::FIELD_ENGINEER => 'Field Engineer',
            self::ADMIN => 'Admin',
        };
    }

    public static function all(): array
    {
        return array_map(fn(self $role) => $role->value, self::cases());
    }

    public static function labels(): array
    {
        return array_reduce(
            self::cases(),
            fn($acc, $case) => [...$acc, $case->value => $case->label()],
            []
        );
    }
}
```

---

## Migration File

### 6.1 Complete Migration

**Location:** `backend/database/migrations/{timestamp}_create_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->enum('role', [
                'customer',
                'contractor',
                'supervising_architect',
                'field_engineer',
                'admin',
            ])->default('customer');

            $table->rememberToken();
            $table->timestamps();

            $table->index('email');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

---

## Database Relationships & Future Extensions

### 7.1 Entity Relationship Diagram (Future Stages)

```
┌─────────────────┐
│     users       │  <- STAGE_01 ✓
│─────────────────│
│ id (PK)         │
│ name            │
│ email (UNIQUE)  │
│ password        │
│ role (ENUM)     │
│ created_at      │
│ updated_at      │
└────────┬────────┘
         │ hasMany
         ▼
┌─────────────────────┐
│     projects        │  <- STAGE_12
│─────────────────────│
│ id (PK)             │
│ user_id (FK)        │
│ name                │
│ status (ENUM)       │
└──────────┬──────────┘
           │ hasMany
           ▼
┌──────────────────┐
│      tasks       │  <- STAGE_13
│──────────────────│
│ id (PK)          │
│ project_id (FK)  │
│ name             │
│ status (ENUM)    │
└──────────────────┘
```

### 7.2 Query Examples (Future)

```php
// Find user's projects (once Project model exists)
$user->projects()->where('status', 'active')->get();

// Find all tasks in a user's projects
$user->projects()
    ->with('tasks')
    ->get()
    ->flatMap->tasks;

// Eager load relationships
User::with(['projects', 'projects.tasks'])->find($id);
```

---

## Testing & Seeding

### 8.1 User Factory

**Location:** `backend/database/factories/UserFactory.php`

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => $this->faker->randomElement([
                'customer',
                'contractor',
                'supervising_architect',
                'field_engineer',
            ]),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function contractor(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'contractor',
        ]);
    }
}
```

### 8.2 Seeder

**Location:** `backend/database/seeders/DatabaseSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        // Generate 50 random users
        User::factory(50)->create();
    }
}
```

---

## Verification Queries

### 9.1 MySQL CLI Verification

```sql
-- Check table structure
DESCRIBE users;

-- Count records
SELECT COUNT(*) FROM users;

-- List all roles
SELECT DISTINCT role FROM users;

-- Find users by role
SELECT id, email, role FROM users WHERE role = 'admin';

-- Check email uniqueness
SELECT email, COUNT(*) FROM users GROUP BY email HAVING COUNT(*) > 1;
```

### 9.2 Laravel Tinker Verification

```bash
php artisan tinker

# Check if table exists
>>> DB::table('users')->count()
=> 3

# Create a user
>>> use App\Models\User; $user = User::create(['name' => 'Test', 'email' => 'test@example.com', 'password' => bcrypt('password'), 'role' => 'customer']);
=> User object

# Verify methods work
>>> $user->isCustomer()
=> true

>>> $user->hasRole('customer')
=> true

# Generate token
>>> $token = $user->createToken('test-token')->plainTextToken
=> "1|xxxxx..."
```

---

## Summary

**STAGE_01 Data Model:**

- ✅ `users` table with 5 roles (enum)
- ✅ `personal_access_tokens` table (Sanctum)
- ✅ Eloquent User model with scopes and methods
- ✅ TypeScript types for frontend
- ✅ Form Request validation classes
- ✅ API Resource formatters
- ✅ UserRole enum with labels
- ✅ Migration file (forward + rollback)
- ✅ Factory for testing
- ✅ Seeder for test data

**Ready for:** STAGE_02 (extended schema), STAGE_03 (authentication logic)
