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
- TLS terminates at Caddy; Laravel trusts its `X-Forwarded-*` headers via
  `trustProxies(at: '*')` in `bootstrap/app.php` (safe: the container is
  only reachable through the internal Docker network). Without it Laravel
  generates `http://` asset/route URLs and the browser blocks them as
  mixed content — no JS runs at all.
- Database: shared **MariaDB** (`mariadb_docker_server`), own database
  `magnesium`, user `magnesium`. Reachable only within the
  `docker_server_database` network, not from the host. Laravel reads the
  credentials from `.env` (`DB_*`).
- **Least privilege on prod**: the app user `magnesium` only has
  SELECT/INSERT/UPDATE/DELETE; DDL (migrations) runs as a separate user
  `magnesium_migrate` via the `mariadb_migrations` connection
  (`config/database.php`), fed by `DB_MIGRATIONS_USERNAME`/`_PASSWORD` in
  prod's `.env`. `./develop checkout` and `./deploy` pass
  `--database=mariadb_migrations` to `artisan migrate` — do the same for
  ad-hoc migrate commands (`migrate:rollback` etc.) on prod. The
  `DB_MIGRATIONS_*` vars are **always set** (no fallback): in dev they
  simply repeat the regular `DB_USERNAME`/`DB_PASSWORD` values, since one
  unrestricted user suffices there. The users/grants themselves are managed
  by Jeroen on the shared MariaDB (not by this repo).
- External networks (created by DockerServer): `docker_server_caddy`,
  `docker_server_database` — declared `external: true` in our
  `docker-compose.yml`.
- After a change to our Caddyfile, the **DockerServer stack must be
  rebuilt/restarted** (the user does this themselves).
- The repo is bind-mounted over `/var/www/html` (dev AND prod); the image
  stays runtime-only. Deploy = git pull on the server + rebuild/restart
  (`./deploy`).
- **Writable dirs on prod**: `storage/` and `bootstrap/cache/` stay owned by
  the deploying user and are handed to Apache through the **group**
  (`chown -R <user>:www-data` + `chmod -R g+rwX`, last step of `./deploy`).
  Never `chown -R www-data:www-data` them: because of the bind mount that
  also takes the tracked `.gitignore` files inside those directories, and the
  next deploy's `git reset --hard` then aborts with "unable to unlink old …
  Permission denied" (hit on 2026-08-06). The step runs after
  `artisan optimize` so it also normalizes what `docker exec` wrote as root.

### Maintenance mode during deploys

`./deploy` brackets everything between the git pull and the final chown with
`artisan down --render="errors::503" --retry=180` … `artisan up`. Without it
visitors get a raw **500** for the whole deploy (measured 2026-08-06):
`composer install` swaps out the autoloader under the running app, and
`npm run build` empties `public/build/`, so the layout's `@vite` throws
`ViteManifestNotFoundException`.

- **`--render` is what makes it work, not `artisan down` on its own.** It
  prerenders the view into `storage/framework/down`, which `public/index.php`
  echoes *before* `require vendor/autoload.php`. Plain `artisan down` boots the
  framework to render the 503 — exactly what is broken mid-deploy. Verified:
  with `vendor/` moved away entirely the page still returns a clean 503.
- `resources/views/errors/503.blade.php` must stay **fully self-contained** —
  no `@vite`, no external fonts/images, inline `<style>` only. It mirrors
  Pico's default light/dark palette by hand. `MaintenancePageTest` guards this.
- The prerender runs **before** the git pull, so it uses the *previous*
  revision of the view: a change to the maintenance page only shows up from
  the next deploy onwards.
- `artisan up` is the **last** step, after the chown — coming back before
  `storage/` is writable would fail the first requests on sessions/logs.
- A failed deploy deliberately **leaves the site down** (a half-deployed app
  should not serve traffic); the `ERR` trap prints the `artisan up` escape
  hatch.
- Not covered: the few seconds of `docker compose down` → `up -d`, where there
  is no PHP at all and Caddy answers **502**. Closing that too would need a
  `handle_errors` block in `docker/caddy/Caddyfile` (Caddy mounts the repo at
  `/apps/magnesium:ro`) — not done, same trade-off as jeroendn-website.

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

## 4. Localization

- **No hardcoded copy in templates**: user-facing text always goes through
  `__('English text')` — the English string is the key (fleet convention,
  same as jeroendn-website). `APP_LOCALE=nl` renders Dutch,
  `APP_FALLBACK_LOCALE=en` makes English work automatically.
- App-specific strings live in `lang/nl.json` (kept alphabetically
  sorted); framework strings (validation, auth, passwords, pagination)
  in `lang/nl/*.php` + the bulk of `lang/nl.json`, generated by
  **laravel-lang**.
- **laravel-lang/common** is a regular dev dependency (a sub-dependency
  needs `ext-bcmath`, which the Dockerfile installs for this reason).
  Day-to-day translations never touch it — new app strings are added to
  `lang/nl.json` by hand. The package is only used to regenerate the
  framework translations: `./develop artisan lang:update` after a Laravel
  upgrade, or `lang:add <locale>` for an extra language. The generated
  files are committed.

## 5. Authentication

- **laravel/ui** (fleet standard, same as jeroendn-website):
  `Auth::routes()` in `routes/web.php` + controllers in
  `app/Http/Controllers/Auth/`. **Registration, e-mail verification and
  password confirmation are disabled on purpose** (client blog — only
  admins log in); the unused controllers are deleted. Login/logout +
  password reset remain, with Pico-styled views under
  `resources/views/auth/`. **The public site shows no login link** —
  admins navigate to `/login` directly; only the logout button is shown
  to authenticated users.
- The laravel/ui stubs' `$this->middleware()` constructor calls don't
  exist in modern Laravel — controllers implement `HasMiddleware` with a
  static `middleware()` method instead (see `LoginController`).
- The first admin (`info@jeroendn.nl`) is bootstrapped by a **data
  migration** (`2026_08_06_084152_create_admin_user`) without a usable
  password (random hash — the column is not nullable); the admin gains
  access via the password-reset flow. In dev (`MAIL_MAILER=log`) the reset
  link lands in `storage/logs/laravel.log`; prod needs working `MAIL_*`
  settings in `.env` before the reset mail can arrive.
- Additional accounts are created by hand (no UI for it yet):
  `./develop artisan tinker`, then
  `App\Models\User::create(['name' => '…', 'email' => '…', 'password' => '…'])`
  — the `password` cast hashes automatically.

## 6. Blog

- **Model `Post`**: `title`, `slug` (unique, public URL key), `body`
  (HTML from the editor), `published_at` (null = draft, future =
  scheduled). `Post::published()` is the public query; `isPublished()`
  the per-model check.
- **Public**: `/` lists published posts (newest first, `simplePaginate`),
  `/blog/{slug}` shows one; drafts and scheduled posts 404 there.
- **Admin CRUD** at `/admin/posts` (auth middleware on the route group;
  every authenticated user is an admin). Create/edit share
  `admin/posts/_form.blade.php`. The slug is generated from the title
  when left empty (`StorePostRequest::prepareForValidation`); a
  "Published" switch maps to `published_at` (first publish date is kept
  when re-saving a published post).
- **Editor**: **Quill 2** (npm dependency, BSD-3). Deliberately NOT Trix:
  Trix 2.1.x never gets keyboard input into its document model (text shows
  in the DOM but nothing is saved) — reproduced with both its ESM and UMD
  builds; TinyMCE 7 and CKEditor 5 are GPL, unusable for this proprietary
  project. The snow toolbar is icon-only, so there is no JS-side copy to
  translate. `app.js` seeds Quill from the hidden `body` input and syncs
  back on change/submit via `getSemanticHTML()` (with an `&nbsp;`
  workaround for quill#4509). Quill ships its own complete styling;
  `app.css` only sets an editor height and keeps the editor surface
  light in dark mode (Quill has no dark theme). Do NOT theme the toolbar
  icons with Pico variables: Pico redefines `--pico-color` inside every
  `<button>`, which turns them white-on-white. No image/attachment
  support (no upload backend).
- **Sanitization**: the body is stored as-is and sanitized on output by
  **stevebauman/purify** (HTMLPurifier) in `Post::bodyHtml()` — the only
  place `{!! !!}` is allowed.
- **Known npm audit finding** (accepted): quill 2.0.3 has advisory
  GHSA-v3m3-f69x-jf25 / CVE-2025-15056 (low, CVSS 2.0) — XSS via the HTML
  export. There is NO patched release (npm's "fix" is a downgrade to
  2.0.2, which does not remove the behavior). Already mitigated here: the
  export is never rendered raw — HTMLPurifier sanitizes on output, covered
  by `PublicBlogTest::testUnsafeHtmlIsStrippedFromTheBody`. Update quill
  once a patched version ships.
- **bladestan gotcha**: standard Blade idioms (`{{ __() }}`, `{{ old() }}`,
  `@error`'s `$message`) are loosely typed in compiled templates;
  `phpstan.dist.neon` carries three documented `ignoreErrors` entries for
  exactly those patterns (scoped to constructs that only exist in compiled
  templates — real app code is unaffected). Shared partials get an
  explicit `@php /** @var ... */ @endphp` type hint (see `_form`).

## 7. Tests

Feature tests live under `tests/Feature/` (`HomePageTest`, `Auth/LoginTest`,
`Auth/PasswordResetTest`, `Blog/PublicBlogTest`, `Blog/AdminPostsTest`,
`MaintenancePageTest`)
and use `RefreshDatabase` on sqlite `:memory:`. They are the default level
here — almost everything is framework-coupled and best tested over HTTP.
`tests/Unit/` is only for pure model logic (`PostTest`: `Post::excerpt()`
and the `isPublished()` boundaries); it still extends `Tests\TestCase`
(the container is needed for the Purify facade) but skips
`RefreshDatabase`, since nothing is persisted.
New functionality gets feature tests in the same style; keep phpstan level
10 clean (fix errors in new code rather than baselining them).

`Tests\TestCase::setUp()` calls **`withoutVite()`** for every test: the
layout's `@vite` directive needs `public/build/manifest.json`, which is a
gitignored build artifact. Locally it happens to exist (`./develop
checkout`), on CI it does not — without this, every test that renders a page
dies with a 500 (`ViteManifestNotFoundException`). The `assets` job guards
the real build. Simulate CI locally with
`mv public/build /tmp/b && ./develop composer phpunit; mv /tmp/b public/build`.

**Never leave a testsuite directory empty**: git does not track empty
directories, so the directory is missing on CI and PHPUnit aborts the whole
run with `Test directory "…" not found` (exit code 2) — green locally, red
on CI. Either the directory holds a committed test, or its `<testsuite>` is
removed from `phpunit.xml`.

## 8. Quality gate

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

## 9. Status / outstanding

- [x] Base setup: Laravel 13 + Pico CSS, Docker/develop/deploy scripts,
      quality gate green, migrations run against the shared MariaDB
      (committed).
- [x] Authentication: login/logout + password reset (laravel/ui,
      registration disabled), with feature tests.
- [x] Localization: NL via `__()` + `lang/` (English keys, laravel-lang
      framework translations).
- [x] Blog functionality: public list/detail + admin CRUD with Quill
      WYSIWYG and HTMLPurifier output sanitization (see §6), with
      feature tests.
- [x] Maintenance page: branded, self-contained 503 prerendered by `./deploy`
      instead of the raw 500 visitors used to get (see §2).
- [ ] User management: an admin UI to create accounts / grant others
      access (for now this goes through tinker or a no-password row +
      password reset, see §5).
