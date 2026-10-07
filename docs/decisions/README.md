# Architecture Decision Records

An ADR records one decision that shapes the codebase or the way we work: why it was made, what it rules out, and what it costs. Accepted ADRs are **binding** for contributors and coding agents. To change one, write a new ADR that supersedes it; do not edit the old one beyond its status line.

## When to write one

- Introducing, replacing or removing a dependency, framework, table, or public URL pattern
- Choosing between two viable designs for a cross-cutting concern (caching, auth, i18n, search)
- Adopting or changing a process rule (branching, CI gates, review)
- Deciding to keep, send upstream, or drop an experiment when the reason is not obvious

Small implementation choices do not need an ADR; a sentence in the PR is enough.

## How

1. Copy [`0000-template.md`](0000-template.md) to `NNNN-short-kebab-title.md` with the next number.
2. Status starts as `Proposed`. Open it in the PR that introduces the change, or on its own for process decisions.
3. On merge the status becomes `Accepted`. Later: `Superseded by NNNN` or `Deprecated`.
4. Add a row to the index below.

Keep it short: a page, rarely two. Context and consequences matter more than prose.

## Index

| # | Title | Status | Date |
|---|---|---|---|
| [0001](0001-record-architecture-decisions.md) | Record architecture decisions | Accepted | 2026-09-16 |
| [0002](0002-fork-governance-and-branch-model.md) | Fork governance and branch model | Accepted | 2026-09-16 |
| [0003](0003-filament-is-the-admin-target.md) | Filament is the admin target; `/admin2` is retired, not extended | Accepted | 2026-09-16 |
| [0004](0004-lexicon-shell-is-the-public-target.md) | The `/lexicon/{slug}` shell is the public target; `/lex` is legacy | Accepted | 2026-09-16 |
| [0005](0005-testing-and-ci-baseline.md) | Testing and CI baseline | Accepted | 2026-09-16 |
| [0006](0006-conventional-commits-and-pr-workflow.md) | Conventional Commits and PR workflow | Accepted | 2026-09-16 |
| [0007](0007-coding-agents-policy.md) | Coding agents policy | Accepted | 2026-09-16 |
| [0008](0008-secrets-and-local-configuration.md) | Secrets and local configuration | Accepted | 2026-09-16 |
| [0009](0009-platform-direction-laravel-now-django-later.md) | Platform direction: Laravel now, framework-neutral data model, Django later | Accepted | 2026-09-16 |
| [0010](0010-public-repository-and-private-companion.md) | Public repository hygiene and the private companion repository | Accepted | 2026-09-16 |
