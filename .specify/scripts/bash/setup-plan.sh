#!/usr/bin/env bash
# setup-plan.sh — Copy plan template into runtime directory for current stage
#
# Usage: bash .specify/scripts/bash/setup-plan.sh [STAGE_XX_NAME]

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/common.sh"

# ── Resolve stage ──
STAGE_NAME="${1:-}"

if [[ -z "$STAGE_NAME" ]]; then
  STAGE_NAME=$(get_stage_from_branch)
  if [[ -z "$STAGE_NAME" ]]; then
    print_error "Cannot determine stage. Provide as argument or run from a spec/* branch."
    exit 1
  fi
fi

REPO_ROOT=$(get_repo_root)
RUNTIME_DIR=$(resolve_runtime_path "$STAGE_NAME")
TEMPLATES_DIR="${REPO_ROOT}/specs/templates"

print_header "Setting Up Plan: ${STAGE_NAME}"

# ── Ensure runtime directory exists ──
if [[ ! -d "$RUNTIME_DIR" ]]; then
  print_error "Runtime directory does not exist: ${RUNTIME_DIR}"
  print_error "Run create-new-feature.sh first."
  exit 1
fi

# ── Copy templates ──
TEMPLATES=(
  "specify-template.md:spec.md"
  "plan-template.md:plan.md"
  "tasks-template.md:tasks.md"
  "checklist-template.md:checklist.md"
  "clarify-template.md:clarify.md"
  "analyze-template.md:analyze.md"
  "implement-template.md:implement.md"
)

for entry in "${TEMPLATES[@]}"; do
  SRC="${entry%%:*}"
  DST="${entry##*:}"

  if [[ -f "${TEMPLATES_DIR}/${SRC}" ]]; then
    if [[ ! -f "${RUNTIME_DIR}/${DST}" ]]; then
      cp "${TEMPLATES_DIR}/${SRC}" "${RUNTIME_DIR}/${DST}"
      # Replace placeholder with stage name
      sed -i '' "s/{{STAGE_NAME}}/${STAGE_NAME}/g" "${RUNTIME_DIR}/${DST}" 2>/dev/null || \
      sed -i "s/{{STAGE_NAME}}/${STAGE_NAME}/g" "${RUNTIME_DIR}/${DST}" 2>/dev/null || true
      print_success "Created: ${DST}"
    else
      print_warning "Skipped (exists): ${DST}"
    fi
  else
    print_warning "Template not found: ${SRC}"
  fi
done

echo ""
print_success "Plan setup complete for ${STAGE_NAME}"
echo "  Runtime: ${RUNTIME_DIR}"
