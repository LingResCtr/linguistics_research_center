# Adding a viewer language

Last verified: 2026-09-16 against commit c0fde76.

Ordered checklist for adding a new UI/content locale (for example, adding French, `fr`) across every place the codebase encodes the current `en`/`es`/`te` set. Background: `docs/architecture/i18n.md`. There is no single config flag for this — the locale set is duplicated across at least five independent places, and missing one produces a partial, confusing result rather than an error.

1. **Copy the UI string files.** Create `server/resources/lang/fr.json` as a full translation of `server/resources/lang/en.json` — every key, including every `lexicon.pages.data.column_header_<Display Name>` key currently in use by any lexicon (check all of `en.json`/`es.json`/`te.json` for the full set, since different lexicons contribute different column headers). Missing a key falls back to English or to the raw key string, rather than erroring, so this step is easy to under-do silently — diff the new file's key set against `en.json`'s to confirm parity.

2. **Register the locale with Filament's translatable-content plugin.** In `server/app/Providers/Filament/AdminPanelProvider.php`:
   ```php
   ->plugin(SpatieTranslatablePlugin::make()->defaultLocales(['en', 'es', 'te', 'fr'])->useFallbackLocale())
   ```
   Without this, Filament never offers an `fr` tab on any translatable field (`protolang_name`, page content, language/etymon/reflex names, etc.), so editors have no way to enter French content even if everything else here is done.

3. **Add a display name.** `LexLexicon::getDisplayTextViewerLang()` in `server/app/Models/LexLexicon.php` has a small hard-coded map:
   ```php
   $lang_names = ['en' => 'English', 'es' => 'Español', 'te' => 'తెలుగు'];
   ```
   Add `'fr' => 'Français'`. Any locale code missing from this map renders as `"Unknown: fr"` in the language switcher rather than failing outright — an easy way to notice this step was skipped.

4. **Opt each lexicon in.** `viewer_lang_options` is a per-`lex_lexicon` comma-separated string (e.g. `"en, es"`), edited via Filament (`/admin/lex-lexicons/{id}/edit`) or directly (`LexLexicon::where('slug', '...')->update(['viewer_lang_options' => 'en, es, fr'])`). A locale being in steps 1–3 doesn't make it appear on any given lexicon's public pages until this per-lexicon step is also done. There's no validation tying this field to the actual configured locale set (it's a plain `TextInput`), so a typo here fails silently — the switcher link would 404 lookups or fall back oddly rather than error at save time.

5. **Regenerate the data cache for every lexicon you opted in.** `make lexicon-cache ID=<id>` (`docs/runbooks/regenerating-lexicon-cache.md`) for each lexicon whose `viewer_lang_options` you changed in step 4. `GenerateLexiconDataCache` only writes `lex_lexicon_data_cache` rows for locales listed in `viewer_lang_options` **at the moment it runs** — adding `fr` to the column without re-running this leaves the Data page with zero French rows even though the switcher now offers French.

6. **Add a DataTables i18n file**, if you want the Data page's own generated chrome (search box, pagination text, "showing X of Y", empty-state message) in the new language rather than falling back to English. Download the locale's file from the DataTables CDN (`http://cdn.datatables.net/plug-ins/<version>/i18n/`) or the `DataTables/Plugins` GitHub repo (`https://github.com/DataTables/Plugins/tree/master/i18n`), save it to `server/public/assets/datatables/plugins/i18n/fr.json`, and set:
   ```json
   "lexicon.pages.data.datatables_translation_file": "fr.json"
   ```
   in `server/resources/lang/fr.json` (this key is read by `resources/views/lexicon/lex_data.blade.php:50-53`, which skips loading a file entirely when the value is exactly `"en-US.json"`).

7. **(Optional) Localize Laravel's own auth/validation strings.** `server/resources/lang/en/{auth,validation,pagination,passwords}.php` currently exist only for `en` — neither `es` nor `te` has ever had these translated, so a French version is optional and consistent with current practice, not a regression, if you skip it. If you do want it, copy the four files into `server/resources/lang/fr/` and translate them.

## Verify

1. Log a viewer's session locale by visiting `GET /lexicon/{slug}/switchlang/fr` on a lexicon whose `viewer_lang_options` includes `fr` — you should land back on the lexicon home page with French UI strings from `fr.json` rendering (page titles, sidebar labels, etc.).
2. Open that lexicon's Data page (`/lexicon/{slug}/data`) — column headers should read the new locale's translations (or fall back to English if you skipped adding some `column_header_*` keys, rather than the page breaking) and rows should populate (confirms step 5 ran).
3. In Filament, open a translatable field (e.g. a `LexLexicon`'s `protolang_name`, or an `EieolLanguage`) and confirm a French tab/locale switch option appears (confirms step 2).
4. Confirm the language switcher dropdown on a public lexicon page shows "Français" rather than "Unknown: fr" (confirms step 3).

## Troubleshooting

| Symptom | Likely missing step |
|---|---|
| Switcher shows "Unknown: fr" | Step 3 (`getDisplayTextViewerLang` map) |
| Switcher link exists but the lexicon's public pages still show English/Spanish strings | Step 1 incomplete (missing keys in `fr.json` fall back to English), or step 4 not done for that lexicon (so the link was never reachable/relevant) |
| Data page loads but is empty in French while other locales show rows | Step 5 not run since step 4 was changed |
| Column headers show raw keys like `lexicon.pages.data.column_header_Deptotic` instead of text | Step 1's `column_header_*` keys are incomplete for `fr.json` |
| Filament shows no way to enter French content on a translatable field | Step 2 (`defaultLocales` in `AdminPanelProvider`) |
| DataTables search/pagination text still shows in English despite everything else working | Step 6 skipped, or the file wasn't saved under the exact filename referenced by `datatables_translation_file` |
