#!/usr/bin/env bash
# SessionStart: ensure graphify's post-commit hook is installed, so each commit
# (~ the end of a feature) re-extracts changed code and rebuilds the graph.
# Idempotent and guarded — only acts once a graph exists (graphify-out/), and
# never appends a second time if the post-commit hook already mentions graphify.
set -euo pipefail

command -v graphify >/dev/null 2>&1 || exit 0
[ -d "graphify-out" ] || exit 0
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || exit 0

hookfile="$(git rev-parse --git-path hooks/post-commit 2>/dev/null || true)"
if [ -n "${hookfile:-}" ] && [ -f "$hookfile" ] && grep -qi graphify "$hookfile"; then
  exit 0
fi

graphify hook install >/dev/null 2>&1 || true
exit 0
