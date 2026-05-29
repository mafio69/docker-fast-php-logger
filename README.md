# Developer Suite

Kompletne środowisko deweloperskie: Logger + MCP Server + Time Agent

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
| **MCP Server** | Slim 4 | 8080 | Model Context Protocol server |
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
- **http://localhost:8080** - MCP Server
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
