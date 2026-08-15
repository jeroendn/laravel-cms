# Laravel CMS

Generic client-website CMS with page management, built with
[Laravel](https://laravel.com) and [Tabler](https://tabler.io). One
codebase serves every site: everything site-specific lives in two
untracked per-site files — `.env` and `docker/caddy/Caddyfile` — and in
the site's own database, so a site is a plain clone of this repo plus
configuration. How the app works in detail is specified in `CLAUDE.md`;
this file is the practical front door.

## Stack

- **Laravel 13** (PHP 8.5, php:8.5-apache container — name set via
  `APP_CONTAINER` in `.env`)
- **Tabler** (Bootstrap 5), bundled by Vite from `resources/css/app.css`
- **Font Awesome Free 7** for icons (solid style only)
- **Quill** WYSIWYG editor, output sanitized with HTMLPurifier
  (stevebauman/purify)
- **MariaDB** — the shared `mariadb_docker_server` from the sibling
  [DockerServer](../DockerServer) repo, one database per site
- **Caddy** — the shared DockerServer Caddy imports
  `docker/caddy/Caddyfile`, a per-site untracked file created from
  `docker/caddy/Caddyfile.example`

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

## Setting up a new site

Prerequisite for both dev and prod: the [DockerServer](../DockerServer)
stack checked out and running next to this repo, with a database + user
for the site created in its MariaDB.

### Dev

1. Clone this repo next to `DockerServer`.
2. Copy `.env.example` to `.env` and fill in `APP_NAME`, `APP_URL`,
   `APP_CONTAINER`, `ADMIN_EMAIL` and the `DB_*` credentials — in dev the
   `DB_MIGRATIONS_*` vars simply repeat the regular
   `DB_USERNAME`/`DB_PASSWORD` values.
3. `./develop up -d --build`, then `./develop checkout` (installs
   dependencies, generates `APP_KEY`, builds assets, runs migrations).
4. Copy `docker/caddy/Caddyfile.example` to `docker/caddy/Caddyfile`: set
   the `.local` domain and the `APP_CONTAINER` value, and give the snippet
   a name unique among all sites the shared Caddy imports (it inlines them
   into one config).
5. Add the domain to your hosts file, add the site's `PUBLIC_DIR_*` mount
   to DockerServer and restart that stack.
6. Log in at `/login` with the `ADMIN_EMAIL` account via "Forgot password" — with `MAIL_MAILER=log` the reset link lands in
   `storage/logs/laravel.log`.

If the browser rejects the `.local` certificate, run
`cd ../DockerServer && ./develop trust-certs` once per machine.

### Prod

1. Point the site's DNS at the server and clone this repo next to
   `DockerServer`.
2. Copy `.env.example` to `.env` with production values:
   `APP_ENV=production`, `APP_DEBUG=false`, the real domain in `APP_URL`,
   working `MAIL_*` settings (the admin's first login needs a
   password-reset mail) and a real `APP_KEY` — copy the output of
   `./develop artisan key:generate --show` from a dev machine. The
   `DB_MIGRATIONS_*` vars hold a separate DDL-capable user here: migrations
   run as that user, while the regular DB user is restricted to
   SELECT/INSERT/UPDATE/DELETE.
3. Create `docker/caddy/Caddyfile` from the example: production domain,
   container name, unique snippet name.
4. Add the site's `PUBLIC_DIR_*` mount to DockerServer and restart that
   stack.
5. `./deploy`.
6. Log in via the password-reset flow, as in dev — the link arrives by
   mail here.

## The app

Public: `/` shows the page slugged `home` (a bare layout until one
exists); other pages live at `/{page}`, `/{group}/{page}` or
`/{group}/{subgroup}/{page}`, and a group URL shows an overview of its
pages. Drafts stay hidden. The admin area starts at `/admin`, with the
pages under `/admin/pages`, the page groups under `/admin/page-groups`
and the accounts under `/admin/users`.

Login is at `/login`; registration is disabled and the public site shows
no login link. The migrations bootstrap an admin account (the
`ADMIN_EMAIL` from `.env`) **without a usable password** — set one through
"Wachtwoord vergeten" (`/password/reset`).

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
sudo docker exec -it <app-container> php artisan up
```

(the failed deployment's output prints the exact command with the container
name filled in)
