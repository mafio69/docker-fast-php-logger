# docker-fast-php-logger

> PHP dev environment with [fast-php-logger](https://github.com/mafio69/fast-php-logger)
> and [fast-php-log-viewer](https://github.com/mafio69/fast-php-log-viewer) pre-installed.
> Part of the **fast-php-\*** suite.

## Quick start

```sh
git clone https://github.com/mafio69/docker-fast-php-logger.git
cd docker-fast-php-logger
docker compose up --build
```

| URL | What |
|---|---|
| `http://localhost:8080` | Your PHP app |
| `http://localhost:8080/logs` | Log viewer UI |

## How to use with your own project

Mount your code instead of the example `app/`:

```yaml
# docker-compose.yml
volumes:
  - /path/to/your/project:/var/www/html/app
  - ./logs:/var/www/html/logs
```

Then in your PHP code:

```php
require_once '/var/www/html/vendor/autoload.php';

$logger = \Mariusz\Logger\DualLogger::create('/var/www/html/logs');
$logger->info('Hello from my app');
$logger->warning('Something off', ['user' => 'jan@example.com']);
```

Logs appear in `./logs/YYYY/MM/YYYY-MM-DD.log` on your host.
Open `http://localhost:8080/logs` to browse them in the viewer.

## Environment variables

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_ENV` | `development` | Application environment |
| `LOG_DIR` | `/var/www/html/logs` | Log directory (used by viewer) |
| `DB_HOST` | `db` | MySQL host |
| `DB_PORT` | `3306` | MySQL port |
| `DB_DATABASE` | `app` | Database name |
| `DB_USERNAME` | `app` | Database user |
| `DB_PASSWORD` | `secret` | Database password |

## Structure

```
docker-fast-php-logger/
├── app/              ← mount your project here (or use the example)
├── logs/             ← log files written here (host-accessible)
├── viewer/
│   └── index.php     ← viewer entry point (served at /logs)
├── docker/
│   └── php.ini       ← dev PHP config
├── Dockerfile
├── docker-compose.yml
└── composer.json     ← pulls fast-php-logger + fast-php-log-viewer from GitHub
```

## fast-php-* suite

| Package | Description |
|---|---|
| [fast-php-logger](https://github.com/mafio69/fast-php-logger) | PSR-3 file logger |
| [fast-php-log-viewer](https://github.com/mafio69/fast-php-log-viewer) | Log viewer UI |
| [docker-fast-logger](https://github.com/mafio69/docker-fast-logger) | This repo — Docker environment |
