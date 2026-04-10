#!/usr/bin/env bash
# check-prerequisites.sh — Validate that all development prerequisites are installed
#
# Usage: bash .specify/scripts/bash/check-prerequisites.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/common.sh"

print_header "Checking Prerequisites"

ERRORS=0

# ── PHP ──
if command -v php &>/dev/null; then
  PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
  if [[ "$(echo "$PHP_VERSION >= 8.2" | bc -l 2>/dev/null || echo 0)" == "1" ]] || [[ "$PHP_VERSION" == "8.2" ]] || [[ "$PHP_VERSION" > "8.2" ]]; then
    print_success "PHP ${PHP_VERSION}"
  else
    print_error "PHP ${PHP_VERSION} (requires >= 8.2)"
    ERRORS=$((ERRORS + 1))
  fi
else
  print_error "PHP not installed"
  ERRORS=$((ERRORS + 1))
fi

# ── Composer ──
if command -v composer &>/dev/null; then
  print_success "Composer $(composer --version --no-ansi 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)"
else
  print_error "Composer not installed"
  ERRORS=$((ERRORS + 1))
fi

# ── Node.js ──
if command -v node &>/dev/null; then
  NODE_VERSION=$(node -v)
  print_success "Node.js ${NODE_VERSION}"
else
  print_error "Node.js not installed"
  ERRORS=$((ERRORS + 1))
fi

# ── npm ──
if command -v npm &>/dev/null; then
  print_success "npm $(npm -v)"
else
  print_error "npm not installed"
  ERRORS=$((ERRORS + 1))
fi

# ── MySQL ──
if command -v mysql &>/dev/null; then
  print_success "MySQL client $(mysql --version | grep -oP '\d+\.\d+\.\d+' | head -1)"
else
  print_warning "MySQL client not found (Docker may provide it)"
fi

# ── Docker ──
if command -v docker &>/dev/null; then
  print_success "Docker $(docker --version | grep -oP '\d+\.\d+\.\d+' | head -1)"
else
  print_warning "Docker not installed (needed for local development)"
fi

# ── Git ──
if command -v git &>/dev/null; then
  print_success "Git $(git --version | grep -oP '\d+\.\d+\.\d+' | head -1)"
else
  print_error "Git not installed"
  ERRORS=$((ERRORS + 1))
fi

# ── Laravel Pint (optional — installed via Composer) ──
REPO_ROOT=$(get_repo_root)
if [[ -f "${REPO_ROOT}/vendor/bin/pint" ]]; then
  print_success "Laravel Pint (via Composer)"
else
  print_warning "Laravel Pint not found (run: composer install)"
fi

# ── PHPStan (optional — installed via Composer) ──
if [[ -f "${REPO_ROOT}/vendor/bin/phpstan" ]]; then
  print_success "PHPStan (via Composer)"
else
  print_warning "PHPStan not found (run: composer install)"
fi

echo ""
if [[ $ERRORS -gt 0 ]]; then
  print_error "${ERRORS} prerequisite(s) missing. Fix the errors above before proceeding."
  exit 1
else
  print_success "All prerequisites satisfied!"
fi
