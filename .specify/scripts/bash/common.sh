#!/usr/bin/env bash
# common.sh — Shared helpers for Bunyan SpecKit scripts

set -euo pipefail

# ── Colors ──
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# ── Helpers ──

get_repo_root() {
  git rev-parse --show-toplevel 2>/dev/null || {
    echo -e "${RED}Error: Not inside a git repository${NC}" >&2
    exit 1
  }
}

get_current_branch() {
  git branch --show-current 2>/dev/null || echo "detached"
}

get_stage_from_branch() {
  local branch
  branch=$(get_current_branch)
  echo "$branch" | grep -oP 'STAGE_\d+_[A-Z_]+' || echo ""
}

ensure_clean_tree() {
  if [[ -n "$(git status --porcelain)" ]]; then
    echo -e "${RED}Error: Working tree is not clean. Commit or stash changes first.${NC}" >&2
    exit 1
  fi
}

ensure_on_spec_branch() {
  local branch
  branch=$(get_current_branch)
  if [[ ! "$branch" =~ ^spec/ ]]; then
    echo -e "${RED}Error: Not on a spec/* branch. Current branch: ${branch}${NC}" >&2
    exit 1
  fi
}

print_header() {
  echo ""
  echo -e "${BLUE}╔══════════════════════════════════════════╗${NC}"
  echo -e "${BLUE}║  ${GREEN}$1${BLUE}  ║${NC}"
  echo -e "${BLUE}╚══════════════════════════════════════════╝${NC}"
  echo ""
}

print_success() {
  echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
  echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
  echo -e "${RED}✗ $1${NC}"
}

confirm_action() {
  local prompt="${1:-Continue?}"
  read -r -p "$(echo -e "${YELLOW}${prompt} [y/N]: ${NC}")" response
  [[ "$response" =~ ^[Yy]$ ]]
}

# ── Feature Path Resolution ──

resolve_feature_path() {
  local stage_name="$1"
  local repo_root
  repo_root=$(get_repo_root)
  
  # Find matching spec file
  local spec_file
  spec_file=$(find "${repo_root}/specs/phases" -name "${stage_name}.md" -type f 2>/dev/null | head -1)
  
  if [[ -z "$spec_file" ]]; then
    echo -e "${RED}Error: No spec file found for ${stage_name}${NC}" >&2
    return 1
  fi
  
  echo "$spec_file"
}

resolve_runtime_path() {
  local stage_name="$1"
  local repo_root
  repo_root=$(get_repo_root)
  echo "${repo_root}/specs/runtime/${stage_name}"
}
