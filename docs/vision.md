# Vision

Last verified: 2026-09-16. This page states intent, not current behaviour; for what the system does today see [architecture/overview.md](architecture/overview.md) and the [September 2026 platform review](reports/2026-09-15-platform-review.md).

## Mission

The Linguistics Research Center publishes scholarly reference material on the historical development of languages: the Indo-European Lexicon built on Pokorny, newer comparative lexicons for Semitic, Mayan and Dravidian, and the Early Indo-European Online lesson series that teaches ancient languages through glossed texts. The platform exists so that this material is **findable, readable, citable and correct**, for a first-year student on a phone and for a comparative philologist checking a cognate set.

## Who we serve

| Audience | What they come for | What they need from the platform |
|---|---|---|
| General public and students | Look up a word, follow an etymology, work through a lesson | One search box, the word as the heading, plain-language gloss first, a page that loads in under a second on a phone, one consistent site |
| Scholars | Cognate sets, reflexes by language, sources, reproducible queries | Correct script rendering with `lang` and `dir`, philological fonts, stable and citable URLs, full-result exports, filters that survive in the URL, a bibliography per lexicon |
| Lexicon editors (LRC staff, student assistants) | Enter and correct etyma, reflexes, sources, translations | A single admin (Filament) with clear permissions, documented column configuration, bulk import that is idempotent, cache regeneration that is automatic or one click and non-blocking |
| EIEOL authors and glossers | Write lessons, attach glosses, review issues | A lesson editor whose delete and save actually work, an issue tracker with filters, offline glossing workflows |
| Translators | Add and maintain Spanish, Telugu and future viewer languages | One place to add a locale, translation keys that do not encode display names |
| LAITS operators | Deploy and keep it running | A tested `master`, no secrets in git, a cache and queue that behave, a repository a new developer can run from the README |

## Product principles

These guide design choices in the fork and are the standard an experiment is judged against.

1. **The word is the heading.** A dictionary entry is a dictionary article, not a form. Headword first, in a script-correct typeface, then gloss, then apparatus.
2. **One site, one shell.** A reader moving from a lesson to a lexicon entry should not feel they changed websites. The `/lexicon/{slug}` shell is the future for every public dictionary surface; `/lex` is legacy.
3. **Search is the front door.** Every page has a search box that searches headwords and meanings across languages.
4. **Citable by default.** Every entry has a permalink, a citation block, and a last-modified date. Numeric IDs in URLs give way to slugs where stable.
5. **Fast on a phone.** No page ships the whole dictionary. Indices are cached and paginated by letter. Server time for an entry is measured in tens of milliseconds, not seconds.
6. **Data-driven, not deploy-driven.** Adding a lexicon, a column or a viewer language is configuration edited in Filament, not a code change per slug.
7. **Correct scripts, correct sorting.** Hebrew, Arabic, Syriac, Ethiopic, Telugu, Tamil and reconstructed forms render and sort as a philologist expects.
8. **One admin.** Filament is the admin. The legacy `/admin2` editor is retired feature by feature, never extended.
9. **Safe to change.** Every change carries a test. Authorization is explicit. Inputs are validated. Secrets are outside git.
10. **Legible to newcomers and agents.** The repository explains itself: architecture pages, runbooks, decisions and a glossary that stay true to the code.

## What this fork is for

The fork exists to try things at full scale that would be hard to justify as incremental changes to production: a redesigned entry page, a letter-indexed sidebar, a global search, a data-driven column model, a consolidated public shell, a queued cache. Each experiment is:

- **proposed** as an issue with a hypothesis and a kill criterion,
- **built** on an `exp/*` branch with tests and a write-up,
- **judged** against the principles above and real data from a sanitized dump,
- then **sent upstream** as a clean PR, **kept fork-only** with a recorded reason, or **dropped** with a short retrospective in `docs/reports/`.

The fork is not the production deployment, not a place for unsanitized production data, and not a second source of truth for content. Content lives in the production database and is edited there.

## How we will know it is working

- A new contributor runs the site from the README in under an hour and finds the answer to "where does X live" in the docs, not by asking.
- The five things the platform review says to fix first are fixed, upstream, within the first phases of the roadmap.
- The English IELEX page weighs tens of kilobytes, not 1.5 MB, and a reader can search from any page.
- Scholars cite entries by URL and the citation still resolves a year later.
- LAITS receives small, tested, well-described PRs and the sync from upstream is routine, not a fire.
