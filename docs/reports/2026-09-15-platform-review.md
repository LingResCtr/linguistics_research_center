# LRC Lexicon Platform Audit

Public version. Sections 2, 4 and 5 and some §3 detail are withheld until the findings are remediated upstream; the full review is in the team's private repository.

**Read-only codebase and UI audit** of the Linguistics Research Center platform: Laravel 13 / Filament 5 application serving the Indo-European Lexicon, the newer multi-lexicon framework (SEMITILEX, MAYALEX, DRAVIDILEX), and the EIEOL lesson series. Nothing in the repository was modified.

| | |
|---|---|
| Repository | `linguistics_research_center` at commit `c0fde76` (branch `master`) |
| History | 843 commits, 2014 to 2026 |
| Stack | Laravel 13.24, Filament 5.7.6, Vue 3.5, Vite 8, MariaDB 10.6 |
| Live checks | Public pages and the data API on `lrc.la.utexas.edu` |

All paths below are relative to `server/` unless they start with `compose.yaml`, `Dockerfile`, or `.woodpecker/`.

---

## §1 Summary

The platform is a fifteen-year-old Laravel app that has been kept on a current framework, with a modern Filament admin bolted on beside a legacy admin and three unrelated public front ends. The database model for the lexicons is sound and the Filament resources are clean. The problems cluster in four places: security findings in the legacy admin and the public data API, tracked privately; a lexicon front end that ships the entire dictionary in every page; and a repository with no tests, no README, and roughly 20 MB of dead or duplicated assets.

| High findings | Medium findings | Low findings | Bugs confirmed live |
|---|---|---|---|
| 14 | 31 | 40+ | 9 |

### The five things to fix first

1. **Security fixes (detail in the private review).**
2. **The Vue lesson editor's delete confirmations do nothing** because the Modal component never emits the event its callers listen for. Audio URL edits are also lost.
3. **The English page of the IELEX lexicon is a 1.5 MB HTML document** with 13,975 sidebar items, and every single word page re-ships it.

---

## §2 Confirmed on the live site

This section is withheld until the underlying findings are remediated upstream. The full detail, including what was verified directly against the production site, lives in the team's private security review.

---

## §3 Architecture

### Three front ends, three shells, two admins
A visitor moves between three unrelated designs. The EIEOL lessons and the legacy IE Lexicon at `/lex` use a 2015 UT College of Liberal Arts template on Foundation 5.5.2 (239 KB unminified CSS) with jQuery 1.11 loaded on every page and used on one. The new lexicon framework at `/lexicon/{slug}` uses Bootstrap 5.0.2 from a 7 MB vendored dist folder. The admin is split between Filament at `/admin` and the legacy Vue lesson editor at `/admin2`, which Filament still links to. The IELEX data now exists in both the legacy views and the new framework (`/lexicon/ielex` is live), so the two public renderings of the same data can drift.

### Framework wiring frozen in the Laravel 5–10 era
`bootstrap/app.php` still binds HTTP and Console Kernels and an Exception Handler; `config/app.php` carries explicit provider and alias lists that lack everything added since roughly Laravel 10 (`Vite`, `Number`, `Js`, `Concurrency`). The first `Number::format()` in a Blade file will fatal. `BroadcastServiceProvider` is not registered, `routes/api.php` and `channels.php` are boilerplate, and the `'Form'` alias points at a package that is not installed. Two auth stacks coexist: laravel/ui at `/login` and Filament at `/admin/login`, with the legacy `Authenticate` middleware redirecting to the wrong one — see the private security review for auth-related gaps in this area.

### The data cache design
`lex_lexicon_data_cache` stores one JSON blob per reflex per viewer locale, with no primary key (`uuid()` without `->primary()`, migration `2025_10_01_140228`). The public search does `JSON_EXTRACT(...) LIKE '%x%'` or `REGEXP` over the whole lexicon slice and sorts on an extracted JSON path: a full scan and filesort per DataTables draw, three queries each. It works at 10^4 rows and will degrade linearly as IELEX (tens of thousands of reflexes) and each new lexicon times each locale are added. The cache is rebuilt by hand, whole-lexicon, with a delete-then-insert and no transaction, so the public table is empty during regeneration. Filament edits never touch it. The column list that drives both cache generation and the API is hard-coded per slug in `app/Models/LexLexicon.php:53-140`, so adding a lexicon requires a deploy.

> **Recommendation.** Keep the cache idea, but give the table an `id` and a unique key on (lexicon, reflex, locale); add MariaDB generated columns with indexes for the fixed sortable fields; regenerate per reflex from a model observer via a queued job; move the column definitions to a JSON column on `lex_lexicon` edited in Filament. For free-text search across 10^5+ rows, Laravel Scout with Meilisearch or the database driver is the natural next step.

### Model layer
- **Relations to columns that do not exist.** `LexSource.php:17` (`source_id` on reflexes), `LexPartOfSpeech.php:21` and `LexReflexPartOfSpeech.php:24` (`part_of_speech_id`). They are dead and would SQL-error if called. Part of speech is matched by text, not key.
- **`$appends` accessors that lazy-load.** `LexEtyma`, `LexReflex`, `LexSemanticField` append labels that read `$this->lexicon` or `$this->language`. Every `toArray()`, JSON response, and Filament table row triggers a query per record, and `->sortable()` on such a column in `LexEtymasTable.php:40` will throw on click.
- **Query hacks in relations.** `LexLanguage::reflex_count()` is a `hasMany` with a raw `GROUP BY`; `reflexes()->orderBy('entries')` orders by a JSON column. `LexLanguageSubFamily::getFamilySubFamilyAttribute` runs a fresh query per call.
- **`hasOne` used where `belongsTo` is correct** in the three pivot models (`LexEtymaReflex`, `LexEtymaSemanticField`, `LexReflexSource`).
- **Dead models:** `UserPermission`, `LexEtymaCrossReference`, and `IsoLanguage` (used only by the legacy series editor). Dead members: `small_reflexes()`, `stripped_name`. Deprecated `etymas()` relations are still called in six places.

### No caching, no queue, no schedule
`grep Cache::` returns nothing. The Pokorny index (1.1 MB), the language index, the printable lesson series, and every CMS page are rebuilt per request. `lex_lang_reflexes` needs `ini_set('memory_limit','256M')` to collate English in PHP each time. Content changes only through Filament, so `Cache::remember` with an observer flush would remove most server time with no schema change. `DataCacheStatus.php:38` tests `method_exists(Artisan::class, 'queue')` on the facade, which is always false, so regeneration always runs synchronously inside a Livewire request. `Console/Kernel.php` has an empty schedule.

### Import tooling
Three importers with three naming schemes (`lrc:protosemitic_import`, `app:import-mayalex-csv`, `app:import-dravidilex-csv`) and three "create missing language" implementations, plus a fourth inside the Filament `LexiconUtilities` page (494 lines of import logic in a page class). `ImportMayalexCSV` has its transaction commented out and embeds a timestamp in the lexicon slug, so each run creates a new lexicon and a failed run leaves a half-built one. `ImportProtoSemiticCsv` calls `die()` three times and clones semantic categories from every lexicon into lexicon 2. 9.2 MB of CSV sits inside `app/Console/Commands/import_data/` and is resolved by working-directory-relative paths. `ImportDravidilexCSV` is the well-designed one.

---

## §4 Bugs and implementation errors

This section is withheld until the underlying findings are remediated upstream. The full detail lives in the team's private security review.

---

## §5 Build, infrastructure, security

This section is withheld until the underlying findings are remediated upstream. The full detail lives in the team's private security review.

---

## §6 Documentation gaps

`server/readme.md` is the stock Laravel 5 readme with Travis badges. The only project-authored documentation is a 72-line translators' note (`resources/lang/README_translations.txt`) and a 38-line Filament help page describing what an etymon and a reflex are. User guides are CMS pages stored in the database. Of 227 PHP files under `app/`, 70 carry any block comment; the data-cache algorithm has a two-line inline comment.

### What a new developer cannot find out from the repository
1. How to run locally: that `./server` is bind-mounted over the image so composer, npm and artisan must run on the host; that the compose `CACHE_DRIVER` is ignored; the `lrc.localhost.utexas.edu` proxy assumption.
2. How to load a database dump and create the first admin user and Spatie roles (the seeder is empty and there are no factories, so a fresh install has no pages and the one feature test cannot pass).
3. Which of the three importers to run for which lexicon, what CSV columns they expect, whether they are idempotent.
4. That `app:generate-lexicon-data-cache` must be re-run after edits or the public search goes stale, and that Filament's button runs it synchronously.
5. The four separate places to touch to add a viewer language: `resources/lang/*.json`, Spatie translatable model fields, the per-lexicon `viewer_lang_options` string, the hard-coded `$lang_names` map in `LexLexicon.php:49`, plus the DataTables i18n file.
6. The deploy flow (master → test namespace; `release-*` tag → prod) and what the external base image's entrypoint does.
7. Which admin surfaces are Filament versus legacy `/admin2`, and the plan to retire the latter.
8. Why there are three public shells, and which one new work should target.

### What a lexicon editor cannot find out
How the Data page columns are configured, why some column headers need a `column_header_*` translation key, when to regenerate the cache after bulk edits, that the CSV export button exports only the current page, and what the `[highlight-link:id]` shortcode on landing pages does.

---

## §7 Lexicon UI audit

This section covers the public lexical-database surfaces: the new framework at `/lexicon/{slug}` (home, proto-language, language, etymon, word, semantic field, and data pages) and the legacy IELEX pages at `/lex`. The Filament admin is out of scope here.

### Visual layout and design

**Two products, two brands.** The legacy IELEX inherits the 2015 College of Liberal Arts site: burnt-orange bar, Roboto, a left side-nav with the centre's postal address and social links on every page, and content set in Foundation's 12-column grid. The new lexicon shell abandons all of it: a banner image, a blue sidebar in two arbitrary shades (`#317094`, `#18384a`) that appear nowhere else in UT or LRC branding, Bootstrap defaults for everything else, and no link back to the LRC or the rest of the site apart from the banner. A user following a link from an EIEOL lesson to a lexicon entry experiences a different website.

**Typography does not serve the content.** The one thing a lexicon page exists to show is the word. On `/lexicon/ielex/etymon/2223` the `<h1>` reads "Dictionary" and the headword *ā sits in the second cell of a bordered table in 16 px system sans. The legacy pages at least wrap lexical forms in `<span lang>` and load a philological font stack (Gentium Plus, with Miklosic for Old Church Slavonic) from `public/css/lrcstyle.css`; the new shell loads neither, so Hebrew, Arabic, Syriac and Ethiopic forms in SEMITILEX and Telugu or Tamil forms in DRAVIDILEX fall to whatever the operating system picks, with no `dir="rtl"` handling. There is no type scale: h1, h2 and table labels are Bootstrap defaults, and body copy on wide screens runs the full container width, well past 120 characters per line.

**Bootstrap used against itself.**
- `class="table table-bordered table-responsive"` on the `<table>` element (`lex_etymon.blade.php:16`, `lex_word.blade.php:30`). `.table-responsive` belongs on a wrapper; on the table it forces `display:block` and breaks the border model.
- `class="vw-100"` on `<td>` sets `width: 100vw` on a cell, forcing horizontal overflow inside the responsive wrapper.
- Inline `style="height:100vh;overflow-y:scroll;padding:15px"` on the main container makes the page a nested scroll region: find-in-page, scroll restoration, and anchor links behave oddly, and on iOS the outer document still scrolls.
- Sidebar footer is a fixed `height: 120px` band; when a lexicon has only one search type the band is mostly empty.
- Label cells use `text-end` with `white-space:nowrap`, so the label column width is set by the longest translated label in the current locale. In Telugu it eats half the table.

**The entry page as a form, not a dictionary.** Etymon and word pages are label:value tables ("Language:", "Word:", "Part of Speech:", "Meaning (Gloss):"). Dictionaries have a 400-year-old, instantly recognisable layout for this: bold headword, part of speech in small italics, gloss, then etymology, then citations, each in its own typographic register. Cognates and related words are plain bullet lists sorted by nothing in particular (the live entry for English *a* lists Old English *ān* three times in a row because each reflex is a separate row).

### User experience

**There is no search.** The sidebar filter is the only find mechanism on the home, language and entry pages, and it only filters what is already on the page: headwords of the current language, or semantic field names. A reader who wants the Latin cognate of English *foot* has to know to open the Latin dictionary and scroll, or go to the Data page and learn a 40-column DataTable. A reader who wants to search by meaning has only the Data page. The home page has no search box at all. The legacy IELEX is the same: the Pokorny master index is a 2,222-row table with no filter, and the language index has a good regex filter that only exists on that one page.

**The sidebar is the whole dictionary.** Every page renders the complete word list for the current language into the sidebar, then filters it client-side on `keyup` by walking every `<li>` and toggling `display`. For English that is 13,975 nodes on every keystroke, and the 1.5 MB page is re-downloaded for every word the reader clicks. The category tab re-opens and re-closes every accordion on each keystroke.

**Navigation and state.**
- Sidebar tabs are `<a href="#" onclick>` with no ARIA tab semantics and no keyboard affordance; the "highlight-link" shortcode generates `href="javascript:..."`.
- The active tab, the sidebar filter text, and the Data page's regex toggle are not in the URL. Toggling regex does `document.location.href = "data?use_regex=..."`, reloading the page and discarding every column filter the user typed.
- The Data page rebuilds the entire DataTable (`destroy: true`) on every window resize, refetching from the server, to compute a scroll height that CSS could give for free.
- Row links on the Data page are an icon-only `<a target="_blank">` with no text or label.
- The CSV export button exports the current page of a server-side table. A scholar who expects the filtered result set gets 25 rows.
- Language selector is a 376-option `<select>` for IELEX with no counts; the sidebar language list has no counts either.
- Locale is stored in the session, not the URL, so a shared link to a Spanish view opens in English and there is no `hreflang`.
- On mobile the sidebar is off-canvas to the right with a 30 px handle; the header is not sticky; the body is a 100vh nested scroller.

**Missing scholarly affordances.** No stable citation ("Cite this entry"), no visible permalink, no last-modified date, no per-entry JSON or CSV, no source bibliography page, no way to link to a specific sense, and numeric IDs in URLs (`/word/102331`) rather than slugs. Sources appear as a bold display string followed by the pivot's original text with no page-number formatting beyond "p. N".

### Implementation choices
- **All UI logic is inline JavaScript in Blade** (`lexicon/layout.blade.php:13-215`, `lexicon/lex_data.blade.php:14-200`). None of it goes through Vite, none is linted or testable, and it is duplicated across three near-identical filter functions.
- **Sorting in the template.** `@php function sortSidebarItemsByEntries()` is declared inside a Blade view (`layout-dict.blade.php:3-10`), normalising every entry with `Normalizer::normalize` on every request. Declared at file scope, including that layout twice in one request would fatal with a redeclaration error.
- **Collation is bespoke and per-request.** The legacy language index defines the alphabet as a comma-separated string inside the controller (`PublicIELexController.php:70`) and hashes each entry per request; `AlphabetSorter` exists but is used elsewhere. A stored sort key per reflex would replace both.
- **Translation keys encode display names.** Column headers are looked up as `lexicon.pages.data.column_header_{display_name}`, so a column named "Deptotic" needs a translation key spelled exactly that way in three JSON files.
- **CMS HTML with a home-grown shortcode** (`[highlight-link:id]`) parsed with regex at render time and printed unescaped.
- **DataTables, jQuery 3.7 and Bootstrap 5 are loaded from a vendored bundle** outside the build, while the rest of the platform builds through Vite with Bootstrap 5.3 already installed.

---

## §8 What each audience needs

**General public**
- A single search box on every page that searches headwords *and* meanings across all languages in the lexicon.
- The word as the visible heading; a plain-language gloss first; the technical apparatus second.
- A readable line length, larger lexical forms, and a page that loads in under a second on a phone.
- Orientation: what this lexicon is, how many words and languages, a "random entry" or featured etymon on the home page.
- A consistent shell shared with the EIEOL lessons so the two halves feel like one resource.

**Scholars**
- Correct script rendering: `lang` and `dir` attributes, a philological font stack (Gentium Plus, Noto), IPA that does not fall back.
- Stable, citable URLs, a "Cite this entry" block in Chicago and BibTeX, and a last-modified date.
- Full-result exports (CSV, JSON) of a filtered query, not the visible page.
- Regex and column filters that survive in the URL so a query can be shared or footnoted.
- Source apparatus: a bibliography page per lexicon, source codes expanded on hover, page references formatted.
- Counts everywhere: reflexes per language, etyma per semantic field.

---

## §9 Low-cost, high-impact changes

Ordered by ratio of impact to effort. The first six are each a day or less.

1. **Fix the six visible defects.** Placeholder in the tab title, "Other Info" and "Sources" phantom headings, raw `<b>` in Etymology, stray `</table>`, dead "back to English" button, duplicate IDs. Two hours, and the product stops looking unmaintained.

2. **Make the headword the heading.** Render the entry as a dictionary article: an eyebrow with the lexicon and language name, the headword as `<h1 lang="…">` in a Gentium Plus stack at 2.5 rem, part of speech in small caps beside it, gloss as a lede, then etymology, cognates grouped by language, sources, and a definition list for the remaining fields. Replace `table-bordered` with a `<dl>`. Add `max-width: 70ch` to prose blocks. One template pass over four views, no backend change.

3. **Stop shipping the whole dictionary in the sidebar.** Render the sidebar as an A–Z letter bar plus the current letter's entries (server-side, cached per language and letter), and fetch other letters or filter results from the existing data endpoint. Show a count next to each language in the selector and list. The English page drops from 1.5 MB to roughly 50 KB and the per-keystroke filter walks a few hundred nodes, not 14,000. Cache the sorted list with `Cache::remember` keyed on the language's `updated_at` until then.

4. **A global search box in the header.** The `ajaxData` endpoint already searches every cached column with LIKE. Add a text input to the lexicon header that queries it with a small debounce and shows the top ten hits (language, headword, gloss) with a "see all results" link into the Data page with the query pre-filled. This is the single largest usability change available without new infrastructure.

5. **Script-aware typography.** Wrap every lexical form in `<span lang class="lex">` using the language's ISO code (already stored as `lang_attribute`), set `dir="auto"`, and load Gentium Plus and a Noto fallback in the lexicon layout. Reuse the `[lang]` rules from `lrcstyle.css`. Half a day; transforms SEMITILEX and DRAVIDILEX for their intended readers.

6. **Harden the Data page without redesigning it.** Keep the regex flag and column filters in the URL with `history.replaceState` instead of reloading; recompute scroll height with CSS (`flex: 1; min-height: 0`) instead of destroying the table on resize; give the row link a label; persist column visibility in `localStorage`; add a server-side "export all matching rows" endpoint and rename the button to say which it does.

7. **Citation and permalink block.** A small footer on each entry page: canonical URL, "Cite this entry" in Chicago and BibTeX built from lexicon name, editors, headword, and access date, plus the record's `updated_at`. Pure template work; scholars ask for this first.

8. **Native Bootstrap tabs and accessible controls.** Replace the `href="#" onclick` tabs with Bootstrap's `data-bs-toggle="tab"` markup (ARIA and keyboard handling come free), make the sidebar toggle a `<button aria-expanded>`, and replace `javascript:` hrefs with buttons. Move the inline scripts into one Vite entry so they are minified and linted.

9. **One shell for everything public.** The new framework already renders IELEX at `/lexicon/ielex`. Add the missing legacy features it lacks (the Pokorny page-number index, the language-family index with counts, prev/next etymon), then 301 the `/lex/*` routes to it and retire the Foundation 5 template, jQuery 1.11, and 380 KB of CSS. Give the lexicon and EIEOL layouts a shared header that ties back to the LRC brand: a burnt-orange accent, one wordmark, one navigation bar.

10. **Modernise the surface cheaply.** Upgrade the vendored Bootstrap 5.0.2 to the 5.3.8 already in `node_modules` and enable `data-bs-theme` for a dark mode; define five CSS custom properties for the palette instead of the two hard-coded blues; make the header sticky and the sidebar a bottom sheet on mobile; add a `published` flag so half-built lexicons are not reachable.

---

## §10 Suggested order of work

1. **Security fixes (private review).**
2. **Visible defects and entry-page typography** (days). §9 items 1, 2, 5, 7.
3. **Vue editor repairs** (day). Modal events, `modelValue` props, escaping in `getElementsForDisplay`.
4. **Performance** (week). Eager-load and batch the cache generator inside a transaction and queue it; `Cache::remember` the public read pages; letter-indexed sidebar.
5. **Search and Data page** (week). §9 items 4, 6.
6. **Hygiene** (days). Delete dead Requests, models, controllers, routes, assets and the 2018 bundle; gitignore Filament assets; fix `CACHE_STORE`; add Pint, Larastan and a test step to CI; write the README that §6 outlines.
7. **Consolidation** (weeks). Migrate to the Laravel 11+ bootstrap and drop laravel/ui auth; finish the Filament Issue resource and lesson editor so `/admin2` can go; one public shell (§9 item 9); data-driven column definitions; primary key and indexes on the cache table.

---

*Audit performed read-only on the working tree at commit c0fde76 (branch master). Live checks were HTTP fetches of public pages on lrc.la.utexas.edu; no authenticated surfaces were exercised. PHP was not available on the audit machine, so no code was executed; every finding cites the file and line it was read from.*
