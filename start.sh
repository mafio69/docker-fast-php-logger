#!/bin/bash
# Zwalnia port 80 i uruchamia środowisko dev

IS_WSL=false
if grep -qi microsoft /proc/version 2>/dev/null || grep -qi wsl /proc/version 2>/dev/null; then
    IS_WSL=true
fi

PORT80=$(docker ps --filter "publish=80" --format "{{.ID}}" | head -1)
if [ -n "$PORT80" ]; then
    NAME=$(docker ps --filter "id=$PORT80" --format "{{.Names}}")
    echo "⚠ Port 80 zajęty przez: $NAME — zatrzymuję..."
    docker stop "$PORT80"
fi

if $IS_WSL; then
    if ss -tlnp 2>/dev/null | grep -q ":80 " || netstat -tlnp 2>/dev/null | grep -q ":80 "; then
        echo "⚠ Port 80 może być zajęty przez Windows (IIS, W3SVC)."
        echo "   W PowerShell (Admin): Stop-Service W3SVC -Force"
        echo "   Lub: netsh http show servicestate | findstr :80"
    fi
fi

cd "$(dirname "$0")"
docker compose up -d
echo "✅ http://app.local gotowe"
