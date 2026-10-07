# Security policy

## Reporting a vulnerability

Do not open a public issue for a security problem.

- **Problems in the production site** (`lrc.la.utexas.edu`) are the responsibility of UT LAITS, who maintain and deploy `cola-laits/linguistics_research_center`. Report them through the upstream repository's private vulnerability reporting, or through UT Austin's Information Security Office.
- **Problems in this fork's code** that are not present upstream: use GitHub's private vulnerability reporting on this repository ("Security" tab → "Report a vulnerability"), or contact a code owner listed in `.github/CODEOWNERS` directly.

Include the affected route or file, steps to reproduce, and the impact you believe it has. You will get an acknowledgement, and we will tell you whether the fix will be made here, upstream, or both.

## Known findings

A read-only platform review from September 2026 recorded security findings in the shared codebase. Because this repository is public and the findings concern a live site, that detail is held in the team's private companion repository until remediated (ADR 0010); the public version at `docs/reports/2026-09-15-platform-review.md` withholds those sections. Remediation is Phase 1 of `docs/roadmap.md`. Do not describe unfixed findings in public issues, PRs or docs; say "see the private security review" and reference it by section number.

## Rules for contributors

- No secrets in git, ever: application keys, database passwords, Sentry DSNs, tokens. Use `compose.override.yaml` and `server/.env`, both gitignored. See `AGENTS.md` §4.
- Database dumps used for development must have the `users`, `password_resets` and `sessions` tables dropped or anonymized before they leave the machine that produced them. They live in `data/`, which is gitignored.
- New mutating routes need authorization (`authorize()` with a Policy) and validated input (a FormRequest). See `docs/architecture/auth.md`.
- Dependencies are updated by Dependabot monthly; security advisories are merged as soon as CI passes.
