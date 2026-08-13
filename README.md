# Magnesium en gezondheid website

Client website with page management, built with [Laravel](https://laravel.com) and
[Tabler](https://tabler.io). How the app works in detail is specified in
`CLAUDE.md`; this file is the practical front door.

## Stack

- **Laravel 13** (PHP 8.5, `php_magnesium` container, php:8.5-apache)
- **Tabler** (Bootstrap 5), bundled by Vite from `resources/css/app.css`
- **Font Awesome Free 7** for icons (solid style only)
- **Quill** WYSIWYG editor, output sanitized with HTMLPurifier
  (stevebauman/purify)
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
and the `DB_MIGRATIONS_*` vars — in dev those repeat the regular
`DB_USERNAME`/`DB_PASSWORD` values (database and user must exist in the
shared MariaDB), then `./develop checkout`. Add `magnesium.local` to your
hosts file and restart the DockerServer stack once so Caddy picks up the
imported Caddyfile.

## The app

Public: `/` teases the newest pages, `/blog` is the full archive and
`/blog/{slug}` a single page; drafts stay hidden. (The `/blog` URLs are
temporary — the pages rebuild replaces them with dynamic URLs, see
`docs/plan-paginas.md`.) The admin area starts at `/admin`, with the pages
under `/admin/pages` and the accounts under `/admin/users`.

Login is at `/login`; registration is disabled and the public site shows
no login link. The migrations bootstrap an admin account
(`info@jeroendn.nl`) **without a usable password** — set one through
"Wachtwoord vergeten" (`/password/reset`). With `MAIL_MAILER=log` the
reset link ends up in `storage/logs/laravel.log`.

## Quality gate

`./develop cqa` runs composer normalize + validate, rector, php-cs-fixer,
phpstan (level 10, larastan) and phpunit. Run it before opening a PR.

## Deploy

On the server, from the repo checkout:

```bash
./deploy
```

Hard-resets to `origin/master`, rebuilds the image, restarts the
containers, installs prod dependencies, builds assets, runs migrations,
warms Laravel's caches and finally hands `storage/` and `bootstrap/cache/`
to Apache through the `www-data` group — never `chown` those to `www-data`
outright, that breaks the next deploy (see CLAUDE.md).

The whole run sits in maintenance mode, so visitors get the branded "Zo
weer online" page (HTTP 503) instead of the errors a half-installed app
throws. Preview it locally with:

```bash
./develop artisan down --render="errors::503" && ./develop artisan up
```

A **failed** deploy leaves the site in maintenance mode on purpose. Fix the
cause and re-run `./deploy`, or force it back online:

```bash
sudo docker exec -it php_magnesium php artisan up
```

In prod's `.env` the `DB_MIGRATIONS_USERNAME`/`DB_MIGRATIONS_PASSWORD`
vars hold a dedicated DDL user: migrations run as that user, while the
regular DB user is restricted to SELECT/INSERT/UPDATE/DELETE. In dev the
same vars simply repeat the regular credentials.
