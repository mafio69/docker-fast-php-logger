# docker-fast-php-logger

> Gotowe środowisko deweloperskie PHP + Apache + MySQL z preinstalowanymi pakietami
> [fast-php-logger](https://github.com/mafio69/fast-php-logger) i
> [fast-php-log-viewer](https://github.com/mafio69/fast-php-log-viewer).
> Część zestawu **fast-php-\***.

---

## Instalacja (dla nowego dewelopera)

```sh
git clone https://github.com/mafio69/docker-fast-php-logger.git
cd docker-fast-php-logger
bash setup.sh
source ~/.bashrc
```

**To wszystko.** Skrypt `setup.sh` zrobi resztę:
- Zbuduje i uruchomi kontenery Docker
- Doda domeny lokalne (app.local, mail.local, portainer.local)
- Skonfiguruje PATH, aliasy i zmienne środowiskowe

---

## Adresy po instalacji

| Adres | Co tam jest |
|---|---|
| http://app.local | Aplikacja PHP |
| http://app.local/logs | Przeglądarka logów |
| http://mail.local | Mailpit — przechwycone maile |
| http://portainer.local | Portainer — zarządzanie kontenerami |

Fallback (bez proxy): `http://localhost:8080`

---

## Przydatne komendy

```sh
# Maskowanie wrażliwych danych w terminalu:
TOKEN=$(mask "Podaj token")

# Połączenie z MotherDuck:
duckconnect

# Restart kontenerów:
docker compose restart

# Logi PHP:
docker compose logs -f php

# Shell w kontenerze:
docker compose exec php bash

# Generowanie przykładowych logów:
docker compose exec php php /var/www/html/app/seed_logs.php
```

---

## Architektura

```
docker-fast-php-logger/
├── app/                ← Twój kod PHP (live reload)
├── logs/               ← Pliki logów (widoczne na hoście)
├── viewer/             ← Log viewer (serwowany pod /logs)
├── docker/             ← Konfiguracja PHP, Xdebug
├── bin/mask            ← Narzędzie do maskowania danych w terminalu
├── docs/              
│   └── xdebug-guide.md ← Instrukcja konfiguracji debuggera
├── setup.sh            ← Skrypt instalacyjny (uruchom raz)
├── docker-compose.yml  ← Serwisy: php, db, proxy, mailpit, portainer
└── .env                ← Tokeny i konfiguracja (nie commituj!)
```

---

## Serwisy Docker

| Serwis | Obraz | Opis |
|---|---|---|
| php | PHP 8.3 + Apache + Xdebug | Aplikacja |
| db | MySQL 8.0 | Baza danych |
| proxy | nginx-proxy | Routing domen .local |
| mailpit | Mailpit | Przechwytywanie maili |
| portainer | Portainer CE | GUI do Dockera |

---

## Xdebug

Xdebug jest zainstalowany i aktywny. Szczegóły konfiguracji: [docs/xdebug-guide.md](docs/xdebug-guide.md)

---

## Zestaw fast-php-*

| Pakiet | Opis |
|---|---|
| [fast-php-logger](https://github.com/mafio69/fast-php-logger) | PSR-3 logger z anonimizacją |
| [fast-php-log-viewer](https://github.com/mafio69/fast-php-log-viewer) | UI do przeglądania logów |
| [docker-fast-logger](https://github.com/mafio69/docker-fast-php-logger) | To repo — środowisko Docker |

---

## Wymagania

- Docker >= 24
- Docker Compose >= 2.20
- Token GitHub (`GIT_ACCES_TOKEN` w `.env`)
