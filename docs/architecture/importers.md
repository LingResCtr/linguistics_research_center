# Import tooling

Last verified: 2026-09-16 against commit c0fde76.

Lexicon data gets into the database four different ways, written at four different times by different people, with three different naming conventions for the artisan commands and no shared base class or convention between them. This page inventories what exists today so a maintainer can pick the right tool and knows what hazards to expect; it is not a recommendation to keep this shape (see "Guidance for new importers" below and the platform review §3 "Import tooling").

## Every importer

| Command | Class | Targets | Input | Transaction | Idempotent? |
|---|---|---|---|---|---|
| `lrc:protosemitic_import {path}` | `app/Console/Commands/ImportProtoSemiticCsv.php` | SEMITILEX, hard-coded `lexicon_id = 2` | One CSV path given on the command line (nominals or verbals sheet); five language names (`Akkadian, Syriac, Ethiopic, Hebrew, Arabic`) must already exist as `lex_language` rows | Wraps the reflex/etyma import in `DB::transaction()`, and separately wraps `copySemantic()` in its own transaction | No. Always `new LexEtyma()` / `new LexReflex()` with no existence check; rerunning the same file duplicates every row. `copySemantic()` is the one idempotent part — it skips if `lex_semantic_category` rows already exist for the lexicon. |
| `app:import-mayalex-csv` (no arguments) | `app/Console/Commands/ImportMayalexCSV.php` | MayaLex — creates a **new** lexicon every run | Nine CSV files under `app/Console/Commands/import_data/` (languages, Buck semantic category/field lookups, part-of-speech lookup, and four dialect files: Kaqchikel, Kaufman, Yucatec, Cholti, K'iche') read with hard-coded, working-directory-relative paths | Commented out (`//\DB::beginTransaction()` / `//\DB::commit()` at lines 77 and 296) — no transaction at all | No, by design: `$lex = LexLexicon::create(['slug' => 'mayalex_' . date('Ymd_His'), ...])` embeds a timestamp in the slug, so every run creates a brand-new lexicon row. A failed run partway through leaves an orphaned, half-populated lexicon behind with nothing to clean it up automatically. |
| `app:import-dravidilex-csv {reflexes_json?}` | `app/Console/Commands/ImportDravidilexCSV.php` | DravidiLex — slug `dravidilex_pilot` | `app/Console/Commands/import_data/dravidilex/Dravidilex_Languages.csv` and `Dravidilex_Sources.csv`, plus the shared `buck_semantic_category.csv` / `buck_semantic_field.csv`; optionally a `dravidilex_batch_import.json` path argument for etyma + reflexes | `DB::beginTransaction()` / `DB::commit()` with a `catch (\Throwable $e) { DB::rollBack(); ... throw $e; }` around the whole run | Guarded, not idempotent: refuses to run at all if a lexicon with slug `dravidilex_pilot` already exists (`$this->error(...); return self::FAILURE;`). You must delete the existing lexicon before re-importing; there's no merge/upsert path. This is the best-designed of the three (per the platform review §3: "the well-designed one"). |
| Filament "Lexicon Utilities" page | `app/Filament/Pages/LexiconUtilities.php` (494 lines) | Any existing lexicon, selected from a dropdown | CSV files uploaded through the browser (languages, semantic categories, semantic fields, reflexes — see below) | Each of the four upload actions calls `DB::beginTransaction()` / `DB::commit()` around itself, with `try`/`catch` only on the reflex upload; language/semantic uploads have no rollback path if something throws mid-loop | Mixed: `runSemanticsUploadAction()` uses `LexSemanticField::updateOrCreate(...)` for fields (idempotent) but plain `LexSemanticCategory::create(...)` for categories (not idempotent — reruns duplicate categories). The language upload creates a new family/subfamily per CSV row rather than looking one up first (see the private security review). |

All four require **Site Manager** or **Lexicon Manager** to reach through Filament; the three artisan commands have no authorization of their own (anyone with shell/artisan access can run them).

## Where the bundled CSVs live

`app/Console/Commands/import_data/` (about 9.2 MB) holds the fixture CSVs the Mayalex and Dravidilex commands read, plus the shared Buck semantic-category/field lookups both use:
- `Mayalex languages.csv`, `Mayalex Kaufman.csv`, `Mayalex Kaqchikel.csv`, `Mayalex Yutatek.csv`, `Mayalex Cholti.csv`, `Mayalex Kiche.csv`, `Mayalex Kaufman_partofspeech_lookup.csv`, `Mayalex Kaufman_Semantic_Categories.csv`, `Mayalex Kaufman_Semantic_Fields.csv`
- `buck_semantic_category.csv`, `buck_semantic_field.csv` (shared)
- `dravidilex/Dravidilex_Languages.csv`, `dravidilex/Dravidilex_Sources.csv`
- `SemitiLEX Data - nominals - Sheet1.csv`, `SemitiLEX Data - verbals - Sheet1.csv` (these look like the fixtures for `lrc:protosemitic_import`, but that command takes its CSV path as an explicit argument rather than reading these by a hard-coded name — pass one of these two files' path when running it)

**Hazard:** every `Reader::createFromPath(...)` call in `ImportMayalexCSV.php` and `ImportDravidilexCSV.php` uses a path relative to the process's working directory (e.g. `'app/Console/Commands/import_data/Mayalex languages.csv'`), not `base_path()` or `__DIR__`. These commands only work if invoked with the Laravel project root (`/var/www/html` inside the container) as the current working directory — which is how `php artisan` is normally invoked, but a script or cron job that `cd`s elsewhere first will get a "file not found" error instead of importing anything.

## Which importer to use for which lexicon

| Lexicon slug | Importer |
|---|---|
| `semitilex` | `php artisan lrc:protosemitic_import <path-to-nominals-or-verbals-csv>` (run once per sheet; hard-coded to `lexicon_id = 2`) |
| `mayalex_<timestamp>` | `php artisan app:import-mayalex-csv` (always creates a new lexicon; there is no way to import into an existing MayaLex lexicon with this command) |
| `dravidilex_pilot` | `php artisan app:import-dravidilex-csv` for languages/semantics, then re-run with a `dravidilex_batch_import.json` path to add etyma + reflexes |
| Any lexicon, ad hoc CSVs | Filament → Lexicon Utilities page (`/admin/lexicon-utilities`), if the target lexicon already exists |

## Expected CSV columns

Derived from what each command actually reads (not from any schema file — none exists):

**`lrc:protosemitic_import`** (`ImportProtoSemiticCsv.php`) — headers are lower-cased and have parentheticals stripped at read time; `pS root` is renamed to `root`. Distinguishes a "nouns" vs. "verbs" sheet by checking whether the file path contains `verbals` or `nominals`, and dies with `Can't tell if nouns or verbs` if neither substring matches. Rows are split into etyma (`id` containing `ETYMON`) vs. reflexes (everything else) by their `id` column.

**`app:import-mayalex-csv`** — reads fixed filenames under `import_data/`; the extra-data keys it looks for in the dialect CSVs are, verbatim: `word (source spelling)`, `word (practical orthography)`, `word (ipa)`, `spanish definition`, `english definition`, `part of speech`, `source`, `page number`, `full original entry`, `alternate forms`, `editors`, `other`.

**`app:import-dravidilex-csv`** — `Dravidilex_Languages.csv` and `Dravidilex_Sources.csv` drive the three-tier language tree and source list; the optional `dravidilex_batch_import.json` supplies etyma/reflex records directly (JSON, not CSV) with keys grouped in the command as `JSON_CORE_KEYS` (`Headwords`, `HeadwordEntries`, `EtymonEntry`, `Gloss`, `Language`), `JSON_LINK_KEYS` (`IsEtymon`, `HomographNumber`, `Etyma`, `EtymaHomographNumber`), `JSON_SOURCE_CITATION_KEYS` (`Starling ID`, `URL`), and `JSON_ETYMON_IMPORT_ONLY_KEYS` (`Semantic Tag (Buck)`, `Semantic Field (Buck)`).

**Filament Lexicon Utilities** (self-documenting in the UI, enforced by a header-validation rule before upload):
- Language CSV: `Family`, `Subfamily`, `Language`
- Semantic Categories CSV: `Text`, `Number`, `Abbr`
- Semantic Fields CSV: `Text`, `Number`, `Abbr`, `SemanticCategoryAbbr` (validated against the categories CSV's `Abbr` set)
- Reflexes CSV/JSON: required `Headwords` (comma-separated if multiple), `Gloss` (English; add a `Gloss.es` column for Spanish); optional `Etyma`, `HomographNumber`, `Sources` (JSON array of `{source, page_number, original_entry}`); every other column is stored as reflex "extra data"

## Guidance for new importers

None of this is prescriptive today, but if you're writing a fourth (or fifth) importer:

1. **Name it `lrc:import-<lexicon-slug>`.** Two of the three existing commands already use inconsistent prefixes (`lrc:` vs `app:import-`); standardize on `lrc:import-<slug>` going forward so `php artisan list` groups them.
2. **Wrap the whole run in one `DB::transaction()`**, following `ImportDravidilexCSV`'s pattern — `try { ... DB::commit(); } catch (\Throwable $e) { DB::rollBack(); throw $e; }` — so a mid-import failure never leaves a half-built lexicon, unlike `ImportMayalexCSV` today.
3. **Upsert, keyed on a stable identifier**, not a fresh `create()`/`new Model()` per row. Follow `LexSemanticField::updateOrCreate([...])` in `LexiconUtilities.php` rather than `LexEtyma::create(...)` in `ImportProtoSemiticCsv.php`. Pick an identifier that survives a re-import: a source-provided id column, or a composite of (lexicon, language, headword, homograph number).
4. **Add a `--dry-run` flag** that runs the same logic but rolls back at the end (or skips the final `DB::commit()`) and prints what would have changed — none of the three existing commands has one, so a bad CSV is currently only caught by reading the database afterward.
5. **Log counts**, not just a final "done" message: created vs. updated vs. skipped rows per entity type, so a rerun's diff is visible without a manual row count.
6. **Remind the operator to regenerate the cache.** `ImportDravidilexCSV` already prints `Then run: php artisan app:generate-lexicon-data-cache <id>` after a successful import — carry that forward, since nothing does it automatically (see `docs/architecture/data-cache.md`).
7. **Resolve file paths with `base_path()` or `storage_path()`**, not bare relative strings, so the command works regardless of the shell's current directory.
