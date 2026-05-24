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


Zasady dla AI: myśl, uprości, wykonaj precyzyjnie

Pracujemy z AI codziennie. Te zasady.
_Warto je dodać do wytycznych dla AI_

I Prostota przede wszystkim

Minimum kod. Nie spekuluj. Brak funkcji poza żądaniem, brak abstrakcji dla kodu jednorazowego, brak "elastyczności" która nie była proszona, jeśli 200 → 50 linii, przepisz.

Test: Czy senior powiedziałby "skomplikowane"? Uprości.

II Chirurgiczne zmiany

Dotykaj co musisz. Nie ulepszaj sąsiedni kod. Nie refaktoruj niepsutego, pasuj do stylu, martwego kodu: wspomni, nie usuwaj, usuń tylko swoje orphans.

Test: Każda zmiana → bezpośrednio do wymagania.

III Wykonanie sterowane celem

Zdefiniuj sukces. Pętluj aż działa.

Zamiast "Dodaj walidację" → zmień na "Testy invalid → pass"
Zamiast "Napraw błąd" → zmień na "Test reprodukuje → pass"
Zamiast "Refaktoruj X" → zmień na "Testy przed i po: pass"

Wieloetapowe: plan + weryfikacja:
1. [Krok] → verify: [check]
2. [Krok] → verify: [check]
3. [Krok] → verify: [check]

# Pryncypia
KOD musi działać przed wysłaniem `push`  
KOD musi byc zgodny z zasadami programowania SOLID, DRY, cienkich kontrolerów i pojedyńczej odpowiedzielaności, dodatkowo obsługa błędów, `user` nie ma widzieć nieprzechwyconego błędu. Naprawiamy tam, gdzie jesteśmy
`hard code` np.: 8080 nie ma tego, ZAKAZ `hard code`, gdzie dotykamy- widzisz `hard code` pytasz! czy naprawiać zawsze!
Mój język to polski w nim rozmawiamy. Jednak w kodzie tylko angielski, polski to nasz prywatny język na zewnątrz tylko angielski.

Start sesji — espservice-customeraftercare
Adres kodu tego i pokrewnych
/home/m.franciszczak@sellasist.pl/https:/gitlab.devsel.pl/backend/
reszta ~/PhpstormProjects


Dotenv: źródła danych poufnych i zmiennych konfiguracji. Zmienne do .env najbardziej poufne i wrażliwe tylko .env.local lub .env.dev
.env
.env.local
.env.dev

### Useful links
Article link Coding Guidelines for Your AI Agents https://blog.jetbrains.com/idea/2025/05/coding-guidelines-for-your-ai-agents/
Karpathy-Inspired Claude Code Guidelines https://github.com/multica-ai/andrej-karpathy-skills