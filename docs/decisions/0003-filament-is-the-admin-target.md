# 0003. Filament is the admin target; `/admin2` is retired, not extended

- Status: Accepted
- Date: 2026-09-16
- Deciders: Danny Law (LRC), recording a direction already taken upstream since 2025
- Revisit when: ADR 0009 selects a non-Laravel platform for new work; until then this holds.
- Related: review §3 "Three front ends, three shells, two admins", §4 "Vue lesson editor"; `docs/architecture/overview.md`, `docs/architecture/auth.md`

## Context

Two admin surfaces coexist. Filament 5 at `/admin` covers every lexicon model, pages, books, users and settings, with policies. The legacy surface at `/admin2` (Blade plus a Vue lesson editor and an issue tracker) still edits EIEOL lessons, glosses, grammar and head words, predates the policy layer, and has broken delete and save behaviour. Upstream replaced Backpack with Filament in 2025 and links to `/admin2` from Filament as a stopgap.

## Decision

Filament is the only admin surface for new work. The `/admin2` surface is retired feature by feature: each EIEOL editing capability is rebuilt as a Filament resource, page or Livewire component, then its `/admin2` route is removed. Until removed, `/admin2` receives security and defect fixes only (tracked in the private security review and the roadmap) and nothing else.

## Consequences

- New admin features have one place to live and inherit Filament's auth, forms, tables and translatable plugin.
- The EIEOL lesson editor and issue tracker are a sizeable porting effort; it is Phase 5 of `docs/roadmap.md`.
- Hardening of `/admin2` is still required in Phase 1 and is not blocked on the port.

## Alternatives considered

- Modernise `/admin2` in place (Vue 3 rewrite): keeps two admins and two auth stacks indefinitely.
- Remove `/admin2` immediately: EIEOL editors would lose their only editor.
