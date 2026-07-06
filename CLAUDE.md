# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Symfony 7 (PHP 8.2+, runs on a PHP 8.4 image) "DevBox" web app that bundles a
log viewer, a Markdown viewer, an MCP dashboard, and an SSH-test tool, packaged
with a full Docker suite (MySQL, Adminer, Portainer, Mailpit, reverse proxy).

The log viewer, MCP server, and logger are pulled in as Composer packages from
GitHub VCS (`mafio69/fast-php-logger`, `mafio69/log-viewer`,
`mafio69/enhanced-php-mcp-server`), not vendored locally.

> Note: `README.md`/`DEVBOX.md` describe a legacy multi-directory layout
> (`app/`, `time-agent/`, `mcp-server/`). That is outdated — the app is now a
> single Symfony monolith under `src/`, and the Time Agent has been removed
> (its `./suite agent:*` commands are stubs).

## Commands

Run PHP tooling **inside the container** (`./suite shell` or `docker compose exec php ...`)
since the host may not have PHP 8.4.

```bash
# Docker suite
./suite start          # docker compose up -d (also: ./suite stop|restart|status|logs|shell)
./suite install        # composer install (root + mcp-server)

# Tests (PHPUnit 11)
composer test                          # full suite
composer test:unit                     # tests/Unit, tests/Controller, tests/Service
composer test:integration              # tests/Integration
bin/phpunit --filter LogViewerServiceTest   # single test class
bin/phpunit --filter test_method_name       # single test method

# E2E (Playwright, targets http://app.local — suite must be running)
npm run test:e2e
npm run test:e2e:ui

# Static analysis & style
vendor/bin/phpstan analyse             # level 5 on src+tests (needs dev cache warmed)
vendor/bin/pint                        # Laravel Pint code style

# Env secret scan (also runs as git pre-commit hook)
php bin/check-env-security
php bin/check-env-security --staged-only
```

Cache warming for PHPStan's Symfony extension: it reads
`var/cache/dev/App_KernelDevDebugContainer.xml`, so run `bin/console cache:warmup`
first if analysis complains about a missing container.

## Architecture

Standard Symfony structure with autowiring/autoconfigure enabled (`config/services.yaml`,
`App\` mapped to `src/`). Controllers are thin; logic lives in `src/Service/`.

- **Routing** is mostly PHP 8 attributes (`#[Route]`), but `DashboardController`
  and the `/api/config*` endpoints are wired explicitly in `config/routes.yaml`
  — check both places when adding/looking for routes.
- **Key routes**: `/` (Dashboard), `/logs` (LogViewer), `/mcp` (MCP dashboard),
  `/mdviewer` (Markdown viewer), `/api/*` and `/api/ssh-test` (JSON APIs).
- **Interface binding**: `App\Service\MdViewer\DataProviderInterface` is bound to
  `MockDataProvider` in `services.yaml` — swap there to change the data source.

### Docker / networking

- `php` container runs nginx + php-fpm via supervisor (`docker/supervisor.conf`),
  built from `mafio69/php-env:8.4-fpm-alpine`. The repo is bind-mounted at
  `/var/www/html`, so PHP code changes are live (no rebuild), but Composer
  dependency changes require `docker compose build`.
- The `proxy` container (`docker/proxy.conf`) maps `*.local` hostnames on port 80:
  `app.local`, `logs.local`, `pma.local`, `mail.local` (add these to `/etc/hosts`).
  Direct ports: PHP `:8082`, MCP `:8000`, Adminer `:8081`, Portainer `:9090`,
  Mailpit `:8025`. All ports are overridable via `.env` — never hardcode them.
- Databases: both MySQL 8 (`DATABASE_URL`, service `mysql`) and SQLite
  (`DB_PATH=.../data/devbox.sqlite`) are referenced in env; confirm which a given
  feature uses before assuming.

### Config precedence

`.env` (base) → `.env.local` (local, most sensitive) → `.env.dev`. Copy
`.env.example` to `.env` for setup.

## Conventions (from AIREADME.md & CODING_STANDARDS.md)

- **Language**: converse with the user in **Polish**; all code, comments, commit
  messages, and PR descriptions in **English**.
- **No hardcoded values** (ports, hosts, secrets). If you encounter a hardcoded
  value where you're working, **ask** before changing it — always ask.
- **Surgical changes**: touch only what the requirement needs. Don't refactor
  unrelated/working code or improve neighboring code unprompted. Mention dead
  code rather than deleting it (only remove your own orphans).
- **Simplicity first**: prefer the smallest correct solution; no speculative
  abstraction or unrequested flexibility.
- SOLID (esp. SRP — the "AND" test), DRY, thin controllers + service layer.
- No debugging artifacts (`var_dump`, `dd`, `console.log`) or commented-out code
  in committed code. Users must never see an unhandled exception.
- Secrets only via environment variables; the pre-commit hook blocks commits that
  fail `bin/check-env-security`.
