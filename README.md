# Developer Suite

Kompletne środowisko deweloperskie: Logger + MCP Server + Time Agent

## docker-fast-logger

Docker environment that bundles `fast-php-logger` and `log-viewer` into a ready-to-use PHP + Nginx + MySQL container.

Both packages are pulled from GitHub via Composer during `docker build` — no local copies needed.

### URLs

| URL | What |
|---|---|
| `http://localhost:8082` | PHP app (served from `/var/www/html/public`) |
| `http://localhost:8082/logs` | Log viewer (served from `/var/www/html/viewer`) |
| `http://localhost:8000` | Enhanced MCP Server (Control Panel) |
| `http://localhost:8081` | Adminer - database management |
| `http://localhost:9090` | Portainer - Docker management |
| `http://localhost:8025` | Mailpit - test SMTP server |

### Key Files

| File | Purpose |
|---|---|
| `Dockerfile` | PHP 8.4 + Nginx, installs Composer deps from GitHub |
| `docker-compose.yml` | All services: PHP, MySQL, Adminer, Portainer, Mailpit, Proxy, and new `mcp-server` container |
| `composer.json` | Requires logger, viewer, and `mafio69/enhanced-php-mcp-server` from GitHub VCS |
| `viewer/index.php` | Entry point for log viewer (accessible via `/logs`) |
| `docker/nginx.conf` | Nginx configuration |
| `docker/php.ini` | Dev PHP config (error reporting, etc.) |
| `docker/supervisor.conf` | Supervisor config to run nginx + php-fpm |

### How packages are loaded

`composer install` runs during `docker build`. Packages fetched from GitHub:
- `mafio69/fast-php-logger` — from Packagist
- `mafio69/log-viewer` — from GitHub VCS
- `mafio69/enhanced-php-mcp-server` — from GitHub VCS

`vendor/` lives inside the Docker image at `/var/www/html/vendor/`.
It is NOT mounted as a volume — changes require `docker compose build`.

### Nginx routing

- Default vhost root: `/var/www/html/public` (your PHP app)
- `/logs` alias → `/var/www/html/viewer` (log viewer interface)
- PHP files are handled by php-fpm on port 9000
- Vendor, .git, node_modules directories are blocked

### Suite context

| Repo | Role |
|---|---|
| `mafio69/fast-php-logger` | PSR-3 logger, writes log files |
| `mafio69/log-viewer` | Viewer UI, reads log files |
| `mafio69/enhanced-php-mcp-server` | MCP Protocol integration and control panel |
| `mafio69/docker-fast-logger` | This repo — Docker environment unifying all 3 repositories |

### Conventions

- All git commits, PR descriptions, and code comments in **English**
- `vendor/` is excluded from git (built inside Docker image)
- To update a package version: bump `composer.json`, run `docker compose build`

## Struktura

```
docker-fast-php-logger/
├── suite                    # Główny skrypt zarządzania
├── docker-compose.yml       # Wszystkie usługi
├── composer.json            # Root package
├── app/                     # Fast PHP Logger app
├── time-agent/             # Time Doctor monitor (Symfony Console)
└── mcp-server/             # MCP Server (Slim 4)
```

## Komponenty

| Komponent | Tech | Port | Opis |
|-----------|------|------|------|
| **Logger** | PHP 8.2 | 8794 | Fast PHP Logger + Log Viewer |
| **MCP Server** | PHP 8.2 | 8000 | Enhanced Model Context Protocol Server |
| **Time Agent** | Symfony | - | Monitorowanie Time Doctor (GUI) |
| **MySQL** | 8.0 | 3307 | Baza danych |
| **Mailpit** | - | 1025 | Testowy SMTP |
| **phpMyAdmin** | - | - | Zarządzanie DB |

## Szybki start

```bash
# Instalacja wszystkich komponentów
./suite install

# Uruchomienie całej suite
./suite start

# Status
./suite status
```

## Użycie

### Zarządzanie suite

```bash
./suite start      # Uruchom wszystko
./suite stop       # Zatrzymaj
./suite restart    # Restart
./suite status     # Sprawdź status
./suite logs       # Logi na żywo
./suite shell      # Wejdź do PHP
```

### Komponenty osobno

```bash
# MCP Server
./suite mcp:start
./suite mcp:logs

# Time Agent
./suite agent:start
./suite agent:logs
./suite snooze      # Wyłącz do jutra 6:00
./suite bypass      # Przełącz bypass
```

### Time Agent - sterowanie

```bash
# Ręczne wyłączenie alarmów do jutra
./suite snooze

# Przełącz tryb prywatny
./suite bypass

# Zobacz logi
./suite agent:logs
```

## Dostępne usługi po uruchomieniu

- **http://logs.local** - Log Viewer
- **http://app.local** - Główna aplikacja
- **http://localhost:8000** - MCP Server (Panel)
- **http://pma.local** - phpMyAdmin
- **http://mail.local** - Mailpit

## Time Agent - co robi?

1. Pyta przy starcie: "Czy jesteś w pracy?"
2. Wykrywa przerwy (blokada ekranu)
3. Pokazuje alert gdy Time Doctor nie działa:
   - 🔴 Czerwone okno w godzinach pracy (7-17)
   - 🟠 Pomarańczowe po godzinach
4. Przycisk "Wyłącz do jutra 6:00"
5. Automatyczne wznawianie o 6:00

## Wymagania

- Docker + docker-compose
- Linux z GUI (dla Time Agent)
- PHP 8.1+ (opcjonalnie, dla lokalnego developmentu)

## Logi

Logi każdego komponentu w osobnych katalogach:
- `./logs/` - Logger
- `./time-agent/logs/` - Time Agent
- `./mcp-server/logs/` - MCP Server
