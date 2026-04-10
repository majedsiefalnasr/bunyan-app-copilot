#!/usr/bin/env bash
# create-new-feature.sh — Create a new feature branch and spec runtime directory
#
# Usage: bash .specify/scripts/bash/create-new-feature.sh STAGE_XX_NAME [base_branch]

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/common.sh"

# ── Arguments ──
STAGE_NAME="${1:-}"
BASE_BRANCH="${2:-develop}"

if [[ -z "$STAGE_NAME" ]]; then
  print_error "Usage: $0 STAGE_XX_NAME [base_branch]"
  print_error "Example: $0 STAGE_07_CATEGORIES develop"
  exit 1
fi

REPO_ROOT=$(get_repo_root)
BRANCH_NAME="spec/${STAGE_NAME}"

print_header "Creating Feature: ${STAGE_NAME}"

# ── Validate spec file exists ──
SPEC_FILE=$(resolve_feature_path "$STAGE_NAME") || exit 1
print_success "Spec file found: ${SPEC_FILE}"

# ── Validate clean tree ──
ensure_clean_tree
print_success "Working tree is clean"

# ── Create branch ──
echo "Creating branch: ${BRANCH_NAME} from ${BASE_BRANCH}..."
git checkout "${BASE_BRANCH}"
git pull origin "${BASE_BRANCH}"
git checkout -b "${BRANCH_NAME}"
print_success "Branch created: ${BRANCH_NAME}"

# ── Create runtime directory ──
RUNTIME_DIR="${REPO_ROOT}/specs/runtime/${STAGE_NAME}"
mkdir -p "${RUNTIME_DIR}"

# ── Initialize .workflow-state.json ──
cat > "${RUNTIME_DIR}/.workflow-state.json" << EOF
{
  "stage": "${STAGE_NAME}",
  "branch": "${BRANCH_NAME}",
  "baseBranch": "${BASE_BRANCH}",
  "currentStep": "pre-step",
  "status": "in-progress",
  "startedAt": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "completedAt": null,
  "steps": {
    "pre-step": { "status": "completed", "completedAt": "$(date -u +%Y-%m-%dT%H:%M:%SZ)" },
    "specify": { "status": "not-started", "completedAt": null },
    "clarify": { "status": "not-started", "completedAt": null },
    "plan": { "status": "not-started", "completedAt": null },
    "tasks": { "status": "not-started", "completedAt": null },
    "analyze": { "status": "not-started", "completedAt": null },
    "implement": { "status": "not-started", "completedAt": null },
    "closure": { "status": "not-started", "completedAt": null }
  },
  "artifacts": {},
  "guardianResults": {},
  "mergeSemantics": {
    "strategy": "squash",
    "targetBranch": "${BASE_BRANCH}",
    "deleteBranchAfterMerge": true
  }
}
EOF

print_success "Runtime directory created: ${RUNTIME_DIR}"
print_success "Workflow state initialized"

# ── Initial commit ──
git add "${RUNTIME_DIR}"
git commit -m "chore(${STAGE_NAME}): initialize spec runtime

- Create runtime directory
- Initialize .workflow-state.json
- Branch: ${BRANCH_NAME} from ${BASE_BRANCH}"

print_success "Initial commit created"

echo ""
print_header "Feature Ready"
echo "  Branch:  ${BRANCH_NAME}"
echo "  Runtime: ${RUNTIME_DIR}"
echo "  Next:    Start the orchestrator with /orchestrator autopilot"
echo ""
