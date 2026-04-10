---
name: terminal-capability-governance
description: Tool availability detection and fallbacks
---

# Terminal Capability Governance — Bunyan

## Tool Detection

Before running commands, detect available tools:

```bash
command -v rtk && echo "RTK available" || echo "RTK not available"
command -v jq && echo "jq available" || echo "jq not available"
command -v rg && echo "ripgrep available" || echo "ripgrep not available"
command -v fd && echo "fd available" || echo "fd not available"
```

## Command Preference Hierarchy

1. **RTK**: If available, always prefer `rtk <command>`
2. **Native tools**: ripgrep (`rg`) > grep, fd > find
3. **Standard tools**: grep, find, cat — always available

## Fallback Strategy

```bash
# Search: rg > grep
if command -v rg &>/dev/null; then
    rg "pattern" --type php
else
    grep -r "pattern" --include="*.php" .
fi

# Find: fd > find
if command -v fd &>/dev/null; then
    fd -e php "Controller"
else
    find . -name "*Controller.php"
fi
```
