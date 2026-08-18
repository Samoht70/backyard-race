---
name: run-commands
description: Use whenever you write or are about to write a `php artisan`, `composer`, or Laravel test-runner command in a Xefi Laravel project. RUN THE COMMAND yourself instead of writing it as text for the user to copy. Detection order — (1) if Claude is already inside the project's container and `php artisan --version` works, run directly; (2) if `./vendor/bin/sail` exists, use Sail (`./vendor/bin/sail artisan ...`); (3) if `docker-compose.yml` / `compose.yaml` declares a Laravel service, use `docker compose exec SERVICE php artisan ...`; (4) otherwise ASK the user which runtime. NEVER guess between detected paths — if Sail exists but the container isn't running, ASK before falling back. Confirm before destructive commands (`migrate:fresh`, `db:wipe`). Long-running daemons (`queue:work`, `horizon`) run in background, never blocking. Triggers on `php artisan ...`, `composer ...`, `pest` / `phpunit` invocations, and on phrasings like "run the migrations", "run the tests", "install dependencies".
---

# Laravel: Run the command — don't write it for the user to copy-paste

## The rule

**For new Xefi Laravel work, when an `artisan` / `composer` / test command would naturally appear in the response, Claude RUNS it through the right wrapper instead of writing the command as text. The wrapper is detected, not guessed — Claude-in-container first, then Sail, then Docker compose, then ASK. When detection is ambiguous or the chosen path errors, ASK the user; never silently fall back to a different path.**

```bash
# Forbidden — writing the command as text and stopping
# "To apply this migration, run:"
#     php artisan migrate

# OK — detect and execute
# Step 1: detect runtime (see below)
# Step 2: invoke via the right wrapper
./vendor/bin/sail artisan migrate
# or
docker compose exec laravel.test php artisan migrate
# or
php artisan migrate
# Step 3: read the output, surface failures, iterate
```

The skill applies to `php artisan ...`, `composer ...`, `./vendor/bin/pest`, `./vendor/bin/phpunit`, and other Laravel-dev one-shots. It does NOT cover `npm` / `pnpm` (frontend tooling), `mysql` / `redis-cli` (direct service access), or anything else outside the PHP runtime.

## Why this rule exists

Writing commands as text for the user to copy is the path of least resistance, but it's costly:

- **Claude can't see the output.** A `php artisan migrate` that fails with `SQLSTATE[42S01]: Base table or view already exists` produces a useful diagnostic — but only if Claude actually ran it. Writing the command and stopping forces the user to copy, paste, hit a wall, paste the error back. Every loop is a roundtrip.
- **The "right" command form depends on the runtime.** `php artisan migrate` is wrong if the project uses Sail (the local PHP can't see the Sail database). `./vendor/bin/sail artisan migrate` is wrong if Claude is already inside the Sail container (the script tries to call into `docker` from inside the container and fails). The detection logic has to live somewhere — better here than in the user's head.
- **Wrong runtime = wrong DB / queue / redis.** Running `php artisan migrate` directly when the project uses Sail can hit a stale local DB that the user doesn't even know is there. The migration "works", the next test fails for incomprehensible reasons. Detecting Sail and routing through it avoids this entirely.
- **Long-running commands need orchestration.** `php artisan queue:work` blocks until killed; running it in the foreground hangs the conversation. The right form is background + Monitor, or a timeout, or `php artisan queue:work --once`. Writing the bare `queue:work` for the user to copy ends with someone Ctrl-C'ing it ten minutes later.
- **Destructive commands need confirmation.** `php artisan osdd:seed --fresh` (or the flat `migrate:fresh --seed`) wipes the database. Routing through this skill means Claude pauses to confirm before invoking; writing the command as text means the user copy-pastes it without re-reading.
- **Tests deserve the same treatment as code.** By the stack-agnostic rule that a generated test must be run, after generating a test Claude must execute it. This skill is the Laravel-specific HOW for that obligation — Sail / Docker / direct invocation.

## The detection logic

In order, stop at the first match:

1. **Claude-in-container + `php artisan --version` works.** Tested by running `php artisan --version` in the project root via `Bash`. If it exits 0 and prints a Laravel version, Claude is in the right environment — run directly with `php artisan ...`, `composer ...`, `./vendor/bin/pest`. The "Claude-in-container" qualifier matters: even if `php artisan --version` works on the host, prefer the project's declared runtime (Sail / Docker) unless the harness was explicitly started inside the container. Signals that Claude is in the container: `/.dockerenv` exists, hostname matches the container pattern, cwd is the container's app path (often `/var/www/html`).

2. **`./vendor/bin/sail` exists and is executable.** Use Sail for every command:

   ```bash
   ./vendor/bin/sail artisan migrate
   ./vendor/bin/sail composer install
   ./vendor/bin/sail test                          # shorthand for artisan test
   ./vendor/bin/sail pest                          # if Pest is the test runner
   ./vendor/bin/sail php -v                         # the runtime PHP, not the host
   ```

   Before the first Sail command in a session, verify the stack is up: `./vendor/bin/sail ps` should show the services running. If they're down, ASK the user before bringing them up — `./vendor/bin/sail up -d` is fine to suggest, but starting infra is the user's call.

3. **`docker-compose.yml` / `compose.yaml` declares a Laravel service.** Identify the service name by inspecting the compose file — common names: `laravel.test` (Sail-style), `app`, `php`, `web`, the project name. When ambiguous, list candidates and ASK which one. Then:

   ```bash
   docker compose exec laravel.test php artisan migrate
   docker compose exec app composer install
   docker compose exec app php artisan test
   ```

   As with Sail, verify the container is running (`docker compose ps`) before exec; if it's not, ASK before `docker compose up -d`.

4. **None of the above.** ASK the user. Don't fall back to bare `php artisan` on the host — the user may not have system PHP at all, or it may not match the project's required version / extensions. Tell them what was tried and what wasn't found.

## When the chosen path errors

When the detected wrapper fails — Sail container is down, compose service has a different name, `php artisan` exits non-zero — STOP and ASK. Do not silently try the next path in the priority list; the failure usually tells you something specific (the user hasn't run `sail up` yet, the migration depends on a service that's also down, the test DB needs to be created). Surface the error verbatim, propose the most likely fix, and wait.

The one exception: detection that errors before even attempting the command (e.g. `./vendor/bin/sail` exists but is not executable, indicating a permissions issue) can be diagnosed and worked around in the same turn — those aren't runtime errors, they're setup errors.

## Destructive commands — confirm first

These need explicit confirmation in the conversation before Claude invokes them, even when the user previously approved similar commands:

- `php artisan migrate:fresh` (with or without `--seed`) — wipes all tables and re-runs migrations
- `php artisan migrate:reset` — rolls back every migration
- `php artisan migrate:rollback --step=N` — fine without confirmation only if N is small and the user explicitly asked for the rollback
- `php artisan db:wipe` — drops all tables
- `php artisan db:seed --class=...` against a non-dev env — confirm regardless of dev/prod
- `composer update` (no `--lock`) — changes lockfile, can break other developers
- `php artisan queue:flush`, `php artisan cache:clear`, `php artisan config:clear` against shared dev — confirm if multiple devs share the env

Read-only / non-destructive commands run without confirmation: `migrate:status`, `route:list`, `tinker --execute=...`, `test`, `pest`, `composer install`, `composer show`, `artisan about`, `artisan --version`.

## Long-running daemons — background, never blocking

Commands that don't return until killed:

- `php artisan queue:work`, `queue:listen`
- `php artisan horizon`
- `php artisan schedule:work`
- `php artisan serve` (rare in a Sail/Docker project, but possible)
- `php artisan websockets:serve`, `reverb:start`, similar

These run in background with the harness's `run_in_background` flag, or with a `--once` / `--stop-when-empty` qualifier when the goal is "process the current queue and exit". Never start one of these in the foreground — the conversation hangs until something kills the process.

For one-shot debugging (e.g. "process the next job and show me the output"), prefer `queue:work --once --tries=1` over starting a daemon and tailing logs.

## Tests specifically — chain to the obligation

After writing or modifying a test file (per the stack-agnostic run-after-generating rule and the `laravel:automated-tests` skill), the test MUST be executed and the output read. The mechanics here:

```bash
# Detect runtime, then one of:
./vendor/bin/sail test --filter=TheTest                    # Sail
docker compose exec laravel.test php artisan test --filter=TheTest   # Docker
php artisan test --filter=TheTest                          # direct
```

Run with `--filter=` scoped to the new test first (fast feedback), then re-run the full suite if the new test passes to confirm nothing regressed. If the test fails, read the output, fix the code or the test, re-run. Don't report "tests added" without having run them green.

For Pest specifically: `./vendor/bin/sail pest` / `docker compose exec ... ./vendor/bin/pest` / `./vendor/bin/pest`. Pest's `--filter` accepts the test description string.

## Anti-patterns to refuse for new code

- **Writing `php artisan migrate` (or any command) as text and stopping.** Detect the runtime and invoke it. If detection fails, ASK — don't leave it for the user.
- **Hard-coding `./vendor/bin/sail ...` without checking the Sail binary exists.** It may be a plain Docker project, or a direct-PHP project. Detect first.
- **Hard-coding `docker compose exec app ...` without checking the service name.** `app` is convention, not guarantee. Read the compose file.
- **Running on the host when the project uses Sail.** Even if `php artisan --version` works on the host, the local PHP may not see the Sail-managed DB / queue / redis. Prefer Sail when its binary is present, unless the harness is provably inside the Sail container.
- **`Mail::raw(...)` to "trigger the queue and see what happens"** as part of running commands. That's not a command — it's code, and it belongs in a Tinker session or a feature test, not a Bash invocation.
- **Foreground daemons.** `php artisan queue:work` in the foreground blocks the conversation; the harness eventually times out. Background or `--once`.
- **Destructive commands without confirmation.** `migrate:fresh` wipes data; pause and confirm before invoking, even on dev.
- **`composer install --no-dev` on a dev box** "to match production". Wrong env — install dev deps locally; CI runs `--no-dev`.

## Working with existing projects

- **Don't change the project's runtime choice unprompted.** If the project uses Sail, run Sail. If it uses raw Docker, run Docker. If it uses Laravel Herd / Valet / direct PHP, run direct. The skill detects what's there; it doesn't migrate the project.
- **When the user's project has both Sail AND a custom compose file**, Sail wins (it's a Laravel-native abstraction the project explicitly opted into via `composer require laravel/sail`). Mention what was detected so the user can correct.
- **DDEV / Lando / Laradock**: these are valid runtimes too but rarer in Xefi projects. If the user has one of them set up, ASK before trying anything — the wrapper command differs (`ddev artisan ...`, `lando artisan ...`).
- **Boost MCP**: if Boost is installed (see the `laravel:boost` skill), the `laravel/boost` package exposes some artisan-equivalent operations through MCP (DB queries, log tails, model introspection) without needing a shell exec at all. Prefer Boost MCP for those specific operations when available; shell exec stays the path for general `artisan` / `composer` / test invocations.

## Related skills

- The stack-agnostic "after generating a test, you MUST execute it" rule. This skill is the Laravel-specific HOW for that obligation.
- the `laravel:automated-tests` skill — Feature vs Unit tier choice and placement under `tests/`. Once you've chosen the tier and written the test, this skill is how you execute it.
- the `laravel:boost` skill — when present, Boost's MCP server can perform some artisan-equivalent operations without shell exec. Prefer Boost MCP for DB / log / model introspection.
- the `laravel:no-fakerphp` skill — factories used in tests this skill executes use the Xefi binding, not upstream Faker.
- the `laravel:osdd-scaffolding` skill — OSDD-layer scaffolding commands (`make:layer`, etc. if the project has them) follow the same detection logic.
- the `laravel:crud-via-rest-api` skill — lomkit/laravel-rest-api ships its own artisan generators; invoke them through the right wrapper like any other artisan command.

## Response template

When the user asks Claude to do something that requires a command:

> Running `<command>` via [Sail / Docker compose / directly] — checking output now.

When detection is ambiguous or fails:

> I'd like to run `<command>` for this. I see [`./vendor/bin/sail` exists / a `docker-compose.yml` with services X, Y, Z / `php` in PATH but no Sail or compose / nothing recognisable]. Which runtime should I use — Sail, `docker compose exec <service>`, direct, or something else?

When the user requests a destructive command:

> `<command>` is destructive — it'll [wipe the DB / reset migrations / clear all caches]. Confirm before I run it? On a dev box this is usually fine; just want to make sure this is the right moment.

When a command produces an error:

> The command failed with: `<verbatim error>`. Most likely cause is [diagnosis]. Want me to [proposed fix], or check something else first?
