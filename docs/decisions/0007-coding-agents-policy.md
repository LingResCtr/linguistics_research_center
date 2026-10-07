# 0007. Coding agents policy

- Status: Accepted
- Date: 2026-09-16
- Deciders: Danny Law (LRC)
- Related: `AGENTS.md`, `CLAUDE.md`, `.claude/`, `CONTRIBUTING.md` §5

## Context

Coding agents (Claude Code, Codex, Junie, Laravel Boost and others) are already used on this codebase; upstream's maintainer gitignored their configuration rather than committing it. The fork intends to use agents heavily and with several teams. Without shared, versioned policy each agent session rediscovers the environment, guesses conventions, and can undo deliberate decisions. Without disclosure, reviewers cannot calibrate their scrutiny.

## Decision

1. A root `AGENTS.md` is the single, versioned workspace policy for all agents and tools. Tool-specific files (`CLAUDE.md`, `.claude/`) point to it and add only tool-specific configuration. `AGENTS.md` is binding alongside the ADRs.
2. Laravel Boost's generated files under `server/` stay gitignored, matching upstream, so they never appear in upstream PRs. Its Laravel guidelines are advisory; `AGENTS.md` and the ADRs take precedence.
3. Agent-assisted PRs disclose the tool and model and what the human verified. A human always reviews and merges. Agents may commit to and push their own branches on `origin` so that the human's role is to open and merge the PR; they never push to `master` or `upstream`, never force-push, never merge, and never open upstream PRs. Branch rulesets on GitHub enforce the `master` and `upstream` protections independently of agent configuration.
4. Model tiering: exploration, search and summarisation use small models via subagents; drafting and test runs use mid-size models; design, security review and judgment use the largest models. Parallel large-model agents over the same files are avoided.
5. Agents stop and ask before changing auth, altering existing tables or the data cache schema, deleting many files, adding dependencies, touching a hosted environment, or opening upstream PRs.
6. Agents cite `path:line` evidence, keep documentation `Last verified` lines current, and draft ADRs for architectural changes.

## Consequences

- Any agent, in any tool, starts from the same facts and rules; sessions are cheaper and safer.
- Reviewers know when to look harder.
- Policy has a maintenance cost; it lives in one file and changes by PR.
- Upstream PRs carry no agent configuration unless LAITS asks for it.

## Alternatives considered

- Per-tool files only: duplicates rules, drifts, and misses tools we did not anticipate.
- No agent use: forgoes the capacity the fork depends on.
- Agents may merge their own PRs once CI passes: rejected; CI does not yet cover enough, and review is where disclosure is acted on.
