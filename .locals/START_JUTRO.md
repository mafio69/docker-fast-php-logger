# START JUTRO - DevBox

## Status (co działa)
```
✅ DevBox Dashboard    http://localhost:8794
✅ Log Viewer          http://localhost:8795/logviewer.html  
✅ MD Viewer           http://localhost:8794/mdviewer.html
✅ Portainer           http://localhost:9000
✅ Adminer             http://localhost:8081
✅ Mailpit             http://localhost:8025
```

## Start rano
```bash
cd /path/to/docker-fast-php-logger
docker compose up -d
echo "DevBox ready at http://localhost:8794"
```

## Co dokończyć (w kolejności)

### 1. Dashboard tiles (PRIORYTET)
Problem: Przeglądarka cache'uje stary plik
Rozwiązanie:
- Wymuś odświeżenie: Ctrl+F5 lub tryb incognito
- LUB usuń cache przeglądarki dla localhost
- Sprawdź czy widać kafelki zamiast "CONTROLS"

### 2. Log Viewer - dane rzeczywiste
Teraz: Mock data
Do zrobienia:
- Endpoint PHP czytający /app/logs
- WebSocket lub polling na żywo
- Podłączenie pod istniejący UI

### 3. Time Agent (opcjonalnie)
Decyzja: Czy włączyć do DevBox?
- Jako osobny kontener z GUI?
- Czy jako proces w kontenerze devbox?
- Czy na hoscie (bez Dockera)?

### 4. MCP Server (opcjonalnie)
Port 8795 jest zajęty przez Log Viewer
Opcje:
- Zmienić port MCP (np. 8890)
- Lub połączyć MCP z Log Viewerem w jeden

## Problemy znane
1. Port 8080 był zajęty - Adminer jest na 8081 ✓
2. Dashboard pokazuje stary plik - cache przeglądarki
3. Nie ma jeszcze czytania logów z plików (tylko mock)
4. SSH do logów - na później

## Szybkie komendy
```bash
# Restart wszystkiego
docker compose restart

# Logi
docker compose logs -f

# Wejdź do kontenera
docker exec -it devbox sh

# Zatrzymaj wszystko
docker compose down
```

## Decyzje do podjęcia
1. Czy dashboard ma być tylko landing page, czy też sterować serwisami?
2. Czy dodawać więcej narzędzi do tile'i (RabbitMQ, Redis, MySQL)?
3. Czy Log Viewer ma być osobną usługą, czy częścią devbox?

---
Ostatnia aktualizacja: 2026-05-27
