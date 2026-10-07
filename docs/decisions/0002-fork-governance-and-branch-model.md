# 0002. Fork governance and branch model

- Status: Accepted
- Date: 2026-09-16
- Deciders: Danny Law (LRC)
- Related: `docs/upstream.md`, `docs/runbooks/syncing-upstream.md`, `CONTRIBUTING.md` §3 and §7, ADR 0009
- Revisit when: ADR 0009's platform direction changes to a non-Laravel target, at which point the upstream PR path applies only to fixes in the shared Laravel code and the sync becomes a data and schema parity exercise.

## Context

`cola-laits/linguistics_research_center` is the production repository, maintained and deployed by UT LAITS. `LingResCtr/linguistics_research_center` was forked from it on 2026-09-10 so that the LRC can try substantial changes without each one having to be justified as an incremental production change. Upstream continues to evolve: its maintainer upgrades the framework roughly yearly and touches shared files often. A fork that drifts unchecked becomes impossible to merge in either direction.

## Decision

1. The fork is **experimental by design and may diverge** from upstream. Proven directions are contributed back as clean PRs; unproven ones stay on the fork or are dropped.
2. Branches: `master` is the fork's protected integration line. `upstream` is a fast-forward-only mirror of `cola-laits/master`. `exp/<topic>` holds an experiment and needs an experiment issue and a row in `docs/upstream.md`. `feature/`, `fix/`, `docs/`, `chore/` are short-lived and target `master`. `upstream-pr/<topic>` is cut from `upstream` and contains only what upstream should receive.
3. Upstream is merged into `master` **monthly**, by **merge** not rebase, owned by a **named human** recorded in `docs/upstream.md`. A failed or skipped sync is a blocking issue.
4. Every intentional divergence from upstream is listed in `docs/upstream.md` with the ADR that justifies it, so that upstream PR branches can strip it.
5. No upstream PR is ever opened from a `master`-based branch.

## Consequences

- The LRC can build and evaluate large changes at full scale on real (sanitized) data.
- Merge cost grows with divergence; the monthly sync and the "new code in new files" guidance in `AGENTS.md` §5 keep it bounded.
- LAITS receives small, tested PRs cut against their own tree rather than a firehose diff.
- The fork has no production deployment and no Woodpecker; CI is GitHub Actions (ADR 0005). There is no shared hosted environment for now (decided 2026-10-07): evaluation happens on each contributor's local Compose stack with a sanitized dump.

## Alternatives considered

- Never diverge, mirror upstream exactly: rejected by the LRC; it prevents the experiments the fork exists for.
- Make the fork canonical and have LAITS retarget deploys: a larger organisational change; can be revisited once the fork has a track record.
- Rebase `master` onto upstream: rewrites shared history and breaks every open branch.
