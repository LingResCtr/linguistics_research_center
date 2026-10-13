# 0011. Tests in proportion to risk

- Status: Proposed
- Date: 2026-10-13
- Deciders: Danny Law (LRC)
- Related: supersedes decision 3 of ADR 0005; `AGENTS.md` §6, `CONTRIBUTING.md` §5–6, `.github/PULL_REQUEST_TEMPLATE.md`, ADR 0007

## Context

ADR 0005 decision 3 requires every PR that touches `server/app`, `server/routes`, `server/database` or `server/resources/views` to add or update at least one Feature test, with a smoke test as the minimum. It was written when the repository had no tests. The baseline now exists (fixtures, smoke tests for every public route family, blocking CI), and the first fixes made against it show both sides of the rule. Tests found real defects that review had not: a local test environment that differed from CI, migrations that no longer reproduce production's schema, and a regression in a fix under review. But the rule is triggered by which paths a PR touches, not by what could break. It sets a floor with no ceiling, and nothing allows a test to be retired. A typo in a Blade view needs a test, a change to authorization needs no more than that, and agents (ADR 0007) tend to read a floor as an invitation to exhaustive coverage. Left alone, the suite grows in size and upkeep faster than it grows in value, and test work competes with platform work.

## Decision

1. **A PR adds or updates tests when it:**
   - fixes a bug: at least one test that fails without the fix;
   - adds or changes behaviour that users or other code rely on;
   - touches authorization, authentication, input validation, or anything request input reaches (queries, redirects, file storage);
   - adds or changes a migration, the data cache, an importer or console command, or a service under `server/app/Services`;
   - adds a public route: at least a smoke test that it responds.
2. **No new test is required for** wording, translation or markup-only view changes; documentation; dependency updates that CI already exercises; and refactors or moves that existing tests already cover. The PR says which applies, and for a refactor names the covering tests.
3. **Proportion.** Test the behaviour the change adds or fixes, at the level that would catch it breaking again: usually one Feature test per rule, with one representative case per kind of input. Enumerate every entry point (each route or action, allowed and refused) only at a security boundary, where missing one case leaves a hole. When the test code is larger than the change it covers, the PR says why.
4. **Retiring tests.** A test may be removed together with the behaviour it covers (for example when a legacy surface is retired under ADR 0003 or ADR 0004), or when another test covers the same behaviour. The PR names each removed test and the reason. Disabling, skipping or deleting a test to make CI pass remains forbidden (ADR 0005 decision 5).
5. **Review in both directions.** Human and agent reviewers check that the tests this ADR requires are present, and that tests are not out of proportion to the change.
6. Everything else in ADR 0005 is unchanged: the CI jobs and their blocking status, real MariaDB, the fixture lexicon, the `lrc_test` database, Pint and Larastan.

## Consequences

- Copy fixes, view tweaks, docs and covered refactors ship without test ceremony, and reviewers have a stated basis for asking for fewer tests as well as more.
- The rule becomes a judgment rather than a path check. Reviewers must decide which category a change falls into, and no CI job can enforce it (none enforced the old rule either).
- Under-testing can creep back. The required categories cover the places where regressions have been found so far (authorization, input handling, schema, cache, importers), and every bug fix still brings a regression test.
- This PR updates `AGENTS.md` §6, `CONTRIBUTING.md` §5–6, the PR template and the `policy-reviewer` agent to match. CI does not change.

## Alternatives considered

- Keep ADR 0005 decision 3: it keeps producing tests whose main purpose is to satisfy the rule.
- A coverage threshold: it measures lines executed, not behaviour checked, and ratchets in the same way.
- No requirement, review only: it loses the floor the fork still needs for security and schema work while the suite is thin.
- Exempt the legacy surfaces (`/admin2`, `/lex`): they still serve users, and fixes there need regression tests like any other.
