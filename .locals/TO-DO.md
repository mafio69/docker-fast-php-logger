# Audyt - Lista zadań do wykonania

## Ogólne
- [ ] **AIREADME.md**: Plik zawiera mieszankę języka polskiego i angielskiego. Należy go ujednolicić do języka angielskiego, zgodnie z przyjętymi konwencjami.
- [ ] **CODING_STANDARDS.md**: Plik jest bardzo długi i zawiera wersje w obu językach. Rozważ podzielenie go na dwa osobne pliki (`CODING_STANDARDS.en.md` i `CODING_STANDARDS.pl.md`) oraz skrócenie treści do najważniejszych zasad.
- [ ] **.kiro/steering/project.md**: Plik zawiera mieszankę języka polskiego i angielskiego. Należy go ujednolicić do języka angielskiego.

## docker-compose.yml
- [ ] **Hardcoded secrets**: W serwisie `db` hasła `MYSQL_PASSWORD` i `MYSQL_ROOT_PASSWORD` są zahardkodowane. W serwisie `phpmyadmin` hasło `PMA_PASSWORD` jest zahardkodowane. Przenieś je do zmiennych środowiskowych w pliku `.env`.
- [ ] **Niespójne nazewnictwo zmiennych**: W serwisie `php` używana jest zmienna `GIT_ACCES_TOKEN`, podczas gdy w pliku `.env` prawdopodobnie jest `GITHUB_TOKEN`. Zmienna `motherduck_token` powinna być zapisana jako `MOTHERDUCK_TOKEN` dla spójności.
- [ ] **Nieużywany wolumen**: Serwis `php` montuje katalog `docs`, który nie wydaje się być używany. Zweryfikuj jego potrzebę i ewentualnie usuń.
- [ ] **Zależność od lokalnej ścieżki**: Serwis `php` montuje `../PhpstormProjects/fast-php-log-viewer`, co jest ścieżką spoza projektu. To utrudnia przenoszalność. Zastąp to rozwiązanie np. poprzez sklonowanie repozytorium do podkatalogu projektu lub użycie Composera do zarządzania zależnościami.
