#!/bin/bash
# scripts/ci/validate-skill-sizes.sh
#
# Validates that SKILL.md files do not exceed 500 lines.
# Q4 governance requirement: Keep skill files concise and navigable.
#
# Exit codes:
#   0 = all skills pass size validation
#   1 = one or more skills exceed 500 lines

set -euo pipefail

SKILL_MAX_LINES=500
EXIT_CODE=0

# Loop through all SKILL.md files changed in this commit
for skill_file in "$@"; do
  # Skip if not a SKILL.md file (safety check)
  if [[ ! "$skill_file" =~ SKILL\.md$ ]]; then
    continue
  fi

  # Count lines
  line_count=$(wc -l < "$skill_file")

  if [ "$line_count" -gt "$SKILL_MAX_LINES" ]; then
    echo "❌ SKILL.md size violation: $skill_file"
    echo "   Lines: $line_count (max: $SKILL_MAX_LINES)"
    EXIT_CODE=1
  else
    echo "✅ $skill_file ($line_count lines)"
  fi
done

exit "$EXIT_CODE"
