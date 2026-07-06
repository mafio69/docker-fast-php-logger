# Developer Suite Dashboard

Kompletne środowisko deweloperskie: PHP Logger + Log Viewer + MCP Server.

## Usługi i adresy

| URL | Opis |
|---|---|
| `http://localhost:8082` | Dashboard (Symfony — `/var/www/html/public`) |
| `http://localhost:8082/logs` | Log Viewer |
| `http://localhost:8000` | Enhanced MCP Server (panel + API) |
| `http://localhost:8081` | Adminer |
| `http://localhost:9090` | Portainer |
| `http://localhost:8025` | Mailpit |

## Szybki start

```bash
docker compose up -d
```

Po rebuildzie (zmiana konfiguracji, pakietów):

```bash
docker compose up -d --build
```

## Powiązane repozytoria

| Repo | Rola |
|---|---|
| `mafio69/fast-php-logger` | PSR-3 logger, zapisuje pliki logów (Packagist) |
| `mafio69/log-viewer` | UI przeglądarki logów (GitHub VCS) |
| `mafio69/enhanced-php-mcp-server` | MCP Server + panel admina (GitHub VCS) |
| `mafio69/docker-fast-logger` | To repozytorium — środowisko Docker |

`vendor/` jest budowany wewnątrz obrazu Docker. Nie jest montowany jako wolumin — zmiany w pakietach wymagają `docker compose build`.

Aby zaktualizować pakiet VCS (log-viewer, mcp-server) bez pełnego rebuild:

```bash
docker exec devbox composer update mafio69/log-viewer mafio69/enhanced-php-mcp-server --working-dir=/var/www/html
```

## Konfiguracja

Skopiuj `.env.example` → `.env` i dostosuj:

```bash
cp .env.example .env
```

### Zmienne środowiskowe

| Zmienna | Domyślnie | Opis |
|---|---|---|
| `PHP_PORT` | `8082` | Port PHP app na hoście |
| `DB_PORT` | `3307` | Port MySQL na hoście |
| `APP_ENV` | `dev` | Środowisko Symfony (`dev`/`prod`) |
| `DATABASE_URL` | *(patrz .env.example)* | DSN do MySQL |
| `SSH_TEST_TOKEN` | *(puste)* | Token dla `/api/ssh-test`. Puste = endpoint wyłączony (404) |
| `DASHBOARD_API_TOKEN` | *(puste)* | Token dla API konfiguracji (patrz sekcja Bezpieczeństwo) |
| `LOG_VIEWER_ALLOWED_CONTAINERS` | *(puste)* | Lista kontenerów Docker, z których log-viewer może czytać logi (patrz sekcja Bezpieczeństwo) |
| `LOG_VIEWER_ALLOWED_CONTAINER_PATHS` | *(puste)* | Dozwolone prefiksy ścieżek plików w kontenerach (domyślnie: `/var/log/`, `/var/www/html/logs/`) |

## Bezpieczeństwo

### Dashboard API (`/api/config`, `/api/config/hosts`, `/api/config/app`)

Endpointy konfiguracji mogą odczytywać i zapisywać dane poświadczeń SSH. Są chronione przez `ApiTokenListener`:

- Gdy `DASHBOARD_API_TOKEN` jest puste i `APP_ENV=dev` → endpointy dostępne bez uwierzytelnienia (wygoda lokalna)
- Gdy `DASHBOARD_API_TOKEN` jest ustawione → wymagany nagłówek `X-Dashboard-Token: <token>`
- W każdym środowisku innym niż `dev` z pustym tokenem → 401

```bash
# Przykład wywołania z tokenem
curl -H "X-Dashboard-Token: twoj_token" http://localhost:8082/api/config
```

### Endpoint SSH Test (`/api/ssh-test`)

- Gdy `SSH_TEST_TOKEN` puste → endpoint zwraca 404 (wyłączony)
- Gdy `SSH_TEST_TOKEN` ustawione → wymagany nagłówek `X-Dashboard-Token: <SSH_TEST_TOKEN>`

### MCP Server API (`http://localhost:8000/api/*`)

Wszystkie endpointy API MCP (narzędzia, logi, metryki, status) wymagają uwierzytelnienia. Tylko `/api/health` jest publiczne.

Aby uzyskać token:

```bash
# 1. Zaloguj się do panelu admina
curl -s -c /tmp/mcp-cookies.txt \
  -d "username=admin&password=admin" \
  http://localhost:8000/admin/login

# 2. Wywołaj chroniony endpoint z tokenem sesji
SESSION_ID=$(grep 'session' /tmp/mcp-cookies.txt | awk '{print $NF}')
curl -H "Authorization: Bearer $SESSION_ID" \
  http://localhost:8000/api/tools
```

### Log Viewer — czytanie plików z kontenerów Docker

Log Viewer może czytać pliki logów z innych kontenerów przez zamontowany socket Docker (`/var/run/docker.sock`). Funkcja jest **domyślnie wyłączona** (fail-closed) — wymaga jawnego opt-in:

```bash
# W .env: dozwolone kontenery (lista oddzielona przecinkami)
LOG_VIEWER_ALLOWED_CONTAINERS=devbox,mysql

# Opcjonalnie: niestandardowe prefiksy ścieżek (domyślnie: /var/log/, /var/www/html/logs/)
LOG_VIEWER_ALLOWED_CONTAINER_PATHS=/var/log/,/var/www/html/logs/
```

Każde żądanie `?container_id=X&file=/ścieżka` jest sprawdzane:
- Czy `X` jest na liście `LOG_VIEWER_ALLOWED_CONTAINERS` → jeśli nie: 403
- Czy `/ścieżka` zaczyna się od dozwolonego prefiksu → jeśli nie: 403

## Struktura plików

```
docker-fast-php-logger/
├── docker-compose.yml       # Definicja wszystkich usług
├── composer.json            # Root package z zależnościami VCS
├── Dockerfile               # PHP 8.4 + Nginx + Supervisor
├── .env.example             # Wzorzec konfiguracji
├── docker/
│   ├── nginx.conf           # Konfiguracja Nginx (server_tokens off)
│   ├── proxy.conf           # Konfiguracja proxy Nginx
│   ├── php.ini              # PHP config (expose_php = Off)
│   └── supervisor.conf      # Nginx + php-fpm pod Supervisorem
├── public/                  # Entry point Symfony (index.php)
├── src/
│   ├── Controller/
│   │   ├── DashboardController.php
│   │   ├── ApiController.php
│   │   └── SshTestController.php
│   └── EventListener/
│       └── ApiTokenListener.php   # Ochrona endpointów /api/*
├── templates/               # Szablony Twig
└── viewer/                  # Entry point Log Viewer (index.php)
```

## Konwencje

- Commity, PR i komentarze w kodzie: **po angielsku**
- `vendor/` wykluczone z gita (budowany w obrazie Docker)
- Każdy komponent ma własne repozytorium — zmiany w pakietach = commit w siostrzynym repo + `composer update`
