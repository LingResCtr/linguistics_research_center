# Contributing

This document is the authoritative description of how work moves through this repository. `AGENTS.md` summarizes it for coding agents; where they differ, this file wins on process and `AGENTS.md` wins on code rules.

Last verified: 2026-09-16.

## 1. Who this repository is for

`LingResCtr/linguistics_research_center` is the Linguistics Research Center's experimental fork of the production repository `cola-laits/linguistics_research_center`, which UT LAITS maintains and deploys. Work here may diverge from upstream. When a direction proves out, we send it upstream as a clean pull request. Several teams contribute: LRC staff, LAITS, students, and coding agents working with any of them.

This repository is public. Material that should not be public, such as unremediated security findings, admin procedures and coordination logs, lives in the team's private companion repository (ADR 0010). Ask a maintainer for access.

If you are a member of the `LingResCtr` organization, work on branches in this repository. Otherwise fork it and open pull requests from your fork; the same rules apply.

## 2. Getting set up

1. Install Docker Desktop. PHP and Composer are not required on your machine.
2. Clone the repository and run `make init`, then `make up`.
3. Follow `docs/runbooks/local-development.md` for the proxy assumption, loading a database dump, and creating an admin user.
4. Read `AGENTS.md`, `docs/README.md` and `docs/architecture/overview.md`.

## 3. Branch model

| Branch | Purpose | Lifetime | Merges into |
|---|---|---|---|
| `master` | The fork's integration line. Protected: PR-only, one review, green CI. | Permanent | — |
| `upstream` | Read-only mirror of `cola-laits/master`. Fast-forward only. Never commit to it. | Permanent | `master` via a monthly sync PR |
| `feature/<topic>`, `fix/<topic>`, `docs/<topic>`, `chore/<topic>` | One concern each. | Days | `master` |
| `exp/<topic>` | A longer experiment that may or may not be kept. Requires an experiment issue (template provided) and an entry in `docs/upstream.md`. | Weeks | `master`, or deleted |
| `upstream-pr/<topic>` | A clean change for LAITS, cut from `upstream`, containing only what upstream should receive. | Until merged upstream | `cola-laits/master` |

Branch from `master` unless the table says otherwise. Rebase or merge `master` into your branch before opening a PR so the diff is only your change.

## 4. Commits

Conventional Commits, enforced by review (and by CI once ADR 0006 tooling lands):

```
type(scope): imperative subject under 72 characters

Optional body explaining why, not what.

Refs: #12
```

- Types: `feat`, `fix`, `docs`, `refactor`, `perf`, `test`, `chore`, `ci`, `build`. Add `!` after the type for a breaking change and a `BREAKING CHANGE:` footer explaining it.
- Scopes: `lexicon`, `eieol`, `ielex`, `cms`, `filament`, `admin2`, `import`, `i18n`, `cache`, `auth`, `ci`, `docs`, `deps`, `infra`. Add a scope to this list in the same PR if you need a new one.
- Reference upstream issues as `cola-laits/linguistics_research_center#NNN` and fork issues as `#NNN`.
- Commits authored with a coding agent carry the tool's co-author trailer.

## 5. Pull requests

1. Open the PR against `master` (or `upstream` for `upstream-pr/*`, see §7). Fill in every section of the template; delete none.
2. One concern per PR. If a refactor is needed first, send it first.
3. Include tests for any change under `server/app`, `server/routes`, `server/database` or `server/resources/views`.
4. Update documentation in the same PR: the relevant `docs/architecture` page, runbook, glossary entry, or an ADR when a decision is being made. Refresh the page's `Last verified` line.
5. Disclose agent assistance in the PR's "Agent assistance" section: tool, model, and what you personally verified. Agent-assisted PRs are welcome; undisclosed ones are not. An agent may push the branch; a person opens the PR and merges it.
6. Request a review from a code owner (`.github/CODEOWNERS`). Reviewers check behaviour, tests, docs, and conformance to `AGENTS.md` and the ADRs.
7. Merge method is **squash**. The squash message must itself be a valid Conventional Commit. The merger is always a human.

## 6. Review standards

Reviewers approve when all of the following hold:

- CI blocking jobs are green.
- The change does what the PR says and nothing else.
- Tests exercise the change, not just the happy path where inputs come from users.
- No hard rule in `AGENTS.md` §4 is broken.
- Documentation matches the new behaviour.
- For UI changes, screenshots are attached and the change targets the Filament admin or the `/lexicon` shell, not the legacy surfaces.

Reviews are of the work, not the author. Ask for changes with a reason and, where possible, a suggestion.

## 7. Working with upstream

Full procedure: `docs/runbooks/syncing-upstream.md`. Register of experiments and upstream submissions: `docs/upstream.md`.

**Syncing from upstream (monthly, human-owned).** The named owner for the month fetches `cola-laits/master`, fast-forwards the `upstream` branch, opens `chore/upstream-sync-YYYY-MM` from `master`, merges `upstream` into it, resolves conflicts, runs `make test` and `make npm ARGS="run build"`, and opens a PR. A failed sync is a blocking issue for the fork.

**Sending work upstream.** When an experiment is judged ready:

1. Open a heads-up issue on `cola-laits/linguistics_research_center` describing the change, especially if it touches schema, auth or the deploy.
2. Cut `upstream-pr/<topic>` from a fresh `upstream`. Cherry-pick or reimplement only what upstream should receive. Do not include fork-only conventions, docs, or CI.
3. Follow upstream's conventions in that PR. Include tests. Keep it small.
4. Open the PR against `cola-laits/master`, link the fork issue and the experiment entry, and record the submission in `docs/upstream.md`.

## 8. Issues and planning

- Issues are tracked on this repository. Use the templates: bug report, feature request, lexicon data issue, experiment proposal.
- The upstream tracker holds 48 long-standing requests from the LRC. `docs/roadmap.md` maps them onto the fork's phases. Reference them by full name so links resolve from either repository.
- Labels: `bug`, `enhancement`, `experiment`, `lexicon-data`, `upstream-candidate`, `sent-upstream`, `agent-assisted`, `good-first-issue`, `blocked`.

## 9. Releases and environments

The fork has no production deployment and, by decision on 2026-10-07, no shared hosted environment for now. Upstream deploys `master` to a test namespace on every push and production on `release-*` tags via Woodpecker; that pipeline does not run here. Every contributor runs the stack locally with Docker and a sanitized database dump in `data/` (`docs/runbooks/loading-a-database-dump.md`). The requirements are Docker Desktop (or Docker Engine with Compose v2) and `make`.

## 10. Licence

No licence has been chosen yet. Until one is, the code is under the default copyright of its authors and The University of Texas at Austin; treat it as source-available, not open source. Two separate decisions are pending: a code licence and a data licence for the lexicon content, both subject to UT's intellectual-property policy. Relicensing later needs every contributor's consent, so this is being resolved before further external contributions are solicited. Do not add a `LICENSE` file without a recorded decision (an ADR).
