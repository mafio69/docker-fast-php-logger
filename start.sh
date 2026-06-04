#!/bin/bash
# Zwalnia port 80 i uruchamia środowisko dev

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$SCRIPT_DIR/bin/lib/common.sh"

free_port_80

if detect_wsl; then
    if ss -tlnp 2>/dev/null | grep -q ":80 " || netstat -tlnp 2>/dev/null | grep -q ":80 "; then
        echo "⚠ Port 80 może być zajęty przez Windows (IIS, W3SVC)."
        echo "   W PowerShell (Admin): Stop-Service W3SVC -Force"
        echo "   Lub: netsh http show servicestate | findstr :80"
    fi
fi

cd "$(dirname "$0")"
docker compose up -d
echo "✅ http://app.local gotowe"
