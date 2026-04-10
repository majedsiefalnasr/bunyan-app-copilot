# Bunyan — Copilot Commit Message Instructions

## Format

Use Conventional Commits format:

```
type(scope): description

[optional body]

[optional footer]
```

## Types

| Type     | When                                 |
| -------- | ------------------------------------ |
| feat     | New feature                          |
| fix      | Bug fix                              |
| refactor | Code change without feature/fix      |
| chore    | Build, tooling, config changes       |
| docs     | Documentation changes                |
| test     | Adding or updating tests             |
| style    | Formatting, linting (no code change) |
| perf     | Performance improvement              |
| ci       | CI/CD changes                        |

## Scope

Use the stage name for spec work:

- `feat(STAGE_07_CATEGORIES): add category tree API`
- `fix(STAGE_19_ORDERS): fix order total calculation`

Use module name for non-spec work:

- `chore(ci): add PHP 8.2 to CI matrix`
- `docs(api): update authentication guide`

## Rules

1. First line max 72 characters
2. Use imperative mood: "add" not "added"
3. Arabic descriptions allowed in body if needed
4. Reference issue numbers in footer: `Closes #123`
5. Breaking changes: `feat(scope)!: description` + `BREAKING CHANGE:` footer
