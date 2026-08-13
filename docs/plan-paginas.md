# Rebuild: blog → pages + page groups

Roadmap for replacing the blog with a generic page system. Executed as the
numbered commits below, each self-contained and green under `./develop cqa`;
every commit ticks its own checkbox here and updates CLAUDE.md (and README
where the front door changes) in the same commit. The file stays in the repo
as the record of the rebuild; it can be deleted once everything is done.

## Decisions (final, agreed 2026-08-13)

- **Pages** replace posts. Prod's `posts` table is empty → dropped, no data
  migration, no redirects (the site is still behind the under-construction
  placeholder).
- New page fields: `is_published` toggle (new page = draft), `show_in_menu`
  toggle, `priority` (int, default 0 — higher sorts further left in the
  menu; ties sort alphabetically), optional `page_group_id`.
- `published_at` only applies to pages **in a group**: required once such a
  page is published, a future date keeps it hidden until then (scheduling
  stays), and the date is only shown (form + public page) for grouped pages.
  Ungrouped pages ignore the date entirely.
- **Page groups**: name, slug, `show_in_menu`, `priority`, optional
  `parent_id` — max one level of nesting. A group URL renders an overview of
  its directly attached visible pages (newest first, paginated) plus links
  to its subgroups. Deleting a non-empty group is blocked.
- **Menu**: root groups + ungrouped pages (each behind its `show_in_menu`
  toggle; pages also must be publicly visible), sorted priority DESC then
  alphabetically. Groups are dropdowns; subgroups are real flyout submenus
  (custom CSS/JS — Bootstrap 5 has none). Last item of every dropdown links
  to the group overview ("Show All"). Toggles only affect the menu — URLs
  stay reachable.
- **URLs**: `/{slug}`, `/{group}/{slug}`, `/{group}/{subgroup}/{slug}`.
  Slugs are DB-unique within their own table; a validation rule
  additionally keeps pages and groups from ever sharing a slug
  (cross-table — lands with commit 4). Root slugs must not collide with
  application routes (`/admin`, `/login`, …) — the reserved list is
  derived from the route table. A grouped page is only reachable at its
  full path.
- **Home**: the ungrouped page slugged `home` renders at `/` (`/home`
  301-redirects there); without one, `/` is the bare layout. No fixed Home
  menu item — the home page appears in the menu via its own toggle.

## Commits

- [x] **2. Mechanical rename posts → pages** — migration drops `posts`,
      creates `pages` (same columns); `Post` → `Page` across model,
      controllers, requests, factory, views, route names (`posts.*` →
      `pages.*`; public URLs stay `/blog` until commit 5), `/admin/pages`,
      breadcrumbs, nav labels, tests (`tests/Feature/Blog/` →
      `Pages/`), lang keys, docs, composer.json. Zero behavior change.
- [x] **3. PageGroup entity + admin CRUD** — `page_groups` migration
      (`parent_id` self-FK, restrict on delete), model/factory/requests
      with the one-level nesting validations, CRUD at `/admin/page-groups`
      in the existing posts/users style, blocked delete with an error
      toast (layout gains a `session('error')` twin), admin nav +
      breadcrumb + noindex coverage, `AdminPageGroupsTest`.
- [ ] **4. Page fields + visibility semantics** — migration adds
      `is_published`, `show_in_menu`, `priority`, `page_group_id`;
      `Page::isVisible()`/`visible()` replace `published()`; the
      cross-table slug rule (pages ↔ groups) + reserved root slugs from
      the route table; `published_at` required when published + grouped; admin form
      gains group select, switches, priority, and JS show/hide of the date
      field; status badges Draft/Scheduled/Published; the validation
      matrix (scope × toggle × date) lands as tests first.
- [ ] **5. Dynamic URLs, home, group overviews, breadcrumbs** — admin
      routes registered before the public group; `/`, `/home` redirect and
      the 1–3 segment catch-alls (last) inside the UnderConstruction
      group; `Route::bind()` closures resolve paths before route
      middleware so unknown paths still 404 on prod; `PageController`
      dispatches Page vs PageGroup (new overview view); `Page::url()` /
      `PageGroup::url()`; `/blog`, the home teaser and the archive view
      are deleted; breadcrumbs walk the bound models' ancestors; heavy
      test rebuild (path matrix, home scenarios, route-order regressions).
- [ ] **6. The menu** — `App\Support\Menu` + `MenuItem` DTO (two queries,
      tree + shared comparator in PHP), recursive `partials/menu-item`
      replaces the placeholder dropdowns, flyout CSS (desktop) + inline
      expansion below `md` (burger), small hand-rolled submenu JS,
      `MenuTest`, manual browser check (flyout, burger, dark mode,
      keyboard) before pushing.
- [ ] **7. Final sweep** — repo-wide `grep -ri "post\|blog"`, orphaned
      lang keys pruned, final CLAUDE.md/README pass, tick this box.
