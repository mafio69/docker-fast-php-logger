## Dzisiaj zrobione (2025-05-31)

- ~~PHP 8.4 compatibility - lokalny composer zamiast systemowego~~ ✅
- ~~AIRules.md - zasady dla AI z "STOP bez TAK"~~ ✅
- ~~MdViewer refactor - z chaosu do Symfony MVC~~ ✅
  - ~~Controller + Service + Interface~~
  - ~~Twig template z cyberpunk style~~
  - ~~API endpoint /api/mdviewer/data~~
- ~~SSH Test refactor - z app_old do Service~~ ✅
  - ~~SshTestService~~
  - ~~SshTestController~~
- ~~Routing fix - działające #[Route]~~ ✅
- ~~Dashboard naprawa - template + config~~ ✅
- ~~Usunięty EDITOR_URL z dashboard~~ ✅
- ~~Unit tests + composer test integration~~ ✅
  - ~~SshTestServiceTest (6 tests)~~
  - ~~MdViewerServiceTest (8 tests)~~
  - ~~MdViewerControllerTest (5 tests)~~
  - ~~SshTestControllerTest (5 tests)~~
  - ~~27 tests, 332 assertions - all passing~~ ✅

---

## AUDYT KONFIGURACJI (2025-05-31) 🔍

### Znalezione Problemy:

| Problem | Poziom | Opis | Plik |
|---------|--------|------|------|
| **Duplikacja SSH endpoint** | 🔴 HIGH | Dwa endpointy SSH: `/api/ssh-test` (stary YAML) i `/api/ssh/test` (nowy Controller) | routes.yaml + SshTestController |
| **MdViewer templates path** | 🟡 MEDIUM | Ścieżka `__DIR__ . '/../../templates'` może nie działać w kontenerze | MdViewerController.php:22 |
| **Stale routes dla SSH** | 🟡 MEDIUM | ApiController::sshTest nadal istnieje - prawdopodobnie nieużywany | ApiController.php |
| **Brak czyszczenia cache** | 🟡 MEDIUM | Brak automatycznego czyszczenia Symfony cache po zmianach | - |
| **Legacy files** | 🟢 LOW | `app_old/`, `public/mdviewer.html` nadal istnieją | app_old/, public/ |

### Endpointy - Status:

| Endpoint | Controller | Status | Uwagi |
|----------|------------|--------|-------|
| `/` | DashboardController | ✅ OK | |
| `/logs` | LogController | ✅ OK | |
| `/mdviewer` | MdViewerController | ✅ OK | Attribute routing |
| `/api/mdviewer/data` | MdViewerController | ✅ OK | GET |
| `/api/ssh/test` | SshTestController | ✅ OK | POST |
| `/api/ssh-test` | ApiController | ⚠️ STARY | Do usunięcia |
| `/api/config` | ApiController | ✅ OK | |
| `/api/config/hosts` | ApiController | ✅ OK | POST |
| `/api/config/app` | ApiController | ✅ OK | POST |

---

## Do zrobienia (kolejność priorytetowa):

### 🔴 HIGH Priority:

1. **~~Usunąć duplikację SSH endpoint~~** ✅
   - [x] ~~Zmieniono ścieżkę w SshTestController na `/api/ssh-test`~~
   - [x] ~~Usunięto duplikat z `app/config/routes.yaml`~~
   - [x] ~~Sprawdzono - logs/index.html.twig używa `/api/ssh-test`~~

### 🟡 MEDIUM Priority:

2. ~~**Wyczyścić legacy files**~~ ✅
   - ~~[ ] Usunąć `app_old/` (cały katalog)~~ ✅
   - ~~[ ] Usunąć `public/mdviewer.html` (stary plik)~~ ✅
   - ~~[ ] Sprawdzić czy `app/docs-browser.php`, `app/index.php`, `app/logs.php` są używane~~ ✅

3. **Poprawić MdViewer template path**
   - [ ] Sprawdzić czy templates ładują się poprawnie w kontenerze
   - [ ] Jeśli nie - poprawić ścieżkę w MdViewerController

4. ~~**Dodać cache clearing do workflow**~~ ✅
   - ~~[ ] Opcjonalnie: dodać `cache:clear` do composer scripts~~ ✅
   - ~~[ ] Lub: dokumentacja w README jak czyścić cache~~ ✅

### 🟢 LOW Priority:

5. **Code Review / PR**
   - [ ] Commit wszystkich zmian
   - [ ] Push do GitHub
   - [ ] Utworzyć PR z opisem

---

## KONTYNUACJA PRACY PO PRZERWIE 🔄

### Aktualny status (2026-06-05):
- **Branch**: `feature/duckdb-statistics`
- **Ostatnie commit**: `2d1278e` (wszystko pushed)
- **Aplikacja**: Działa poprawnie (localhost:8082)
- **Testy**: 11/11 przechodzą
- **Log Viewer**: Zaktualizowany do dev-master, zielona skórka CRT działa

### Co zrobione w ostatniej sesji:
1. ✅ Log Viewer - pełna naprawa i aktualizacja
2. ✅ Czyszczenie legacy files (app/, viewer/, home/, patches/)
3. ✅ Testy dla LogController i LogViewerService
4. ✅ GitHub Actions do auto-update log-viewer
5. ✅ PR Monitor skill/config (dla przyszłego wykorzystania)
6. ✅ Dokumentacja zaktualizowana

### Pierwsze kroki po powrocie:
```bash
# 1. Sprawdź status
git status
git pull origin feature/duckdb-statistics

# 2. Uruchom kontener
docker-compose up -d

# 3. Sprawdź czy działa
curl http://localhost:8082/logs

# 4. Uruchom testy
composer test
```

### Następne zadania (priorytet):
1. **Poprawić MdViewer template path** (jeśli potrzebne)
2. **Dodać cache clearing do workflow** (opcjonalne)
3. **Code Review / PR** - merge do master

### Ważne pliki:
- `TO-DO.md` - ten plik
- `composer.json` - zaktualizowany do dev-master log-viewer
- `src/Service/LogViewerService.php` - nowe ścieżki log-viewer
- `.github/workflows/update-log-viewer.yml` - auto-update
- `.devin/config.json` - PR Monitor agent

### Uwagi:
- Log viewer używa teraz `dev-master` - może wymagać ręcznego update czasem
- PR Monitor agent jest skonfigurowany ale nie aktywny (wymaga dodatkowej konfiguracji GitHub)
- Wszystkie legacy files usunięte - czysta struktura Symfony
- **Nowe composer scripts dla cache:**
  - `composer cache:clear` - czyści cache
  - `composer cache:warmup` - nagrzewa cache
  - `composer cache:clear-warmup` - czyści i nagrzewa cache
- **GitHub Actions automatycznie czyści cache** po aktualizacji log-viewer

---
Ocena
Komentarz
React vs. Vue.js prioritet
⚠️ Odwrotnie
Dokument wymienia Vue.js jako "preferred", ale w index.php rzeczywiście używa Vue.js ✓
jQuery jako narzędzie
⚠️ Anachronizm
jQuery w 2026 roku? W projekcie nie widać jQuery. Rekomendacja: usunąć lub oznaczyć jako "legacy"
Symfony Standards + php-cs-fixer
❓ Nie zweryfikowane
Dokument deklaruje, ale brak php-cs-fixer w composer.json — powinno być w dev dependencies
gRPC communication
❓ Nie używane
Deklaracja o "well-defined APIs" via gRPC, ale projekt nie zawiera gRPC — zbyt zaawansowane dla tego scope'u
React-PHP asynchronicity
❓ Nie używane
Dokument wspomina react-php, ale projekt to tradycyjne PHP + Apache
Microservices architecture
❓ Nie dotyczy
Dokument mówi o "constellation of services", ale to monolith + Docker — przesada
Twelve-factor app
✓ Faktycznie
.env configuration + externalized config.json ✓
AI Collaboration
✓ Aktualne
Sekcja jest realistyczna i praktyczna## Code Correctness & Robustness
- **Functionality:** Code is not "done" until it works
- **Testing Strategy:** Auto tests (unit/integration), manual QA for critical paths
- **Code Coverage:** Target 70%+ for services, 90%+ for security-critical code
