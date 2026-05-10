# docker-fast-php-logger

Środowisko dev: PHP 8.3 + Apache + MySQL 8 + narzędzia.  
Preinstalowane: [fast-php-logger](https://github.com/mafio69/fast-php-logger) + [fast-php-log-viewer](https://github.com/mafio69/fast-php-log-viewer).

---

## Wymagania

- Docker >= 24
- Docker Compose >= 2.20
- Plik `.env` (skopiuj `.env.example` i uzupełnij)

---

## Instalacja

```sh
git clone https://github.com/mafio69/docker-fast-php-logger.git
cd docker-fast-php-logger
bash setup.sh          # przy pierwszym uruchomieniu utworzy .env — uzupełnij
bash setup.sh          # uruchom ponownie po uzupełnieniu .env
source ~/.bashrc
```

Skrypt `setup.sh` automatycznie:
- Sprawdza `.env` (jeśli brak — kopiuje `.env.example` i prosi o uzupełnienie)
- Buduje i uruchamia wszystkie kontenery
- Dodaje domeny `.local` do `/etc/hosts`
- Konfiguruje PATH, aliasy i zmienne środowiskowe

---

## Adresy

| Adres | Opis |
|---|---|
| http://app.local | Dashboard — Twój kod PHP |
| http://logs.local | Przeglądarka logów |
| http://pma.local | phpMyAdmin |
| http://adminer.local | Adminer |
| http://mail.local | Mailpit — przechwycone maile |
| http://portainer.local | Portainer — zarządzanie kontenerami |

Na każdej stronie jest przycisk **☰** (prawy dolny róg) — rozwija nawigację między serwisami.

---

## Codzienne komendy

```sh
# Start / stop
docker compose up -d
docker compose down

# Rebuild po zmianach w Dockerfile lub composer.json
docker compose up -d --build php

# Shell w kontenerze
docker compose exec php bash

# Logi PHP (live)
docker compose logs -f php

# Generowanie testowych logów
docker compose exec php php /var/www/html/app/seed_logs.php

# Maskowanie danych w terminalu
TOKEN=$(mask "Podaj token")
```

---

## Struktura projektu

```
app/                ← Twój kod PHP (live reload, montowany jako volume)
app/suite-nav.js    ← Nawigacja dropdown (wstrzykiwana na wszystkie serwisy)
logs/               ← Pliki logów (widoczne na hoście i w kontenerze)
viewer/             ← Entry point log viewera (kopiowany do obrazu)
docker/             ← php.ini, xdebug.ini, nginx-inject.conf
bin/mask            ← Narzędzie do maskowania danych
setup.sh            ← Instalacja (uruchom raz)
.env                ← Konfiguracja lokalna — NIE commituj!
```

---

## Kiedy rebuild?

| Zmiana | Komenda |
|---|---|
| Pliki w `app/` | Nic — live reload |
| `viewer/` lub `Dockerfile` | `docker compose up -d --build php` |
| `composer.json` | `docker compose up -d --build php` |
| `docker-compose.yml` | `docker compose up -d` |
| `docker/nginx-inject.conf` | `docker compose restart proxy` |

---

## Xdebug

Zainstalowany i aktywny. Konfiguracja: [docs/xdebug-guide.md](docs/xdebug-guide.md)

---

## Baza danych

| Parametr | Wartość |
|---|---|
| Host | `db` (z kontenera) / `localhost` (z hosta) |
| Port | 3306 |
| Database | app |
| User | app |
| Password | secret |
| Root password | root |

---

## Powiązane repozytoria

| Repo | Opis |
|---|---|
| [fast-php-logger](https://github.com/mafio69/fast-php-logger) | PSR-3 logger z anonimizacją |
| [fast-php-log-viewer](https://github.com/mafio69/fast-php-log-viewer) | UI do przeglądania logów |
