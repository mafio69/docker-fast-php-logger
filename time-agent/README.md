# Time Agent

Agent monitorujący Time Doctor - część docker-fast-php-logger suite.

## Instalacja

```bash
cd time-agent
composer install
```

## Uruchomienie

```bash
# Ręcznie
php bin/time-agent

# W tle
nohup php bin/time-agent > /dev/null 2>&1 &
```

## Funkcje

- ✅ Pyta o tryb przy starcie (praca/prywatne)
- ✅ Wykrywa przerwy (blokada ekranu)
- ✅ Pokazuje alerty gdy TD nie działa
- ✅ Przycisk "Wyłącz do jutra 6:00"
- ✅ Symfony Console + Process

## Struktura

```
time-agent/
├── bin/time-agent      # Główny skrypt
├── src/
│   ├── Kernel.php      # Symfony kernel
│   └── Command/
│       └── MonitorCommand.php  # Główna logika
├── var/                # Logi
└── composer.json
```
