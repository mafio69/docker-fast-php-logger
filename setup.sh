#!/bin/bash
# ============================================================
# setup.sh — jednorazowa konfiguracja środowiska dev
# Uruchom: bash setup.sh
# ============================================================

set -e
echo "🚀 Konfiguracja docker-fast-php-logger..."

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
IS_WSL=false
if grep -qi microsoft /proc/version 2>/dev/null || grep -qi wsl /proc/version 2>/dev/null; then
    IS_WSL=true
    echo "🪟 Wykryto WSL2 – konfiguracja pod Windows 11"
fi

# ── 1. Plik .env ──────────────────────────────────────────────
echo ""
echo "📌 Sprawdzam plik .env..."
if [ -f "$PROJECT_DIR/.env" ]; then
    echo "   ✓ .env istnieje"
else
    cp "$PROJECT_DIR/.env.example" "$PROJECT_DIR/.env"
    echo "   ⚠ Skopiowano .env.example → .env"
    echo "   ✏️  Uzupełnij .env i uruchom setup.sh ponownie."
    exit 1
fi

# ── 2. /etc/hosts (domeny .local) ─────────────────────────────
echo ""
echo "📌 Dodaję domeny do /etc/hosts..."
HOSTS_ENTRY="127.0.0.1 app.local logs.local portainer.local mail.local pma.local adminer.local mdviewer.local"
if grep -q "app.local" /etc/hosts 2>/dev/null; then
    echo "   ✓ Domeny już dodane w WSL"
else
    echo "$HOSTS_ENTRY" | sudo tee -a /etc/hosts > /dev/null
    echo "   ✓ Dodano do WSL /etc/hosts"
fi

if $IS_WSL; then
    echo ""
    echo "⚠️  Windows hosts – dodaj ręcznie (jako Administrator):"
    echo "   Notatnik → Otwórz: C:\\Windows\\System32\\drivers\\etc\\hosts"
    echo "   Dopisz na końcu:"
    echo "   $HOSTS_ENTRY"
    echo ""
    read -p "   Naciśnij Enter po dodaniu wpisów..."
fi

# ── 3. PATH (bin/mask) ────────────────────────────────────────
echo ""
echo "📌 Dodaję bin/ do PATH..."
if grep -q "docker-fast-php-logger/bin" ~/.bashrc 2>/dev/null; then
    echo "   ✓ PATH już skonfigurowany"
else
    echo "export PATH=\"$PROJECT_DIR/bin:\$PATH\"" >> ~/.bashrc
    echo "   ✓ Dodano $PROJECT_DIR/bin do PATH"
fi

# ── 4. Zwolnij port 80 ────────────────────────────────────────
echo ""
echo "📌 Sprawdzam port 80..."
PORT80=$(docker ps --filter "publish=80" --format "{{.ID}}" | head -1)
if [ -n "$PORT80" ]; then
    echo "   ⚠ Port 80 zajęty przez kontener $PORT80 — zatrzymuję..."
    docker stop "$PORT80"
fi

# ── 5. Docker build & up ──────────────────────────────────────
echo ""
echo "📌 Buduję i uruchamiam kontenery..."
cd "$PROJECT_DIR"
docker compose up -d --build

# ── 6. Podsumowanie ───────────────────────────────────────────
echo ""
echo "═══════════════════════════════════════════════"
echo "✅ Gotowe! Uruchom: source ~/.bashrc"
echo ""
echo "🌐 Adresy:"
echo "   http://app.local        — aplikacja PHP"
echo "   http://logs.local       — log viewer"
echo "   http://mail.local       — mailpit (poczta dev)"
echo "   http://portainer.local  — zarządzanie kontenerami"
echo "   http://pma.local        — phpMyAdmin"
echo "   http://adminer.local    — Adminer"
echo ""
echo "🔧 Komendy:"
echo "   mask          — maskowanie wrażliwych danych"
echo "═══════════════════════════════════════════════"
