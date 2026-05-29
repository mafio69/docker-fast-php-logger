#!/bin/bash

echo "╔═══════════════════════════════════════╗"
echo "║        DEVBOX INITIALIZING...         ║"
echo "╚═══════════════════════════════════════╝"

# Create necessary directories
mkdir -p /app/logs /app/data /run/nginx
chown -R www-data:www-data /app/logs /app/data

# Start PHP-FPM
echo "[1/3] Starting PHP-FPM..."
php-fpm -D

# Start Nginx
echo "[2/3] Starting Nginx..."
nginx

# Health check
echo "[3/3] Health check..."
sleep 2
if curl -s http://localhost/ > /dev/null; then
    echo "✅ DevBox ready at http://localhost"
else
    echo "⚠️  Warning: HTTP check failed"
fi

echo ""
echo "Services:"
echo "  📊 Dashboard:  http://localhost"
echo "  📄 MD Viewer:  http://localhost/mdviewer.html"
echo "  🔧 MCP Server: http://localhost:8080"
echo ""
echo "Press Ctrl+C to stop"

# Keep container running
tail -f /dev/null
