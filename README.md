# Magnesium

Blog website, built with [Laravel](https://laravel.com) and
[Pico CSS](https://picocss.com).

## Stack

- **Laravel 13** (PHP 8.5, `php_magnesium` container, php:8.5-apache)
- **Pico CSS** via Vite (`resources/css/app.css`)
- **MariaDB** — the shared `mariadb_docker_server` from the sibling
  [DockerServer](../DockerServer) repo, own database `magnesium`
- **Caddy** — the shared DockerServer Caddy imports
  `docker/caddy/Caddyfile` and proxies `magnesium.local` (dev) to this
  container

## Development

All PHP/composer/npm tooling runs **in the container**, never on the host.
`./develop` is the front door:

```bash
./develop up -d --build     # start/build the stack (docker compose passthrough)
./develop checkout          # composer + npm install, asset build, migrations
./develop artisan migrate   # any artisan command
./develop npm run build     # rebuild assets after CSS/JS changes
./develop cqa               # quality gate before every PR
./develop shell             # interactive shell in the container
```

First-time setup: copy `.env.example` to `.env`, fill in `DB_PASSWORD`
(database and user must exist in the shared MariaDB), then
`./develop checkout`. Add `magnesium.local` to your hosts file and
restart the DockerServer stack once so Caddy picks up the imported
Caddyfile.

## Quality gate

`./develop cqa` runs composer normalize + validate, rector, php-cs-fixer,
phpstan (level 10, larastan) and phpunit. Run it before opening a PR.

## Deploy

On the server, from the repo checkout:

```bash
./deploy
```

Hard-resets to `origin/master`, rebuilds the image, restarts the
containers, installs prod dependencies, builds assets, runs migrations
and warms Laravel's caches.
