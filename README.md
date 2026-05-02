# docker-fast-logger

> PHP dev environment with [fast-php-logger](https://github.com/mafio69/php-logger) pre-installed.
> Mount your code, run one command, start logging.

## Quick start

```sh
git clone https://github.com/mafio69/docker-fast-logger.git
cd docker-fast-logger
docker compose up --build
```

Open http://localhost:8080 — PHP + MySQL running, logger configured, logs in `./logs/`.

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
$logger->warning('Something off', ['user' => 'jan@example.com', 'token' => 'abc123']);
```

Logs appear in `./logs/YYYY/MM/YYYY-MM-DD.log` on your host machine.

## Environment variables

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_ENV` | `development` | Application environment |
| `DB_HOST` | `db` | MySQL host |
| `DB_PORT` | `3306` | MySQL port |
| `DB_DATABASE` | `app` | Database name |
| `DB_USERNAME` | `app` | Database user |
| `DB_PASSWORD` | `secret` | Database password |

## Structure

```
docker-fast-logger/
├── app/              ← mount your project here (or use the example)
├── logs/             ← log files written here (host-accessible)
├── docker/
│   └── php.ini       ← dev PHP config
├── Dockerfile
├── docker-compose.yml
└── composer.json     ← fast-php-logger pre-installed
```

---

## Roadmap

### v0.9 — current
- [x] PHP 8.3 + Apache
- [x] fast-php-logger pre-installed via Composer
- [x] MySQL 8.0 with healthcheck
- [x] Volume mounts for app code and logs
- [x] Dev php.ini (errors on, opcache off)
- [x] Example `index.php` showing all log levels + DB connection

### v1.0 — log viewer
- [ ] Built-in log viewer at `http://localhost:8080/logs`
- [ ] List log files by date
- [ ] Parse and display log entries in a table
- [ ] Filter by level (DEBUG / INFO / WARNING / ERROR / CRITICAL)
- [ ] Filter by date range
- [ ] Color-coded levels
- [ ] Zero JS frameworks — vanilla PHP + HTML

### v1.1 — DX improvements
- [ ] `make up` / `make logs` / `make shell` shortcuts
- [ ] Auto-detect `composer.json` in mounted app and run `composer install`
- [ ] PostgreSQL variant (`docker-compose.postgres.yml`)
- [ ] Redis service option

### v1.2 — log viewer enhancements
- [ ] Search by message text
- [ ] Expand JSON context inline
- [ ] Tail mode — auto-refresh last N entries
- [ ] Download log file button
