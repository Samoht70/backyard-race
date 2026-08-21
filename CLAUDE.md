## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).

## commit-convention

Commit messages and the end-of-story squash follow `.claude/skills/commit-convention/SKILL.md`.

Rules:
- Before writing any commit message on this repo, invoke the Skill tool with `skill: "commit-convention"`.
- Commit per task while a story is in flight; a finished story becomes a single commit.
- **Never run the squash.** At the end of a story, provide the squash message as text and stop — no `git reset`, no `git rebase`, no `--amend` on a published commit, no `git push`. Those belong to the project owner.

## no-comment

Code on this repo carries no comments. The gate lives in the `global:no-comment` skill.

Rules:
- Before writing any code file, invoke the Skill tool with `skill: "global:no-comment"`, and apply it as written rather than from memory.
- The only survivors are docblocks carrying a **type the language cannot express** (`@property`, `@return list<T>`, `@param array<string, mixed>`, `@implements`, `@mixin`), a `@throws`, or a named hazard stated as the failure. Nothing else — no prose, no rationale, no design notes, no rejected alternatives, no story keys, no `//` anywhere.
- Zero is the target. Most files get none, and two blocks in one file is a smell.
- Reasoning about *why* the code is shaped this way belongs in the commit message and in `project/DECISIONS.md`, never in the source.
- Keep a docblock only after proving it is load-bearing: strip it, run `./vendor/bin/sail composer types:check`, and put it back only if Larastan fails without it.
