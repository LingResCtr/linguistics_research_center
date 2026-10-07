# 0001. Record architecture decisions

- Status: Accepted
- Date: 2026-09-16
- Deciders: Danny Law (LRC)
- Related: `docs/README.md`, `AGENTS.md` §2

## Context

The repository has twelve years of history, one long-term maintainer, and no written record of why things are the way they are. Several teams and coding agents will now work on it in parallel. Decisions that live only in one person's head or in chat get relitigated, and agents in particular will happily "improve" a deliberate choice they cannot see.

## Decision

We record decisions as Architecture Decision Records in `docs/decisions/`, using `0000-template.md`. Accepted ADRs are binding for humans and agents. Changing a decision means writing a superseding ADR. ADRs are dated records and are not edited after acceptance except for the status line.

## Consequences

- Newcomers and agents can find the reasoning behind conventions without asking.
- Each PR that changes architecture or process carries a small documentation cost.
- The first batch of ADRs (0002–0008) records decisions already implicit in the code or made during workspace setup, so that they become visible.

## Alternatives considered

- A wiki: not versioned with the code, not seen by agents reading the repo.
- Comments in code: scattered, not discoverable, no status.
