# 0009. Platform direction: Laravel now, framework-neutral data model, Django later

- Status: Accepted
- Date: 2026-09-16
- Deciders: Danny Law (LRC)
- Related: ADR 0002, 0003, 0004; `docs/data-model/README.md`; `docs/vision.md`
- Revisit when: the new data model is implemented and stable in Laravel, or a feasibility analysis (below) is written up.

## Context

The LRC has a substantially developed new data model in preparation that the current schema does not express. A longer-term move from Laravel to Django is a live possibility. Doing both at once would change two variables together and would also end the path for contributing fixes to the production Laravel repository. The order of operations decides what the fork builds for the next year.

The sound default is to change one thing at a time: implement the new data model in Laravel, against a working reference, and only then consider a framework change. That default has one failure mode: if the new model invalidates most of the existing PHP, the Laravel implementation is throwaway work. The deciding question is therefore what fraction of the current code survives the new model.

## Decision

1. **Near-term work is Laravel.** The new data model is implemented in the Laravel application first. ADRs 0002, 0003 and 0004 stand.
2. **The schema is the durable artifact.** The new data model is specified framework-neutrally in `docs/data-model/` as an entity-relationship description plus plain SQL DDL, and that directory is the declared source of truth. The Laravel migrations and Eloquent models implement it; they do not define it.
3. **New PHP is written thin.** Domain logic goes in plain service and value classes; Eloquent handles persistence; Filament and Blade handle presentation. Framework-idiomatic magic (accessors carrying business rules, model events, closures in Filament schemas) is avoided for anything that would need to be ported.
4. **A feasibility analysis is written before any Django work starts.** It answers one question: what fraction of the current codebase survives the new data model, counted by tables touched, models that change shape, logic in services versus controllers and views, and scaffolding versus hand-written domain code. Its result and reasoning are recorded as a superseding or confirming ADR.
5. **The database engine is decided as its own step.** Production is MariaDB 10.6 with `utf8mb4_unicode_ci`. Any change of engine (for example to PostgreSQL) is a separate migration with its own verification, decided before data-model implementation begins, because collation and full-text behaviour differ and this data is almost entirely non-ASCII. Until decided, the target is MariaDB.
6. **A shared-database cutover is kept possible.** If two applications ever run side by side, they should be able to share one database and migrate route by route behind a proxy. This is only feasible if point 2 is honoured.

## Consequences

- The fork can keep sending fixes upstream while the new model is built.
- The data-model work produces an artifact that outlives both frameworks and gives agents an authoritative schema to work from.
- A later Django port becomes largely mechanical: introspect an existing schema, port plain services, rebuild presentation.
- Contributors must resist Laravel conveniences that embed business rules in the framework; `AGENTS.md` §5 states the rule and review enforces it.
- The engine decision may constrain the data-model design (JSON columns, generated columns, collation) and should not be left implicit.

## Alternatives considered

- Migrate to Django first, then build the new model there: right only if code survival is low; the feasibility analysis is the gate for reopening this.
- Build both in parallel: doubles the work and splits a small team.
- Laravel forever: not ruled out, but not assumed; the framework-neutral schema costs little and keeps the option open.
