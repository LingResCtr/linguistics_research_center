# Syncing with upstream

Last verified: 2026-09-16 against commit c0fde76.

Two procedures: bringing `cola-laits/master` into this fork (monthly, human-owned), and sending a proven change from the fork to upstream. Background: [upstream.md](../upstream.md), [ADR 0002](../decisions/0002-fork-governance-and-branch-model.md).

## A. Monthly sync from upstream

Owner: the person named for the month in `docs/upstream.md`. Budget half a day; more if upstream shipped a framework upgrade.

1. Make sure the `upstream` remote exists:
   ```bash
   git remote get-url upstream || git remote add upstream git@github.com:cola-laits/linguistics_research_center.git
   ```
2. Fetch and fast-forward the mirror branch:
   ```bash
   make upstream-fetch
   ```
   This runs `git fetch upstream` and moves the local `upstream` branch to `upstream/master`. It prints how far `master` is ahead and behind. Push the mirror so others see it:
   ```bash
   git push origin upstream
   ```
3. Read what changed upstream before merging:
   ```bash
   git log --oneline master..upstream
   git diff --stat master...upstream
   ```
   Look for framework or PHP version bumps, new migrations, changes to files the fork has also changed.
4. Create the sync branch from `master` and merge (do not rebase; the fork's history must stay intact):
   ```bash
   git checkout master && git pull
   git checkout -b chore/upstream-sync-$(date +%Y-%m)
   git merge --no-ff upstream
   ```
5. Resolve conflicts using these defaults:
   - `server/composer.lock`, `server/package-lock.json`: take upstream's version, then re-run `make composer ARGS="install"` and `make npm ARGS="ci"` so the fork's added dependencies, if any, are re-resolved. Commit the regenerated lock.
   - `server/public/{js,css,fonts}/filament/**`: take upstream's version. These are generated files.
   - `server/database/migrations/**`: keep both sides. Never edit upstream's migration; if the fork has a migration with a conflicting intent, add a new migration that reconciles.
   - `server/resources/lang/*.json`: merge by key; both sides usually add keys.
   - Application code: understand both changes. If the fork's experiment and upstream's change overlap, prefer upstream's behaviour and re-apply the experiment on top, noting it in the PR.
6. Rebuild and test:
   ```bash
   make up
   make composer ARGS="install"
   make migrate
   make test
   make build
   ```
7. Open a PR titled `chore(deps): sync upstream to <short sha>` against `master`. In the description list the upstream commits merged, every conflict and how it was resolved, and anything that needs follow-up.
8. After merge, record the sync in the table in `docs/upstream.md` (owner, PR, upstream commit, notes).

**Verify:** `git merge-base --is-ancestor upstream master` exits 0 after the PR merges. `make test` passes. The site loads at `/lexicon/ielex` on the local stack.

### Troubleshooting

- **`make upstream-fetch` fails to move the branch** because you have `upstream` checked out: `git checkout master` first.
- **Merge stops on hundreds of Filament asset conflicts:** `git checkout --theirs -- server/public/js/filament server/public/css/filament server/public/fonts/filament && git add server/public`.
- **Composer install fails on PHP version** after an upstream PHP bump: the `composer:2` image runs the latest PHP; the base image in `Dockerfile` may be the constraint. Rebuild with `make down && docker compose build --no-cache && make up`.
- **A migration upstream conflicts with a fork migration on the same table:** keep both, then add a new migration on the sync branch that makes the schema consistent. Document it in the PR.

## B. Sending a change upstream

Do this when an experiment's status in `docs/upstream.md` reaches "Evaluating" with a positive result, or for a bug fix that is clearly upstream's concern.

1. Open a heads-up issue on `cola-laits/linguistics_research_center` if the change touches schema, authentication, the deploy, or a public URL pattern. Wait for an acknowledgement for those categories.
2. Refresh the mirror: `make upstream-fetch`.
3. Cut the upstream PR branch from the mirror, never from `master`:
   ```bash
   git checkout -b upstream-pr/<topic> upstream
   ```
4. Bring over only what upstream should receive:
   - Prefer `git cherry-pick <sha>...` for self-contained commits.
   - Reimplement by hand when the fork's commits are entangled with fork-only files.
   - Strip anything listed under "Intentional divergences" in `docs/upstream.md`: governance docs, CI, Makefile, fork-specific conventions.
5. Match upstream's conventions in this branch. Include tests. Keep the diff small enough to review in one sitting.
6. Run the checks as upstream would: `make test`, `make build`, and confirm `docker compose build` succeeds.
7. Push to `origin` and open the PR **against `cola-laits:master`** from `LingResCtr:upstream-pr/<topic>`. Link the fork issue and the experiment row.
8. Record the submission in the "Sent upstream" table in `docs/upstream.md`. Update the experiment's status.
9. When it merges upstream, the next monthly sync brings it back into `master`; the experiment row moves to "Sent upstream" with the outcome, and the `exp/*` branch is deleted.

**Verify:** `git log upstream..upstream-pr/<topic>` shows only the intended commits, and `git diff upstream...upstream-pr/<topic> --stat` lists no fork-only files.

### Troubleshooting

- **The cherry-pick drags in unrelated changes:** the source commits were too broad. Reimplement on the clean branch rather than sending a mixed PR.
- **Upstream asks for a different commit style or structure:** do it in that branch; the fork's `master` is unaffected.
- **Upstream declines the change:** record the outcome and reason in `docs/upstream.md`. Decide whether it stays fork-only (write the reason in the "Intentional divergences" table) or is dropped.
