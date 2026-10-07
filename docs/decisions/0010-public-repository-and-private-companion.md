# 0010. Public repository hygiene and the private companion repository

- Status: Accepted
- Date: 2026-09-16
- Deciders: Danny Law (LRC)
- Related: `SECURITY.md`, `CONTRIBUTING.md` §1, `AGENTS.md` §4 rule 11, ADR 0008

## Context

The fork inherited public visibility from upstream. Every commit on every pushed branch is public immediately, and history cannot be un-published without rewriting it for everyone. At the same time the team needs to coordinate on things that must not be public: a platform review that enumerates unremediated vulnerabilities in the live site, admin procedures, access lists, coordination logs, and drafts not yet fit to publish. One-time setup material also has no lasting value in a public history.

## Decision

1. **The public repository holds what contributors and agents need to do the work:** code, tests, architecture pages, runbooks for recurring tasks, ADRs, the data model, the public version of reports, and policy. Nothing else.
2. **A private companion repository in the `LingResCtr` organization** holds material that should not be public: the full security review and remediation tracking, GitHub and org admin procedures, the upstream sync log, drafts of ADRs and data-model work, and dated working notes. Its layout is described in its own README. It is private, not a vault: no credentials there either.
3. **Movement is one-way and by move, not copy.** When a draft settles it moves into the public repo. When a public file turns out to be sensitive it moves to the private repo and the public file is replaced by a pointer. Two copies means one is soon wrong.
4. **Security findings are published only after remediation.** Until then public docs may state rules for new code and may call a component legacy or low quality, but may not enumerate exploitable weaknesses. The public review carries stubs for withheld sections so that section references still resolve.
5. **Names.** Public files refer to people only by their public GitHub identity or role. No personal contact details beyond a monitored, role-based address.
6. **Before the first public commit of new material,** the tree is scanned for secrets (gitleaks) and reviewed against this ADR. The same scan runs in CI on every PR.

## Consequences

- Contributors and agents have a simple test: would this be fine on the front page of the repo? If not, it goes to the private repo.
- Some duplication of pointers, and a second repository to keep tidy.
- Section-numbered references to the review keep working in public docs even though some sections are withheld.
- The audit's UI, documentation and roadmap sections remain public, which is where most contributors need them.

## Alternatives considered

- Make the fork private: possible in principle, but the upstream is public, the code is already public, and a private fork cannot receive fork PRs from students or LAITS in the usual way.
- Keep sensitive material only on individual machines: does not support team coordination and is lost when people leave.
- A `private/` directory in the public clone, gitignored: invisible to teammates, and agents might cite its paths in public files.
