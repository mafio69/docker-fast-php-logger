# AI Project Context — docker-fast-logger

## What this project is

A Docker dev environment that bundles `fast-php-logger` and `fast-php-log-viewer`
into a ready-to-use PHP + Apache + MySQL container.

Both packages are pulled from GitHub via Composer during `docker build` — no local copies.

## URLs

| URL | What |
|---|---|
| `http://localhost:8080` | PHP app (served from `/var/www/html/app`) |
| `http://localhost:8080/logs` | Log viewer (Apache Alias → `/var/www/html/viewer/index.php`) |

## Key files

| File | Purpose |
|---|---|
| `Dockerfile` | PHP 8.3 + Apache, installs Composer deps from GitHub |
| `docker-compose.yml` | Mounts `./app`, `./logs`, sets `LOG_DIR` env var |
| `composer.json` | Requires `mafio69/fast-php-logger` and `mafio69/fast-php-log-viewer` from GitHub VCS |
| `viewer/index.php` | Entry point for log viewer, copied into image at `/var/www/html/viewer/` |
| `docker/php.ini` | Dev PHP config (error reporting, etc.) |
| `docker/entrypoint.sh` | Fixes permissions on `logs/` and `app/`, starts Apache |

## How packages are loaded

`composer install` runs during `docker build`. Both packages are fetched from GitHub:
- `mafio69/fast-php-logger` — from Packagist
- `mafio69/fast-php-log-viewer` — from GitHub VCS (not yet on Packagist)

`vendor/` lives inside the Docker image at `/var/www/html/vendor/`.
It is NOT mounted as a volume — changes require `docker compose build`.

## Apache routing

- Default vhost root: `/var/www/html/app` (your PHP app)
- `Alias /logs /var/www/html/viewer` — routes `/logs` to the viewer entry point
- `viewer/index.php` sets `LOG_DIR` from env and delegates to the Composer package

## Suite context

| Repo | Role |
|---|---|
| `mafio69/fast-php-logger` | PSR-3 logger, writes log files |
| `mafio69/fast-php-log-viewer` | Viewer UI, reads log files |
| `mafio69/docker-fast-logger` | This repo — Docker environment using both |

## Conventions

- All git commits, PR descriptions, and code comments in **English**
- `vendor/` is excluded from git (built inside Docker image)
- To update a package version: bump `composer.json`, run `docker compose build`
