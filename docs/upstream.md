# Upstream relationship and experiment register

Last verified: 2026-09-16.

This page records how `LingResCtr/linguistics_research_center` relates to the production repository, who is responsible for keeping them in sync, and what is currently in flight in each direction. The decision behind it is [ADR 0002](decisions/0002-fork-governance-and-branch-model.md). The procedures are in [runbooks/syncing-upstream.md](runbooks/syncing-upstream.md).

## The two repositories

| | `cola-laits/linguistics_research_center` | `LingResCtr/linguistics_research_center` |
|---|---|---|
| Role | Canonical; deployed to `lrc.la.utexas.edu` | Experimental fork; may diverge |
| Owner | UT LAITS (maintainer Chris Pittman) | Linguistics Research Center |
| CI | Woodpecker: build, deploy test on push to master, deploy prod on `release-*` tag | GitHub Actions: tests, lint, frontend and image build |
| Issues | 48 open, mostly LRC feature requests, 2017–2025 | Fork issues and experiment proposals |
| History | 2014 → ; migrated from UT GitHub Enterprise 2026-02-24 | Forked 2026-09-10 |

Git remotes on a developer clone: `origin` → LingResCtr, `upstream` → cola-laits.

## Branch model

| Branch | Meaning |
|---|---|
| `master` | The fork's integration line. Diverges from upstream as experiments land. |
| `upstream` | Read-only mirror of `cola-laits/master`, fast-forwarded on each sync. Diff against it to see exactly what the fork has changed. |
| `exp/<topic>` | An experiment. Listed below while open. |
| `upstream-pr/<topic>` | A clean change for LAITS, cut from `upstream`. Listed below until merged or closed. |

## Sync cadence and ownership

Upstream is merged into `master` **monthly**, owned by a named human, not an agent. A failed or skipped sync is a blocking issue for the fork.

The month-by-month log (owner, PR, upstream commit, notes) is kept in the private companion repository under `coordination/upstream-sync-log.md`. The fork point is upstream commit `c0fde76` (2026-09-10).
## Experiments in flight

One row per `exp/*` branch. Status: Proposed · Building · Evaluating · Sent upstream · Kept fork-only · Dropped.

| Experiment | Branch | Issue | Hypothesis | Kill criterion | Status | Upstream intent |
|---|---|---|---|---|---|---|
| Workspace and safety net | `chore/workspace-setup` | — | Docs, policy, CI and a Makefile make the repo legible to humans and agents and make change safe | Contributors still cannot run the site from the README | Building | Partial: README, tests and CI hygiene are upstream candidates; fork governance docs are not |

## Sent upstream

| Change | Fork issue / PR | Upstream PR | Opened | Outcome |
|---|---|---|---|---|
| (none yet) | | | | |

## Intentional divergences from upstream

Things the fork does differently on purpose, with the ADR that says why. Anyone preparing an `upstream-pr/*` branch strips these out.

| Divergence | ADR | Notes |
|---|---|---|
| Root `AGENTS.md`, `CLAUDE.md`, `CONTRIBUTING.md`, `docs/`, `.github/`, `Makefile` | 0001, 0002, 0005, 0006, 0007 | Fork governance. README, CI and tests may be offered upstream separately. |
| `APP_KEY` removed from `compose.yaml`; `compose.override.yaml.example` added | 0008 | Upstream candidate. |
| Conventional Commits | 0006 | Upstream has no enforced convention. Squash messages to upstream may be restyled. |
| Generated Filament assets untracked and gitignored (`server/public/{js,css,fonts}/filament`) | 0005 | Upstream candidate; the image regenerates them at build time. |
| `server/.env.example` rewritten; `CACHE_DRIVER` → `CACHE_STORE` in `compose.yaml` | 0005, 0008 | Upstream candidate; fixes a silently ignored cache setting. |
| `laravel/pint` and `larastan/larastan` as dev dependencies with configs and baseline | 0005 | Upstream may or may not want them. |
| Root `.mcp.json` launching Laravel Boost inside the container | 0007 | Fork-only convenience. |
| `Makefile` runs artisan as `www-data` and `make up` creates `/tmp/laravel` owned by `www-data` | — | Works around a local-dev 500 (`tempnam()` notice) caused by root-owned compiled views when artisan runs as root under compose. Upstream candidate: the same compose setup is affected. |

## Etiquette when sending work upstream

- Open a heads-up issue on the upstream repository first for anything touching schema, authentication, the deploy, or a public URL pattern.
- One concern per PR, cut from a fresh `upstream`, with tests, following upstream's existing conventions in that PR.
- Reference the fork issue and this register. Record the submission above.
- Do not send fork-only governance files, CI, or docs unless LAITS has asked for them.
