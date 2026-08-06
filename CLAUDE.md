# CLAUDE.md — Magnesium

Technical specification & working agreements for the Magnesium blog website.
This file is loaded automatically as context.

> ## ⚠️ Maintenance rule (hard, always applies)
>
> Keep this file **and** `README.md` up to date as part of the same change —
> never afterwards. CLAUDE.md carries decisions, conventions and status;
> the README stays the short practical front door (stack, commands, setup).
> Bump the "Last updated" date below on every change.
>
> Last updated: 2026-08-06

---

## 1. What this is

A **blog website** for a client, built with Laravel and styled with
**Pico CSS**. Currently a freshly scaffolded base: the blog functionality
(posts, admin) is still to be built.

## 2. Architecture & environment

- Runs as its own container **`php_magnesium`** (php:8.5-apache) behind the
  shared **Caddy** from the sibling repo `../DockerServer` (checked out next
  to this repo on every machine).
- The shared Caddy does `import /apps/*/docker/caddy/Caddyfile`; our
  `docker/caddy/Caddyfile` reverse-proxies `magnesiumengezondheid.nl` (prod)
  and `magnesium.local` (dev) to `php_magnesium:80`. Apache serves Laravel
  from `public/`.
- Database: shared **MariaDB** (`mariadb_docker_server`), own database
  `magnesium`, user `magnesium`. Reachable only within the
  `docker_server_database` network, not from the host. Laravel reads the
  credentials from `.env` (`DB_*`).
- External networks (created by DockerServer): `docker_server_caddy`,
  `docker_server_database` — declared `external: true` in our
  `docker-compose.yml`.
- After a change to our Caddyfile, the **DockerServer stack must be
  rebuilt/restarted** (the user does this themselves).
- The repo is bind-mounted over `/var/www/html` (dev AND prod); the image
  stays runtime-only. Deploy = git pull on the server + rebuild/restart
  (`./deploy`).

### ⚠️ Tooling rule (hard)

`php`, `composer`, `artisan`, `npm`, `phpunit` and migrations **ALWAYS
execute in the container**, never on the host. The **`./develop` wrapper is
the front door**: it auto-detects context and forwards dev commands into
`php_magnesium`. Anything it doesn't recognize is passed straight to
`docker compose`:

```bash
./develop up -d --build     # start/build the stack
./develop checkout          # composer + npm install, asset build, migrations
./develop artisan <cmd>     # any artisan command
./develop cqa               # quality gate, run before every PR
```

## 3. Frontend

- **Pico CSS** (`@picocss/pico`, npm dev dependency), imported in
  `resources/css/app.css`, bundled by Vite (`npm run build` — there is no
  Vite dev-server setup; rebuild after asset changes).
- Customization goes through Pico's CSS custom properties in `app.css` —
  no utility classes, mostly semantic HTML.
- Blade: `resources/views/layouts/app.blade.php` is the base layout
  (header/nav, `container` classes, `@vite`); pages extend it
  (`resources/views/home.blade.php`).

## 4. Quality gate

`./develop cqa` → composer normalize + validate, rector, php-cs-fixer
(`@auto`), phpstan (level 10, larastan + bladestan, baseline in
`phpstan-baseline.neon`), phpunit (`failOnWarning` + `failOnDeprecation`),
npm build. **Every tracked .php file is covered** by rector/cs-fixer/phpstan
— including `artisan`, `bootstrap/app.php`, `bootstrap/providers.php` and
`public/index.php` (but never the generated `bootstrap/cache/`); keep the
three config's path lists in sync when adding root-level PHP files.
Tool caches live in `tmp/`.
Tests run against sqlite `:memory:` (see `phpunit.xml`), so they never
touch MariaDB. CI (`.github/workflows/ci.yml`) runs the check variants
(`rector-check`, `cs-check`, `phpstan`, `phpunit`) on every PR to master,
with a dummy `APP_KEY` since CI has no `.env`.

## 5. Status / outstanding

- [x] Base setup: Laravel 13 + Pico CSS, Docker/develop/deploy scripts,
      quality gate green, migrations run against the shared MariaDB.
- [ ] Blog functionality: posts (model/migration/controller), admin.
      **Not started yet on purpose** — the initial setup gets committed
      first.
