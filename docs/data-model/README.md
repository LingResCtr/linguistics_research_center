# Data model

Last verified: 2026-09-16 against commit c0fde76.

This directory is the **declared source of truth for the database schema** (ADR 0009). Laravel migrations and Eloquent models implement what is specified here; they do not define it. Agents and contributors read the schema from here rather than inferring it from whichever model file they open first. When this directory and the code disagree, one of them is wrong, and the PR that notices says which.

## Contents

| File | What it is | Status |
|---|---|---|
| [`current-schema.sql`](current-schema.sql) | DDL of the schema as it exists today, exported from a freshly migrated MariaDB 10.6 database (`mariadb-dump --no-data`, auto-increment counters stripped). 47 tables, `utf8mb4_unicode_ci`. | Descriptive: regenerate after any migration lands, using `make schema-export`. |
| `new-model/` | The new data model in preparation: entity-relationship description, plain SQL DDL, and the mapping from current tables to new ones. | Not yet in this repository. Drafts live in the private companion repository until they are ready to be reviewed publicly. |

For the meaning of the current tables see [`../architecture/domain-model.md`](../architecture/domain-model.md), which groups them into the lexicon, EIEOL, CMS, user and support clusters with diagrams.

## Rules

- **Framework-neutral.** Specify tables, columns, types, constraints, indexes and collations in SQL and prose. Do not specify Eloquent relationships, casts or Filament forms here; those belong in the implementation and in `domain-model.md`.
- **Engine explicit.** The target engine is MariaDB 10.6 with `utf8mb4_unicode_ci` until ADR 0009's engine decision says otherwise. Anything engine-specific (JSON functions, generated columns, collation choices) is called out where it is used.
- **Migrations follow the spec.** A schema change starts as a change to the spec in this directory, reviewed in the same PR as the migration that implements it. Deployed migrations are never edited (`AGENTS.md` §4).
- **Every entity has a stable identifier strategy** stated in the spec, because the public site needs citable URLs (`docs/vision.md`, principle 4).
- **Collation and sorting are design decisions, not defaults.** Etymological data is almost entirely non-ASCII; sort order and uniqueness comparisons depend on collation. The spec states, per text column that is sorted or compared, which collation applies and why.

## Regenerating `current-schema.sql`

With the local stack running and migrated:

```bash
make schema-export
```

This runs `mariadb-dump --no-data` against the local `db` service, strips auto-increment counters, and writes the file here. Commit it in the same PR as the migration that changed it.
