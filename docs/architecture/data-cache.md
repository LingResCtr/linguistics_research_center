# The lexicon data cache

Last verified: 2026-10-09 against commit b6e8455.

`lex_lexicon_data_cache` is a denormalized, per-reflex, per-viewer-locale snapshot of everything the public "Data" page (`/lexicon/{slug}/data`) and its DataTables AJAX endpoint (`/api/v1/lexicon/{slug}/data`) need to display, search, filter and sort a lexicon without joining across `lex_reflex`, `lex_etyma`, `lex_reflex_extra_data`, `lex_reflex_part_of_speech` and friends on every request. It is not a framework-level cache (there is no `Cache::` facade use anywhere in the codebase); it is an ordinary table that a console command populates and the public controller reads with plain query-builder calls.

## Schema

Defined in `server/database/migrations/2025_10_01_140228_create_lex_lexicon_data_cache_table.php`:

| Column | Type | Notes |
|---|---|---|
| `uuid` | `uuid` | Generated with `$table->uuid()`, which is **not** marked `->primary()`. The table has no primary key at all. |
| `lexicon_id` | `foreignId` → `lex_lexicon.id` | `onDelete('cascade')` |
| `reflex_id` | `foreignId` → `lex_reflex.id` | `onDelete('cascade')` |
| `content_lang_code` | `string(10)` | The viewer locale this row's `data` was rendered in (`en`, `es`, `te`, ...) |
| `data` | `json` | One object per reflex, keyed by the lexicon's column names |
| `created_at` / `updated_at` | timestamps | |

There is a composite index `lexicon_id_lang_code_reflex_id_index` on `(lexicon_id, content_lang_code, reflex_id)`, but nothing enforces uniqueness on that triple — a bug in the generator that inserted twice, or a partial rerun, would simply add duplicate rows with no constraint to catch it.

## How it is built

`app/Console/Commands/GenerateLexiconDataCache.php`, signature `app:generate-lexicon-data-cache {lexicon_id?}`. With no argument it loops over every `LexLexicon` row; with an id it processes just that lexicon.

For a given lexicon:
1. Resolve the lexicon's languages by walking `lex_language → lex_language_sub_family → lex_language_family` filtered on `lexicon_id`.
2. Resolve viewer locales from `LexLexicon::$viewer_lang_options` (a comma-separated string edited in Filament, e.g. `"en, es"`), defaulting to `['en']` if unset.
3. `DB::table('lex_lexicon_data_cache')->where('lexicon_id', $lex_id)->delete()` — the **entire** lexicon's cache rows are deleted up front, with no wrapping transaction.
4. `LexReflex::whereIn('language_id', $lex_language_ids)->chunk(100, ...)` walks every reflex belonging to the lexicon's languages. For each reflex, for each viewer locale, it calls `app()->setLocale($lang)` (so translatable attributes like `text` on semantic fields/parts of speech render in that locale) and builds one associative array keyed by `LexLexicon::getDataColumns()->name`, then inserts one row per reflex per locale with `DB::table(...)->insert(...)`.
5. The locale set by `app()->setLocale($lang)` is never restored afterward, so the process (or subsequent commands sharing the same request/console lifecycle) can be left in whatever locale the last chunk left it in.

The column list — which fields exist and where their values come from — is **hard-coded per lexicon slug** in `LexLexicon::getDataColumns()` (`server/app/Models/LexLexicon.php:53-140`): a long `if ($this->slug === 'semitilex') { ... } else if (...) { ... } else { ... }` chain with a `// FIXME make this database-driven at some point` comment on it. Most column values come from `$reflex->extra_data->where('key', $column_desc->name)->first()?->value`, i.e. a string match against `lex_reflex_extra_data.key`; a handful of names (`meaning`, `part_of_speech`, `semantic_tag`, `root`, `etymon`, `language`) are computed from relations instead. Adding a lexicon or a column means editing this method and redeploying — see `docs/runbooks/adding-a-lexicon.md`.

Each reflex triggers several lazy-loaded relations (`parts_of_speech`, `etyma` and `etyma.semantic_fields`, `language`, `extra_data`) with no eager-loading, so the command is effectively N+1 per reflex, repeated once per viewer locale.

## Who reads it

`app/Http/Controllers/PublicLexiconController.php`:
- `data($lexicon_slug)` just renders the `lexicon/lex_data` Blade view, which wires up a DataTables instance pointed at the AJAX endpoint.
- `ajaxData($lex_slug)` is the DataTables server-side handler. It reads the current viewer locale from `Session::get('viewer_lang_code', 'en')` and issues three separate queries against `lex_lexicon_data_cache` filtered to `(lexicon_id, content_lang_code)`: a total count, a filtered/sorted/paginated `get()`, and a filtered count for `recordsFiltered`. Column search, the global search box, and sorting all compile directly from the request's `columns`/`search`/`order` parameters into the query. Input handling on this endpoint is covered in the private security review.

Requests to the data endpoint are validated by `server/app/Http/Requests/LexiconDataRequest.php` (shapes, lengths, a 100-row page cap, `asc`/`desc` only) and executed by `server/app/Services/Lexicon/DataTableQuery.php`, which accepts only the lexicon's own data columns for searching and ordering, applies defaults when DataTables parameters are absent, and treats the DataTables `regex` flag as a boolean. A search expression the database cannot evaluate (for example an unbalanced regular expression) returns HTTP 422 with an `error` field rather than a server error.

## When it goes stale

The cache is a hand-rebuilt snapshot with no invalidation hooks: there are no model observers, no event listeners, and no scheduled job touching `lex_lexicon_data_cache` (`grep -rn lex_lexicon_data_cache app/` turns up only the generator command, `DataCacheStatus.php`, and `PublicLexiconController.php`). Any edit made through Filament (reflex, etymon, extra data, source, part of speech, language, semantic field) or through one of the importers leaves the public Data page and API showing the pre-edit values until someone re-runs the generator for that lexicon.

## How it is triggered today

Two ways, both ultimately running the same artisan command:
1. **CLI**: `php artisan app:generate-lexicon-data-cache [lexicon_id]` (see `docs/runbooks/regenerating-lexicon-cache.md`).
2. **Filament**: the "Data Cache Status" page (`app/Filament/Resources/LexLexicons/Pages/DataCacheStatus.php`), reachable from a lexicon's row action in `/admin/lex-lexicons`, shows a live reflex count vs. cached-row count and has a "Regenerate Cache" button.

The button's action checks `method_exists(Artisan::class, 'queue')` to decide whether to queue the job or run it synchronously. `Artisan` is a Laravel facade; its static methods are dispatched through `__callStatic` rather than being declared on the `Artisan` class itself, so `method_exists(Artisan::class, 'queue')` is always `false` regardless of what the underlying kernel supports. **In practice this button always falls through to `Artisan::call(...)`, which runs the whole delete-then-rebuild synchronously inside the Livewire request** — for a large lexicon this blocks the admin's browser tab until it finishes, and the public site sees the row count for that lexicon (and viewer locale) at zero for the whole rebuild, because step 3 of the generator deletes before it re-inserts.

## Known limitations (see the platform review §3 for the fuller discussion and the recommended redesign)

- No primary key and no uniqueness constraint on `(lexicon_id, content_lang_code, reflex_id)`.
- Delete-then-rebuild with no transaction: the public table is empty (zero rows for that lexicon/locale) for the duration of every regeneration.
- Free-text search and sort run as `LIKE`/`REGEXP`/`ORDER BY` over a JSON column with no generated/indexed columns — a full scan and filesort per DataTables draw, and it will not stay fast as reflex counts grow.
- Column definitions are hard-coded per slug in PHP, not data-driven.
- Input handling on `ajaxData()` (missing-parameter and search-input validation) is covered in the private security review.

## Rules for new code

- Never write to the lexicon or reflex tables through Filament, an importer, or tinker without also planning to re-run `app:generate-lexicon-data-cache <lexicon_id>` (or documenting to the operator that they must).
- Don't add a new "computed" column to `getDataColumns()` without also adding its case to the `foreach ($column_descs as $column_desc)` branch in `GenerateLexiconDataCache::handleLexiconId()` — the two are not automatically in sync.
- Treat `lex_lexicon_data_cache` as disposable and rebuildable, not authoritative: don't add foreign keys or features that assume its rows are unique or always current.
- If you touch the generator, wrap the delete+insert in a `DB::transaction()` and eager-load `parts_of_speech`, `etyma.semanticFields`, `language`, `extra_data` before the per-reflex loop — both are called out in the platform review §3 and neither requires a schema change.
- Don't rely on `method_exists(Artisan::class, 'queue')` as a queued-vs-synchronous check anywhere else in the codebase; it is always false for a facade and is why the Filament button is synchronous today.
