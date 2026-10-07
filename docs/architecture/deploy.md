# LRC Build and Deploy

Last verified: 2026-09-16 against commit c0fde76.

This document covers how the application is built into a container image, how it runs locally via Compose, how the upstream Woodpecker pipeline deploys it, and what is and is not true for this fork specifically.

## `Dockerfile` stages

Three stages, no stage names wasted on anything but the build:

1. **`phpbuild`** (`composer:2`): copies `server/` in, runs `composer install --ignore-platform-reqs --no-dev`. `--ignore-platform-reqs` means Composer will install regardless of whether the *build* image's PHP version/extensions match `composer.json`'s requirements — it does not affect what runs at runtime.
2. **`npmbuild`** (`node:24`): copies the composer-installed tree forward, runs `npm ci && npm run production`, then deletes `node_modules` to keep it out of the final image. `npm run production` runs Vite's build against `server/vite.config.js`'s three entry points (see `docs/architecture/overview.md`).
3. **Final stage** (`ghcr.io/utaustin-laits/laravel-base:13.x-php8.5`): copies the fully built tree in, then `chmod 777 -R` on `bootstrap/cache` and `storage`. The base image is external (published by UT LAITS) and this repository does not define its entrypoint, PHP-FPM/Nginx wiring, or startup scripts — that behavior is **unknown as of 2026-09-16** from this repository alone.

`.dockerignore` excludes only `.git` and `server/node_modules` — nothing else is excluded, so a local `.env`, `vendor/`, and `storage/logs` content present in the build context get baked into the `phpbuild`/`npmbuild` layers if they exist on disk at build time (see the private security review).

## `compose.yaml` locally

- Builds the same `Dockerfile` (`build.context: .`) but **bind-mounts `./server` over `/var/www/html`** at runtime (`volumes: ['./server:/var/www/html']`), so the image's baked-in `vendor/`/`public/build` from the Docker build are shadowed by whatever is actually on the host's `server/` directory. In practice this means `composer install` and `npm run dev`/`build` need to be run on the host, not just inside the image, for local development to reflect a fresh checkout — the container alone does not regenerate them.
- Sets `VIRTUAL_HOST=${VIRTUAL_HOST:-lrc.localhost.utexas.edu}`, implying an external reverse proxy (e.g. `nginx-proxy` or similar, watching the `VIRTUAL_HOST` label/env convention) is expected to route that hostname to the `web` service; that proxy is not defined in this repository.
- Ships a MariaDB 10.6 service (`db`) with credentials `lrc`/`lrc`/`lrc`, a persistent `dbdata` volume, and exposes it on the host at `${DB_EXPOSED_PORT:-13309}` (mapped to the container's `3306`).
- Sets DB credentials and other environment values directly in `compose.yaml` — these are fixed local-dev values, not production secrets. `APP_KEY` was moved out into a gitignored `compose.override.yaml` (see `docs/runbooks/local-development.md`); rotation of the key formerly committed here for the upstream project is tracked in the roadmap.

## Woodpecker pipeline (`.woodpecker/workflow.yaml`)

Three steps, using YAML anchors to share Kubernetes credentials/conditions:

| Step | Image | Trigger | Effect |
|---|---|---|---|
| `build` | `woodpeckerci/plugin-docker-buildx` | `push`, `tag`, or manual | Builds and pushes `laitsdev/lrc:woodpecker-<pipeline-number>-<short-sha>` to Docker Hub |
| `deploy_test_web` | `laitsdev/woodpeckerci-kubernetes` | push to `master` | Updates the `web` deployment/container in the `lrc-test` namespace to that tag |
| `deploy_prod_web` | `laitsdev/woodpeckerci-kubernetes` | a tag matching `refs/tags/release-*` | Updates the `web` deployment/container in the `lrc` (production) namespace to that tag |

There is no test, lint, static-analysis, or audit step in this pipeline — it is build → deploy only (see the private security review).

## The `release-*` tag convention

Pushing a tag matching `release-*` triggers the production deploy step above. `git tag -l` on this repository shows the convention was used regularly from 2018 through mid-2020 (`release-2018-03-05` through `release-2019-10-02`), and the **last such tag is `release-2020-07-29`** — over five years old as of this writing. Whatever runs in the `lrc` production namespace today, if anything is still deployed from this pipeline at all, corresponds to that commit or a manually-applied image; there is no more recent `release-*` tag in this repository's history to correspond to current `master`.

## Environment variables the app reads

Pulled from `env(...)` calls across `server/config/*.php` (not exhaustive — see the individual config files for the full list, especially `database.php`'s per-driver blocks):

| Variable | Read by | Notes |
|---|---|---|
| `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY` | `config/app.php` | Standard Laravel bootstrap config |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `config/database.php` | Default connection is `mariadb`, not Laravel's stock `mysql` |
| `CACHE_STORE` | `config/cache.php:20` | Not `CACHE_DRIVER`; see note below |
| `SESSION_DRIVER`, `SESSION_LIFETIME` | `config/session.php` | `compose.yaml` sets `SESSION_DRIVER=database` |
| `QUEUE_CONNECTION` | `config/queue.php` | Defaults to `sync`; nothing in the app dispatches queued jobs currently (review §3) |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | `config/mail.php` | |
| `SENTRY_LARAVEL_DSN` (or `SENTRY_DSN`), `SENTRY_ENVIRONMENT`, `SENTRY_RELEASE`, `SENTRY_SAMPLE_RATE`, `SENTRY_TRACES_SAMPLE_RATE`, plus per-breadcrumb/tracing toggles | `config/sentry.php` | Server-side Sentry SDK config |
| `SENTRY_JS_DSN` | read directly via `env()` in `resources/views/layout_header.blade.php` (not through a config file) | Browser-side Sentry snippet; because it bypasses `config()`, it is silently blanked by `php artisan config:cache` (see the private security review) |
| `BCRYPT_ROUNDS` | `config/hashing.php` | |

**`CACHE_STORE`, not `CACHE_DRIVER`.** `config/cache.php` reads `CACHE_STORE`. Upstream's `compose.yaml` and `.env.example` set `CACHE_DRIVER`, which is silently ignored (the cache falls back to the `file` store). This fork's `compose.yaml` and `.env.example` use `CACHE_STORE`; the fix is an upstream candidate.

## For this fork

This repository (`LingResCtr/linguistics_research_center`) is a fork of the canonical `cola-laits/linguistics_research_center`; see `docs/upstream.md` for the governance relationship. On the infrastructure side specifically:

- **Woodpecker does not run on this fork.** The pipeline described above is the upstream LAITS CI/CD configuration as committed in `.woodpecker/workflow.yaml`; it depends on Woodpecker server credentials, Kubernetes cluster access, and Docker Hub credentials (`laitsdev/lrc`) that are upstream infrastructure, not configured for this fork.
- **CI for this fork is GitHub Actions**, defined in `.github/workflows/ci.yml`: on pull requests to `master` and `exp/**` and pushes to `master` it runs the PHP test suite and Pint (both non-blocking until the baseline in ADR 0005 lands), the Vite frontend build, and a no-push build of the production `Dockerfile` (both blocking). Nothing deploys from this workflow.
- **No hosted environment for this fork (decided 2026-10-07).** Development and evaluation happen on each contributor's machine with the Compose stack and a sanitized production dump placed in `data/` (see `docs/runbooks/loading-a-database-dump.md`). The requirements are Docker Desktop (or Docker Engine with Compose v2) and `make`; nothing else is installed on the host. Revisit if non-developers need to review UI experiments without running Docker.
