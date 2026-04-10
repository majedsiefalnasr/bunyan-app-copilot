---
description: Perform a non-destructive cross-artifact consistency and quality analysis across spec.md, plan.md, and tasks.md after task generation.
---

## User Input

```text
$ARGUMENTS
```

You **MUST** consider the user input before proceeding (if not empty).

## Goal

Identify inconsistencies, duplications, ambiguities, and underspecified items across the three core artifacts (`spec.md`, `plan.md`, `tasks.md`) before implementation. This command MUST run only after `/speckit.tasks` has successfully produced a complete `tasks.md`.

## Operating Constraints

**STRICTLY READ-ONLY**: Do **not** modify any files. Output a structured analysis report.

**Constitution Authority**: The project constitution (`.specify/memory/constitution.md`) is **non-negotiable**.

## Execution Steps

### 1. Initialize Analysis Context

Run `.specify/scripts/bash/check-prerequisites.sh --json --require-tasks --include-tasks` once from repo root and parse JSON for FEATURE_DIR.

### 2. Load Artifacts

**From spec.md:** Overview, Functional Requirements, Non-Functional Requirements, User Stories, Edge Cases
**From plan.md:** Architecture/stack choices, Data Model references, Phases, Technical constraints
**From tasks.md:** Task IDs, Descriptions, Phase grouping, Parallel markers, Referenced file paths
**From constitution:** Load `.specify/memory/constitution.md` for principle validation

### 3. Build Semantic Models

- **Requirements inventory**: Each requirement with a stable key
- **User story/action inventory**: Discrete user actions with acceptance criteria
- **Task coverage mapping**: Map each task to requirements or stories
- **Constitution rule set**: Extract principle names and normative statements

### 4. Detection Passes

#### A. Duplication Detection

- Identify near-duplicate requirements

#### B. Ambiguity Detection

- Flag vague adjectives lacking measurable criteria
- Flag unresolved placeholders

#### C. Underspecification

- Requirements with verbs but missing measurable outcome
- User stories missing acceptance criteria alignment
- Tasks referencing undefined files or components

#### D. Constitution Alignment

- Any element conflicting with a MUST principle

### 5. Report

Output structured analysis with findings organized by severity (CRITICAL, WARNING, INFO).
