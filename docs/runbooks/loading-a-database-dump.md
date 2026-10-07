# Loading a database dump

Last verified: 2026-09-16 against commit c0fde76.

Procedure for loading a sanitized production/staging MariaDB dump into a local dev environment, and creating a working admin login afterward. Assumes `make init` / `make up` have already been run (see `docs/runbooks/local-development.md`).

## Obtaining a dump

Dumps are produced by UT LAITS from the production MariaDB database on request from an LRC maintainer and shared through a UT-approved file transfer, never through this repository or an issue. Ask a maintainer listed in `.github/CODEOWNERS`. Before it leaves LAITS the dump must be sanitized: the `user`, `password_resets` and `sessions` tables dropped or anonymized, and any other table that carries personal data (the `issue` and `issue_comment` tables may contain staff names in comment threads) reviewed. A dump is a `mysqldump`/`mariadb-dump` SQL file of MariaDB 10.6; a gzip-compressed file is fine (`gunzip` it first). Expect to refresh it whenever production content has moved on enough to matter for what you are testing.

## Placing the file

Put the sanitized dump under the repo-root `data/` directory (gitignored — never commit a dump). Example: `data/lrc-2026-09-01-sanitized.sql`.

## Importing

1. `make db-import FILE=data/lrc-2026-09-01-sanitized.sql` (the target refuses to run if `FILE` is missing or doesn't exist, and prints `Now run: make migrate` when it finishes).
   - Raw equivalent: `docker compose exec -T db mariadb -ulrc -plrc lrc < data/lrc-2026-09-01-sanitized.sql`
   - (`lrc`/`lrc`/`lrc` are the username/password/database name set in `compose.yaml`'s `db` service — `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DATABASE`. Override in `compose.override.yaml` if your dump was sanitized against a different schema/credentials setup.)
   - This replaces existing tables the dump contains; it does not drop tables the dump doesn't mention. If you need a fully clean slate first, drop and recreate the `db` volume (`docker compose down -v` — **destroys all local data**, only do this if you have nothing to keep) before importing.
   - `make db-shell` opens an interactive `mariadb` client against the same database if you want to poke around before or after (raw: `docker compose exec db mariadb -ulrc -plrc lrc`); `make db-dump` writes a timestamped dump of your current local database to `data/` if you want to snapshot before experimenting further.
2. Run any migrations added after the dump was taken: `make artisan ARGS="migrate"` (raw: `docker compose exec web php artisan migrate`). This is also what (re-)populates the Spatie `roles`/`permissions` tables if the dump predates `2022_03_21_102536_add_initial_roles_permissions.php`, or leaves them alone if it doesn't (that migration is idempotent-by-`insertGetId` in the sense that Laravel just won't re-run an already-recorded migration — it will NOT dedupe roles if the dump already contains role rows and the migration also tries to insert them; check `select * from roles` before assuming this step is safe against a dump that already has roles seeded).

## Creating the first admin user

If the dump's `user` table was fully stripped (or you're starting from an empty database), there is no seeder or factory that creates an admin — `database/seeders/DatabaseSeeder.php` is empty and there are no model factories for `User`. Create one by hand via `artisan tinker`:

```
make artisan ARGS="tinker"
```
(raw: `docker compose exec web php artisan tinker`)

```php
$user = new \App\Models\User();
$user->email = 'you@example.edu';
$user->username = 'you';
$user->first_name = 'Your';
$user->last_name = 'Name';
$user->password = bcrypt('choose-a-real-password');
$user->save();

// Grant a role. Roles are Spatie roles (table `roles`), created by migration
// 2022_03_21_102536_add_initial_roles_permissions.php, not by a seeder:
//   Site Manager   -> manage_users, manage_settings, manage_pages, manage_menu, manage_books
//   EIEOL Manager  -> manage_eieol
//   Lexicon Manager -> manage_lexicon
// Site Manager is the closest thing to a superuser role, but note it does NOT
// include manage_lexicon — grant Lexicon Manager too if you need to edit lexicon data.
$user->assignRole('Site Manager');
$user->assignRole('Lexicon Manager');
```

Once created, grant the account a role so Filament's per-resource policies (in `app/Policies/*Policy.php`) allow it to do something once logged in — those check permissions (`manage_lexicon`, `manage_eieol`, `manage_books`, `manage_pages`, `manage_users`, `manage_settings`) resolved through whichever roles you assign. See `docs/architecture/auth.md` for how panel- and policy-level access control fit together. `App\Filament\Pages\LexiconUtilities::canAccess()` is the one place that checks a **role name** directly (`Site Manager` or `Lexicon Manager`) rather than a permission — assign one of those two roles if the account needs the Lexicon Utilities import page.

## Regenerating the lexicon cache

Loading a dump does not populate `lex_lexicon_data_cache` — see `docs/runbooks/regenerating-lexicon-cache.md`. Run it for every lexicon you expect to browse:

```
make lexicon-cache ID=1   # or whatever id ielex has in your dump
```
(raw: `docker compose exec web php artisan app:generate-lexicon-data-cache 1`, or omit the id to rebuild every lexicon)

## Verify

1. `make artisan ARGS="tinker"` → `\App\Models\LexLexicon::pluck('slug', 'id')` shows the lexicons your dump contains.
2. Load `http://localhost:<port>/lexicon/ielex` (or whatever the IELEX slug is in your dump) — the home page should list language families, not a blank/error page.
3. Load `http://localhost:<port>/lexicon/ielex/data` — the DataTables view should populate rows (confirms the cache regeneration step actually ran for that lexicon and locale).
4. Log in to `http://localhost:<port>/admin` with the account you created and confirm the expected navigation groups (Lexicon, EIEOL, Books, General) appear per the roles you assigned.

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Import fails with an access-denied or unknown-database error | `data/*.sql` was dumped from a schema/user setup that doesn't match `compose.yaml`'s `lrc`/`lrc`/`lrc` | Adjust the import command's `-u`/`-p`/database name to match the dump, or restore against a temporary database and re-dump against `lrc` |
| `/lexicon/ielex` 500s after import | Migrations weren't run after the import, so a column the code expects doesn't exist yet | `make artisan ARGS="migrate"` |
| `/admin/login` accepts the password but immediately redirects back to the login form | The account has no role with a permission Filament's panel-level middleware needs, or `password` wasn't hashed (don't set it as a plain string — always run it through `bcrypt()` or the model's `password` cast, which is `'hashed'` per `App\Models\User::casts()`) | Re-set the password through `bcrypt()`; assign at least one role |
| Data page loads but shows zero rows for a lexicon you know has data | Cache not regenerated for that lexicon/locale after import | Re-run `make lexicon-cache ID=<id>` |
| `assignRole()` throws "role does not exist" | The `2022_03_21_102536_add_initial_roles_permissions.php` migration hasn't run against this database (fresh database, or dump taken before that migration existed and migrations weren't caught up) | `make artisan ARGS="migrate"`, then re-check `select * from roles` |
