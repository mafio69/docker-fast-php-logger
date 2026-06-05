# TO-DO — docker-fast-logger
Bardzo ważne fast-php-log-viewer to inna aplikacja z innego repo niż docker-fast-logger. I tak ma działać - zmiany w viewer robimy w repo viewer a docker-fast-logger. samą przegladarkę logów instaluje się przez composer-a
## Completed Issues ✅

### nginx Configuration
- [x] **Missing viewer route**: Added `/logs` alias in nginx.conf to serve viewer

### Hardcoding Violations
- [x] **Port 8082** in docker-compose.yml - moved to `.env` (PHP_PORT)
- [x] **Port 3307** for MySQL in docker-compose.yml - moved to `.env` (DB_PORT)
- [x] **Port 9090** for Portainer in docker-compose.yml - moved to `.env` (PORTAINER_PORT)
- [x] **Port 8081** for Adminer in docker-compose.yml - moved to `.env` (ADMINER_PORT)
- [x] **Port 8025** for Mailpit in docker-compose.yml - moved to `.env` (MAILPIT_WEB_PORT, MAILPIT_SMTP_PORT)
- [x] **Port 80** for Proxy in docker-compose.yml - moved to `.env` (PROXY_PORT)

### Documentation Issues
- [x] **AIREADME.md cleanup** - removed project guidelines, kept only AI principles
- [x] **README.md update** - added docker-fast-logger project guidelines
- [x] **Dockerfile version mismatch** - updated to PHP 8.4 + Nginx
- [x] **Port mismatch** - updated to port 8082
- [x] **Web server mismatch** - updated to Nginx
- [x] **Viewer configuration** - added `/logs` route to nginx and updated README

## Remaining Issues

### Hardcoding Violations
- [ ] **Path `/var/www/html`** hardcoded in multiple files (Dockerfile, nginx.conf, docker-compose.yml) - consider using env variable

### docker-compose.yml Issues
- [ ] **Hardcoded secrets**: MySQL passwords are hardcoded in environment variables
- [ ] **Unused volumes**: Verify if all mounted volumes are necessary
- [ ] **External path dependency**: If any service mounts paths outside project, consider alternatives

## Verification Steps

After fixes, verify:
- [ ] `docker compose build` succeeds without errors
- [ ] `docker compose up -d` starts all containers
- [ ] `http://localhost:8082` serves PHP application
- [ ] `http://localhost:8082/logs` serves log viewer
- [ ] Logger writes logs to `/var/www/html/logs`
- [ ] MySQL accessible on configured port
- [ ] No hardcoded values in configuration files
