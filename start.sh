#!/bin/bash
# Zwalnia port 80 i uruchamia środowisko dev

PORT80=$(docker ps --filter "publish=80" --format "{{.ID}}" | head -1)
if [ -n "$PORT80" ]; then
    NAME=$(docker ps --filter "id=$PORT80" --format "{{.Names}}")
    echo "⚠ Port 80 zajęty przez: $NAME — zatrzymuję..."
    docker stop "$PORT80"
fi

cd "$(dirname "$0")"
docker compose up -d
echo "✅ http://app.local gotowe"
