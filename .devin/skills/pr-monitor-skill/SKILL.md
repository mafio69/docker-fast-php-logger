# PR Monitor Skill

Monitoruje i reaguje na Pull Requests w dwóch repozytoriach projektu:
- suite (docker-fast-logger)
- log-viewer (fast-php-log-viewer)

## Kiedy się aktywuje

Skill aktywuje się automatycznie gdy:
1. Utworzony zostanie nowy Pull Request w dowolnym z dwóch repozytoriów
2. Zmieniony zostanie status istniejącego PR (review, comment, update)

## Co robi

### Po utworzeniu PR:
1. Analizuje zmienione pliki
2. Sprawdza czy wpływają na inne repozytoria
3. Oznacza zależności między repozytoriami
4. Sugeruje testy integracyjne

### Po zmianie statusu PR:
1. Przy approval - przygotowuje merge
2. Przy request changes - sugeruje poprawki
3. Przy comment - analizuje czy wymaga akcji

## Repozytoria

### 1. Suite (docker-fast-logger)
Główna aplikacja Symfony z log-viewer
- **Trigger**: zmiany w src/, templates/, config/, composer.json
- **Impact**: może wymagać aktualizacji log-viewer

### 2. Log-Viewer (fast-php-log-viewer)
Samodzielna biblioteka do przeglądania logów
- **Trigger**: zmiany w public/, src/, composer.json
- **Impact**: może wymagać aktualizacji suite

## Konfiguracja

Skill wymaga dostępu do GitHub API z odpowiednimi permissions:
- `pull_requests: read`
- `pull_requests: write` (do komentarzy)
- `repos: read` (dla obu repo)

## Przykład użycia

Agent automatycznie:
```bash
# Gdy PR w log-viewer zmienia API
→ Oznaczy PR w suite jako "zależny od log-viewer#123"
→ Sugeruje aktualizację composer require

# Gdy PR w suite zmienia LogViewerService
→ Sugeruje testy jednostkowe dla log-viewer
→ Oznaczy jako wymagające aktualizacji log-viewer
```