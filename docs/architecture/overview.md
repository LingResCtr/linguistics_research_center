# LRC Platform Overview

Last verified: 2026-09-16 against commit c0fde76.

This document is a system-level map of the Linguistics Research Center (LRC) platform: what the application does, how a request moves through it, where each public and admin surface lives, how the code is laid out, and where the frontend build fits. It is meant to orient a new contributor before they read the domain model, auth, deploy, or data-cache documents.

## What the platform is

The LRC platform is a single Laravel application serving three unrelated content domains for the UT Austin Linguistics Research Center:

1. **The multi-lexicon comparative-etymology framework** at `/lexicon/{slug}` — a general model for comparative dictionaries (etyma, reflexes, semantic fields, sources) that currently backs IELEX, SEMITILEX, MAYALEX, and DRAVIDILEX.
2. **The legacy Indo-European Lexicon** at `/lex` — the original, purpose-built IELEX implementation that predates the multi-lexicon framework and still runs in parallel with it.
3. **EIEOL ("Early Indo-European Online")** at `/eieol` — a series of language-learning lessons (grammar sections, glossed texts, interlinear glosses) organized into `EieolSeries` → `EieolLesson`.

Alongside these there is a small CMS (`Page`, `Book`, `BookSection`) for static content and site navigation, and an issue-tracking feature (`Issue`, `IssueComment`) used by both admins.

Two administrative surfaces exist side by side: **Filament**, at `/admin`, is the target admin for everything going forward, and a **legacy Vue/Blade admin**, at `/admin2`, still owns EIEOL lesson editing and is being retired in place (Filament's navigation links out to it — see `server/app/Providers/Filament/AdminPanelProvider.php`).

## Request flow

```
browser -> k8s ingress (namespace lrc-test or lrc) -> PHP-FPM container (server/public/index.php)
        -> Laravel HTTP kernel -> route (routes/web.php or routes/api.php)
        -> Controller (Http/Controllers/Public*Controller, admin2 controllers, or Livewire/Filament)
        -> Eloquent models -> MariaDB 10.6
        -> Blade view (or Filament/Livewire component) -> response
```

Ingress and pod topology are Kubernetes-managed outside this repository (deployed by the Woodpecker pipeline — see `docs/architecture/deploy.md`). Inside the container, the public surfaces (`/`, `/lex`, `/eieol`, `/lexicon/{slug}`) are conventional Blade-rendered controller actions with no Livewire involved. Livewire enters only through Filament: every `/admin` page is a Filament resource or page, which is itself a Livewire component tree, and the one exception — the legacy lesson editor at `/admin2` — is a hand-written Vue 3 SPA mounted into a Blade page, unrelated to Livewire. `routes/api.php` is unmodified Laravel boilerplate (`/api/user` behind `auth:api`); the only real JSON API is `GET /api/v1/lexicon/{lex_slug}/data`, a DataTables server-side endpoint registered in `routes/web.php` and handled by `PublicLexiconController::ajaxData`.

## The three public surfaces and two admin surfaces

| Surface | Route prefix | Controller(s) | Layout | Status |
|---|---|---|---|---|
| CMS pages / books | `/`, `/books/{slug}` | `PublicPageController`, `PublicBookController` | `layout.blade.php` (2015 CLA/Foundation template) | Legacy shell, still primary for site chrome |
| Legacy IE Lexicon | `/lex` | `PublicIELexController` | `layout.blade.php` | Legacy; superseded in principle by IELEX under `/lexicon/ielex`, but still live and diverging |
| EIEOL lessons | `/eieol`, `/eieol_printable`, `/eieol_toc`, `/eieol_master_gloss`, `/eieol_base_form_dictionary`, `/eieol_english_meaning_index` | `PublicEieolController` | `layout.blade.php` | Active, no newer replacement planned |
| Multi-lexicon framework | `/lexicon/{lex_slug}` | `PublicLexiconController` | `lexicon.layout-home` / `lexicon.layout` (Bootstrap 5.0.2, vendored) | Target public surface for all lexicon data (IELEX, SEMITILEX, MAYALEX, DRAVIDILEX) |
| Filament admin | `/admin` | Filament Resources/Pages (`app/Filament/**`) | Filament's own theme, built via Vite | Target admin |
| Legacy admin | `/admin2` | `EieolSeriesController`, `EieolLessonController`, `EieolGrammarController`, `EieolGlossedTextController`, `EieolGlossController`, `EieolHeadWordController`, `IssueController`, `FilesController`, etc. | Vue 3 SPA (`resources/js`) mounted in a Blade shell | Legacy; being retired, currently the only way to edit EIEOL lesson content in detail |

The `/admin/issue` and `/admin2/issues` routes both point at `IssueController`; `/admin` additionally links out to `/admin2` for anything not yet ported to Filament (see `AdminPanelProvider`'s `navigationItems`).

## Code layout of `server/`

| Path | What lives there |
|---|---|
| `app/Models` | All Eloquent models — Lexicon (`Lex*`), EIEOL (`Eieol*`), CMS (`Page`, `Book`, `BookSection`), users/permissions (`User`, `UserPermission`), and support (`Issue`, `IsoLanguage`). See `docs/architecture/domain-model.md`. |
| `app/Http/Controllers` | Public-surface controllers (`Public*Controller`) and legacy `/admin2` mutating controllers. No Filament controllers — Filament routes to Resources/Pages directly. |
| `app/Filament/Resources` | One directory per admin-managed model (`LexLexicons`, `LexEtymas`, `LexReflexes`, `EieolSeries`, `EieolLessons`, `Users`, `Pages`, `Books`, `BookSections`, etc.), each with its own `Schemas`, `Tables`, and `Pages` subfolders in Filament 5's file-per-concern layout. |
| `app/Filament/Pages` | Custom, non-resource admin pages: `LexiconUtilities` (CSV import/data-cache tooling), `LexiconHelp`, `ManageSiteSettings`. |
| `app/Filament/Widgets` | `SeriesEditorNavWidget`, the dashboard widget that links into `/admin2` per-series lesson editing. |
| `app/Console/Commands` | Artisan commands, chiefly the lexicon CSV importers (`ImportDravidilexCSV`, `ImportMayalexCSV`, `ImportProtoSemiticCsv`) and `GenerateLexiconDataCache`. |
| `app/Console/Commands/import_data/` | The CSV fixtures those importers read (Mayalex, SemitiLEX, Buck semantic category/field lookups, a `dravidilex` subfolder), resolved by working-directory-relative paths — see the data-cache/importers docs. |
| `app/Policies` | Spatie-permission-backed authorization policies, one per model that has one — coverage is uneven; see `docs/architecture/auth.md`. |
| `app/Settings` | `SiteSettings`, a `spatie/laravel-settings` class backing `ManageSiteSettings` (group `site`; e.g. `show_donation_popup`). |
| `resources/views/lexicon` | The Blade views and shared layouts for the multi-lexicon public framework (`layout.blade.php`, `layout-home`, `layout-dict`, `layout-sidebar`, `lex_home`, `lex_etymon`, `lex_word`, `lex_field`, `lex_language`, `lex_protolanguage_home`, `lex_data`, `lex_page`). |
| `resources/views/*.blade.php` (top level) | The legacy IELEX (`lex_*`), EIEOL (`eieol*`), and CMS (`index`, `page`, `book_section`) views, all sharing `layout.blade.php`. |
| `resources/js` | The legacy Vue 3 `/admin2` lesson-editor SPA plus `admin.js` (the Filament/Vite entry). |
| `resources/lang` | Locale strings: `en.json`, `es.json`, `te.json` (flat key/value JSON used by Laravel's `__()`), an `en/` directory (older PHP-array translation files), and `README_translations.txt`, the one piece of project-authored documentation besides this set. |
| `database/settings` | Migrations for `spatie/laravel-settings`-backed settings (currently one: `2025_08_14_122412_create_site_settings.php`). |
| `database/migrations` | The full schema history — this is the closest thing to a schema reference for tables that lack a matching model comment. |

## Frontend build

`server/vite.config.js` declares exactly three entry points via the `laravel-vite-plugin`: `resources/sass/admin.scss`, `resources/js/admin.js`, and `resources/css/filament/admin/theme.css`, plus a static-copy step that vendors the `tinymce` npm package into `public/build`. That is the entire Vite-managed surface — it covers Filament's admin theme and whatever `admin.js` wires up (Vue 3 registration for `/admin2` mount points, per `resources/js`). Everything else the public pages load — Bootstrap 5.0.2 and jQuery 3.7 for the lexicon framework, Foundation 5.5.2, jQuery 1.11, DataTables, TinyMCE 6.8.2, CKEditor 4, Font Awesome, and a standalone Vue 2 bundle (`public/js/app.js`) used by the `laravel/ui` auth views — is checked into `public/{assets,css,js,fonts}` as vendored, unbuilt files and loaded directly from Blade with `<script src="/js/...">`/`<link>` tags, bypassing Vite entirely. `public/js/filament`, `public/css/filament`, and `public/fonts/filament` are also committed, regenerated by `filament:upgrade` on every `composer install` (see `composer.json`'s `post-autoload-dump` scripts).

## Cross-cutting concerns

- **Translatable fields.** Most Lexicon models (`LexLexicon`, `LexLanguageFamily`, `LexLanguageSubFamily`, `LexLanguage`, `LexEtyma`, `LexReflex`, `LexSemanticCategory`, `LexSemanticField`, `LexPartOfSpeech`, `LexEtymaExtraData`, `LexReflexExtraData`, `LexReflexCrossReference`) plus `Page` use `spatie/laravel-translatable`'s `HasTranslations` trait to store per-locale JSON in specific columns (`$translatable`). The Filament admin exposes this through `lara-zeus/spatie-translatable-plugin`, configured in `AdminPanelProvider` for locales `en`, `es`, `te`. See `docs/architecture/i18n.md` for how a viewer locale differs from these admin/content locales.
- **Permissions.** `spatie/laravel-permission` backs all Filament policies via `$user->can('permission_name')`; a parallel, unrelated role-name check (`User::isAdmin()`, `hasRole('Site Manager'|'EIEOL Manager'|'Lexicon Manager')`) gates navigation visibility and one Filament page (`LexiconUtilities`). See `docs/architecture/auth.md` for the full permission-name inventory and the gap in `/admin2`.
- **Settings.** `spatie/laravel-settings` backs `App\Settings\SiteSettings` (group `site`), currently just the donation-popup flag/text, edited through the Filament `ManageSiteSettings` page.
- **Sentry.** Configured via `config/sentry.php` (`sentry/sentry-laravel`), reading `SENTRY_LARAVEL_DSN`/`SENTRY_DSN` and a full set of breadcrumb/tracing toggles; a separate browser-side Sentry snippet (2019-era, version 5.6.3) is loaded directly in `layout_header.blade.php` via a raw `env('SENTRY_JS_DSN')` call in a Blade file, which means it is silently blanked whenever `php artisan config:cache` runs.

## Known architectural debts

The platform carries a substantial, already-catalogued list of architectural and code-quality debts — three public front ends with no shared shell, a home-grown per-request data cache with no primary key, model relations that reference non-existent columns, `hasOne` used where `belongsTo` is correct on pivot models, no queue/schedule/cache usage anywhere in the request path, and three CSV importers with three different "create missing language" implementations. Rather than restate them, see:

- §3 ("Architecture") of `docs/reports/2026-09-15-platform-review.md` for the model-layer issues; the private security review has the fuller backend bug catalogue.
- The private security review for the frontend/library sprawl and CI gaps.
- §9 and §10 for a prioritized remediation order.

## Related docs

- [`domain-model.md`](./domain-model.md) — every Eloquent model, grouped and diagrammed.
- [`auth.md`](./auth.md) — the two auth stacks, permission names, and policy coverage.
- [`deploy.md`](./deploy.md) — Dockerfile, compose, Woodpecker, and this fork's CI story.
- [`data-cache.md`](./data-cache.md) — the `lex_lexicon_data_cache` design and its regeneration path.
- [`importers.md`](./importers.md) — the three CSV importers and the Filament `LexiconUtilities` import path.
- [`i18n.md`](./i18n.md) — viewer languages vs. admin/content locales vs. translatable columns.
