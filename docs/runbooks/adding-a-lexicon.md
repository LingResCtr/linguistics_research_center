# Adding a lexicon

Last verified: 2026-09-16 against commit c0fde76.

**This is the procedure the codebase currently requires — not the recommended design.** Several of these steps exist only because `LexLexicon::getDataColumns()` hard-codes column definitions per slug in PHP rather than storing them in the database. See the platform review §3 ("The data cache design") for the recommended data-driven alternative; nothing below should be read as an endorsement of the current shape, only an accurate description of it.

## What "adding a lexicon" means today

A lexicon is a row in `lex_lexicon` (`id`, `slug`, `name`, `protolang_name`, `viewer_lang_options`, `landing_page_content`, `protolanguage_page_content`) that everything else — languages, etyma, reflexes, sources, semantic categories/fields, parts of speech — hangs off of via a `lexicon_id` foreign key (added by `database/migrations/2022_04_05_100415_create_lex_lexicon_table.php`). Creating the row is easy; making the public pages and Data page actually work for it is the part with hidden requirements.

## Steps

1. **Create the `lex_lexicon` row.** Filament: `/admin/lex-lexicons/create` (requires the `manage_lexicon` permission — see `docs/architecture/i18n.md`/roles in `docs/runbooks/loading-a-database-dump.md`). Fill in `name`, `slug`, `protolang_name` (translatable — use the locale switcher for `es`/`te` versions), and `viewer_lang_options` (comma-separated locale codes, e.g. `en, es`). Alternatively create it via one of the importers, which do this step themselves (`docs/architecture/importers.md`).

2. **Add a case to `LexLexicon::getDataColumns()`** (`server/app/Models/LexLexicon.php:53-140`). This is a single PHP method with an `if ($this->slug === '...') { ... } else if (...) { ... } else { <generic 6-column fallback> }` chain. Add a new `else if ($this->slug === 'your_slug')` branch listing every `(object)['display_name' => '...', 'name' => '...']` pair the Data page and cache generator should expose for this lexicon. Without this step, your new lexicon silently falls through to the six generic columns (`root`, `meaning`, `semantic_tag`, `etymon`, `language`, `part_of_speech`), which may be wrong for lexicon-specific data.

3. **Match `GenerateLexiconDataCache::handleLexiconId()`'s computed-column branches to your new column names**, if any of your columns should be computed from a relation rather than read from `lex_reflex_extra_data` by key. The method special-cases `meaning`, `part_of_speech`, `semantic_tag`, `root`, `etymon`, `language` by name; anything else falls through to `$reflex->extra_data->where('key', $column_desc->name)->first()?->value`, which requires the extra-data `key` string to match your column's `name` exactly.

4. **Add translation keys for every column header**, in all three locale files (`server/resources/lang/en.json`, `es.json`, `te.json`): `lexicon.pages.data.column_header_<Display Name>`, with `<Display Name>` matching the `display_name` string from step 2 verbatim, spaces and capitalization included (see `docs/architecture/i18n.md`). Missing a key doesn't error — it just falls back to Laravel's raw-key rendering, which looks broken but isn't fatal.

5. **Set `viewer_lang_options`** on the lexicon row (step 1, or edit it afterward) to whichever of `en`/`es`/`te` the lexicon should offer. This drives both the language switcher on public pages (`LexLexicon::getViewerLangsArray()`) and which locales `GenerateLexiconDataCache` populates.

6. **Build out the language tree and semantic taxonomy** the lexicon needs: `lex_language_family` → `lex_language_sub_family` → `lex_language` (all lexicon-scoped via `lexicon_id`, except `lex_language` which inherits it transitively), and `lex_semantic_category` → `lex_semantic_field`. Do this through Filament resources directly, through an importer (`docs/architecture/importers.md`), or through the Filament "Lexicon Utilities" page's CSV uploads (language and semantic-category/field uploads both target an existing lexicon selected from a dropdown).

7. **Import or create reflexes/etyma** for the new lexicon's languages, via an importer or the Filament resources for `LexEtyma`/`LexReflex`.

8. **Write an importer, if you're bringing in bulk data**, following the naming/transaction/idempotency guidance in `docs/architecture/importers.md` rather than copying `ImportMayalexCSV`'s timestamped-slug/no-transaction pattern.

9. **Generate the data cache**: `make lexicon-cache ID=<new-lexicon-id>` (`docs/runbooks/regenerating-lexicon-cache.md`). The Data page and its search API return nothing until this has run at least once.

10. **Check `resources/views/lexicon/layout-sidebar.blade.php:170`** — it has a single hard-coded `@if ($lexicon->slug !== 'ielex')` that hides the "Advanced Search" / Data-page link specifically for IELEX (because IELEX's ~14,000-row sidebar is too large for the client-side widget it would otherwise share space with). This doesn't need to change for a new lexicon unless the new lexicon is also large enough to want the same treatment — but it's worth knowing this file is where a per-lexicon layout exception like that lives, since it's currently the only one.

11. **No Filament resource needs writing.** `LexLexiconResource` (`app/Filament/Resources/LexLexicons/LexLexiconResource.php`) and its form/table already work for any slug — nothing here is per-lexicon Filament code.

## What does *not* need to change

- Routes (`routes/web.php`'s `lexicon/{lex_slug}/...` group is slug-parameterized already).
- `PublicLexiconController` — every action resolves the lexicon by slug at request time.
- Filament resources for languages, etyma, reflexes, sources, semantic fields/categories, parts of speech — all are generic and scoped by `lexicon_id` already.

## Recommended direction (not implemented)

The platform review §3 recommends moving the column definitions from `LexLexicon::getDataColumns()`'s PHP `if` chain into a JSON column on `lex_lexicon` editable through Filament, so that adding a lexicon (or changing its columns) doesn't require a code change and a deploy. That would remove steps 2–4 above entirely. This has not been done as of this writing.
