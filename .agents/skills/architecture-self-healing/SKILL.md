---
name: architecture-self-healing
description: Detects and guides remediation of architectural drift
---

# Architecture Self-Healing — Bunyan

## Purpose

Automatically detect, report, and guide remediation of architectural drift.

## Detection Rules

### Layer Violations
- Controller imports Repository directly → Violation
- Controller imports Model and runs queries → Violation
- Service imports Request or Response objects → Violation
- Frontend code imports backend modules → Violation

### RBAC Violations
- Route without auth middleware → Violation (unless public)
- Controller action without Policy check → Warning
- Role check in blade/template without server validation → Violation

### Migration Violations
- Modified existing migration file → Critical violation
- Migration without `down()` method → Warning
- Raw SQL without justification comment → Warning

## Remediation Process

1. **Detect**: Static analysis identifies violation
2. **Report**: Generate violation report with file, line, rule
3. **Propose**: Suggest compliant alternative code
4. **Validate**: Run lint + analyze after fix

## Commands

```bash
# Backend analysis
composer run analyze          # PHPStan level 8
php artisan route:list        # Check route middleware

# Frontend analysis
npm run lint                  # ESLint
npm run typecheck             # Vue TSC
```

## AI Behavior

When a violation is detected:
1. STOP current generation
2. Diagnose the violation
3. Propose compliant fix
4. Regenerate code
5. Validate with analysis commands
