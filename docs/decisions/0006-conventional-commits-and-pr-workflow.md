# 0006. Conventional Commits and PR workflow

- Status: Accepted
- Date: 2026-09-16
- Deciders: Danny Law (LRC)
- Related: `CONTRIBUTING.md` §4–§6, `.github/PULL_REQUEST_TEMPLATE.md`

## Context

Upstream has no enforced commit convention; about two fifths of recent commits use gitmoji prefixes, a habit of the single maintainer, and the rest are free text. The fork will have several contributors and agents. Machine-readable history matters here more than usual: it drives changelogs, lets us filter by area when assembling upstream PR branches, and gives agents an unambiguous format to produce.

## Decision

1. Commits follow Conventional Commits: `type(scope): subject`, with `!` and a `BREAKING CHANGE:` footer for breaking changes. Types and the scope list are in `CONTRIBUTING.md` §4.
2. Pull requests target `master`, use the template, address one concern, include tests and documentation, and disclose agent assistance.
3. Merges are **squash** merges performed by a human after one approving review and green blocking CI. The squash message is itself a valid Conventional Commit.
4. Branches named `upstream-pr/*` may restyle commits to match upstream's expectations; the fork's `master` history is unaffected.
5. Enforcement is by review now; a commitlint check may be added to CI later without a new ADR.

## Consequences

- History is filterable by type and scope; changelog generation is possible.
- Agents produce the format reliably without per-session instruction.
- Contributors used to gitmoji must switch on the fork; the cost is small and the PR template reminds them.

## Alternatives considered

- Gitmoji: no grammar, no tooling, one person's habit, rendering varies across tools.
- Free text: unfilterable; makes upstream PR assembly harder.
- Merge commits instead of squash: preserves noisy intermediate history and complicates cherry-picking to upstream branches.
