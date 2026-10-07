# Reports

Dated, point-in-time records. A report describes what was true when it was written and is **not edited afterwards**; a newer report supersedes it. Living descriptions of the system belong in `docs/architecture/`, procedures in `docs/runbooks/`, and decisions in `docs/decisions/`.

## What belongs here

- Audits and assessments of the codebase, UI, accessibility or security
- Measurements: page weight, server time, query counts, before/after comparisons
- Usability sessions and user feedback write-ups
- Experiment retrospectives when an `exp/*` branch is closed, kept or dropped
- Upstream sync notes when a sync was non-trivial

Security findings about unfixed problems do not belong here while the repository is public; they go to the private companion repository (ADR 0010) and are published once remediated.

## Naming

`YYYY-MM-DD-<short-kebab-title>.md`, dated by when the observations were made. Each report opens with what was examined, at which commit, by what method, and what was deliberately out of scope.

## Index

| Date | Report | Scope |
|---|---|---|
| 2026-09-15 | [Platform review](2026-09-15-platform-review.md) | Read-only review of the whole codebase and the public lexicon UI at commit `c0fde76`, with live checks against production. Public version: §2, §4 and §5 are withheld until remediated and live in the private companion repository. §7 to §10 give the UI findings and the recommended order of work. |
