# LRC Authentication and Authorization

Last verified: 2026-09-16 against commit c0fde76.

This document covers how a request gets an authenticated `User`, how that user is (or is not) authorized to act, and where the coverage gaps are, so new code adds to the correct mechanism instead of a fourth one.

## Two auth stacks

The application runs two independent login flows against the same `User` model (`server/app/Models/User.php`, table `user`):

- **`laravel/ui` at `/login`.** Registered by the bare `Auth::routes(['register' => false])` call at the end of `routes/web.php`. Uses the `web` guard (session-based, `config/auth.php`) and the stock `Illuminate\Foundation\Auth` traits pulled in through `routes/web.php`'s controllers. This is what protects `/admin` (the "admin" prefix group) and `/admin2` routes.
- **Filament's own login at `/admin/login`.** Registered by `->login()` in `AdminPanelProvider`. Filament also authenticates against the `web` guard and the same `User` model, so a session established via one login screen is valid on both — there are not two separate credential stores, just two login forms hitting the same guard.

`App\Http\Middleware\Authenticate` (extending Filament/Laravel's base `Authenticate` middleware) redirects unauthenticated requests to `route('login')`, i.e. the laravel/ui login page, even when the request originated under `/admin` — a Filament-panel guest gets bounced to the non-Filament login screen rather than `/admin/login`.

## Session configuration

`config/session.php` reads `SESSION_DRIVER` (compose.yaml sets `database` locally; see `docs/architecture/deploy.md` for how this is set in each environment) with a 120-minute lifetime (`SESSION_LIFETIME`). The Filament panel's own middleware stack in `AdminPanelProvider` includes `AuthenticateSession::class`. Session handling on the legacy `/admin2` surface is covered in the private security review.

## Panel access

`User::canAccessPanel()` decides who may open the Filament panel at all; the per-model policies below decide what they may do inside it. New code relies on policies and permissions, never on panel access alone.

## Spatie roles and permission names

`spatie/laravel-permission` (`User` uses `HasRoles`) backs the model policies. Every policy in `server/app/Policies` calls `$user->can('<permission>')` uniformly across all seven ability methods (`viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`) — there is no per-ability granularity. The permission names actually referenced in code:

| Permission | Checked by | Covers |
|---|---|---|
| `manage_lexicon` | `LexLexiconPolicy`, `LexLanguagePolicy`, `LexLanguageFamilyPolicy`, `LexLanguageSubFamilyPolicy`, `LexEtymaPolicy`, `LexReflexPolicy`, `LexSemanticCategoryPolicy`, `LexSemanticFieldPolicy`, `LexSourcePolicy`, `LexPartOfSpeechPolicy`, `app/Filament/Pages/LexiconHelp.php:19` | All Lexicon-cluster models plus the Filament lexicon help page |
| `manage_eieol` | `EieolSeriesPolicy`, `EieolLessonPolicy`, `EieolLanguagePolicy` | EIEOL series/lesson/language models via Filament; the legacy `/admin2` surface predates these policies |
| `manage_books` | `BookPolicy`, `BookSectionPolicy` | Book/BookSection models |
| `manage_pages` | `PagePolicy` | CMS `Page` model |
| `manage_users` | `UserPolicy` | The `User` Filament resource |
| `manage_settings` | `app/Filament/Pages/ManageSiteSettings.php:41` | The site-settings Filament page |

Separately, and using a **different vocabulary**, `User::isAdmin()` checks **role names** directly (`hasRole('Site Manager')`, `hasRole('EIEOL Manager')`, `hasRole('Lexicon Manager')`) rather than permissions. It is consulted in three places that have nothing to do with the policies above:

- `app/Providers/Filament/AdminPanelProvider.php:52` — whether the Issues nav-badge count is scoped to the user's own editable series or shown unfiltered.
- `app/Filament/Widgets/SeriesEditorNavWidget.php:15` — whether the widget lists all series or only the user's `editableSeries`.
- `resources/views/admin/layout.blade.php:41` — a legacy admin-layout conditional.

`app/Filament/Pages/LexiconUtilities.php:52` (`canAccess()`) checks `hasRole('Site Manager')` / `hasRole('Lexicon Manager')` directly and carries its own `// FIXME check the 'manage lexicon' permission instead of roles` comment — a third, self-acknowledged inconsistency between the permission-name scheme and the role-name scheme.

## The legacy `user_permission` / `editableSeries` mechanism

`User::editableSeries()` is a `belongsToMany(EieolSeries::class, 'user_permission', 'user_id', 'eieol_series_id')`, backed by the `UserPermission` model, which is annotated in its own source as `FIXME old table for mapping series edit permissions - replace with Spatie/permissions eventually`. In current code this relation is read only to **filter what is displayed** — the Issues nav badge and the `SeriesEditorNavWidget` both use `editableSeries` to decide which series' issues/lessons to list for a non-admin user. Whether it is also enforced against mutating requests on the legacy admin surface is covered in the private security review.

## `/admin2` routes and what protects them

The legacy `/admin2` surface predates the policy layer; hardening it is tracked in the private security review.

## `server/app/Policies` coverage

Models **with** a policy: `Book`, `BookSection`, `EieolLanguage`, `EieolLesson`, `EieolSeries`, `LexEtyma`, `LexLanguage`, `LexLanguageFamily`, `LexLanguageSubFamily`, `LexLexicon`, `LexPartOfSpeech`, `LexReflex`, `LexSemanticCategory`, `LexSemanticField`, `LexSource`, `Page`, `User`.

Models **without** a policy (confirmed by directory listing against `server/app/Models`): `EieolElement`, `EieolGloss`, `EieolGlossedText`, `EieolGrammar`, `EieolHeadWord`, `EieolSeriesLanguage`, `IsoLanguage`, `Issue`, `IssueComment`, `LexEtymaCrossReference`, `LexEtymaExtraData`, `LexEtymaReflex`, `LexEtymaSemanticField`, `LexReflexCrossReference`, `LexReflexExtraData`, `LexReflexPartOfSpeech`, `LexReflexSource`, `UserPermission`. Most of these are pivot/child models edited only as nested Filament form components under a parent resource that does have a policy (e.g. `EieolGrammar` under `EieolLesson`), which limits but does not eliminate the exposure — direct Eloquent access or a future standalone resource for any of these would have no policy to fall back on.

## Rules for new code

- Use `$user->can('permission_name')` inside a policy method — never a bare `hasRole()` check — for any new authorization decision. `isAdmin()` and the role-name checks are a parallel, undocumented vocabulary that should not gain new call sites (see the private security review).
- Add a policy for every new model that is ever mutated outside of a fully policy-protected parent Filament resource, including pivot/child models — an empty coverage list is not evidence a model is safe to skip.
- Call `authorize()` (or route through a Filament resource, which does this for you) in every mutating controller action. The legacy `/admin2` surface predates the policy layer; hardening it is tracked in the private security review.
