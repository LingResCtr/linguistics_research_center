# Linguistics Research Center platform

The web platform of the [Linguistics Research Center](https://lrc.la.utexas.edu) at The University of Texas at Austin: the Indo-European Lexicon, the newer multi-lexicon framework (SEMITILEX, MAYALEX, DRAVIDILEX), the Early Indo-European Online (EIEOL) lesson series, and the site's pages and books. A Laravel 13 / Filament 5 application backed by MariaDB.

[![CI](https://github.com/LingResCtr/linguistics_research_center/actions/workflows/ci.yml/badge.svg)](https://github.com/LingResCtr/linguistics_research_center/actions/workflows/ci.yml)

## About this repository

This is the LRC's **experimental fork** of [`cola-laits/linguistics_research_center`](https://github.com/cola-laits/linguistics_research_center), the production repository maintained by UT LAITS. Work here may diverge from upstream so that ideas can be tried at full scale. Directions that prove out are contributed back as pull requests. How that works: [`docs/upstream.md`](docs/upstream.md).

## Quick start

Requires Docker Desktop. PHP and Composer are not needed on your machine.

```bash
make init   # first time: local config, dependencies, image build
make up     # start the app and database
make test   # run the test suite
```

Then read [`docs/runbooks/local-development.md`](docs/runbooks/local-development.md) for the reverse-proxy assumption, loading a database dump, and creating an admin user. `make help` lists every target.

## Repository layout

```
server/            the Laravel application (app code, views, migrations, tests)
docs/              documentation: architecture, runbooks, decisions, reports
  decisions/       Architecture Decision Records (binding)
  reports/         dated, immutable assessments such as the 2026-09 audit
compose.yaml       local stack: web (PHP) and db (MariaDB 10.6)
Dockerfile         production image, three stages
.woodpecker/       upstream's deploy pipeline (does not run on this fork)
.github/           CI, issue and PR templates, code owners
Makefile           canonical commands for humans, agents and CI
AGENTS.md          workspace policy for contributors and coding agents
```

## Documentation

Start at [`docs/README.md`](docs/README.md). Highlights:

- [Vision](docs/vision.md) — who the platform serves and what good looks like
- [Architecture overview](docs/architecture/overview.md) — surfaces, code layout, cross-cutting concerns
- [Roadmap](docs/roadmap.md) — phased plan for the fork, mapped to the audit and upstream issues
- [Platform review, September 2026](docs/reports/2026-09-15-platform-review.md) — the baseline assessment (public version)
- [Data model](docs/data-model/README.md) — the declared source of truth for the schema
- [Glossary](docs/glossary.md) — etymon, reflex, gloss, and the rest

## Contributing

Read [`CONTRIBUTING.md`](CONTRIBUTING.md) for the branch model, commit convention, review process and how to work with upstream. Coding agents are welcome and must follow [`AGENTS.md`](AGENTS.md). Security concerns: [`SECURITY.md`](SECURITY.md).

## Licence

Not yet specified. Until a licence is chosen and recorded, the code is under the default copyright of its authors and The University of Texas at Austin.
