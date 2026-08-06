# Magnesium en gezondheid website

Blog website, built with [Laravel](https://laravel.com) and
[Pico CSS](https://picocss.com).

## Stack

- **Laravel 13** (PHP 8.5, `php_magnesium` container, php:8.5-apache)
- **Pico CSS** via Vite (`resources/css/app.css`)
- **Quill** WYSIWYG editor for writing posts (sanitized on output with
  HTMLPurifier via stevebauman/purify)
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
`DB_USERNAME`/`DB_PASSWORD` values
(database and user must exist in the shared MariaDB), then
`./develop checkout`. Add `magnesium.local` to your hosts file and
restart the DockerServer stack once so Caddy picks up the imported
Caddyfile.

## Blog

Published posts are listed on `/` and shown on `/blog/{slug}`; drafts
stay hidden. After logging in, posts are managed at `/admin/posts`
("Artikelen" in the nav): create, edit, publish/unpublish and delete,
with a WYSIWYG editor. An empty slug field is filled automatically from
the title.

Admin login lives at `/login` (registration is disabled; the public site
shows no login link). The migrations bootstrap an admin account
(`info@jeroendn.nl`) **without a usable password**: set one through
"Wachtwoord vergeten" (`/password/reset`). With `MAIL_MAILER=log` the
reset link ends up in `storage/logs/laravel.log`. Additional accounts go
through `./develop artisan tinker`:

```php
App\Models\User::create(['name' => '…', 'email' => '…', 'password' => '…']);
```

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
warms Laravel's caches and finally makes `storage/` and
`bootstrap/cache/` writable for Apache.

Those last two stay owned by the deploying user and get their write access
through the `www-data` group — the repo is bind-mounted, so handing the
directories to `www-data` outright would lock the deploying user out of the
tracked `.gitignore` files in them and break the next `git reset --hard`.

In prod's `.env` the `DB_MIGRATIONS_USERNAME`/`DB_MIGRATIONS_PASSWORD`
vars hold a dedicated DDL user: migrations run as that user, while the
regular DB user is restricted to SELECT/INSERT/UPDATE/DELETE. In dev the
same vars simply repeat the regular credentials.
