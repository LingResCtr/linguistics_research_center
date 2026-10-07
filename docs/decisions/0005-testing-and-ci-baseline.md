# 0005. Testing and CI baseline

- Status: Accepted
- Date: 2026-09-16
- Deciders: Danny Law (LRC)
- Related: `.github/workflows/ci.yml`, `AGENTS.md` §6, review §6

## Context

The repository has no application tests (only Laravel's two example tests), no factories, an empty seeder, no linter configuration, and a CI pipeline that builds and deploys without checking anything. Upstream's Woodpecker pipeline does not run on this fork at all. Several contributors, including agents, are about to make changes at pace. Without a safety net the fork will accumulate regressions it cannot see.

## Decision

1. CI is GitHub Actions in `.github/workflows/ci.yml`, run on every PR to `master` and `exp/**` and on pushes to `master`. Jobs: PHP tests against a real MariaDB 10.6 service container with the production collation (`utf8mb4_unicode_ci`), not SQLite, because the application relies on MariaDB JSON functions, `REGEXP` and collation-dependent ordering; Pint style check; secret scan (gitleaks); frontend build; Docker image build. CI uses the `pull_request` trigger and needs no repository secrets, so fork PRs get the same checks.
2. All jobs are blocking: PHP tests, Pint and Larastan, secret scan, frontend build, Docker image build. (PHP tests and lint were non-blocking from 2026-09-16 until the baseline landed on 2026-10-07.) No job may be made non-blocking.
3. Every PR that touches `server/app`, `server/routes`, `server/database` or `server/resources/views` adds or updates at least one Feature test. Smoke tests that assert a public route returns 200 are the minimum acceptable coverage.
4. The baseline PR adds: a small fixture lexicon via factories and a seeder so tests and local development have data; smoke tests for every public route family; a Pint configuration (`server/pint.json`, Laravel preset) and a single repo-wide style commit; Larastan at level 2 (`server/phpstan.neon`) with a committed `phpstan-baseline.neon` (80 pre-existing findings at level 2 as of 2026-09-22) so only new findings fail. Both are dev dependencies; `make lint` runs both as CI does. The fixture is representative, not random: multiple scripts and writing directions (Latin with diacritics, Greek, Hebrew or Arabic right-to-left, Telugu), reconstructed forms with conventional notation, etyma with many reflexes and with none, disputed or uncertain etymologies, sources with and without page numbers, translated and untranslated glosses, and the hardest normalization and sorting cases. The same fixture is the onboarding dataset and the agent sandbox.
5. Contributors run Pint only on the files they changed; a repo-wide style pass happens once in the baseline PR. CI's Pint job checks the whole tree and is informational until that baseline exists, then blocking. Tests run against a dedicated `lrc_test` database locally (`make test` creates it and sets `DB_DATABASE`) and against the CI service container, never against a developer's working database; `RefreshDatabase` is therefore safe to use. Tests are never skipped or deleted to make CI pass.

## Consequences

- Regressions in routes, builds and the image are caught before merge from day one.
- A short window exists in which test failures do not block; the roadmap tracks its closure.
- The one-time style commit will conflict with upstream on the next sync; it is scheduled right after a sync to minimise that.
- Upstream may want the tests and CI; they are upstream candidates once stable.

## Alternatives considered

- Make tests blocking immediately: would block every PR until the baseline lands, including the baseline itself.
- Reuse Woodpecker: it is LAITS infrastructure and not available to the fork.
- No CI until tests exist: leaves the frontend and image builds unchecked for no reason.
