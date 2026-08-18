#!/usr/bin/env bash
# UserPromptSubmit: inject quick knowledge-graph context for the user's prompt.
# Runs the raw `graphify query` (substring + IDF matching). No-op until a graph
# has been built (graphify-out/). For deep/cross-language questions, the graphify
# SKILL gives better results — this hook is the always-on lightweight companion.
set -euo pipefail

command -v graphify >/dev/null 2>&1 || exit 0
[ -d "graphify-out" ] || exit 0

input="$(cat)"

if command -v jq >/dev/null 2>&1; then
  prompt="$(printf '%s' "$input" | jq -r '.prompt // empty')"
else
  prompt="$(printf '%s' "$input" \
    | grep -oE '"prompt"[[:space:]]*:[[:space:]]*"([^"\\]|\\.)*"' \
    | head -n1 \
    | sed -E 's/^"prompt"[[:space:]]*:[[:space:]]*"//; s/"$//')"
fi

[ -n "${prompt:-}" ] || exit 0

result="$(graphify query "$prompt" 2>/dev/null | head -c 4000 || true)"

if [ -n "$result" ]; then
  echo "Knowledge-graph context (graphify) for this request — treat as hints, verify against the code:"
  echo "$result"
fi

exit 0
