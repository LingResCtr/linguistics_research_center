# Regenerating the lexicon data cache

Last verified: 2026-09-16 against commit c0fde76.

Procedure for rebuilding `lex_lexicon_data_cache`, the table that powers the public "Data" page and its search API for a lexicon. Background on why this exists and its limitations: `docs/architecture/data-cache.md`.

## When it is needed

Any time lexicon content changes and the change should show up on `/lexicon/{slug}/data` or `/api/v1/lexicon/{slug}/data`:
- After editing reflexes, etyma, extra data, sources, parts of speech, semantic fields/categories, or languages through Filament.
- After running any importer (`docs/architecture/importers.md`) — all four import paths add or change reflex-level data that the cache doesn't pick up on its own.
- After loading a database dump (`docs/runbooks/loading-a-database-dump.md`) — a dump does not include a fresh cache unless the dump's author ran this first.
- After adding a viewer language to a lexicon's `viewer_lang_options` — the cache only has rows for locales that were listed at the time it was last generated.

There is no automatic trigger: no observer, event listener, or scheduled job touches this table (`server/app/Console/Kernel.php`'s schedule is empty). If you don't run this, the public site keeps showing stale data indefinitely — there's no staleness warning shown to visitors.

## Finding lexicon ids

- In Filament: `/admin/lex-lexicons` lists every lexicon by name and slug. Click the "Data Cache Status" row action to reach a page whose URL contains the id (`/admin/lex-lexicons/{id}/data-cache-status`), or click "Edit" for the same.
- Via tinker: `make artisan ARGS="tinker"` then `\App\Models\LexLexicon::pluck('slug', 'id')`.
- Known ids as seeded by `database/migrations/2022_04_05_100415_create_lex_lexicon_table.php`: `1` = `ielex`, `2` = `semitilex`. MayaLex and DravidiLex ids depend on when/how they were imported in your database (MayaLex in particular gets a new row, and a new id, every time `app:import-mayalex-csv` is run — see `docs/architecture/importers.md`).

## Running it

For one lexicon:
```
make lexicon-cache ID=1
```
Raw equivalent: `docker compose exec web php artisan app:generate-lexicon-data-cache 1`

For every lexicon (omit the id):
```
docker compose exec web php artisan app:generate-lexicon-data-cache
```
(there is no dedicated `make` target for "all lexicons" — run the raw command, or loop `make lexicon-cache ID=<n>` over each id.)

From Filament: open a lexicon's "Data Cache Status" page and click "Regenerate Cache". This runs the identical artisan command under the hood — `DataCacheStatus.php`'s action checks `method_exists(Artisan::class, 'queue')` to decide between queued and synchronous execution, but that check is always `false` for a facade class, so **this button always runs synchronously inside the Livewire request**, regardless of lexicon size.

## Expected runtime characteristics

- Cost scales with `(number of reflexes in the lexicon) × (number of viewer locales in viewer_lang_options)`. Each reflex triggers several un-eager-loaded relation queries per locale (parts of speech, etyma + their semantic fields, language, extra data) — this is an N+1 pattern, not a single efficient query, so it gets slower per-reflex as a lexicon grows, not just slower in total.
- Inserts happen one row at a time (`DB::table(...)->insert(...)` per reflex per locale) inside 100-row `chunk()` batches for reading, but the batches don't translate into batch inserts.
- A `Laravel\Prompts\progress` bar shows on the CLI; the Filament button shows no progress indicator while it runs, only a notification when it finishes or fails.
- No published benchmark exists in this repo; the private security review measured page-level load times but not this command's own runtime. Expect it to be noticeably slower for IELEX (thousands of etyma, tens of thousands of reflexes) than for a small pilot lexicon like DravidiLex.

## What the public site shows during regeneration

The command deletes **all** existing `lex_lexicon_data_cache` rows for that `lexicon_id` before it starts re-inserting (`GenerateLexiconDataCache::handleLexiconId()`, no wrapping transaction around the delete+rebuild). For the entire duration of the run:
- `/lexicon/{slug}/data` and its AJAX endpoint return zero rows for that lexicon, for every viewer locale, not just the one being processed at that moment — the delete removes all locales for the lexicon up front, and locales are re-populated one at a time afterward.
- The rest of the public site (home, language, etymon, word, field pages) is unaffected — those pages query the live tables directly, not the cache.
- If the command is interrupted (killed, times out, throws) partway through, the cache is left partially rebuilt: some locales may have complete data, others none, until it is re-run to completion.

## Verify

1. Filament: open the lexicon's "Data Cache Status" page and confirm "Data Cache Count" (for `en`) is close to "Reflex Count" — they won't be exactly equal if some reflexes have no `en` extra data at all, but a cache count of 0 against a nonzero reflex count means the run didn't complete or hasn't been run yet.
2. Or via tinker: compare `\DB::table('lex_lexicon_data_cache')->where('lexicon_id', $id)->where('content_lang_code', 'en')->count()` against `\App\Models\LexReflex::whereIn('language_id', ...)->count()` for the lexicon's languages.
3. Load `/lexicon/{slug}/data` in a browser and confirm rows appear and a text search actually filters them.

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Command prints "Skipping empty Lexicon" and exits | The lexicon has zero reflexes across all its languages (nothing imported yet, or all reflexes belong to languages not yet attached to a family under this lexicon) | Import data first, or confirm the language/family/subfamily rows are linked to the right `lexicon_id` |
| Data page shows zero rows after a completed run | `viewer_lang_options` doesn't include the locale the visitor's session has (`viewer_lang_code`), or is empty/null (defaults to `en` only) | Set `viewer_lang_options` on the lexicon in Filament to include the needed locale(s), then re-run the generator |
| Command runs a long time and the Filament tab appears to hang | This is expected — the button always runs synchronously (see above); there's no progress feedback in the browser | Prefer the CLI (`make lexicon-cache ID=...`) for large lexicons so you can watch progress and won't lose it to a browser tab timeout |
| Public site briefly 404s or empty-states mid-edit-session for an unrelated lexicon | Someone regenerated a different lexicon's cache at the same time — the delete-then-rebuild is scoped by `lexicon_id`, so this shouldn't happen across lexicons; if it does, check for a stray `lexicon_id` argument mismatch | Confirm the id passed matches the intended lexicon before running |
