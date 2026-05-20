# CandleCraft Academy — Site Content CMS (Design Spec)

- **Date:** 2026-05-11
- **Author:** Engineering team (drafted via brainstorming session)
- **Status:** Approved (proceeding to implementation plan)
- **Scope tier:** Extended (rich text + revisions + collaborative locking)

---

## 1. Problem & Goal

The public website (home, contact, courses) currently hardcodes brand text,
hero copy, CTA labels, footer copyright, intro paragraphs, and category card
descriptions inside Twig-style PHP templates. Updating any of these requires a
developer to edit a template, commit, and deploy. Iteration 2 of the project
asks us to deliver a self-built CMS module so that an administrator can change
this content directly from the existing admin portal without touching the
codebase.

**Success looks like:**

- Admin can browse a "Site Content" section in the existing admin sidebar.
- Admin can update titles, brand subtitle, footer copyright, hero copy, CTA
  labels, courses introduction, category descriptions, and the contact intro.
- Admin can upload and swap a logo image, favicon, and hero background image.
- Admin can preview a section's current value, edit it (typed input per
  content kind), and see edits reflected on the public site immediately.
- Admin can review change history per section and roll back to any prior
  revision.
- Two admins editing the same section see a soft lock so they don't overwrite
  each other.
- Public templates fall back to the original copy if a section row is missing
  (defensive default for partial migrations / fresh installs).

**Non-goals:** multi-language, page builder UX (drag-and-drop), front-end live
preview, public API, multi-tenant.

---

## 2. Approach

A small purpose-built CMS, **not** a generic page builder. Five tables capture
*pages → sections → media* with vertical slices for *revisions* and *locks*.
Public render goes through a `CmsHelper` that reads cached page bundles. Admin
UX reuses the existing `admin.php` layout, sidebar, and `.admin-tabs` /
`.admin-table-card` styles so it visually slots into the portal we already
have.

This is the result of considering three alternatives:

| Alt | Shape | Why not chosen |
|---|---|---|
| 1 | Single key-value `site_settings` table with type column | Mixes rich HTML and image references in the same string column, weak modeling for a pages-and-sections concept the user actually wants. |
| 2 | Two tables (`site_settings` + `content_blocks`) | Doubles the admin UI, helper, and revision/lock plumbing; little benefit over Alt 3 once locking + revisions are required. |
| **3 (chosen)** | `site_pages` → `page_sections` (typed) → `site_media`, plus `page_section_revisions` and `page_section_locks` | Models the user's mental model exactly (page → editable spots → media), keeps the typed editor out of the cache hot path, isolates revision and lock concerns. |

---

## 3. Data Model

Five tables. All timestamps are `DATETIME`. All FKs are `ON DELETE CASCADE`
unless otherwise noted.

### 3.1 `site_pages`
| Column | Type | Notes |
|---|---|---|
| `id` | `INT UNSIGNED PK AI` | |
| `page_slug` | `VARCHAR(80) NOT NULL UNIQUE` | `home`, `contact`, `courses`, `global` |
| `page_title` | `VARCHAR(255) NOT NULL` | Admin display label |
| `is_active` | `TINYINT(1) NOT NULL DEFAULT 1` | |
| `sort_order` | `INT NOT NULL DEFAULT 0` | |
| `created_at` | `DATETIME NOT NULL` | |
| `updated_at` | `DATETIME NOT NULL` | |

### 3.2 `page_sections`
| Column | Type | Notes |
|---|---|---|
| `id` | `INT UNSIGNED PK AI` | |
| `page_id` | `INT UNSIGNED NOT NULL FK→site_pages.id` | |
| `section_key` | `VARCHAR(120) NOT NULL` | dotted, e.g. `hero.title` |
| `section_label` | `VARCHAR(255) NOT NULL` | admin form label |
| `section_hint` | `TEXT NULL` | admin helper copy |
| `content_type` | `VARCHAR(20) NOT NULL` | `text`, `textarea`, `html`, `url`, `email`, `image`, `number` |
| `content_value` | `LONGTEXT NULL` | text/HTML/URL/email/number stored verbatim; `NULL` for image type |
| `media_id` | `INT UNSIGNED NULL FK→site_media.id ON DELETE SET NULL` | populated when `content_type='image'` |
| `is_active` | `TINYINT(1) NOT NULL DEFAULT 1` | |
| `sort_order` | `INT NOT NULL DEFAULT 0` | display order within a page |
| `updated_at` | `DATETIME NOT NULL` | |
| `updated_by_id` | `INT UNSIGNED NULL FK→users.user_id ON DELETE SET NULL` | |
| | `UNIQUE (page_id, section_key)` | |
| | `INDEX (page_id, sort_order)` | |

### 3.3 `site_media`
| Column | Type | Notes |
|---|---|---|
| `id` | `INT UNSIGNED PK AI` | |
| `file_name` | `VARCHAR(255) NOT NULL` | original upload name (display only) |
| `file_path` | `VARCHAR(500) NOT NULL` | relative path under `webroot/uploads/site/` |
| `file_url` | `VARCHAR(500) NOT NULL` | precomputed public URL for cheap reads |
| `mime_type` | `VARCHAR(100) NOT NULL` | |
| `file_size` | `INT UNSIGNED NOT NULL` | bytes |
| `alt_text` | `VARCHAR(500) NULL` | |
| `uploaded_by_id` | `INT UNSIGNED NULL FK→users.user_id ON DELETE SET NULL` | |
| `uploaded_at` | `DATETIME NOT NULL` | |
| `updated_at` | `DATETIME NOT NULL` | |

### 3.4 `page_section_revisions`
| Column | Type | Notes |
|---|---|---|
| `id` | `INT UNSIGNED PK AI` | |
| `section_id` | `INT UNSIGNED NOT NULL FK→page_sections.id ON DELETE CASCADE` | |
| `content_value_snapshot` | `LONGTEXT NULL` | |
| `media_id_snapshot` | `INT UNSIGNED NULL` | not FK (media may be deleted; we keep a numeric breadcrumb) |
| `changed_by_id` | `INT UNSIGNED NULL FK→users.user_id ON DELETE SET NULL` | |
| `changed_at` | `DATETIME NOT NULL` | |
| `change_summary` | `VARCHAR(500) NULL` | e.g. "Edited via admin", "Lock force-taken from Bob", "Restored revision #12" |
| | `INDEX (section_id, changed_at)` | |

### 3.5 `page_section_locks`
| Column | Type | Notes |
|---|---|---|
| `id` | `INT UNSIGNED PK AI` | |
| `section_id` | `INT UNSIGNED NOT NULL UNIQUE FK→page_sections.id ON DELETE CASCADE` | only one active lock per section |
| `locked_by_id` | `INT UNSIGNED NOT NULL FK→users.user_id ON DELETE CASCADE` | |
| `locked_at` | `DATETIME NOT NULL` | |
| `expires_at` | `DATETIME NOT NULL` | default `locked_at + 5 min`; refreshed by heartbeat |

---

## 4. Initial Seed Data

Migration also inserts these rows so the site keeps rendering after deploy.
`content_value` for each row is exactly the string currently hardcoded in the
template, so behaviour is unchanged on day 0.

### Page `global` — shared brand & navigation
- `branding.site_name` *(text)* = "CandleCraft Academy"
- `branding.site_subtitle` *(text)* = "Pottery & Knitting Tutoring"
- `branding.copyright_text` *(text)* = "© {year} CandleCraft Academy. All rights reserved."
- `branding.logo_image` *(image)* = NULL (admin can upload; templates render text brand if missing)
- `branding.favicon_image` *(image)* = NULL (templates fall back to `/favicon.ico`)
- `nav.cta_label` *(text)* = "Enquire"
- `nav.login_label` *(text)* = "Log In"

### Page `home`
- `hero.eyebrow` *(text)* = "A Sanctuary For The Creative Soul."
- `hero.title` *(text)* = "CandleCraft Academy"
- `hero.background_image` *(image)* = current `/image/background.jpg` URL
- `hero.cta_primary_label` *(text)* = "Browse Courses"
- `hero.cta_secondary_label` *(text)* = "Enquire Today"

### Page `contact`
- `intro.title` *(text)* = "Enquiry Form"
- `intro.body` *(textarea)* = "Use the enquiry form below and someone from our team will be in touch shortly."

### Page `courses`
- `intro.eyebrow` *(text)* = "CandleCraft Academy"
- `intro.title` *(text)* = "Our Courses"
- `category.pottery_title` *(text)* = "Pottery"
- `category.pottery_description` *(html)* = current pottery card paragraph
- `category.knitting_title` *(text)* = "Knitting"
- `category.knitting_description` *(html)* = current knitting card paragraph

`{year}` in `branding.copyright_text` is rendered by the helper, not stored
literally as a year, so the copyright text never goes stale.

---

## 5. Service Layer (`src/Service/Cms/`)

Each service is a single-responsibility class injectable into controllers and
the helper, so each can be unit-tested without HTTP.

| Service | Public API | Responsibility |
|---|---|---|
| `ContentResolver` | `get(string $slug): array`, `invalidate(string $slug): void` | Read all active sections for a page through cache (`Cache::config('cms')`, key `cms.page.{slug}`, TTL 1h). On miss, query `PageSections` joined with `SiteMedia`. Returns associative array keyed by `section_key` with normalized values (image type returns the `file_url`). |
| `MediaUploader` | `store(UploadedFileInterface $file, ?string $alt, int $userId): SiteMedia` | Validates mime + extension + size + magic bytes (`finfo`); writes to `webroot/uploads/site/YYYY/MM/<sha1>.<ext>`; persists `site_media` row. |
| `SectionLockService` | `acquire(int $sectionId, int $userId): LockOutcome`, `heartbeat(int $sectionId, int $userId): bool`, `release(int $sectionId, int $userId): void`, `forceTake(int $sectionId, int $userId): void`, `currentHolder(int $sectionId): ?LockInfo` | Soft lock with `expires_at` TTL of 5 min; heartbeat extends; expired locks treated as released. |
| `RevisionRecorder` | `snapshot(PageSection $section, int $userId, string $summary): PageSectionRevision`, `restore(int $sectionId, int $revisionId, int $userId): PageSection` | Snapshot pre-save state; restore writes a fresh revision with `change_summary = "Restored revision #N"` so timeline stays append-only. |

All write paths run inside a single CakePHP DB transaction (`getConnection()->transactional(...)`) so revision + section save commit together.

---

## 6. Helper (`src/View/Helper/CmsHelper.php`)

```php
$this->Cms->text('home', 'hero.title', 'CandleCraft Academy');
$this->Cms->html('courses', 'category.pottery_description', '<p>...</p>');
$this->Cms->image('global', 'branding.logo_image');         // returns URL or null
$this->Cms->all('home');                                    // dictionary lookup for hot pages
```

- Internally caches a per-request page bundle from `ContentResolver` so a
  single template render hits the DB at most once per page slug.
- `text()` returns plain string; calling code must HTML-escape with `h()`.
- `html()` returns sanitized HTML markup ready for direct `<?= ... ?>` echo.
- `image()` returns the public URL for the bound `site_media` row or `null`
  if the image hasn't been uploaded yet (templates can choose a fallback).
- The `{year}` placeholder in `branding.copyright_text` is interpolated at
  render time.

### HTML sanitization

Whitelist-based using a small in-house allowlist (no new vendor dependency):

- Tags: `p`, `br`, `strong`, `em`, `b`, `i`, `u`, `a`, `ul`, `ol`, `li`, `span`
- Attributes: `href` (only on `<a>`, only `http(s)` and mailto), `target`,
  `rel` (forced to include `nofollow noopener`).
- Everything else is stripped via `strip_tags($html, $allowed)` plus a regex
  pass to enforce attribute whitelist.

Tested with adversarial inputs (script tags, `javascript:` URLs, on-event
attrs, malformed HTML) in `CmsHelperTest`.

---

## 7. Admin UI

Sidebar adds a single new entry **Site Content** (Bootstrap icon
`bi-pencil-square`) routed to `/admin/cms`. All routes live under
`Admin\Controller\AppController` so the existing role-gate and admin layout
apply automatically.

| Route | Action | Template |
|---|---|---|
| `GET /admin/cms` | `CmsPagesController::index` | List of pages with edit count badges |
| `GET /admin/cms/pages/{slug}` | `CmsPagesController::view($slug)` | Sections of one page; row per section with current value preview, lock badge, "Edit" / "History" actions |
| `GET /admin/cms/sections/{id}/edit` | `CmsPagesController::editSection($id)` | Acquires lock; renders type-aware form |
| `POST /admin/cms/sections/{id}/edit` | `CmsPagesController::editSection($id)` | Validates, snapshots, saves, invalidates cache, releases lock |
| `GET /admin/cms/sections/{id}/history` | `CmsPagesController::history($id)` | Revision timeline + restore buttons |
| `POST /admin/cms/sections/{id}/restore/{revisionId}` | `CmsPagesController::restore($id, $revisionId)` | Snapshot current then write back |
| `POST /admin/cms/sections/{id}/heartbeat` | `CmsPagesController::heartbeat($id)` | JSON `{ ok: true, expiresAt }` |
| `POST /admin/cms/sections/{id}/lock/force` | `CmsPagesController::forceLock($id)` | Audit-logged lock takeover |
| `GET /admin/cms/media` | `CmsMediaController::index` | Media library (thumbnails) |
| `POST /admin/cms/media` | `CmsMediaController::upload` | Multipart upload via `MediaUploader` |
| `DELETE /admin/cms/media/{id}` | `CmsMediaController::delete($id)` | Refuses if `page_sections.media_id` references it |

UI inherits all existing admin styles. Section edit page uses elements under
`templates/element/cms/section_input/` for each `content_type` so adding a new
type later means dropping a new partial in.

The HTML-typed input renders a small assist toolbar (Bold / Italic / Link /
UL / OL) above a plain `<textarea>`. Each toolbar button wraps the current
text selection inside the corresponding HTML tag using `selectionStart` /
`selectionEnd` and writes the resulting markup back into the textarea — no
contenteditable, no `execCommand`, no third-party WYSIWYG bundle. The
sanitization in §6 happens server-side on render so the editor cannot inject
disallowed tags even by typing them manually.

---

## 8. Locking UX

```
acquire() returns one of:
  - acquired  (caller holds lock)
  - already_self (caller already holds it)
  - held_by_other(holder, age_seconds, expires_at)
```

When the admin opens an edit page:
- If `acquired` or `already_self`: render form normally.
- If `held_by_other`: render a banner *"This section is being edited by
  Alice (last active 2 minutes ago). [Edit anyway / take lock] [View
  read-only]"*. The form is disabled until the user explicitly forces.
- Force-take writes a `RevisionRecorder::snapshot` with summary
  `"Lock force-taken from Alice"` so subsequent timeline shows what
  happened.

Front-end JS pings `/admin/cms/sections/{id}/heartbeat` every 60 s while the
edit page is visible; on submit it `release()`s the lock; on `beforeunload` it
fires a `navigator.sendBeacon` release as best-effort cleanup.

Expired locks (`expires_at < NOW()`) are treated as released by every read
path and are physically deleted on the next acquire attempt for the same
section, so we don't need a cron job.

---

## 9. Public Template Replacement

The following hardcoded strings are migrated to helper calls:

| File | Old | New |
|---|---|---|
| `templates/Pages/home.php` | `<title>CandleCraft Academy</title>` | `<title><?= h($this->Cms->text('global','branding.site_name','CandleCraft Academy')) ?></title>` |
| `templates/Pages/home.php` & `contact.php` | `href="/favicon.ico"` / `/favicon.png` | `$this->Cms->image('global','branding.favicon_image','/favicon.ico')` |
| `templates/Pages/home.php` | `class="hero-copy__eyebrow">A Sanctuary...` | `<?= h($this->Cms->text('home','hero.eyebrow','A Sanctuary For The Creative Soul.')) ?>` |
| `templates/Pages/home.php` | hero title `CandleCraft Academy` | `$this->Cms->text('home','hero.title', '...')` |
| `templates/Pages/home.php` | bg image `/image/background.jpg` | `$this->Cms->image('home','hero.background_image','/image/background.jpg')` |
| `templates/Pages/home.php` | "Browse Courses" / "Enquire Today" | `$this->Cms->text('home','hero.cta_primary_label', '...')` and secondary |
| `templates/Pages/home.php` | footer `&copy; ... All rights reserved.` | `$this->Cms->text('global','branding.copyright_text', '...')` (helper interpolates `{year}`) |
| `templates/element/public_nav.php` | brand title + subtitle | `branding.site_name` + `branding.site_subtitle` |
| `templates/element/public_nav.php` | "Enquire" / "Log In" | `nav.cta_label` / `nav.login_label` |
| `templates/Pages/contact.php` | `<h1>Enquiry Form</h1>` + intro paragraph | `contact.intro.title` / `contact.intro.body` |
| `templates/Courses/index.php` | "CandleCraft Academy" eyebrow + "Our Courses" + Pottery / Knitting card titles + descriptions | `courses.intro.*` and `courses.category.*` |

Each helper call passes the original string as the fallback so the site keeps
rendering even if the `page_sections` row is unexpectedly missing.

---

## 10. Caching

- Cache config registered in `config/app.php` (or `app_local.php`) under key
  `cms`, file backend by default (`tmp/cache/cms`), TTL 1 hour.
- Cache key per page slug: `cms.page.{slug}`. Value is the normalized
  associative array `ContentResolver::get` returns.
- Cache invalidated on: section save, section restore, media replace if it
  was referenced by a section, `bin/cake cache clear cms` (manual escape).
- Helper batches reads per-request: a `private array $loaded` keyed by slug
  ensures the same page bundle is not fetched twice in one render.

---

## 11. Error Handling

| Scenario | Behaviour |
|---|---|
| Lock conflict on save | Reject save (HTTP 409 for AJAX, flash + redirect to view for HTML), do not mutate. |
| Validation fail (e.g. invalid URL, oversize text) | Re-render edit form with errors; lock retained. |
| DB transaction fail | Rollback section + revision together; lock retained; flash error. |
| Restore non-existent revision | 404. |
| Upload mime / magic / size fail | 400 with detailed message; nothing persisted. |
| Delete media still referenced | 409 with list of referencing sections. |
| Cache write fail | Log warning, fall through to direct DB read on next request. |

---

## 12. Security

- Admin role gate via existing `Admin\AppController::beforeFilter`.
- CSRF via existing CakePHP middleware (form helper auto-injects token).
- HTML sanitization (Section 6) applied **on render**, not on save, so we can
  tighten the whitelist later without re-parsing stored values.
- File uploads: mime + magic-bytes check (`finfo_file`), extension whitelist
  (`png/jpg/jpeg/webp/svg/ico`), size cap 5 MB, hashed filename so original
  user input cannot influence path.
- All file paths stored as relative under `webroot/uploads/site/`; URL
  generation uses the configured base URL (no user-controlled prefix).
- Audit trail via `page_section_revisions` + lock force `change_summary`.

---

## 13. Testing Plan (TDD)

| Suite | Tests |
|---|---|
| `tests/TestCase/Service/Cms/ContentResolverTest.php` | cache miss → DB query; cache hit returns cached; invalidate; image type returns URL; inactive sections excluded |
| `tests/TestCase/Service/Cms/MediaUploaderTest.php` | accepts valid PNG/JPG/SVG/WebP/ICO; rejects fake `.png` with PHP magic bytes; rejects oversize; persists `site_media` row; sanitizes filename |
| `tests/TestCase/Service/Cms/SectionLockServiceTest.php` | acquire on free section; double acquire by same user OK; conflict for different user; expired lock auto-released; heartbeat extends; forceTake replaces holder and audits |
| `tests/TestCase/Service/Cms/RevisionRecorderTest.php` | snapshot stores current value; restore writes back + creates new revision with summary; restore unknown revision throws |
| `tests/TestCase/View/Helper/CmsHelperTest.php` | text fallback when missing; html sanitization (script tag stripped, javascript: URL stripped, allowed tags preserved); image returns URL or null; `{year}` interpolation |
| `tests/TestCase/Controller/Admin/CmsPagesControllerTest.php` | non-admin gets redirected; index lists pages; view lists sections; edit GET acquires lock; edit POST saves + writes revision + invalidates cache; edit POST under conflict → 409 redirect; history lists revisions; restore restores; forceLock writes audit |
| `tests/TestCase/Controller/Admin/CmsMediaControllerTest.php` | upload happy path; upload fails for bad mime; delete refuses when referenced; delete OK otherwise |

Fixtures: minimal `Users`, `SitePages`, `PageSections`, `SiteMedia`,
`PageSectionRevisions`, `PageSectionLocks`.

---

## 14. File Layout

```
config/Migrations/2026MMDDHHMMSS_CreateCmsTables.php
config/Migrations/2026MMDDHHMMSS_SeedDefaultCmsContent.php
src/Model/Table/{SitePages,PageSections,SiteMedia,PageSectionRevisions,PageSectionLocks}Table.php
src/Model/Entity/{SitePage,PageSection,SiteMedia,PageSectionRevision,PageSectionLock}.php
src/Controller/Admin/{CmsPages,CmsMedia}Controller.php
src/Service/Cms/{ContentResolver,MediaUploader,SectionLockService,RevisionRecorder}.php
src/View/Helper/CmsHelper.php
templates/Admin/CmsPages/{index,view,edit_section,history}.php
templates/Admin/CmsMedia/index.php           (list + inline upload form, no separate upload.php)
templates/element/cms/section_input/{text,textarea,html,url,email,image,number}.php
webroot/css/cms-admin.css
webroot/js/cms-admin.js
webroot/uploads/site/.gitkeep                (uploads/site/* gitignored)
tests/...                                    (see Section 13)
docs/CMS.md                                  (admin user guide)
```

---

## 15. Out of Scope (Explicit Non-Goals)

- Multi-language / i18n routing.
- Drag-and-drop page builder.
- Front-end live preview before publish (current model is publish-on-save).
- Public read API.
- Granular per-section role permissions (any admin can edit any section).
- Scheduled / time-windowed publishing.
- SEO meta-tag editor (could become a follow-up sub-spec).

---

## 16. Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Cache stale after deploy on multi-server setup | Bumping the migration's seeded `updated_at` invalidates the file cache via `Cache::clear('cms')` in a post-migration hook; documented in `docs/CMS.md`. |
| HTML sanitization gaps | Conservative whitelist + adversarial test fixtures; tightening only requires editing the helper, no data migration. |
| Lock TTL exceeded mid-edit (laptop sleep) | 60 s heartbeat; explicit "lock expired, take it back?" banner if heartbeat receives 410. |
| Uploads filling disk | 5 MB cap; future work could add a janitor command for orphaned media. Not in v1. |
| Migration on existing prod DB | All tables are net-new; no destructive change to existing schema. |

---

## 17. Acceptance Checklist

- [ ] All 5 tables created via migration; `bin/cake migrations status` clean.
- [ ] Seed migration populates all sections listed in §4 with current copy.
- [ ] Admin can edit each seeded section through the UI and the public site
      reflects the change after a page reload (within cache TTL we explicitly
      invalidate on save).
- [ ] Admin can revert via History → Restore.
- [ ] Two admin sessions on the same section see the lock banner; force-take
      works and writes an audit row.
- [ ] Logo / favicon / hero background can be replaced via media upload and
      appear on the public site.
- [ ] All 7 service / helper test files pass under `vendor/bin/phpunit`.
- [ ] Both controller integration test files pass.
- [ ] No hardcoded copy remains in `home.php`, `contact.php`,
      `Courses/index.php`, `element/public_nav.php` for the points listed in §9.
- [ ] `docs/CMS.md` documents admin workflow.
