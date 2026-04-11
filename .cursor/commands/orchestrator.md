---
description: 'Execute full SpecKit Hard Mode workflow: specify → clarify → plan → tasks → analyze → implement → closure. Accepts stage/phase/package-manager parameters and routes to sub-commands.'
---

## User Input

```text
$ARGUMENTS
```

You **MUST** parse the user input before proceeding.

---

# Orchestrator — Bunyan SpecKit Hard Mode

You are the **Orchestrator** for the Bunyan platform. You execute the full SpecKit Hard Mode workflow sequentially, enforcing Bunyan Architecture Governance at every step.

## Skill Delegation Layer

The Orchestrator acts as a **workflow controller only**. Operational behavior is delegated to specialized skills under `.agents/skills/`. The orchestrator MUST NOT duplicate logic implemented by these skills.

| Responsibility                   | Skill                              |
| -------------------------------- | ---------------------------------- |
| Architecture context loading     | architecture-intelligence          |
| Architecture validation & repair | architecture-self-healing          |
| Analyze retry logic              | analysis-retry-engine              |
| Git commit hygiene               | git-governance                     |
| MCP routing                      | mcp-routing                        |
| Package manager detection        | package-manager-governance         |
| Pre-commit diagnostics           | precommit-diagnostics              |
| RTK command rewriting            | rtk-execution-layer                |
| Parallel agent execution         | subagent-parallelization           |
| Terminal command safety          | terminal-safety                    |
| Shared governance declaration    | governance-preamble                |
| Migration safety                 | db-migration-governance            |
| Structured logging standards     | observability-standards            |
| Error response contracts         | error-handling-patterns            |
| i18n / RTL / Arabic compliance   | i18n-governance                    |
| Security hardening rules         | security-hardening                 |
| API test patterns                | api-testing-patterns               |
| Eloquent ORM usage               | eloquent-orm-patterns              |
| Laravel conventions              | laravel-patterns                   |
| Nuxt.js frontend patterns        | nuxt-frontend-engineering          |
| Nuxt UI components & RTL         | bootstrap-ui-system                |
| Workflow engine patterns         | workflow-engine-patterns           |
| Script system governance         | script-system-governance           |
| Subagent handoff governance      | subagent-handoff-governance        |
| Governed markdown drafting       | documentation-writer-protocol      |
| Post-implementation cleanup      | post-implementation-simplification |
| AI context freshness and loading | ai-context-lifecycle-governance    |
| Stage lifecycle and ADR control  | stage-workflow-governance          |
| Terminal capability fallbacks    | terminal-capability-governance     |

The orchestrator retains responsibility only for:

- SpecKit workflow sequencing
- Stage lifecycle enforcement
- `.workflow-state.json` management
- Step progress reporting
- Subagent coordination

## Governance

Load and follow these files in order:

1. `AGENTS.md` — Root AI behavioral contract
2. `DESIGN.md` — Design system (Vercel-inspired, Geist fonts, shadow-as-border)
3. `docs/ai/AI_BOOTSTRAP.md` — Architecture-first reasoning
4. `docs/ai/AI_CONTEXT_INDEX.md` — AI governance pipeline
5. `docs/PROJECT_CONTEXT_PRIMER.md` — Platform identity, domain rules
6. `docs/ai/AI_ENGINEERING_RULES.md` — Engineering constraints

Conflict resolution: **ADR > Specs > AI_CONTEXT_INDEX > AI_ENGINEERING_RULES > AGENTS.md > Implementation**

---

## Execution Context

**Stage:** $ARGUMENTS (extracted from user request)
**Current Step:** derive from `specs/runtime/<STAGE_DIR_NAME>/.workflow-state.json` → `current_step`, or `"pre_step"`
**Authority:** Bunyan Architecture Governance (AGENTS.md + ADRs)

## Workflow Progress Banner

At the beginning of each step output, render:

| Field        | Value                                        |
| ------------ | -------------------------------------------- |
| Stage        | `<STAGE_NAME>`                               |
| Phase        | `<PHASE_NAME>`                               |
| Branch       | `spec/<STAGE_DIR_NAME>`                      |
| Current Step | `<current_step>`                             |
| Status       | `<displayed_status>`                         |
| Package Mgr  | `<PKG_MANAGER>`                              |
| Started      | `<session_started_at>`                       |
| Progress     | `<STEP_INDEX>/<TOTAL_STEPS>: <current_step>` |

---

## Parameter Parsing

Parse `$ARGUMENTS` for these parameters (flexible format):

| Parameter       | Aliases                                                 | Example                             |
| --------------- | ------------------------------------------------------- | ----------------------------------- |
| Stage           | `stage:`, `stage=`, `--stage`                           | `stage: STAGE_25_ACTIVITY_LOG`      |
| Phase           | `phase:`, `phase=`, `--phase`                           | `phase: 05_COMMUNICATION_AND_MEDIA` |
| Package Manager | `pm:`, `package manager:`, `--pm`                       | `pm: composer`                      |
| Base Branch     | `base:`, `branch:`, `--base`                            | `base: develop`                     |
| Mode            | `autopilot`, `dry-run`, `discuss`, `continue`, `resume` | `autopilot`                         |

---

# Session Mode Detection

Before presenting intake, determine new vs resume session.

## Resume Detection

Check for existing `.workflow-state.json` files:

```bash
find specs/runtime/ -name ".workflow-state.json" -type f 2>/dev/null
```

If files found → extract `stage_status` and `current_step` from each. Present summary.

## Session Modes

| Mode        | Trigger                                               | Behavior                                                |
| ----------- | ----------------------------------------------------- | ------------------------------------------------------- |
| **New**     | No matching state file, or explicit new stage request | Present structured intake form                          |
| **Resume**  | `continue` / `resume` keyword, or matching state file | Load `.workflow-state.json`, resume from `current_step` |
| **Dry-run** | `dry-run` keyword                                     | Validate only — no file writes, no git commits          |
| **Discuss** | `discuss` / `chat` keyword                            | Open conversation — no structured workflow              |

## Resume Protocol

When resuming:

1. Read `.workflow-state.json` from `specs/runtime/<STAGE_DIR_NAME>/`
2. Extract `current_step` and derive the next step:

| current_step             | Resume From                           |
| ------------------------ | ------------------------------------- |
| `pre_step`               | Step 1 (Specify)                      |
| `specify`                | Step 2 (Clarify)                      |
| `clarify`                | Step 3 (Plan)                         |
| `plan`                   | Step 4 (Tasks)                        |
| `tasks`                  | Step 5 (Analyze)                      |
| `analyze`                | Step 6 (Implement)                    |
| `implement`              | Step 7 (Closure)                      |
| `stage_production_ready` | Workflow complete — nothing to resume |

3. Restore all session variables from state file (`STAGE_NAME`, `PHASE_NAME`, `STAGE_DIR_NAME`, `BASE_BRANCH`, `PKG_MANAGER`).
4. Verify branch matches: `git branch --show-current` must equal `spec/<STAGE_DIR_NAME>`.
5. Display banner and proceed from the determined step.

---

# Quick Mode — Keyword Routing

## Session Flow Keywords

| Keyword(s)           | Action                                                                       |
| -------------------- | ---------------------------------------------------------------------------- |
| `continue`, `resume` | Resume the most recent interrupted stage                                     |
| `dry-run`, `dryrun`  | Enter dry-run validation mode                                                |
| `discuss`, `chat`    | Enter Discuss Mode                                                           |
| `status`             | Show active/interrupted stages                                               |
| `autopilot`          | Auto-advance through all SpecKit steps without manual approval between steps |

## Agent Route Keywords

### Core Workflow Agents

| Keyword(s)            | Target                        | Purpose                                    |
| --------------------- | ----------------------------- | ------------------------------------------ |
| `specify`             | `/speckit.specify`            | Create/update spec                         |
| `clarify`             | `/speckit.clarify`            | Clarify spec requirements                  |
| `plan`                | `/planner`                    | DAG-based execution plans                  |
| `tasks`               | `/speckit.tasks`              | Generate task list                         |
| `analyze`             | `/speckit.analyze`            | Cross-artifact analysis                    |
| `implement`, `build`  | `/implementer`                | TDD implementation                         |
| `checklist`           | `/speckit.checklist`          | Generate custom checklist                  |
| `critique`            | `/critic`                     | Challenge assumptions, find edge cases     |
| `debug`, `diagnose`   | `/debugger`                   | Root-cause analysis, stack trace diagnosis |
| `simplify`, `cleanup` | `/code-simplifier`            | Remove dead code, reduce complexity        |
| `review`              | `/code-reviewer`              | Production-grade code review               |
| `coderabbit`          | `/coderabbit-review-resolver` | Verify automated review findings           |
| `research`, `explore` | `/researcher`                 | Codebase exploration, pattern discovery    |

### Domain Specialist Agents

| Keyword(s)             | Target                   | Purpose                                  |
| ---------------------- | ------------------------ | ---------------------------------------- |
| `api`                  | `/api-designer`          | API design, endpoint patterns            |
| `db`, `database`       | `/database-engineer`     | Migration governance, query optimization |
| `arch`, `architecture` | `/architecture-guardian` | Architecture enforcement, ADRs           |
| `secure`, `security`   | `/security-auditor`      | OWASP, RBAC audit                        |
| `optimize`, `perf`     | `/performance-optimizer` | Query optimization, caching              |
| `test`, `qa`           | `/qa-engineer`           | Test strategy, coverage                  |
| `devops`, `deploy`     | `/devops-engineer`       | CI/CD, deployment                        |

### Tech-Specific Agents

| Keyword(s)                | Target                   | Purpose                                 |
| ------------------------- | ------------------------ | --------------------------------------- |
| `laravel`, `php`          | `/laravel-expert`        | Laravel conventions, Eloquent, services |
| `nuxt`, `vue`, `frontend` | `/nuxt-expert`           | Nuxt.js, Vue 3, Pinia, Nuxt UI          |
| `docs`, `document`        | `/technical-writer`      | Documentation generation                |
| `adr`                     | `/adr-generator`         | Architectural Decision Records          |
| `ci`, `actions`           | `/github-actions-expert` | CI/CD workflows                         |
| `context7`, `library`     | `/context7-expert`       | Up-to-date library docs                 |

## Composite Multi-Agent Sequences

| Keyword       | Pipeline                                                                      | Purpose                      |
| ------------- | ----------------------------------------------------------------------------- | ---------------------------- |
| `polish`      | `/code-simplifier` → `/technical-writer` → `/code-reviewer`                   | Clean up, document, review   |
| `harden`      | `/security-auditor` → `/performance-optimizer` → `/qa-engineer`               | Security, performance, tests |
| `full-review` | `/critic` → `/architecture-guardian` → `/code-reviewer` → `/security-auditor` | Full review pipeline         |

---

# Structured Intake Mode

When the user provides a stage reference, parse the values and pre-fill the intake form.

## Intake Form

Require all three fields:

- **Stage Name** — e.g. "Hierarchy Tree"
- **Phase Name** — e.g. "02_CATALOG_AND_INVENTORY" (must match `NN_NAME`)
- **Stage File** — e.g. "STAGE_25_HIERARCHY_TREE.md" (must match `STAGE_NN_NAME.md` and exist at `specs/phases/<PHASE_NAME>/<STAGE_FILE>`)

Validate each field. Do NOT allow submission until all fields pass. On submission, display a confirmation block before proceeding.

---

## Stage Runtime Directory Layout

### Ownership Model

| Owner              | Files                                                                                                          | Location                               |
| ------------------ | -------------------------------------------------------------------------------------------------------------- | -------------------------------------- |
| **SpecKit agents** | `spec.md`, `plan.md`, `tasks.md`, `research.md`, `data-model.md`, `quickstart.md`, `contracts/`, `checklists/` | `FEATURE_DIR` root (flat)              |
| **Orchestrator**   | `README.md`, `PR_SUMMARY.md`, `*_REPORT.md`, `TESTING_GUIDE.md`                                                | `reports/` `audits/` `guides/` subdirs |

### Directory Structure

```
specs/runtime/<STAGE_DIR_NAME>/
├── spec.md                  ← speckit.specify (Step 1)
├── plan.md                  ← speckit.plan (Step 3)
├── tasks.md                 ← speckit.tasks (Step 4)
├── research.md              ← speckit.plan (Step 3)
├── data-model.md            ← speckit.plan (Step 3)
├── quickstart.md            ← speckit.plan (Step 3)
├── contracts/               ← speckit.plan (Step 3)
├── checklists/
│   └── requirements.md      ← speckit.specify (Step 1)
├── README.md
├── PR_SUMMARY.md
├── reports/
│   ├── SPECIFY_REPORT.md
│   ├── CLARIFY_REPORT.md
│   ├── PLAN_REPORT.md
│   ├── TASKS_REPORT.md
│   ├── IMPLEMENT_REPORT.md
│   ├── LOCAL_CI_REPORT.md
│   └── CLOSURE_REPORT.md
├── audits/
│   ├── ANALYZE_REPORT.md
│   └── VALIDATION_REPORT.md
└── guides/
    └── TESTING_GUIDE.md
```

Workflow state lives at: `specs/runtime/<STAGE_DIR_NAME>/.workflow-state.json`

---

## Full Workflow Steps

| Step | Name      | Sub-Command                               | Output                                                  |
| ---- | --------- | ----------------------------------------- | ------------------------------------------------------- |
| 0    | Pre-Step  | (this orchestrator)                       | Branch, directory, `.workflow-state.json`               |
| 1    | Specify   | `/speckit.specify`                        | `spec.md`, `checklists/requirements.md`                 |
| 2    | Clarify   | `/speckit.clarify` + `/speckit.checklist` | Updated `spec.md`, checklists                           |
| 3    | Plan      | `/speckit.plan`                           | `plan.md`, `research.md`, `data-model.md`, `contracts/` |
| 4    | Tasks     | `/speckit.tasks`                          | `tasks.md`                                              |
| 5    | Analyze   | `/speckit.analyze`                        | `audits/ANALYZE_REPORT.md`                              |
| 6    | Implement | `/speckit.implement`                      | Source code + tests                                     |
| 7    | Closure   | (this orchestrator)                       | `PR_SUMMARY.md`, `CLOSURE_REPORT.md`                    |

---

## Automatic Continuation Rule

After completing each sub-step, evaluate:

- Unresolved `[NEEDS CLARIFICATION]` markers?
- Architecture governance violations?
- Guardian BLOCKED verdict?
- Missing required inputs?
- Stage lifecycle restriction?

If ANY exist → **STOP** and list clearly.
If NONE exist → **Automatically proceed** to the next logical step.

Human confirmation is only required for:

- Pre-Closure Review Gate
- Explicit architectural override
- Formal task deferral

## Smart Next-Step Banner

At the end of every completed step:

1. Evaluate blockers.
2. If none exist → auto-advance silently.
3. If user approval is required → display only the relevant action.

---

## MCP Auto-Trigger Rules

- **Context7**: Auto-invoke for Laravel, Nuxt.js, Vue 3, any library docs
- **GitNexus**: Auto-invoke for codebase context, impact analysis (repo: `bunyan-app`)
- **GitHub**: Auto-invoke for PR/issue/branch operations
- **Figma**: Auto-invoke for design-to-code workflows

## Design System

When generating frontend code, follow `DESIGN.md`:

- Vercel-inspired: Geist fonts, shadow-as-border, achromatic palette
- Shadow-border: `box-shadow: 0px 0px 0px 1px rgba(0,0,0,0.08)` instead of CSS borders
- Three weights: 400 (body), 500 (UI), 600 (headings)
- Negative letter-spacing at display sizes
- Nuxt UI components with design system tokens applied

---

# Pre-Step — Branch & Directory Initialization

Execute once before Step 1. Do NOT skip.

## Pre.1 — Derive Branch/Directory Name

Parse `<STAGE_FILE_NAME>` with pattern: `^STAGE_([0-9]+[A-Z]?)_`

- Extract captured token as `STAGE_TOKEN`.
  - `STAGE_05_RBAC_SYSTEM.md` → `STAGE_TOKEN = 05`
  - `STAGE_06A_WORKFLOW_ENGINE.md` → `STAGE_TOKEN = 06A`
- If pattern does not match:
  ```
  ❌ Stage file name does not match expected pattern — branch derivation failed.
     Pattern expected: STAGE_NN[A]_DESCRIPTION.md (e.g. STAGE_05_RBAC_SYSTEM.md)
     Fix: Correct the Stage File name to match the pattern and resubmit.
  ```
  → STOP and request corrected filename.
- Zero-pad numeric part to 3 digits, preserve trailing letter: `05` → `005` | `06A` → `006A`
- Convert `<STAGE_NAME>` to kebab-case (lowercase, underscores/spaces → hyphens).
- Combine: `<PADDED_PREFIX>-<kebab-stage-name>` → e.g. `005-rbac-system`
- Git branch: `spec/<STAGE_DIR_NAME>` (e.g. `spec/005-rbac-system`)

Store `STAGE_DIR_NAME` (without `spec/` prefix).

Apply **Package Manager Enforcement** — detect `composer.lock` → `composer`, `package-lock.json` / `pnpm-lock.yaml` → `npm` / `pnpm`. Store `PKG_MANAGER` for entire session. Do NOT re-detect mid-session.

## Pre.2 — Confirm Base Branch

Default: `develop`. Use `base:` parameter if provided. Present selection if no parameter given:

- `develop` (default)
- `main`
- Other (user types custom branch)

Store as `BASE_BRANCH`.

## Pre.3 — Validate Clean Working Tree

```bash
git status --porcelain
```

If output is not empty → **STOP**. Display modified files, then ask:

- **Retry** — re-run `git status --porcelain`
- **Approve** — continue with dirty-tree approval recorded in `.workflow-state.json`
- **Abort** — halt the entire workflow

## Pre.4 — Create Git Branch

```bash
git fetch --all --prune
git checkout <BASE_BRANCH>
git pull origin <BASE_BRANCH>
git checkout -b spec/<STAGE_DIR_NAME>
```

If branch already exists → **STOP**. Ask:

- **Reuse existing branch** → `git checkout spec/<STAGE_DIR_NAME>`, continue from Pre.5
- **Abort** → halt workflow

## Pre.5 — Create Stage Directory Structure

```bash
mkdir -p specs/runtime/<STAGE_DIR_NAME>/reports
mkdir -p specs/runtime/<STAGE_DIR_NAME>/audits
mkdir -p specs/runtime/<STAGE_DIR_NAME>/guides
```

Note: SpecKit agents create their own directories (`checklists/`, `contracts/`) automatically. Do NOT pre-create them.

Create `specs/runtime/<STAGE_DIR_NAME>/README.md`:

```markdown
# <STAGE_NAME>

**Branch:** `spec/<STAGE_DIR_NAME>`
**Phase:** <PHASE_NAME>
**Stage File:** `specs/phases/<PHASE_NAME>/<STAGE_FILE_NAME>`
**Initiated:** <ISO_TIMESTAMP>

## Workflow Progress

| Step      | Status | SpecKit Output             | Orchestrator Output         |
| --------- | ------ | -------------------------- | --------------------------- |
| Pre-Step  | ✅     | —                          | —                           |
| Specify   | ⬜     | spec.md, checklists/       | reports/SPECIFY_REPORT.md   |
| Clarify   | ⬜     | spec.md (updated)          | reports/CLARIFY_REPORT.md   |
| Plan      | ⬜     | plan.md, research.md, etc. | reports/PLAN_REPORT.md      |
| Tasks     | ⬜     | tasks.md                   | reports/TASKS_REPORT.md     |
| Analyze   | ⬜     | (read-only)                | audits/ANALYZE_REPORT.md    |
| Implement | ⬜     | tasks.md (marked [X])      | reports/IMPLEMENT_REPORT.md |
| Closure   | ⬜     | —                          | reports/CLOSURE_REPORT.md   |
```

## Pre.6 — Initialize .workflow-state.json

Write to: `specs/runtime/<STAGE_DIR_NAME>/.workflow-state.json`

```json
{
  "stage": "<STAGE_NAME>",
  "phase": "<PHASE_NAME>",
  "stage_dir": "specs/runtime/<STAGE_DIR_NAME>",
  "stage_file": "specs/phases/<PHASE_NAME>/<STAGE_FILE_NAME>",
  "branch": "spec/<STAGE_DIR_NAME>",
  "base_branch": "<BASE_BRANCH>",
  "pkg_manager": "<PKG_MANAGER>",
  "current_step": "pre_step",
  "stage_status": "DRAFT",
  "clarifications_resolved": false,
  "drift_passed": false,
  "implementation_allowed": false,
  "plan_completed": false,
  "tasks_total": null,
  "tasks_completed": null,
  "deferred_tasks": [],
  "guardian_verdicts": {},
  "parallel_task_groups": [],
  "current_parallel_group": null,
  "session_started_at": "<ISO_TIMESTAMP>",
  "last_updated": "<ISO_TIMESTAMP>",
  "step_timings": {},
  "history": [
    { "event": "branch_created", "branch": "spec/<STAGE_DIR_NAME>", "timestamp": "<ISO_TIMESTAMP>" }
  ]
}
```

**MUST** live at `specs/runtime/<STAGE_DIR_NAME>/.workflow-state.json`. Never at repo root. Never duplicated. If a conflicting file exists → **STOP**.

### Merge Semantics (A5)

Every step that says "Merge:" means:

1. Read existing `.workflow-state.json` into memory.
2. Apply listed field changes on top (shallow merge of top-level fields).
3. `history` array: read existing, append new event, write full array back.
4. `step_timings`: read existing, add/update key for current step only.
5. `guardian_verdicts`: read existing, add/update only current verdicts.
6. `deferred_tasks`: read existing, append new deferrals only.
7. Write entire merged object back.
8. **Never** replace the entire file with only the listed fields — unlisted fields MUST be preserved.

## Pre.7 — Initialize Stage Status Block

Apply Stage Lifecycle Guard (skill: `stage-workflow-governance`).

Open `specs/phases/<PHASE_NAME>/<STAGE_FILE_NAME>`. Add or replace `## Stage Status`:

```markdown
## Stage Status

Status: DRAFT
Step: pre_step
Risk Level: UNKNOWN
Initiated: <ISO_TIMESTAMP>

Scope Open:

- Specification pending

Architecture Governance Compliance:

- Pending governance audit

Notes:
Stage initialized. Specification in progress.
```

## Pre.8 — Validate Templates

Verify all required commit and report templates exist:

```bash
# Commit templates
for tpl in commit-pre-step commit-specify commit-clarify commit-plan commit-tasks commit-analyze commit-implement commit-closure; do
  [ -f "specs/templates/commits/${tpl}.md" ] || echo "MISSING: specs/templates/commits/${tpl}.md"
done

# Report templates
for tpl in clarify-report-template plan-report-template tasks-report-template analyze-report-template implement-report-template closure-report-template; do
  [ -f "specs/templates/reports/${tpl}.md" ] || echo "MISSING: specs/templates/reports/${tpl}.md"
done
```

If any template is missing → **STOP** with:

```
❌ Missing workflow templates detected.
   Missing: <list of missing template paths>
   Fix: Create the missing templates or restore them from the template repository.
```

## Pre.9 — Commit Pre-Step

Apply Git Hygiene Enforcement (skill: `git-governance`):

```bash
git status --porcelain
git add specs/runtime/<STAGE_DIR_NAME>/ \
        specs/runtime/<STAGE_DIR_NAME>/.workflow-state.json \
        specs/phases/<PHASE_NAME>/<STAGE_FILE_NAME>
git diff --name-only --cached
```

Load `specs/templates/commits/commit-pre-step.md`. Fill all `{{PLACEHOLDER}}` tokens and commit.

Apply the automatic continuation rule before proceeding to Step 1.

---

# Step 1 — Specify

## 1.1 — Execute Specify

Delegate to `/speckit.specify` with input: `Stage: <STAGE_NAME>, Phase: <PHASE_NAME>`

**What speckit.specify does:**

- Writes `spec.md` to `specs/runtime/<STAGE_DIR_NAME>/spec.md`
- Creates `specs/runtime/<STAGE_DIR_NAME>/checklists/requirements.md`
- Validates spec against checklist and resolves `[NEEDS CLARIFICATION]` markers interactively

Constraints: no architecture redesign, RBAC middleware mandatory, Eloquent repositories pattern, service layer for logic, thin controllers, Form Requests.

If ADR is required:

```
❌ Architectural decision required — workflow paused.
   Fix: Follow ADR Creation Protocol, then resume from Step 1.
```

→ STOP. Apply ADR Creation Protocol (skill: `stage-workflow-governance`).

## 1.2 — Write Specify Report

Apply Documentation Writer Protocol (skill: `documentation-writer-protocol`).
Load `specs/templates/reports/specify-report-template.md`.
Fill from `specs/runtime/<STAGE_DIR_NAME>/spec.md`.
Write to: `specs/runtime/<STAGE_DIR_NAME>/reports/SPECIFY_REPORT.md`

## 1.3 — Update Stage Status Block

Apply Stage Lifecycle Guard. Update `## Stage Status`:

```markdown
Status: DRAFT
Step: specify
Risk Level: UNKNOWN
Last Updated: <ISO_TIMESTAMP>
Scope Defined: [Key scope items from spec]
Deferred Scope: [Anything explicitly excluded]
Architecture Governance Compliance: Specification drafted — governance audit pending
Notes: Specification complete. Clarification step pending.
```

## 1.4 — Update .workflow-state.json

Merge:

```json
{
  "current_step": "specify",
  "stage_status": "DRAFT",
  "step_timings": { "specify": { "started_at": "<ISO>", "completed_at": "<ISO>" } },
  "last_updated": "<ISO>",
  "history": [..., { "event": "specify_complete", "timestamp": "<ISO>" }]
}
```

## 1.5 — Update README.md

Mark Specify row as `✅`.

## 1.6 — Commit Specify Step

Apply Git Hygiene Enforcement:

```bash
git add specs/runtime/<STAGE_DIR_NAME>/ specs/phases/<PHASE_NAME>/<STAGE_FILE_NAME>
git diff --name-only --cached
```

Load `specs/templates/commits/commit-specify.md`. Fill and commit.

Apply automatic continuation rule → Step 2.

---

# Step 2 — Clarify

## 2.1 — Execute Clarify

Delegate to `/speckit.clarify` with input: `Stage: <STAGE_NAME>`

**What speckit.clarify does:**

- Reads `spec.md`, runs ambiguity scan, asks up to 5 targeted questions interactively
- Appends `## Clarifications` / `### Session YYYY-MM-DD` section directly into `spec.md` (in-place update)
- Does NOT create a separate `clarifications.md`

Audit focus: RBAC, Form Request validation, Eloquent relationship integrity, service layer boundaries, Arabic/RTL, workflow state transitions.

## 2.1B — Execute Checklist Generation

Delegate to `/speckit.checklist` with input: `Stage: <STAGE_NAME>, Spec: specs/runtime/<STAGE_DIR_NAME>/spec.md`

**What speckit.checklist does:**

- Reads `spec.md` (including clarifications)
- Generates security, performance, accessibility checklists
- Writes to `specs/runtime/<STAGE_DIR_NAME>/checklists/`

If `/speckit.checklist` is unavailable, manually create minimal checklists:

- [ ] RBAC middleware on all protected routes
- [ ] Form Request validation for all inputs
- [ ] Eloquent relationships properly defined
- [ ] Service layer contains business logic
- [ ] Error contract followed
- [ ] Arabic/RTL support verified

## 2.2 — Write Clarify Report

Load `specs/templates/reports/clarify-report-template.md`. Fill from `## Clarifications` in `spec.md`.
Write to: `specs/runtime/<STAGE_DIR_NAME>/reports/CLARIFY_REPORT.md`

## 2.3 — Update Stage Status Block

```markdown
Status: DRAFT
Step: clarify
Risk Level: <LOW / MEDIUM / HIGH>
Scope Defined: [Updated scope after clarifications]
Architecture Governance Compliance: Clarifications resolved — planning authorized
Notes: All specification ambiguities resolved. Ready for technical planning.
```

## 2.4 — Update .workflow-state.json

Merge:

```json
{
  "current_step": "clarify",
  "clarifications_resolved": true,
  "step_timings": { "clarify": { "started_at": "<ISO>", "completed_at": "<ISO>" } },
  "last_updated": "<ISO>",
  "history": [..., { "event": "clarifications_locked", "timestamp": "<ISO>" }]
}
```

## 2.5 — Update README.md

Mark Clarify row as `✅`.

## 2.6 — Commit Clarify Step

Git add, load `specs/templates/commits/commit-clarify.md`, fill and commit.

Apply automatic continuation rule → Step 3.

---

# Step 3 — Plan

## 3.1-PRE — Context7 MCP Pre-Planning Lookup

Before handing off to `/speckit.plan`, invoke **Context7 MCP** for any third-party library or framework referenced in `spec.md`.

1. Scan `spec.md` for external library/framework references.
2. For each dependency, query Context7 MCP for current API docs, method signatures, usage patterns.
3. Pass resolved documentation context to `/speckit.plan`.

This prevents `plan.md` from using stale API knowledge.

## 3.1 — Execute Plan

Delegate to `/speckit.plan` with input: `Stage: <STAGE_NAME>`

**What speckit.plan does:**

- Generates `research.md`, `data-model.md`, `contracts/`, `quickstart.md`
- Fills and finalizes `plan.md`

Plan must cover: tables/schema, migrations, endpoints, middleware, Form Requests, services, repositories, Eloquent relationships, Arabic/RTL, error handling, logging.

If plan modifies architecture:

```
❌ Architectural modification detected — workflow paused.
   Fix: Follow ADR Creation Protocol, then resume from Step 3.
```

→ STOP.

## 3.1A — Guardian Plan Validation (Parallel)

Run **in parallel**:

- Delegate to `/architecture-guardian`: `Stage: <STAGE_NAME>`
- Delegate to `/api-designer`: `Stage: <STAGE_NAME>`

Both MUST return `VERDICT: PASS`. If any returns `BLOCKED`:

```
❌ Guardian validation failed — plan cannot proceed.
   Blocked by: <guardian name>
   Violations: <list all by severity>
   Fix: Remediate all violations, then re-run 3.1A.
```

→ STOP. Do NOT write PLAN_REPORT or update state.

## 3.2 — Write Plan Report

Load `specs/templates/reports/plan-report-template.md`. Fill from `plan.md`, `research.md`, `data-model.md`.
Write to: `specs/runtime/<STAGE_DIR_NAME>/reports/PLAN_REPORT.md`

## 3.3 — Update Stage Status Block

```markdown
Status: DRAFT
Step: plan
Risk Level: <LOW / MEDIUM / HIGH>
Scope Planned: [Key planned items]
Architecture Governance Compliance: Technical plan compliant — task generation authorized
Notes: Technical plan complete. Task breakdown in progress.
```

## 3.4 — Update .workflow-state.json

Merge:

```json
{
  "current_step": "plan",
  "plan_completed": true,
  "guardian_verdicts": { "architecture_checker": "PASS|BLOCKED", "api_designer": "PASS|BLOCKED" },
  "step_timings": { "plan": { "started_at": "<ISO>", "completed_at": "<ISO>" } },
  "last_updated": "<ISO>",
  "history": [..., { "event": "plan_complete", "timestamp": "<ISO>" }]
}
```

## 3.5 — Update README.md

Mark Plan row as `✅`.

## 3.6 — Commit Plan Step

Git add, load `specs/templates/commits/commit-plan.md`, fill and commit.

Apply automatic continuation rule → Step 4.

---

# Step 4 — Tasks

## 4.1 — Execute Tasks

Delegate to `/speckit.tasks` with input: `Stage: <STAGE_NAME>`

**What speckit.tasks does:**

- Reads `spec.md`, `plan.md`, optional artifacts
- Writes `specs/runtime/<STAGE_DIR_NAME>/tasks.md`

**Task format:**

```
- [ ] T001 [P] [US1] Description with exact file path
```

- `- [ ]` checkbox (incomplete) / `- [X]` (complete, uppercase X)
- `T001` — sequential ID
- `[P]` — optional parallel marker
- `[US1]` — optional user story label

After generation, count all `- [ ]` lines → `TASKS_TOTAL`.

## 4.2 — Write Tasks Report

Load `specs/templates/reports/tasks-report-template.md`. Fill from `tasks.md`.
Write to: `specs/runtime/<STAGE_DIR_NAME>/reports/TASKS_REPORT.md`

Include enriched sections:

- **Risk-Ranked Task View**: 🔴 HIGH (migration, RBAC, payments) | 🟡 MEDIUM (endpoints, services) | 🟢 LOW (config, docs)
- **External Dependency Tasks**: from Context7 lookups in 3.1-PRE

## 4.3 — Update Stage Status Block

```markdown
Status: DRAFT
Step: tasks
Tasks Generated: Total: <TASKS_TOTAL> atomic tasks
Architecture Governance Compliance: Task set compliant — drift analysis required
```

## 4.4 — Update .workflow-state.json

Merge:

```json
{
  "current_step": "tasks",
  "tasks_total": "<TASKS_TOTAL>",
  "tasks_completed": 0,
  "step_timings": { "tasks": { "started_at": "<ISO>", "completed_at": "<ISO>" } },
  "last_updated": "<ISO>",
  "history": [..., { "event": "tasks_complete", "tasks_total": "<TASKS_TOTAL>", "timestamp": "<ISO>" }]
}
```

## 4.5 — Update README.md

Mark Tasks row as `✅`.

## 4.6 — Commit Tasks Step

Git add, load `specs/templates/commits/commit-tasks.md`, fill and commit.

Apply automatic continuation rule → Step 5.

---

# Step 5 — Analyze (Drift Detector)

## 5.1 — Execute Structural Drift Audit

Delegate to `/speckit.analyze` with input: `Stage: <STAGE_NAME>`

**What speckit.analyze does:**

- Reads `spec.md`, `plan.md`, `tasks.md`
- **STRICTLY READ-ONLY** — produces analysis to console only, writes NO files
- Offers remediation suggestions but does NOT apply them

Audit for: RBAC bypass, missing Form Request validation, business logic in controllers, N+1 queries, missing service layer, workflow state violations, Arabic/RTL gaps, missing error handling.

**Strict Pass Rule:** `drift_passed = true` ONLY IF ALL criteria pass. Single FAILED criterion = BLOCKED.

## 5.1A — Composite Guardian Audit (Parallel)

Run **all four in parallel**:

- Delegate to `/security-auditor`: `Stage: <STAGE_NAME>`
- Delegate to `/performance-optimizer`: `Stage: <STAGE_NAME>`
- Delegate to `/qa-engineer`: `Stage: <STAGE_NAME>`
- Delegate to `/code-reviewer`: `Stage: <STAGE_NAME>`

Each MUST return `VERDICT: PASS | BLOCKED`.
Group findings by severity: 🚨 Critical | ⚠️ High | ⚡ Medium | ℹ️ Low

## 5.1B — Composite Verdict Aggregation

- If structural audit (5.1) = BLOCKED **OR** any guardian = BLOCKED:
  → **Final Gate = BLOCKED | Implementation = FORBIDDEN**
- If all pass:
  → **Final Gate = APPROVED | Implementation = AUTHORIZED**

If BLOCKED → **STOP**. Do NOT write ANALYZE_REPORT or update state. Require full remediation and clean re-audit.

## 5.2 — Write Analyze Report

Load `specs/templates/audits/analyze-report-template.md`. Fill with drift audit findings and all guardian verdicts.
Write to: `specs/runtime/<STAGE_DIR_NAME>/audits/ANALYZE_REPORT.md`

## 5.3 — Update Stage Status Block

If APPROVED:

```markdown
Status: IN PROGRESS
Drift Analysis: PASSED (all criteria)
Implementation: AUTHORIZED
```

If BLOCKED:

```markdown
Status: IN PROGRESS
Risk Level: CRITICAL
Drift Analysis: FAILED
Implementation: FORBIDDEN
```

## 5.4 — Update .workflow-state.json

If APPROVED, merge:

```json
{
  "current_step": "analyze",
  "stage_status": "IN PROGRESS",
  "drift_passed": true,
  "implementation_allowed": true,
  "guardian_verdicts": {
    "security_auditor": "PASS",
    "performance_optimizer": "PASS",
    "qa_engineer": "PASS",
    "code_reviewer": "PASS"
  },
  "step_timings": { "analyze": { "started_at": "<ISO>", "completed_at": "<ISO>" } },
  "last_updated": "<ISO>",
  "history": [..., { "event": "drift_analysis_passed", "timestamp": "<ISO>" }]
}
```

If BLOCKED → set `drift_passed: false`, `implementation_allowed: false`.

## 5.5 — Update README.md

Mark Analyze row as `✅ Passed` or `❌ Blocked`.

## 5.6 — Commit Analyze Step

Git add, load `specs/templates/commits/commit-analyze.md`, fill and commit.

**Do NOT proceed to Step 6 if `drift_passed = false`.**

---

# Step 6 — Implement

## 6.1 — Verify Implementation Gate

Before generating ANY code, confirm:

- `drift_passed = true` in `.workflow-state.json`
- No unresolved architecture governance violations from Step 5
- No unresolved ambiguities from any prior step

If any check fails:

```
❌ Implementation gate check failed — code generation is forbidden.
   Fix: Resolve all listed issues and re-run Step 5 (Analyze).
```

→ STOP.

## 6.2 — Check SpecKit Checklists

Verify `checklists/requirements.md` is fully complete before implementation.

## 6.3-PRE — Context7 MCP Pre-Implementation Lookup

Invoke **Context7 MCP** for every task in `tasks.md` that involves a third-party library. Ensure implementation uses accurate, current API knowledge.

## 6.3 — Execute Implement

Delegate to `/speckit.implement` with input: `Stage: <STAGE_NAME>, Tasks Total: <TASKS_TOTAL>`

**What speckit.implement does:**

- Reads `tasks.md`, `plan.md`, optional artifacts
- Executes tasks phase-by-phase following TDD
- Marks completed tasks as `- [X]` (uppercase X)
- Halts on any non-parallel task failure

Rules: Eloquent models via repository pattern, thin controllers → services, Form Request validation, RBAC middleware, Arabic/i18n, structured error responses.

Apply **Package Manager Enforcement** — use `$PKG_MANAGER` for all dependency installs.

## 6.3B — Post-Implementation Simplification Pass

Apply Post-Implementation Simplification Protocol (skill: `post-implementation-simplification`).

## 6.4 — Verify Implementation Completeness

Count `- [X]` lines in `tasks.md`. `TASKS_COMPLETED` must equal `TASKS_TOTAL`.

If incomplete → STOP. Present choice: continue implementation or formally defer remaining tasks.

## 6.5 — Mandatory Validation Gate

Run and record ALL:

- Unit tests (PHPUnit backend, Vitest frontend)
- Integration tests for API flows
- Lint (PHP CS Fixer/Pint backend, ESLint frontend)
- Type check (PHPStan backend, `npx nuxi typecheck` frontend)
- Migration validation (`php artisan migrate --pretend`)

Any failure → **BLOCK** implementation.

Load `specs/templates/audits/validation-report-template.md`.
Write to: `specs/runtime/<STAGE_DIR_NAME>/audits/VALIDATION_REPORT.md`

## 6.6 — Pre-Closure Guardian Validation (Parallel)

Run in parallel:

- Delegate to `/github-actions-expert`
- Delegate to `/devops-engineer`
- Delegate to `/security-auditor`

Each MUST return `VERDICT: PASS | BLOCKED`. If any BLOCKED → **STOP**.

## 6.7 — Write Implement Report

Load `specs/templates/reports/implement-report-template.md`.
Write to: `specs/runtime/<STAGE_DIR_NAME>/reports/IMPLEMENT_REPORT.md`

## 6.8 — Update Stage Status Block

```markdown
Status: BACKEND CLOSED
Implementation: COMPLETE
Tasks: <TASKS_COMPLETED> / <TASKS_TOTAL> completed
```

## 6.9 — Update .workflow-state.json

Merge:

```json
{
  "current_step": "implement",
  "stage_status": "BACKEND CLOSED",
  "tasks_completed": "<TASKS_COMPLETED>",
  "step_timings": { "implement": { "started_at": "<ISO>", "completed_at": "<ISO>" } },
  "last_updated": "<ISO>",
  "history": [..., { "event": "stage_backend_closed", "timestamp": "<ISO>" }]
}
```

## 6.10 — Update README.md

Mark Implement row as `✅`.

## 6.11 — Commit Implement Step

Git add, load `specs/templates/commits/commit-implement.md`, fill and commit.

---

# Mandatory Pre-Closure Review Gate

## Autopilot Bypass Rule

Check `.workflow-state.json` for `"auto_advance": true`:

**If `auto_advance === true` AND no blocking issues detected:**

- Skip manual review → Auto-approve
- Log: `[AUTOPILOT] Pre-Closure Review Gate bypassed (auto_advance=true, no blockers)`
- Proceed immediately to Step 7

**If `auto_advance === false` OR blocking issues found:**

- Present manual review gate (below)

## Manual Review Gate (Non-Autopilot)

**Hard STOP. Do NOT proceed to Step 7 without explicit user approval.**

```
⏸ Pre-Closure Review Gate

All implementation steps are complete. Please review all reports
before closure is executed.

Tasks completed: <TASKS_COMPLETED> / <TASKS_TOTAL>
```

Present each artifact as clickable file links. Ask:

- **Approve** — proceed to closure
- **Reject** — send back for fixes

If rejected → address issues, re-run validation, re-present gate.
If approved → proceed to Step 7.

---

# Step 7 — Closure

Only execute after explicit user approval at Pre-Closure Review Gate (or autopilot bypass).

## 7.1 — Write Closure Report

Load `specs/templates/reports/closure-report-template.md`. Fill from all prior outputs.
Write to: `specs/runtime/<STAGE_DIR_NAME>/reports/CLOSURE_REPORT.md`

## 7.2 — Generate Testing Guide

Load `specs/templates/guides/testing-guide-template.md`.
Fill with actual manual test scenarios, commands, concrete values.
Write to: `specs/runtime/<STAGE_DIR_NAME>/guides/TESTING_GUIDE.md`

## 7.3 — Update Stage Status Block (Final)

```markdown
Status: PRODUCTION READY
Risk Level: <LOW / MEDIUM / HIGH>
Closure Date: <ISO_DATE>
Scope Closed: [All delivered scope items], <TASKS_COMPLETED> / <TASKS_TOTAL> tasks
Deferred Scope: [Formally deferred tasks with justification, or "None"]
Architecture Governance Compliance:

- ADR alignment verified
- RBAC enforcement confirmed
- Service layer architecture maintained
- Error contract compliance verified
  Notes: Stage is production ready. Modifications require a new stage.
```

## 7.4 — Update .workflow-state.json (Final)

Write complete final state:

```json
{
  "current_step": "stage_production_ready",
  "stage_status": "PRODUCTION READY",
  "clarifications_resolved": true,
  "drift_passed": true,
  "implementation_allowed": true,
  "plan_completed": true,
  "tasks_total": "<TASKS_TOTAL>",
  "tasks_completed": "<TASKS_COMPLETED>",
  "step_timings": { "closure": { "started_at": "<ISO>", "completed_at": "<ISO>" } },
  "last_updated": "<ISO>",
  "history": [...,
    { "event": "pre_closure_review_approved", "timestamp": "<ISO>" },
    { "event": "stage_production_ready", "timestamp": "<ISO>" }
  ]
}
```

## 7.5 — Update README.md (Final)

Mark Closure as `✅`. Append:

```
**Final Status:** PRODUCTION READY — <ISO_DATE>
**Tasks:** <TASKS_COMPLETED> / <TASKS_TOTAL> completed
```

## 7.6 — Generate PR Summary

Load `specs/templates/pr-template.md`. Populate from workflow artifacts.
Write to: `specs/runtime/<STAGE_DIR_NAME>/PR_SUMMARY.md`
Output the completed PR summary to the user.

## 7.7 — Commit Closure Step

Git add, load `specs/templates/commits/commit-closure.md`, fill and commit.

## 7.8 — Governance Metadata Lock (Validation Gate)

### 7.8A — Stage Status Block Verification

Open stage file and verify `## Stage Status` is fully populated:

- [ ] `Status:` = `PRODUCTION READY`
- [ ] `Risk Level:` populated
- [ ] `Closure Date:` = current ISO date
- [ ] `Scope Closed:` lists complete scope
- [ ] `Architecture Governance Compliance:` documents compliance

If ANY unchecked → **STOP**. Remediate and re-run.

### 7.8B — Workflow State Consistency Check

Verify `.workflow-state.json`:

- `stage_status` = "PRODUCTION READY"
- `current_step` = "stage_production_ready"
- `tasks_completed` = `tasks_total`
- History contains >= 9 events

If BLOCKED → **STOP**. Remediate.

## 7.9 — Output Final Closure Summary

```
✅ Bunyan Hard Mode Workflow — COMPLETE

Stage:    <STAGE_NAME>
Phase:    <PHASE_NAME>
Branch:   spec/<STAGE_DIR_NAME>
Status:   PRODUCTION READY
Tasks:    <TASKS_COMPLETED> / <TASKS_TOTAL> completed

specs/runtime/<STAGE_DIR_NAME>/
├── spec.md                      ✅
├── plan.md                      ✅
├── tasks.md                     ✅
├── research.md                  ✅
├── checklists/requirements.md   ✅
├── README.md                    ✅
├── PR_SUMMARY.md                ✅
├── reports/
│   ├── SPECIFY_REPORT.md        ✅
│   ├── CLARIFY_REPORT.md        ✅
│   ├── PLAN_REPORT.md           ✅
│   ├── TASKS_REPORT.md          ✅
│   ├── IMPLEMENT_REPORT.md      ✅
│   └── CLOSURE_REPORT.md        ✅
├── audits/
│   ├── ANALYZE_REPORT.md        ✅
│   └── VALIDATION_REPORT.md     ✅
└── guides/
    └── TESTING_GUIDE.md         ✅
```

Present next actions:

- **Push branch**: `git push origin spec/<STAGE_DIR_NAME>` (only on explicit request)
- **Open PR Summary**: `specs/runtime/<STAGE_DIR_NAME>/PR_SUMMARY.md`
- **Open Testing Guide**: `specs/runtime/<STAGE_DIR_NAME>/guides/TESTING_GUIDE.md`

Do NOT auto-push.

---

# Rollback Protocol

## Trigger Conditions

- Step 6 CI validation fails after 2 retry attempts
- Guardian agent issues a REJECT verdict
- User explicitly requests rollback
- Pre-commit diagnostics detect unresolvable violations

## R.1 — Identify Rollback Target

Read `.workflow-state.json` to determine `current_step` and find the last successful commit.

## R.2 — Revert Implementation Commits

```bash
git revert --no-commit <FAILED_COMMIT_SHA>..HEAD
git diff --stat HEAD
```

Do NOT use `git reset --hard`. Revert creates forward-only history.

## R.3 — Update .workflow-state.json

Merge rollback event into history. Set `implementation_allowed: false`, `drift_passed: false`.

## R.4 — Update Stage Status Block

Set status to DRAFT, document rollback event.

## R.5 — Commit Rollback

```bash
git add specs/runtime/<STAGE_DIR_NAME>/ specs/phases/<PHASE_NAME>/<STAGE_FILE_NAME>
git commit -m "spec(<STAGE_DIR_NAME>): rollback from <FAILED_STEP> to <TARGET_STEP>"
```

## R.6 — Re-entry

After rollback, re-enter at the target step. Allowed re-entry points:

- **Step 3 (Plan)** — if approach needs redesign
- **Step 5 (Analyze)** — if analysis needs re-run
- **Step 6 (Implement)** — if only code needs correction

Re-entry at Step 1 or Step 2 requires explicit user approval.

---

# Scope Amendment Protocol

Delegated to skill: `stage-workflow-governance`

Handles invalidated-step mapping, amendment recording, and regeneration requirements when scope changes after committed work.

---

# ADR Creation Protocol

Delegated to skill: `stage-workflow-governance`

Handles ADR escalation triggers, creation requirements, numbering, and stop-the-workflow behavior.

## Escalation

If ambiguous spec, conflicting ADR, migration risk, or RBAC violation → **STOP** and request clarification. Guessing is forbidden.

---

## Workflow Progress Banner

Display at the start of each step:

```
HARD MODE WORKFLOW
Stage: <STAGE_NAME>
Phase: <PHASE_NAME>
Branch: spec/<STAGE_DIR_NAME>
Step: <STEP_INDEX>/<TOTAL_STEPS>: <current_step>
Status: <status>
```
