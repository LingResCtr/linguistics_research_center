# 0008. Secrets and local configuration

- Status: Accepted
- Date: 2026-09-16
- Deciders: Danny Law (LRC)
- Related: private security review §5, `compose.yaml`, `compose.override.yaml.example`, `Makefile` (`init`), `SECURITY.md`

## Context

`compose.yaml` in upstream carries a production-shaped `APP_KEY` and local database credentials. The key is a secret whether or not it matches production; anyone who reuses the file gets a shared key. Development also needs real-scale data, which means database dumps that must not enter git and must not carry user credentials.

## Decision

1. `compose.yaml` contains no secrets. `APP_KEY` and any personal or secret setting live in `compose.override.yaml`, which is gitignored and generated from `compose.override.yaml.example` by `make init` with a fresh random key.
2. The local MariaDB credentials (`lrc`/`lrc`) stay in `compose.yaml`: they are for a throwaway local container and are not secrets. If a hosted environment reuses compose, it must override them.
3. Database dumps live in `data/` at the repository root, gitignored. Dumps must have `users`, `password_resets` and `sessions` dropped or anonymised before leaving the machine that produced them.
4. `.claude/settings.json` denies agent reads of `server/.env`, `compose.override.yaml` and `data/`.
5. Rotation of the key that was committed upstream is LAITS's decision; the fork raises it as part of the Phase 1 security PR.

## Consequences

- A fresh clone gets a unique key with no manual step.
- Contributors with an existing `compose.yaml`-based setup must run `make init` once.
- Removing `APP_KEY` from `compose.yaml` is an upstream candidate and is listed in `docs/upstream.md`.

## Alternatives considered

- Keep the key in `compose.yaml` since it is "only local": rejected; a shared key is a shared secret.
- Use `server/.env` only: works, but the compose stack sets environment through compose, so the override file is the consistent place.
