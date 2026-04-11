---
name: terminal-safety
description: Safety rules for terminal operations
---

# Terminal Safety — Bunyan

## Forbidden Commands

- `rm -rf /` or any wildcard recursive delete without explicit user approval
- `DROP DATABASE`, `TRUNCATE TABLE` — never via terminal
- `git push --force` to `main` or `develop`
- `git reset --hard` without confirmation
- Raw SQL `DELETE` or `UPDATE` without `WHERE` clause

## Safe Command Patterns

### Backend

```bash
php artisan migrate              # Run pending migrations
php artisan migrate:status       # Check migration status (safe)
php artisan test                 # Run tests
composer run lint                # PHP CS Fixer
composer run analyze             # PHPStan
```

### Frontend

```bash
npm run dev                      # Start dev server
npm run build                    # Build for production
npm run lint                     # ESLint
npm run typecheck                # Type checking
npm run test                     # Vitest
```

### Database (Read-Only Safe)

```bash
php artisan tinker               # REPL — use caution
php artisan db:show              # Show database info
php artisan schema:dump          # Dump schema
```

## Confirmation Required

Before executing these, AI must ask for confirmation:

- `php artisan migrate:rollback`
- `php artisan db:wipe`
- `npm run build` (production)
- Any deployment script
- File deletions
