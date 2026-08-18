#!/usr/bin/env bash
# PostToolUse (Edit|Write|MultiEdit): auto-format an edited PHP file with Laravel
# Pint. Always non-blocking (exit 0) — a formatting pass never fails the edit.
set -euo pipefail

input="$(cat)"

file_path="$(printf '%s' "$input" \
  | grep -oE '"file_path"[[:space:]]*:[[:space:]]*"[^"]*"' \
  | head -n1 \
  | sed -E 's/.*:[[:space:]]*"([^"]*)"/\1/')"

[ -n "${file_path:-}" ] || exit 0

case "$file_path" in
  *.php) ;;
  *) exit 0 ;;
esac

[ -f "$file_path" ] || exit 0
[ -x "vendor/bin/pint" ] || exit 0

if vendor/bin/pint "$file_path" -q >/dev/null 2>&1; then
  echo "Pint formatted: $file_path"
fi

exit 0
