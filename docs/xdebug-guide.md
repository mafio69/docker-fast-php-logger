# Xdebug — konfiguracja debuggera

## Jak działa w tym projekcie

Kontener Docker ma zainstalowany Xdebug 3 w trybie `debug` (step debugger).
Xdebug łączy się z IDE na porcie **9003** pod adresem `host.docker.internal`.

## Konfiguracja (już gotowa)

**docker/xdebug.ini:**
```ini
zend_extension=xdebug
xdebug.mode=${XDEBUG_MODE}          # ustawione w docker-compose: debug
xdebug.start_with_request=yes       # zawsze aktywny
xdebug.client_host=${XDEBUG_CLIENT_HOST}  # host.docker.internal
xdebug.client_port=9003
xdebug.idekey=PHPSTORM
```

## PhpStorm

1. **Settings → PHP → Debug** → port: `9003`
2. **Settings → PHP → Servers** → dodaj server:
   - Name: `docker-fast-logger`
   - Host: `localhost`, Port: `8080`
   - Debugger: Xdebug
   - Path mappings:
     - `/var/www/html/app` → `./app`
     - `/var/www/html/viewer` → `./viewer`
     - `/var/www/html/vendor` → `./vendor`
     - `/var/www/html/logs` → `./logs`
3. Kliknij **Start Listening** (ikonka słuchawki/bug)
4. Otwórz `http://localhost:8080/app/` — debugger się zatrzyma na breakpoincie

## VSCode

Konfiguracja już gotowa w `.vscode/launch.json`:
1. Otwórz projekt w VSCode
2. Zainstaluj rozszerzenie **PHP Debug** (xdebug.php-debug)
3. F5 → wybierz "Listen for Xdebug (Docker)"
4. Ustaw breakpoint, otwórz stronę w przeglądarce

## Kiro CLI (debugowanie z terminala)

Xdebug działa też dla skryptów CLI wewnątrz kontenera:

```bash
# Uruchom skrypt z debuggerem (IDE musi nasłuchiwać):
docker compose exec php php -dxdebug.mode=debug app/index.php

# Sprawdź czy Xdebug jest aktywny:
docker compose exec php php -v | grep Xdebug
```

## Troubleshooting

```bash
# Sprawdź logi Xdebug:
cat logs/xdebug.log

# Zwiększ log level (w docker/xdebug.ini):
xdebug.log_level=7

# Sprawdź czy port 9003 jest otwarty na hoście:
ss -tlnp | grep 9003

# Restart po zmianach:
docker compose restart php
```

## Porty

| Usługa | Port |
|--------|------|
| Apache (app) | 8080 |
| Xdebug | 9003 |
| MySQL | 3306 |
| Portainer | 9000 |
| Mailpit UI | 8025 |
| Mailpit SMTP | 1025 |
