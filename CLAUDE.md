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
> **Most changes touch only this file.** The README answers "how do I run
> and develop this", not "what can the app do": a new feature earns a README
> line only when it changes the stack, a command, the setup, the deploy, or
> how a developer gets into the app locally. Behaviour, rationale, edge
> cases and gotchas belong here — do not restate them there. When in doubt,
> leave the README alone.
>
> Last updated: 2026-08-08

---

## 1. What this is

A **blog website** for a client, built with Laravel and styled with
**Tabler** (Bootstrap 5). Currently a freshly scaffolded base: the blog
functionality (posts, admin) is still to be built.

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
  Tabler's light/dark palette by hand — and on `prefers-color-scheme`, since
  Tabler's own `[data-bs-theme]` switch would need the JS this page cannot
  load either. `MaintenancePageTest` guards this.
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

- **Tabler** (`@tabler/core`, MIT), imported in `resources/css/app.css`,
  bundled by Vite (`npm run build` — there is no Vite dev-server setup;
  rebuild after asset changes). Replaced Pico CSS on 2026-08-07: Pico is
  classless and deliberately minimal, and every layout wish (branded
  header, dropdown menu, burger menu) turned into hand-written CSS that
  fought the framework. Tabler ships all of that as components. The price
  is bundle size — 70 kB gzipped CSS + 74 kB JS against Pico's 15 + 59.
- Tabler is a **Bootstrap 5 theme**, so it is utility-class driven: styling
  goes through Bootstrap/Tabler classes in the Blade templates, and only
  what those cannot express belongs in `app.css` (the Quill overrides, the
  admin frame and the toast countdown bar). Customization happens through the `--tblr-*` custom properties.
- **Tabler's own JS bundle is not used** — it only carries widgets we do not
  have (autosize, theme switcher). `app.js` imports `bootstrap/js/dist/collapse`,
  `.../dropdown` and `.../toast` instead; importing them registers their
  data-attribute handlers, so the navbar needs no glue code. `bootstrap` is
  therefore a direct dependency in `package.json`, not a transitive one.
- **Flash messages are toasts**: the layout renders `session('status')` as a
  single toast (bottom center, dismissable) — views never render their own
  status alert. Bootstrap does not auto-show toasts, so `app.js` calls
  `.show()` on every `.toast`. Auto-hide (15 s) is NOT Bootstrap's
  (`data-bs-autohide="false"`): a `.toast-progress` countdown bar shrinks via
  a CSS animation and `app.js` hides the toast on `animationend`, so the bar
  and the hide moment cannot drift apart — the 15 s lives in `app.css`, and
  hover/`:focus-within` pauses the countdown (Bootstrap's paused timer would
  restart in full, a CSS animation resumes where it left off).
  Because it lives in the layout, *any* flashed status shows up, also on
  pages that never rendered one before (e.g. after a completed password
  reset). The reset-link panel on `/admin/users` deliberately stays an
  inline alert: it holds a link the admin must copy, so it must not
  disappear on its own.
- **Icons**: **Font Awesome Free 7** (`@fortawesome/fontawesome-free`,
  CC-BY-4.0 + OFL-1.1 + MIT — fine for this proprietary project, unlike the
  GPL editors in §6). `app.css` imports only `fontawesome.css` + `solid.css`:
  `all.css` would drag in the brands (115 kB) and regular (19 kB) webfonts
  too. Markup is `<i class="fa-solid fa-…" aria-hidden="true">`; add
  `brands.css` if social icons ever land. Tabler's own icon set is not used.
  The icons are CC-BY-4.0, which asks for attribution; **the site deliberately
  shows none** (decided 2026-08-07). Vite also strips Font Awesome's license
  banner when it minifies, so nothing credits it anywhere. If that ever needs
  to change, keeping the banner in the build is cheaper than a visible line.
- **Dark mode**: Pico switched on `prefers-color-scheme`, Tabler themes on
  `[data-bs-theme]`. The layout sets that attribute from the OS preference in
  an inline `<head>` script, before the stylesheet loads, so the behaviour is
  unchanged and there is no flash of the wrong theme.
- Blade: `resources/views/layouts/app.blade.php` is the base layout; pages
  extend it (`resources/views/home.blade.php`).
- **Header**: Tabler's horizontal layout — a first `navbar` holding the
  brand and a second one holding the menu row underneath it. The
  `navbar-toggler` collapses the menu row into a burger menu below `md` for
  free. The menu row is one `navbar-nav`, filled from
  `partials/nav-public.blade.php` or `partials/nav-admin.blade.php`, with
  `partials/nav-account.blade.php` (avatar dropdown: switch site ⇄ admin,
  logout) appended for authenticated users. That partial is **last in the
  list on purpose**: `ms-md-auto` right-aligns it once the row is
  horizontal, and in the burger menu it lands at the bottom. `Home` and
  `Posts` are real links; the two remaining dropdowns are **placeholder
  copy** with `href="#"`, waiting on the client's real site structure. The
  admin menu is `Dashboard`, `Posts` and `Users`; the brand and the account
  dropdown's "Administration" both lead to the dashboard, the admin area's
  own front page.
- **Admin area is visually marked**: a fixed warning-coloured frame around
  the viewport (`.admin-frame`, the one thing Tabler utilities cannot
  express — `position: fixed` + `inset` + a z-index above modals) and a
  warning badge next to the brand. Below `md` the badge **replaces** the
  site name; side by side they overflow a 375px viewport.
- **Breadcrumbs**: `App\Support\Breadcrumbs::current()` maps the current
  route name to a trail, which the layout renders through
  `partials/breadcrumbs.blade.php` above every page. The home crumb is an
  icon the partial always prepends, so the class only returns what follows
  it. An empty trail (the `default` arm) renders nothing at all — the admin
  create/edit forms stay in that group on purpose: they fill the layout's
  `@section('back')` instead, which sits in the same slot, so a page shows
  either a trail or a back link and never both. New routes get a `match`
  arm; labels come from the bound model where there is one (`posts.show`).
  A post detail page is `🏠 / Posts / <title>` — the "Posts" step only
  became meaningful once `/blog` existed; before that it would have pointed
  at the same page as the house. The house itself is not always `/`:
  `Breadcrumbs::homeUrl()` points it at the admin dashboard inside the admin
  area, so a trail never leaves the area it belongs to.
- Both hang off `@adminArea`, a `Blade::if()` registered in
  `AppServiceProvider` — not a controller variable, because the same layout
  also serves the auth views that laravel/ui's own controllers render. A
  directive rather than a view composer keeps bladestan happy too: it only
  knows the variables it sees in `view()` calls, so a composer-supplied one
  is reported as undefined in every template that touches it.

## 4. Localization

- **No hardcoded copy in templates**: user-facing text always goes through
  `__('English text')` — the English string is the key (fleet convention,
  same as jeroendn-website). `APP_LOCALE=nl` renders Dutch,
  `APP_FALLBACK_LOCALE=en` makes English work automatically.
- App-specific strings live in `lang/nl.json` (kept alphabetically
  sorted); framework strings (validation, auth, passwords, pagination)
  in `lang/nl/*.php` + the bulk of `lang/nl.json`, generated by
  **laravel-lang**.
- **Reuse laravel-lang's `:name` templates instead of writing one string
  per entity.** `__('Edit :name', ['name' => __('post')])` renders
  "Artikel bewerken" — `:Name` ucfirst's the replacement, so the noun goes
  in as a lowercase key (`post` → `artikel`, `user` → `gebruiker`) and the
  English fallback reads "Edit post". Same trick for the flash messages
  (`:Name created.` / `:Name updated.` / `:Name deleted.`) and the delete
  confirmation, which takes the record's own title. A third entity type
  then costs one key, not six.
- Not everything can be shared: **`New :name` translates to "Nieuwe :name"**
  and Dutch adjectives inflect on gender, so it would produce "Nieuwe
  artikel" instead of "Nieuw artikel". `New post` and `New user` therefore
  stay separate keys — check the Dutch reads before folding a string into a
  template.
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
  password reset remain, with Tabler-styled views under
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
### User management

- **Admin CRUD at `/admin/users`** (same auth middleware group as the
  posts; every authenticated user is an admin, so anyone who logs in can
  add and remove accounts). Create/edit share `admin/users/_form.blade.php`
  and only carry name + e-mail.
- **No password field anywhere.** `UserController::store()` saves a
  `Str::password(32)` nobody ever sees and flashes a **password reset link**
  instead, which the admin copies and hands to the new user (WhatsApp, a
  call — the site sends no mail, and prod's `MAIL_*` may not even be
  configured). Every row also has a key button to generate a fresh link
  later; that is the only way to change a password.
- The link is the exact URL Laravel would have mailed:
  `route('password.reset', ['token' => Password::createToken($user),
  'email' => …])`. It is therefore bound to the **60 minute**
  `config('auth.passwords.users.expire')` window and creating a new one
  **invalidates the previous link** (the broker keeps one token per user).
  The overview states the validity next to the link, and `app.js` adds a
  copy button (icon swaps to a checkmark — no JS-side copy to translate).
- **Deleting is a soft delete** (`SoftDeletes` on `User`, added by
  `2026_08_08_090041_add_soft_deletes_to_users_table`). Nothing references a
  user yet, but posts are meant to get an author, and a hard delete would
  either orphan those rows or block the deletion. The row stays; access does
  not: Eloquent's user provider, the password broker and the route model
  binding all query through the default scope, so a deleted account cannot
  log in, gets no reset mail, and its edit/reset-link routes 404.
- **The soft delete alone was not enough**: the default scope hides the row
  while it is trashed, but a *remember-me* cookie survives that. The guard's
  recaller path only `hash_equals()`es `remember_token` — it never rechecks
  the password (that check lives in `AuthenticateSession`, which is off) —
  so once the row is revived, an old cookie would silently log the ex-user
  back in as a full admin, before the new holder sets a password. Active
  `sessions` rows resume the same way within `SESSION_LIFETIME`.
  `UserController::revokeAccess()` closes both: it rotates `remember_token`
  (via `forceFill` + `saveQuietly`; the column is not fillable) and deletes
  the user's `sessions` rows. Called from `destroy()` (immediate revocation)
  **and** from `createOrRevive()` (covers rows trashed before the guard
  existed). `UsersTest` asserts both the rotation and the session purge.
- The `email` unique index covers deleted rows too, so their address is not
  really free again. `StoreUserRequest` therefore checks uniqueness among
  **active** users only (`whereNull('deleted_at')`) and
  `UserController::createOrRevive()` revives the old row instead of
  inserting a duplicate — with a fresh random password *and* a rotated
  remember token, so no credential of the deleted account comes back. That
  re-add is also the only way back: there is no trash view or restore
  button.
- **`users.last_active_at`** feeds the "Last active" column. The
  `TrackActivity` middleware (appended to the `web` group, so it also counts
  browsing the public site while logged in) writes it at most once every
  5 minutes per user, quietly and with `withoutTimestamps()` — a heartbeat
  is not an edit of the account. Not derived from the `sessions` table:
  those rows are garbage collected a `SESSION_LIFETIME` after the last
  request, so they can only tell you who is online *now*, never that
  somebody was last seen a week ago. `ActivityTrackingTest` covers the
  throttle, the refresh and the untouched `updated_at`.
- That same `sessions` table *is* the right source for the other half:
  `App\Support\OnlineUsers::ids()` reads which users had a session touched
  in the last 5 minutes, and the overview shows those as "Online now"
  instead of a relative time — free, because the session driver writes
  those rows anyway. It therefore **depends on `SESSION_DRIVER=database`**;
  on another driver the table stays empty and nobody ever shows as online.
- `User` carries `@property` docblocks like `Post` does: without them
  larastan types the model from the migrations, where `last_active_at` is a
  plain `timestamp` — i.e. a `string` you cannot call `diffForHumans()` on.
- **You cannot delete your own account**: the overview hides the button on
  your own row and `destroy()` `abort_if()`s on it. That is the only guard —
  admins can still delete each other, and getting back in after that works
  the way the first admin was bootstrapped: a row without a password plus
  the reset flow.

## 6. Blog

- **Model `Post`**: `title`, `slug` (unique, public URL key), `body`
  (HTML from the editor), `published_at` (null = draft, future =
  scheduled). `Post::published()` is the public query; `isPublished()`
  and `isScheduled()` the per-model checks.
- **Public**: `/` teases the `PostController::RECENT` newest published
  posts and links on to `/blog`, the full archive (newest first,
  `simplePaginate(10)`); `/blog/{slug}` shows one post. Drafts and
  scheduled posts appear in none of the three. Both lists render the same
  `partials/post-card.blade.php`.
- **Admin CRUD** at `/admin/posts` (auth middleware on the route group;
  every authenticated user is an admin). Create/edit share
  `admin/posts/_form.blade.php`. The slug is generated from the title
  when left empty (`StorePostRequest::prepareForValidation`);
  `published_at` is a date field, stored as midnight — day precision is
  enough for this blog (decided 2026-08-08); empty = draft, future =
  scheduled, and the field is prefilled on edit, so re-saving keeps the
  original publish date. The overview shows the status as a badge:
  green "Published", yellow "Scheduled", grey "Draft".
- **Editor**: **Quill 2** (npm dependency, BSD-3). Deliberately NOT Trix:
  Trix 2.1.x never gets keyboard input into its document model (text shows
  in the DOM but nothing is saved) — reproduced with both its ESM and UMD
  builds; TinyMCE 7 and CKEditor 5 are GPL, unusable for this proprietary
  project. The snow toolbar is icon-only, so there is no JS-side copy to
  translate. `app.js` seeds Quill from the hidden `body` input and syncs
  back on change/submit via `getSemanticHTML()` (with an `&nbsp;`
  workaround for quill#4509). Quill ships its own complete styling; the four
  rules in `app.css` are all that is left (measured 2026-08-07 by deleting
  each one in the browser — a fifth, `color: inherit` on the editor's block
  elements, was a Pico artefact and had no effect under Tabler):
  a `min-height` (without it the editor collapses to 42px, since Quill sets
  `height: 100%` on an auto-height parent), a white surface, Tabler's border
  radius, and the `is-invalid` border. **The surface must stay light** — not
  because the text would be unreadable (Tabler's light body color on the dark
  card is fine) but because Quill hard-codes its toolbar icons to `#444`
  stroke/fill, which vanish on a dark background.
  Quill turns `#body-editor` into `.ql-container` and inserts `.ql-toolbar`
  as a **sibling**, so `_form.blade.php` puts the `is-invalid` class on a
  wrapper around it — on the editor itself only half of it would get the
  error border. Its editable div ships without a role or a name, so `app.js`
  sets `role="textbox"` + `aria-labelledby` on it; the visible "Content" label
  is a `<div>`, because a `<label>` could only point at the hidden input.
  No image/attachment support (no upload backend).
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
`Admin/DashboardTest`, `Admin/UsersTest`, `ActivityTrackingTest`,
`NavigationTest`, `BreadcrumbsTest`, `LocalizationTest`,
`AdminUserMigrationTest`, `MaintenancePageTest`)
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

- [x] Base setup: Laravel 13 + Tabler, Docker/develop/deploy scripts,
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
- [x] Navigation: separate public/admin menus, account dropdown, admin
      frame and breadcrumbs (see §3), with feature tests.
- [x] User management: `/admin/users` CRUD with hand-over password reset
      links instead of mail (see §5), with feature tests.
- [ ] Real menu structure: two of the header dropdowns are still
      placeholder copy with `#` links until the client delivers the site
      structure (see §3).
- [ ] Admin dashboard content: `/admin` exists as the landing page of the
      admin area (and the target of its breadcrumb house) but is
      deliberately empty — no widgets decided on yet.
- [ ] Post authorship: `posts` has no `user_id` yet. Users are soft-deleted
      (see §5), so that column can be added later without the delete button
      ever orphaning a post.
