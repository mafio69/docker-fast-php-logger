#!/bin/bash
# ============================================================
# setup.sh — jednorazowa konfiguracja środowiska dev
# Uruchom: bash setup.sh
# ============================================================

set -e
echo "🚀 Konfiguracja docker-fast-php-logger..."

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

# ── 1. /etc/hosts (domeny .local) ─────────────────────────────
echo ""
echo "📌 Dodaję domeny do /etc/hosts..."
if grep -q "app.local" /etc/hosts 2>/dev/null; then
    echo "   ✓ Domeny już dodane"
else
    docker run --rm -v /etc/hosts:/etc/hosts alpine sh -c \
        'echo "127.0.0.1 app.local logs.local portainer.local mail.local" >> /etc/hosts'
    echo "   ✓ Dodano: app.local logs.local portainer.local mail.local"
fi

# ── 2. PATH (bin/mask) ────────────────────────────────────────
echo ""
echo "📌 Dodaję bin/ do PATH..."
if grep -q "docker-fast-php-logger/bin" ~/.bashrc 2>/dev/null; then
    echo "   ✓ PATH już skonfigurowany"
else
    echo "export PATH=\"$PROJECT_DIR/bin:\$PATH\"" >> ~/.bashrc
    echo "   ✓ Dodano $PROJECT_DIR/bin do PATH"
fi

# ── 3. DUCK_TOKEN ─────────────────────────────────────────────
echo ""
echo "📌 Eksportuję DUCK_TOKEN..."
if grep -q "DUCK_TOKEN" ~/.bashrc 2>/dev/null; then
    echo "   ✓ DUCK_TOKEN już wyeksportowany"
else
    DUCK_TOKEN=$(grep DUCK_TOKEN "$PROJECT_DIR/.env" | cut -d= -f2)
    echo "export DUCK_TOKEN=\"$DUCK_TOKEN\"" >> ~/.bashrc
    echo "   ✓ DUCK_TOKEN dodany do .bashrc"
fi

# ── 4. Alias duckconnect ──────────────────────────────────────
echo ""
echo "📌 Dodaję alias duckconnect..."
if grep -q "duckconnect" ~/.bashrc 2>/dev/null; then
    echo "   ✓ Alias już istnieje"
else
    echo 'alias duckconnect="duckdb \"md:my_db?motherduck_token=\$DUCK_TOKEN\""' >> ~/.bashrc
    echo "   ✓ Alias duckconnect dodany"
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
echo "   http://app.local/logs   — log viewer"
echo "   http://mail.local       — mailpit (poczta dev)"
echo "   http://portainer.local  — zarządzanie kontenerami"
echo ""
echo "🔧 Komendy:"
echo "   mask          — maskowanie wrażliwych danych"
echo "   duckconnect   — połączenie z MotherDuck"
echo "═══════════════════════════════════════════════"
