---
name: commit-convention
description: Use whenever you write a commit message on this repo, or finish a story. During a story, commit per task. At the END of a story, DO NOT squash — provide the squash commit message as text and stop; the project owner runs the squash. Never run `git reset`, `git rebase`, `git commit --amend` on published commits, or `git push` here. Message shape is gitmoji + conventional commit (`💄 feat(design-system): establish the design system (BR-02)`), story key in the subject parentheses OR a `Refs BR-XX.` trailer but never both, subject and body in English even though the UI is French, substantial body explaining WHY, and no AI attribution. Triggers on `git commit`, writing or drafting a commit message, "squash", "un nom de commit", finishing a story, and ticking the last task of a story.
---

# Commit convention: one squash per story, message provided

## The rule

**During a story, commit per task. When a story is finished, hand over the squash message as text — the project owner performs the squash.**

Claude never rewrites history on this repo: no `git reset --soft`, no `git rebase -i`, no
`--amend` on a published commit, no `git push`. Those belong to the owner. Claude's deliverable
at the end of a story is a ready-to-paste commit message, nothing more.

Per-task commits during the story are wanted — they make review readable while the work is in
flight. They are provisional: the story lands on `develop` as a single commit.

## Why

The history has to read as the story of the project, not the transcript of a session. On this
repo the hosting decision was settled, reopened, then re-settled — three commits for one final
decision. Nobody reading `git log` in six months benefits from that path.

The squash stays in human hands because collapsing commits is the one operation that destroys
work if it goes wrong, and because the owner is the one who signs off that the story is done.

## Message shape

```
💄 feat(design-system): establish the mobile-first design system (BR-02)

Replace the neutral starter-kit theme with the arrested "Corral" direction:
ultramarine as the single brand accent, chosen outside the semantic set the
four race statuses consume, and oklch so lightness can be aimed at WCAG AA
rather than guessed.

Add the runner status contract. The four statuses are a display set, not a
column: an abandon and a timeout both land on `eliminated` and differ only by
reason. So the enum is derived and carries no transitions.

The inherited auth and settings screens stay English; no story owns
translating them yet, and Q-01 is open on it.
```

- **gitmoji, then conventional commit** — `<emoji> type(scope): subject`. The gitmoji is this
  repo's own preference; **no Xefi skill mentions gitmoji**, so don't present it as a house
  rule. The `type(scope): subject` shape is the one shown in `global-no-ticket-references`.
- **Story key in the message** — either in the subject parentheses `(BR-02)` or as a trailer
  `Refs BR-02.`, **never both**. This is exactly where `global-no-ticket-references` wants it:
  banned in *code*, required in the *commit*, so `git blame` can still trace a line back.
- **Subject and body in English**, imperative mood, per D-14 — the interface is French, the
  repository is not.
- **No AI attribution** — no `Co-Authored-By` naming Claude, no "Generated with Claude Code"
  footer (`global-no-ai-attribution`). This overrides the harness default.
- **A substantial body on a multi-point story.** Say what changed and *why*; `git show` already
  lists the files. Three things earn their place: the reasoning behind a non-obvious choice,
  any discovery that constrains a later story, and what was deliberately left out.

## Anti-patterns

- **Running the squash.** Even when it looks obviously wanted, and even when the branch is
  unpushed. Provide the message; stop.
- **Silently folding a post-story commit into the story.** If work lands after the story's
  commits (a decision entry, a review fix), say so plainly so the owner knows what the squash
  range covers.
- **Both the subject parentheses and the `Refs` trailer.** Pick one.
- **A body that lists files.** That is `git show`'s job.
- **A French commit subject** on a repo whose code and commits are English.

## Response template

At the end of a story:

> Voici le message pour le squash — la branche porte ses N commits, arbre propre, rien poussé.
>
> ```
> <message>
> ```
>
> Je ne touche pas à l'historique : le squash est à toi.
