#!/bin/bash
# ============================================================
# setup.sh — jednorazowa konfiguracja środowiska dev
# Uruchom: bash setup.sh
# ============================================================

set -e
echo "🚀 Konfiguracja docker-fast-php-logger..."

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

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
if grep -q "app.local" /etc/hosts 2>/dev/null; then
    echo "   ✓ Domeny już dodane"
else
    docker run --rm -v /etc/hosts:/etc/hosts alpine sh -c \
        'echo "127.0.0.1 app.local logs.local portainer.local mail.local pma.local adminer.local" >> /etc/hosts'
    echo "   ✓ Dodano: app.local logs.local portainer.local mail.local pma.local adminer.local"
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

# ── 4. Docker build & up ──────────────────────────────────────
echo ""
echo "📌 Buduję i uruchamiam kontenery..."
cd "$PROJECT_DIR"
docker compose up -d --build

# ── 5. Podsumowanie ───────────────────────────────────────────
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
