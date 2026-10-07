@AGENTS.md

# Claude Code notes

The workspace policy above is imported from [`AGENTS.md`](AGENTS.md), the single source of rules for every tool. This file adds only what is specific to Claude Code.

- **Project settings** are in `.claude/settings.json`: a permission allowlist for the `make` targets, `docker compose`, and read-only git and file commands, plus deny rules for secret-bearing files (`server/.env`, `compose.override.yaml`, `data/`). Machine-local overrides go in `.claude/settings.local.json` (gitignored).
- **Subagents** are defined in `.claude/agents/`. Use them instead of doing everything in the main context:
  - `explorer` (Haiku) for read-only search and summarization across many files.
  - `laravel-tester` (Sonnet) to run `make test` and report results.
  - `doc-verifier` (Sonnet) to check a `docs/` page against the code and refresh its `Last verified` line.
  - `policy-reviewer` (Opus) to review a diff against `AGENTS.md` and the ADRs before a PR is opened.
  Pin the smallest model that can do the job. Use the largest model only for design, security and judgment.
- **Laravel Boost** may be installed locally by contributors (`composer.json` lists `laravel/boost` in `require-dev`). Its generated `server/AGENTS.md`, `server/CLAUDE.md` and `server/boost.json` are gitignored on purpose and stay that way (ADR 0007). Treat Boost's Laravel guidelines as advisory; `AGENTS.md` and the ADRs take precedence where they differ. Boost's MCP server runs inside the `web` container: the committed `.mcp.json` at the repo root launches it with `docker compose exec`, so Claude Code gets schema, route and config introspection on any machine once `make up` has run.
- **Private material** lives in the team's private companion repository, not here. If a task needs the full security review or admin procedures, ask a maintainer; do not reconstruct that content in public files.
- **Scratch files** go in the session scratchpad directory, never in the repository.
- **Memory** is for facts that are not derivable from the repo: who owns the upstream sync this month, where the current database dump came from, decisions taken in conversation. Anything about code structure belongs in `docs/`, not in memory.
- **Commits and pushes** go to your own branch when a task is complete and verified; `.claude/settings.json` allows `git push origin <branch>` and denies pushes to `master`, `upstream`, force pushes and deletions. Commit messages follow Conventional Commits and end with the co-author trailer the harness supplies. A human opens and merges the PR.

## Quick orientation

```
AGENTS.md            workspace policy (binding)
CONTRIBUTING.md      branch, commit, PR and upstream workflow
docs/README.md       map of all documentation and reading order
docs/data-model/     declared source of truth for the schema
docs/decisions/      ADRs (binding once Accepted)
docs/upstream.md     relationship to cola-laits and the experiment register
Makefile             the only sanctioned way to run PHP, Composer, npm and tests
server/              the Laravel application
```
