# Documentation

Everything a contributor or coding agent needs to understand the LRC platform and how we work on it. Policy lives in [`../AGENTS.md`](../AGENTS.md) and [`../CONTRIBUTING.md`](../CONTRIBUTING.md); this directory holds the context those policies assume.

## Reading order

1. [Vision](vision.md) — who the platform serves and what "better" means here.
2. [Architecture overview](architecture/overview.md) — surfaces, code layout, cross-cutting concerns.
3. [Glossary](glossary.md) — the linguistic and project vocabulary.
4. The architecture page for your area (below).
5. [Decisions](decisions/README.md) — what has already been decided, and how to propose a change.
6. [Upstream](upstream.md) — how this fork relates to production and what is in flight.
7. [Roadmap](roadmap.md) — what we intend to do, in what order.

## Two kinds of document

**Living documents** describe the system as it is and are edited in place. Each carries a `Last verified: <date> against commit <sha>` line near the top. If you check a page against the code, update that line even when nothing else changed. If the code and the page disagree, the code is right; fix the page in the same PR that touched the code.

**Dated records** describe a moment: [reports](reports/README.md) and accepted [decisions](decisions/README.md). They are not edited after the fact. A newer report or a superseding ADR replaces them.

**Not here.** This repository is public. Unremediated security findings, admin procedures and coordination logs live in the team's private companion repository (ADR 0010). Public pages may point to it by name but never reproduce its content.

## Map

| Path | What it is | Kind |
|---|---|---|
| [vision.md](vision.md) | Mission, audiences, product principles, what the fork is for | Living |
| [glossary.md](glossary.md) | Definitions of domain and project terms | Living |
| [roadmap.md](roadmap.md) | Phased plan mapped to the audit and upstream issues | Living |
| [upstream.md](upstream.md) | Fork governance, branch model, experiment and submission register | Living |
| [data-model/](data-model/README.md) | Declared source of truth for the schema: current DDL, and the new data model as it lands | Living |
| [architecture/overview.md](architecture/overview.md) | System map: request flow, surfaces, code layout, frontend build | Living |
| [architecture/domain-model.md](architecture/domain-model.md) | Every Eloquent model, grouped and diagrammed; invariants | Living |
| [architecture/auth.md](architecture/auth.md) | Auth stacks, permissions actually used, policy coverage, rules for new code | Living |
| [architecture/data-cache.md](architecture/data-cache.md) | The denormalized table behind public search: schema, generation, staleness | Living |
| [architecture/importers.md](architecture/importers.md) | Import commands, their inputs and hazards; guidance for new ones | Living |
| [architecture/i18n.md](architecture/i18n.md) | UI strings and content translation; adding a viewer language | Living |
| [architecture/deploy.md](architecture/deploy.md) | Docker image, local compose, upstream's pipeline, this fork's CI | Living |
| [runbooks/local-development.md](runbooks/local-development.md) | First run through to a working admin login | Living |
| [runbooks/loading-a-database-dump.md](runbooks/loading-a-database-dump.md) | Sanitized dump into the local database, first admin user | Living |
| [runbooks/regenerating-lexicon-cache.md](runbooks/regenerating-lexicon-cache.md) | When and how to rebuild the public data cache | Living |
| [runbooks/adding-a-lexicon.md](runbooks/adding-a-lexicon.md) | Every touch point for a new lexicon slug, as it is today | Living |
| [runbooks/adding-a-viewer-language.md](runbooks/adding-a-viewer-language.md) | Ordered checklist for a new UI locale | Living |
| [runbooks/syncing-upstream.md](runbooks/syncing-upstream.md) | Monthly sync from cola-laits and how to send work back | Living |
| [decisions/](decisions/README.md) | Architecture Decision Records | Dated |
| [reports/](reports/README.md) | Audits, measurements, retrospectives | Dated |

## Writing documentation here

- Plain prose, short sections, a table where it is denser than paragraphs.
- Cite paths relative to the repository root in backticks: `server/app/Models/LexLexicon.php`. Line numbers only for things unlikely to move.
- Write "Unknown as of <date>" rather than guessing. An honest gap is useful; a plausible invention is not.
- Do not duplicate: link to the audit, the glossary, or another page instead of restating it.
- A runbook is a numbered procedure that ends with a "Verify" step and a "Troubleshooting" section.
- Documentation changes ship in the same PR as the code they describe.
