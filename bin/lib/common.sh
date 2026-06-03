#!/bin/bash
# Shared utilities for shell scripts

detect_wsl() {
    if grep -qi microsoft /proc/version 2>/dev/null || grep -qi wsl /proc/version 2>/dev/null; then
        return 0
    fi
    return 1
}

free_port_80() {
    local container_id
    container_id=$(docker ps --filter "publish=80" --format "{{.ID}}" | head -1)
    if [ -n "$container_id" ]; then
        local name
        name=$(docker ps --filter "id=$container_id" --format "{{.Names}}")
        echo "⚠ Port 80 zajęty przez: ${name:-$container_id} — zatrzymuję..."
        docker stop "$container_id"
    fi
}
