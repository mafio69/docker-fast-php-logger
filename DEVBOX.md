# DevBox - Zero Config Developer Suite

## Filozofia
```bash
git clone <repo>
cd devbox
docker-compose up
# Gotowe - wszystko działa
```

## Co jest w środku (jeden kontener PHP)

```
Symfony 7 + SQLite (zero config baza)
├── Logger      → pliki (/logs)
├── MCP Server  → Command symfony (port 8080)
├── Time Agent  → Command symfony + proces w tle
└── Dashboard   → Web UI (port 80)
```

## Brak zewnętrznych zależności

❌ MySQL - niepotrzebny, SQLite wystarczy  
❌ RabbitMQ - overkill dla 1 osoby  
❌ Redis - file-based cache  
❌ Osobne kontenery - wszystko w jednym  

✅ Tylko: Docker + docker-compose

## Architektura

```php
// Jeden kernel, wiele "modułów" jako services
src/
├── Core/
│   └── Service/DevBoxKernel.php
├── Logger/Service/LogAggregator.php
├── MCP/Service/McpRunner.php        // Uruchamia MCP w tle
├── Agent/Service/AgentRunner.php    // Uruchamia Time Agent w tle
└── Dashboard/Controller/
```

## Auto-konfiguracja

```php
// Przy starcie wykrywa:
- Czy jest GUI (X11)? → włącza Time Agent
- Czy jest port 8080 wolny? → włącza MCP
- Tworzy SQLite jeśli nie istnieje
```

## Komendy

```bash
# Wszystko w jednym:
docker-compose up

# Osobno (opcjonalnie):
docker-compose exec app php bin/console agent:start
docker-compose exec app php bin/console mcp:start
docker-compose exec app php bin/console logger:tail
```
