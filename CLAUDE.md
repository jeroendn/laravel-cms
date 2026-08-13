# CLAUDE.md — Magnesium

Technical specification & working agreements for the Magnesium website.
This file is loaded automatically as context.

> ## ⚠️ Maintenance rule (hard, always applies)
>
> Keep this file **and** `README.md` up to date as part of the same change —
> never afterwards. CLAUDE.md carries decisions, conventions and open work;
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
> Last updated: 2026-08-13

---

## 1. What this is

A **website** for a client, built with Laravel and styled with
**Tabler** (Bootstrap 5). The content system (**pages**, formerly the
blog — public archive + admin CRUD, being rebuilt into a generic page
system per `docs/plan-paginas.md`), authentication, user management and
the Dutch localization are in place; §9 lists what is not. Until the
client goes live the public side is hidden behind a placeholder — see §2.

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

### Under construction (temporary)

While the site is being built, `App\Http\Middleware\UnderConstruction`
answers guests on the public routes with
`resources/views/under-construction.blade.php`. It keys off `APP_ENV`
(`App::isProduction()`) — no separate flag, decided 2026-08-08 — so dev
and the tests (`APP_ENV=testing`) always show the real pages.

- **503, not 200**, so the placeholder never gets indexed as the site's
  content; the view carries `robots: noindex` on top of that.
- The middleware sits on the public route group only: `/login` and the
  password reset stay reachable, and any authenticated user sees the whole
  site again, admin area included. That is the only way in — the public
  site still shows no login link, an admin types `/login` himself.
- Route middleware runs *after* the web group's `SubstituteBindings`, so
  the catch-all binders (§6) 404 an unknown path before the placeholder
  can answer; `/up` sits outside the group and stays reachable.
- Prod caches its config (`artisan optimize` in `./deploy`), so `APP_ENV`
  changes only land with a redeploy.
- **This has to be removed at launch** — prod stays `APP_ENV=production`
  forever, so the placeholder does not lift by itself. Delete the
  middleware + its route group wrapper, the view,
  `UnderConstructionTest` and the two `lang/nl.json` keys.

### Search indexing

`public/robots.txt` **disallows nothing on purpose**: a disallowed URL can
still be listed in the results, and a crawler that never fetches a page never
reads its `noindex` either. What keeps the non-public pages out is
`App\Http\Middleware\NoIndex`, which sets `X-Robots-Tag: noindex` on the
`Auth::routes()` group and the admin group.

- The guard is the route group, not the template: anything added to `/admin`
  or to the auth routes is covered without touching a view.
- What this really closes is `/login` and the password reset pages — those
  answer 200 to anyone. Admin *content* was already unreachable for a
  crawler, since it sits behind `auth`.
- The redirect a guest gets from `/admin/*` carries **no** header: `auth`
  throws before the middleware sees a response. Harmless — a 302 is not
  indexed and `/login` says `noindex` itself. `SearchIndexingTest` pins that
  whole journey, including that the public pages keep *no* header at all.
- Not covered: `/up`, the health route from `bootstrap/app.php`, which sits
  in neither group.

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
  bundled by Vite (`npm run build` — rebuild after asset changes; the
  Laravel default dev-server scripts in `package.json`/`vite.config.js`
  are still there but unreachable, since the container publishes no
  ports). Replaced Pico CSS on 2026-08-07: Pico is
  classless and deliberately minimal, and every layout wish (branded
  header, dropdown menu, burger menu) turned into hand-written CSS that
  fought the framework. Tabler ships all of that as components. The price
  is bundle size — 70 kB gzipped CSS + 74 kB JS against Pico's 15 + 59.
- Tabler is a **Bootstrap 5 theme**, so it is utility-class driven: styling
  goes through Bootstrap/Tabler classes in the Blade templates, and only
  what those cannot express belongs in `app.css` (the Quill overrides, the
  admin frame, the toast countdown bar and the flyout submenus).
  Customization happens through the `--tblr-*` custom properties.
  Required fields mark their label with Tabler's `required` class (red
  asterisk), the auth views included; a conditionally required field
  toggles it client-side (the publication date, see §6).
- **Tabler's own JS bundle is not used** — it only carries widgets we do not
  have (autosize, theme switcher). `app.js` imports `bootstrap/js/dist/collapse`,
  `.../dropdown` and `.../toast` instead; importing them registers their
  data-attribute handlers, so the navbar needs no glue code. `bootstrap` is
  therefore a direct dependency in `package.json`, not a transitive one.
- **Flash messages are toasts**: the layout renders `session('status')` as a
  single toast (bottom center, dismissable) — views never render their own
  status alert. `session('error')` gets the same treatment in red
  (`text-bg-danger`, `role="alert"`); it is used when deleting a non-empty
  page group is refused. Bootstrap does not auto-show toasts, so `app.js` calls
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
  extend it (`resources/views/home.blade.php`). Its `<head>` contents live
  in `partials/head.blade.php`, shared with the standalone
  `under-construction.blade.php` — the theme script must not drift between
  the two. The 503 page is *not* in on this: it stays self-contained.
- **Header**: Tabler's horizontal layout — a first `navbar` holding the
  brand and a second one holding the menu row underneath it. The
  `navbar-toggler` collapses the menu row into a burger menu below `md` for
  free. The menu row is one `navbar-nav`, filled from
  `partials/nav-public.blade.php` or `partials/nav-admin.blade.php`, with
  `partials/nav-account.blade.php` (avatar dropdown: switch site ⇄ admin,
  logout) appended for authenticated users. That partial is **last in the
  list on purpose**: `ms-md-auto` right-aligns it once the row is
  horizontal, and in the burger menu it lands at the bottom. The
  admin menu is `Dashboard`, `Pages` and `Users`; the brand and the account
  dropdown's "Administration" both lead to the dashboard, the admin area's
  own front page.
- **The public menu is fully dynamic**: `nav-public.blade.php` loops
  `App\Support\Menu::items()` (readonly `MenuItem` DTOs; two queries,
  tree and ordering in PHP) through `partials/menu-item.blade.php` and
  `menu-dropdown-item.blade.php`. Those are deliberately **not**
  recursive: bladestan re-analyzes a partial per include site, so a
  self-including template recurses until phpstan hits its memory limit —
  and with at most one nesting level a flyout only ever holds plain
  links anyway. Top level: menu-toggled root groups
  and ungrouped visible pages, ordered priority DESC then alphabetically
  (case-insensitive) — the shared comparator all menu levels use. Groups
  are dropdowns of their menu-toggled visible pages and subgroups,
  always closed by a "Show All" link to the group overview. That link
  matters twice for subgroups: clicking a flyout toggle opens the
  submenu instead of navigating, so "Show All" is the way *into* the
  overview. There are no fixed items — the home page joins via its own
  toggle (label = its title, href = `/`) — and the menu never shows
  invisible pages, but a toggle only affects the menu: every URL stays
  reachable. A group is `active` anywhere inside its path, a page only
  on its own URL.
- **Flyout submenus are hand-rolled** — Bootstrap 5 has no nested
  dropdowns. `app.css` positions the nested `.dropdown-menu` (flyout to
  the right ≥`md`, expanding in place inside the burger menu below it)
  and `app.js` toggles `.show`: the toggles carry `data-submenu` instead
  of `data-bs-toggle`, `stopPropagation` keeps Bootstrap's document
  handler from closing the parent dropdown, and that parent's
  `hide.bs.dropdown` sweeps every open submenu shut. The `dropend`
  wrapper only supplies Bootstrap's right-pointing caret.
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
  arm; the dynamic page/group URLs share one arm that walks the bound
  models' ancestors (`🏠 / group / subgroup / title`, as deep as the URL
  goes — every step except the last links). The house itself is not always `/`:
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
  sorted); framework strings in the six `lang/nl/*.php` files (`actions`,
  `auth`, `http-statuses`, `pagination`, `passwords`, `validation`) plus
  the bulk of `lang/nl.json`, all generated by **laravel-lang**. It also
  writes `lang/en/*.php`, committed like the rest even though the keys
  are already English.
- **Reuse laravel-lang's `:name` templates instead of writing one string
  per entity.** `__('Edit :name', ['name' => __('page')])` renders
  "Pagina bewerken" — `:Name` ucfirst's the replacement, so the noun goes
  in as a lowercase key (`page` → `pagina`, `user` → `gebruiker`) and the
  English fallback reads "Edit page". Same trick for the flash messages
  (`:Name created.` / `:Name updated.` / `:Name deleted.`) and the delete
  confirmation, which takes the record's own title. A third entity type
  then costs one key, not six.
- Not everything can be shared: **`New :name` translates to "Nieuwe :name"**
  and Dutch adjectives inflect on gender — a het-word noun breaks it (the
  former `post` → "artikel" would have produced "Nieuwe artikel" instead of
  "Nieuw artikel"). `New page` and `New user` therefore stay separate keys —
  check the Dutch reads before folding a string into a template.
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
  password confirmation are disabled on purpose** (client website — only
  admins log in); the unused controllers are deleted. Login/logout +
  password reset remain, with Tabler-styled views under
  `resources/views/auth/`. **The public site shows no login link** —
  admins navigate to `/login` directly; only the logout button is shown
  to authenticated users.
- The laravel/ui stubs' `$this->middleware()` constructor calls don't
  exist in modern Laravel. `LoginController` is the only one that needs
  middleware and declares it through `HasMiddleware` + a static
  `middleware()` method; the forgot/reset controllers carry none at all,
  so an already authenticated visitor is not bounced off those pages.
- **Authenticating lands you on the admin dashboard**, not on `/`: the
  only reason to log in here is the admin area. That is three places, all
  pointing at `admin.dashboard` — `LoginController` and
  `ResetPasswordController` (a completed reset logs you in too, and it is
  how a new admin first gets in) override laravel/ui's `$redirectTo`
  *property* with a `redirectTo()` **method**, because a route name only
  resolves at runtime; and `redirectUsersTo()` in `bootstrap/app.php`
  covers the `guest` middleware, which would otherwise send an already
  authenticated visitor of `/login` to the `home` route. Logging out still
  ends on the public home — you are a guest by then. Laravel's
  `redirect()->intended()` keeps priority throughout, so being bounced off
  an admin page still returns you to that page.
- The first admin (`info@jeroendn.nl`) is bootstrapped by a **data
  migration** (`2026_08_06_084152_create_admin_user`) without a usable
  password (random hash — the column is not nullable); the admin gains
  access via the password-reset flow. In dev (`MAIL_MAILER=log`) the reset
  link lands in `storage/logs/laravel.log`; prod needs working `MAIL_*`
  settings in `.env` before the reset mail can arrive.
### User management

- **Admin CRUD at `/admin/users`** (same auth middleware group as the
  pages; every authenticated user is an admin, so anyone who logs in can
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
  user yet, but pages are meant to get an author, and a hard delete would
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
- `User` carries `@property` docblocks like `Page` does: without them
  larastan types the model from the migrations, where `last_active_at` is a
  plain `timestamp` — i.e. a `string` you cannot call `diffForHumans()` on.
- **You cannot delete your own account**: the overview hides the button on
  your own row and `destroy()` `abort_if()`s on it. That is the only guard —
  admins can still delete each other, and getting back in after that works
  the way the first admin was bootstrapped: a row without a password plus
  the reset flow.

## 6. Pages

Formerly the blog; rebuilt into a generic page system per
`docs/plan-paginas.md` (the empty `posts` table was dropped and recreated
as `pages`, no data migration; `/blog` is gone).

- **Model `Page`**: `title`, `slug` (unique, public URL key), `body`
  (HTML from the editor), `is_draft` toggle (default on, so a new page
  stays hidden; shown in the form as a "Draft"/"Concept" switch),
  `show_in_menu` toggle, `priority` (int default 0), nullable
  `page_group_id` (FK, `restrictOnDelete`) and `published_at`.
  **The date only means something for grouped pages**: grouped +
  published requires one, and a future date keeps the page hidden until
  then ("scheduled"). Ungrouped pages ignore the date entirely — the
  requests null it on save, so it cannot linger when a page leaves its
  group. `Page::visible()` is the public query, `isVisible()` /
  `isScheduled()` the per-model checks; `isScheduled()` is by definition
  only ever true for a grouped page.
- **Slugs share one URL namespace**: DB-unique per table, plus a
  cross-table rule in the requests (pages and groups may never share a
  slug — no DB constraint spans two tables). Root-level slugs (ungrouped
  pages, root groups) also must not shadow an application route:
  `App\Rules\NotAReservedSlug` derives the reserved list from the route
  table (literal first path segments), so new app routes are covered
  automatically. `home` is excepted for pages — that slug becomes the
  home page and is served by the application itself at `/`. Grouped
  pages and subgroups never occupy a first URL segment and skip the rule.
- **Public URLs are dynamic**: `/{slug}` is an ungrouped page or a root
  group, `/{group}/{slug}` a grouped page or a subgroup,
  `/{group}/{subgroup}/{slug}` a subgroup page — three catch-all GET
  routes (`[a-z0-9-]+` per segment, names `pages.show`/`.grouped`/
  `.subgrouped`), registered **last** so every literal route wins, with
  the admin group registered before them. `Route::bind()` closures in
  `AppServiceProvider` resolve the segments to models inside
  `SubstituteBindings` — i.e. before route middleware, see §2. The
  page-or-group lookups cannot be ambiguous thanks to the cross-table
  slug rule; a grouped page's bare slug 404s (only the full path
  serves it). Visibility is a controller concern
  (`abort_unless(isVisible)`); a group URL renders `pages/group.blade.php`:
  its **own** visible pages (newest first, `simplePaginate(10)`,
  `partials/page-card.blade.php`) plus subgroup links. `Page::url()` /
  `PageGroup::url()` build all public URLs — views never hand-assemble
  paths.
- **Home**: `/` renders the ungrouped page slugged `home` (via
  `partials/page-article.blade.php`, shared with `pages/show`) or a bare
  layout while no visible one exists; `/home` 301-redirects to `/`. A
  draft home page counts as absent.
- **Admin CRUD** at `/admin/pages` (auth middleware on the route group;
  every authenticated user is an admin). Create/edit share
  `admin/pages/_form.blade.php`. The slug is generated from the title
  when left empty (`StorePageRequest::prepareForValidation`);
  `published_at` is a date field, stored as midnight — day precision is
  enough for this site (decided 2026-08-08) — prefilled on edit, so
  re-saving keeps the original publish date. `app.js` hides the date
  block while no group is selected and mirrors the draft toggle in the
  date label's `required` asterisk (both cosmetic only — the server
  nulls the date for ungrouped pages and validates regardless). The
  group select lists subgroups
  as "Parent / Child" (`PageGroup::fullName()`). The overview shows each
  page's group and its status as a badge: green "Published", yellow
  "Scheduled", grey "Draft".
- **Page groups** (`PageGroup`): `name`, `slug`, `show_in_menu`,
  `priority` (default 0 — higher will sort further left in the menu, ties
  alphabetically) and a nullable `parent_id`, **max one level deep**: the
  form only offers root groups as parent and the requests reject a
  non-root parent, a parent for a group that has children, and
  self-parenting. Admin CRUD at `/admin/page-groups` mirrors the
  pages/users style. Deleting a group that still has subgroups or pages
  is refused with the red error toast (and `restrictOnDelete` on both
  FKs as backstop). A group's URL renders its overview; the menu is
  described in §3.
- **Editor**: **Quill 2** (npm dependency, BSD-3). Deliberately NOT Trix:
  Trix 2.1.x never gets keyboard input into its document model (text shows
  in the DOM but nothing is saved) — reproduced with both its ESM and UMD
  builds; TinyMCE 7 and CKEditor 5 are GPL, unusable for this proprietary
  project. The snow toolbar is icon-only, so there is no JS-side copy to
  translate. `app.js` seeds Quill from the hidden `body` input and syncs
  back on change/submit via `getSemanticHTML()` (with an `&nbsp;`
  workaround for quill#4509). Quill ships its own complete styling; the five
  rules in `app.css` are all that is left (measured 2026-08-07 by deleting
  each one in the browser — one more, `color: inherit` on the editor's block
  elements, was a Pico artefact and had no effect under Tabler):
  a `min-height` (without it the editor collapses to 42px, since Quill sets
  `height: 100%` on an auto-height parent), a light surface (`#fff` plus
  Quill's own `#444` text), Tabler's border radius (two rules — the toolbar
  rounds at the top, the container at the bottom), and the `is-invalid`
  border. **The surface must stay light** — not because the text would be
  unreadable (Tabler's light body color on the dark card is fine) but
  because Quill hard-codes its toolbar icons to `#444` stroke/fill, which
  vanish on a dark background.
  Quill turns `#body-editor` into `.ql-container` and inserts `.ql-toolbar`
  as a **sibling**, so `_form.blade.php` puts the `is-invalid` class on a
  wrapper around it — on the editor itself only half of it would get the
  error border. Its editable div ships without a role or a name, so `app.js`
  sets `role="textbox"` + `aria-labelledby` on it; the visible "Content" label
  is a `<div>`, because a `<label>` could only point at the hidden input.
  No image/attachment support (no upload backend).
- **Sanitization**: the body is stored as-is and sanitized on output by
  **stevebauman/purify** (HTMLPurifier) in `Page::bodyHtml()` — the only
  place `{!! !!}` is allowed.
- **Known npm audit finding** (accepted): quill 2.0.3 has advisory
  GHSA-v3m3-f69x-jf25 / CVE-2025-15056 (low, CVSS 2.0) — XSS via the HTML
  export. There is NO patched release (npm's "fix" is a downgrade to
  2.0.2, which does not remove the behavior). Already mitigated here: the
  export is never rendered raw — HTMLPurifier sanitizes on output, covered
  by `PublicPagesTest::testUnsafeHtmlIsStrippedFromTheBody`. Update quill
  once a patched version ships.
- **bladestan gotcha**: standard Blade idioms (`{{ __() }}`, `{{ old() }}`,
  `@error`'s `$message`) are loosely typed in compiled templates;
  `phpstan.dist.neon` carries three documented `ignoreErrors` entries for
  exactly those patterns (scoped to constructs that only exist in compiled
  templates — real app code is unaffected). Shared partials need **no**
  `@var` type hints — bladestan propagates the `@include` variables itself
  (verified 2026-08-13 by removing them all). The one exception is the
  `_form` partials: create renders them without the model, edit with a
  never-null one, so the `Model|null` union only exists in their
  `@php /** @var ... */ @endphp` docblock — without it phpstan reports
  both `property.nonObject` (create) and `nullsafe.neverNull` (edit).

## 7. Tests

Feature tests live under `tests/Feature/` (`HomePageTest`, `Auth/LoginTest`,
`Auth/PasswordResetTest`, `Pages/PublicPagesTest`, `Pages/AdminPagesTest`,
`Pages/AdminPageGroupsTest`,
`Admin/DashboardTest`, `Admin/UsersTest`, `ActivityTrackingTest`,
`NavigationTest`, `MenuTest`, `BreadcrumbsTest`, `LocalizationTest`,
`AdminUserMigrationTest`, `MaintenancePageTest`, `UnderConstructionTest`,
`SearchIndexingTest`) and use `RefreshDatabase` on sqlite `:memory:` — except
`MaintenancePageTest`, which only renders a view and never touches a
database. They are the default level here — almost everything is
framework-coupled and best tested over HTTP.
`tests/Unit/` is only for pure model logic (`PageTest`: `Page::excerpt()`
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
touch MariaDB. CI (`.github/workflows/ci.yml`) runs three jobs on every PR
to master: `setup` (install + `composer validate --strict` +
`composer normalize --dry-run`), a `check` matrix over the check variants
(`rector-check`, `cs-check`, `phpstan`, `phpunit`) with a dummy `APP_KEY`
since CI has no `.env`, and `assets` (`npm ci` + `npm run build`).

A **cold phpstan cache** fails the gate in a way that looks like a code error
but is not (hit 2026-08-08): the parallel workers all build the same Nette
container at once and race on the rename, so `cqa` aborts with `Unable to
create file … tmp/phpstan/cache/nette.configurator/Container_….php` and
"Result is incomplete because of severe errors". Warm it once single-process
with `./develop composer phpstan -- --debug`; every parallel run after that
is green.

## 9. Outstanding

Only open work lives here. **Finished items are deleted from this list, not
ticked off** — what exists is described in the sections above.

- **Pages rebuild in progress**: the blog becomes a generic page system
      with page groups, a dynamic menu and dynamic URLs. Roadmap and
      decisions: `docs/plan-paginas.md` (one commit per checklist item).
- Remove the under-construction placeholder when the site goes live:
      it is tied to `APP_ENV=production` and will not lift by itself
      (see §2).
- Admin dashboard content: `/admin` exists as the landing page of the
      admin area (and the target of its breadcrumb house) but is
      deliberately empty — no widgets decided on yet.
- Page authorship: `pages` has no `user_id` yet. Users are soft-deleted
      (see §5), so that column can be added later without the delete button
      ever orphaning a page.
