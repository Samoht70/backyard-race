#!/usr/bin/env bash
# PreToolUse (Edit|Write|MultiEdit): block edits to secrets / credential files.
# Exit 2 + stderr = deny the tool call and tell Claude why. Exit 0 = allow.
set -euo pipefail

input="$(cat)"

# Extract the target file_path from the tool_input JSON (machine-generated,
# so a simple grep is safe — no external JSON dependency required).
file_path="$(printf '%s' "$input" \
  | grep -oE '"file_path"[[:space:]]*:[[:space:]]*"[^"]*"' \
  | head -n1 \
  | sed -E 's/.*:[[:space:]]*"([^"]*)"/\1/')"

[ -n "${file_path:-}" ] || exit 0

base="$(basename "$file_path")"

deny() {
  echo "BLOCKED: '$file_path' holds secrets/credentials and must not be edited by Claude." >&2
  echo "Edit '.env.example' instead, or ask the user to change this file manually." >&2
  exit 2
}

# .env and its variants are blocked, except the committed templates.
case "$base" in
  .env.example|.env.testing) ;;             # safe, non-secret templates
  .env|.env.*) deny ;;
esac

# Key material, credential dumps, and anything obviously secret-shaped.
case "$file_path" in
  *.key|*.pem|*.p12|*.pfx|*.crt) deny ;;
  auth.json|*/auth.json) deny ;;
  *credentials*|*secrets*) deny ;;
esac

exit 0
