#!/usr/bin/env bash
# update-agent-context.sh — Update AI context files after spec changes
#
# Usage: bash .specify/scripts/bash/update-agent-context.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/common.sh"

REPO_ROOT=$(get_repo_root)

print_header "Updating AI Context"

# ── Generate stage summary for AI ──
CONTEXT_DIR="${REPO_ROOT}/docs/ai/context"
mkdir -p "$CONTEXT_DIR"

SUMMARY_FILE="${CONTEXT_DIR}/stage-summary.md"

echo "# Bunyan — Stage Summary (Auto-Generated)" > "$SUMMARY_FILE"
echo "" >> "$SUMMARY_FILE"
echo "> Generated: $(date -u +%Y-%m-%dT%H:%M:%SZ)" >> "$SUMMARY_FILE"
echo "" >> "$SUMMARY_FILE"

# ── Scan all stage files ──
for phase_dir in "${REPO_ROOT}/specs/phases"/*/; do
  phase_name=$(basename "$phase_dir")
  echo "## ${phase_name}" >> "$SUMMARY_FILE"
  echo "" >> "$SUMMARY_FILE"

  for stage_file in "${phase_dir}"STAGE_*.md; do
    if [[ -f "$stage_file" ]]; then
      stage_basename=$(basename "$stage_file" .md)
      status=$(grep -oP 'Status:\s*\K.*' "$stage_file" | head -1 | xargs || echo "UNKNOWN")
      risk=$(grep -oP 'Risk Level:\s*\K.*' "$stage_file" | head -1 | xargs || echo "UNKNOWN")
      echo "- **${stage_basename}** — Status: ${status} | Risk: ${risk}" >> "$SUMMARY_FILE"
    fi
  done

  echo "" >> "$SUMMARY_FILE"
done

print_success "Stage summary updated: ${SUMMARY_FILE}"

# ── Scan active runtime directories ──
RUNTIME_DIR="${REPO_ROOT}/specs/runtime"
if [[ -d "$RUNTIME_DIR" ]]; then
  ACTIVE_FILE="${CONTEXT_DIR}/active-stages.md"
  echo "# Active Stages (Auto-Generated)" > "$ACTIVE_FILE"
  echo "" >> "$ACTIVE_FILE"
  echo "> Generated: $(date -u +%Y-%m-%dT%H:%M:%SZ)" >> "$ACTIVE_FILE"
  echo "" >> "$ACTIVE_FILE"

  for runtime_stage in "${RUNTIME_DIR}"/STAGE_*/; do
    if [[ -d "$runtime_stage" ]]; then
      stage_name=$(basename "$runtime_stage")
      workflow_state="${runtime_stage}.workflow-state.json"
      if [[ -f "$workflow_state" ]]; then
        current_step=$(grep -oP '"currentStep":\s*"\K[^"]+' "$workflow_state" || echo "unknown")
        echo "- **${stage_name}** — Step: ${current_step}" >> "$ACTIVE_FILE"
      fi
    fi
  done

  print_success "Active stages updated: ${ACTIVE_FILE}"
fi

echo ""
print_success "AI context update complete"
