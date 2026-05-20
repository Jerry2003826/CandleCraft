# CandleCraft CMS Implementation Plan

> **For agentic workers:** Tasks are TDD-style and append to the
> `feature/cms-site-content` branch. Steps use checkbox (`- [ ]`) syntax
> for tracking. Spec: `docs/superpowers/specs/2026-05-11-cakephp-cms-design.md`.

**Goal:** Ship a self-built CMS that lets admins edit site copy, brand
strings, hero copy, category descriptions, and media (logo / favicon /
hero background) without touching templates — backed by revisions and
collaborative locking.

**Architecture:** 5 tables (`site_pages`, `page_sections`, `site_media`,
`page_section_revisions`, `page_section_locks`) → 4 services → `CmsHelper` →
2 admin controllers + UI. Public templates resolve copy through the helper
with hardcoded fallbacks. See spec for full design.

**Tech Stack:** CakePHP 5, MySQL/MariaDB, Bootstrap 5, vanilla JS for the
edit-toolbar/heartbeat. No new vendor dependencies.

---

## File Structure (locked decisions)

```
config/Migrations/
  20260511020000_CreateCmsTables.php
  20260511020100_SeedDefaultCmsContent.php

src/Model/Table/
  SitePagesTable.php
  PageSectionsTable.php
  SiteMediaTable.php
  PageSectionRevisionsTable.php
  PageSectionLocksTable.php

src/Model/Entity/
  SitePage.php
  PageSection.php
  SiteMedia.php
  PageSectionRevision.php
  PageSectionLock.php

src/Service/Cms/
  ContentResolver.php
  MediaUploader.php
  SectionLockService.php
  RevisionRecorder.php
  HtmlSanitizer.php             (used by CmsHelper, isolated for tests)

src/View/Helper/
  CmsHelper.php

src/Controller/Admin/
  CmsPagesController.php
  CmsMediaController.php

templates/Admin/CmsPages/
  index.php           (page list)
  view.php            (sections of a page)
  edit_section.php    (type-aware editor)
  history.php         (revision timeline)

templates/Admin/CmsMedia/
  index.php           (library + inline upload)

templates/element/cms/section_input/
  text.php  textarea.php  html.php  url.php  email.php  image.php  number.php

templates/layout/admin.php   (modify: add Site Content sidebar entry)
templates/Pages/home.php             (modify: replace hardcoded copy)
templates/Pages/contact.php          (modify: replace hardcoded copy)
templates/Courses/index.php          (modify: replace hardcoded copy)
templates/element/public_nav.php     (modify: brand from CMS)

webroot/css/cms-admin.css
webroot/js/cms-admin.js
webroot/uploads/site/.gitkeep        (uploads/site/* gitignored)

tests/Fixture/SitePagesFixture.php
tests/Fixture/PageSectionsFixture.php
tests/Fixture/SiteMediaFixture.php
tests/Fixture/PageSectionRevisionsFixture.php
tests/Fixture/PageSectionLocksFixture.php

tests/TestCase/Service/Cms/ContentResolverTest.php
tests/TestCase/Service/Cms/MediaUploaderTest.php
tests/TestCase/Service/Cms/SectionLockServiceTest.php
tests/TestCase/Service/Cms/RevisionRecorderTest.php
tests/TestCase/Service/Cms/HtmlSanitizerTest.php
tests/TestCase/View/Helper/CmsHelperTest.php
tests/TestCase/Controller/Admin/CmsPagesControllerTest.php
tests/TestCase/Controller/Admin/CmsMediaControllerTest.php

config/app.php                       (modify: register `cms` cache config)
config/routes.php                    (modify: add /admin/cms routes via prefix)
.gitignore                           (modify: ignore webroot/uploads/site/*)

docs/CMS.md                          (admin guide)
```

---

## Phase 1 — Database & Models

### Task 1: Create CMS tables migration

**Files:** Create `config/Migrations/20260511020000_CreateCmsTables.php`

- [ ] Write migration with all 5 tables matching spec §3 column-for-column
      (page_slug VARCHAR 80 UNIQUE; FK + indexes per spec).
- [ ] Run `bin/cake migrations migrate` against the dev DB.
- [ ] Verify with `DESCRIBE site_pages; DESCRIBE page_sections;` etc.
- [ ] Commit: `feat(cms): create CMS schema migrations`

### Task 2: Seed default CMS content migration

**Files:** Create `config/Migrations/20260511020100_SeedDefaultCmsContent.php`

- [ ] Insert 4 `site_pages` rows: `home`, `contact`, `courses`, `global`.
- [ ] Insert all `page_sections` rows from spec §4 (use exact current
      hardcoded strings as `content_value`).
- [ ] Run `bin/cake migrations migrate`; verify `SELECT COUNT(*) FROM page_sections;`
      returns the expected count (≈18 rows).
- [ ] Commit: `feat(cms): seed default CMS content matching current site copy`

### Task 3: Tables + Entities + Fixtures

**Files:**
- Create `src/Model/Table/{SitePages,PageSections,SiteMedia,PageSectionRevisions,PageSectionLocks}Table.php`
- Create `src/Model/Entity/{SitePage,PageSection,SiteMedia,PageSectionRevision,PageSectionLock}.php`
- Create `tests/Fixture/{SitePages,PageSections,SiteMedia,PageSectionRevisions,PageSectionLocks}Fixture.php`

- [ ] Bake or hand-write Tables with associations (PageSection belongsTo SitePage, hasMany Revisions, hasOne Lock; PageSection belongsTo SiteMedia).
- [ ] Add `getAlias()`/validation rules: `setting_key` not empty, `content_type` in enum list.
- [ ] Hand-write minimal fixtures with deterministic seed rows (1 user fixture row, 4 pages, 6 sections covering all content_types).
- [ ] Smoke test: `vendor/bin/phpunit --testsuite app -c phpunit.xml.dist` baseline still green.
- [ ] Commit: `feat(cms): add CakePHP Table/Entity classes and test fixtures`

---

## Phase 2 — Service Layer (TDD)

### Task 4: HtmlSanitizer service (TDD)

**Files:**
- Create `src/Service/Cms/HtmlSanitizer.php`
- Create `tests/TestCase/Service/Cms/HtmlSanitizerTest.php`

Behavior: whitelist tags `p, br, strong, em, b, i, u, a, ul, ol, li, span`;
on `<a>` keep only `href`/`target`/`rel`; only allow http(s)/mailto schemes;
force `rel="nofollow noopener"`; strip all other tags via `strip_tags` plus
DOMDocument-based attribute filter.

- [ ] Write tests: allowed tags survive; `<script>` stripped; `javascript:` URL stripped from href; `onclick` stripped; malformed HTML doesn't blow up; empty input returns empty.
- [ ] Run tests — expect FAIL (class doesn't exist).
- [ ] Implement `HtmlSanitizer::clean(string $html): string`.
- [ ] Run tests — expect all PASS.
- [ ] Commit: `feat(cms): add HtmlSanitizer with whitelist policy`

### Task 5: ContentResolver service (TDD)

**Files:**
- Create `src/Service/Cms/ContentResolver.php`
- Create `tests/TestCase/Service/Cms/ContentResolverTest.php`

Behavior: `get(string $slug): array` returns assoc keyed by section_key with
normalized values; image type returns `[type=>'image', url=>'...', alt=>'...']`;
others return scalar string. Cache `cms.page.{slug}` TTL 3600. `invalidate($slug)` deletes cache key.

- [ ] Write tests: cache miss queries DB; cache hit skips DB (use Cache::clear before each); inactive sections excluded; image type joins SiteMedia and exposes file_url/alt_text; invalidate forces re-query.
- [ ] Run tests — expect FAIL.
- [ ] Implement `ContentResolver`.
- [ ] Tests pass.
- [ ] Commit: `feat(cms): add ContentResolver with per-page cache`

### Task 6: SectionLockService (TDD)

**Files:**
- Create `src/Service/Cms/SectionLockService.php`
- Create `tests/TestCase/Service/Cms/SectionLockServiceTest.php`

Behavior: `acquire($sectionId, $userId)` returns `LockOutcome` enum-like value
object: `acquired | already_self | held_by_other(holderId, holderName, lockedAt, expiresAt)`.
TTL 5 min; `heartbeat` extends `expires_at`; `release` deletes lock if owned
by user; `forceTake` overwrites existing lock; expired locks are auto-cleaned
on next acquire.

- [ ] Tests: free section → acquired; same user re-acquire → already_self; other user → held_by_other; expired lock auto-released; heartbeat extends; release by non-holder is no-op; forceTake overwrites.
- [ ] Run — FAIL.
- [ ] Implement.
- [ ] Tests pass.
- [ ] Commit: `feat(cms): add SectionLockService with TTL + force-take`

### Task 7: RevisionRecorder (TDD)

**Files:**
- Create `src/Service/Cms/RevisionRecorder.php`
- Create `tests/TestCase/Service/Cms/RevisionRecorderTest.php`

Behavior: `snapshot($section, $userId, $summary)` stores current
content_value + media_id into `page_section_revisions`. `restore($sectionId,
$revisionId, $userId)` writes a fresh snapshot of the *current* value first,
then patches the section back to the chosen revision's snapshot, returning
the updated entity. Both wrapped in transaction by caller.

- [ ] Tests: snapshot creates a revision row with summary "Manual edit" / custom summary; restore unknown revision throws RuntimeException; restore success writes back content_value + media_id and produces an additional revision with summary `"Restored revision #N"`.
- [ ] Run — FAIL.
- [ ] Implement.
- [ ] Tests pass.
- [ ] Commit: `feat(cms): add RevisionRecorder for audit + restore`

### Task 8: MediaUploader (TDD)

**Files:**
- Create `src/Service/Cms/MediaUploader.php`
- Create `tests/TestCase/Service/Cms/MediaUploaderTest.php`

Behavior: validate against UploadedFileInterface — mime in
{image/png, image/jpeg, image/webp, image/svg+xml, image/x-icon, image/vnd.microsoft.icon};
size ≤ 5 MB; magic bytes via `finfo_buffer`; write to
`webroot/uploads/site/YYYY/MM/<sha1>.<ext>`; persist `site_media` row;
return SiteMedia entity.

- [ ] Tests: valid PNG accepted and persisted; oversize rejected; bad mime rejected; magic-bytes mismatch rejected (e.g. text content with .png filename); deterministic hashed filename.
- [ ] Run — FAIL.
- [ ] Implement.
- [ ] Tests pass.
- [ ] Commit: `feat(cms): add MediaUploader with validation + hashed storage`

---

## Phase 3 — View Helper (TDD)

### Task 9: CmsHelper

**Files:**
- Create `src/View/Helper/CmsHelper.php`
- Create `tests/TestCase/View/Helper/CmsHelperTest.php`

Public API:
```php
$this->Cms->text(string $page, string $key, string $fallback = ''): string
$this->Cms->html(string $page, string $key, string $fallback = ''): string  // sanitized
$this->Cms->image(string $page, string $key, ?string $fallback = null): ?string  // url
$this->Cms->imageAlt(string $page, string $key, string $fallback = ''): string
$this->Cms->all(string $page): array
```

`text()` interpolates `{year}` → current year. `html()` runs through
`HtmlSanitizer::clean`. Helper memoizes per-request loaded pages so repeated
calls within one render hit cache once.

- [ ] Tests: text returns value; missing key returns fallback; {year} interpolation; html sanitizes scripts; image returns url or null fallback; imageAlt returns alt or fallback; multiple calls only resolve page once (assert via Cache spy or via DB query log if practical, otherwise via direct ContentResolver mock).
- [ ] Run — FAIL.
- [ ] Implement.
- [ ] Tests pass.
- [ ] Commit: `feat(cms): add CmsHelper with type-aware accessors`

---

## Phase 4 — Admin UI

### Task 10: Routes + cache config + .gitignore

**Files:**
- Modify `config/routes.php`: register `Admin` prefix routes for `cms/*`.
- Modify `config/app.php`: register `cms` cache config (file backend, duration `+1 hour`, prefix `cms_`).
- Modify `.gitignore`: ignore `webroot/uploads/site/*` but not `.gitkeep`.
- Create `webroot/uploads/site/.gitkeep`.

- [ ] Routes use connect/connect for: `index`, `view/:slug`, `sections/:id/edit`, `sections/:id/history`, `sections/:id/restore/:revId`, `sections/:id/heartbeat`, `sections/:id/lock-force`, `media`, `media (POST)`, `media/:id (DELETE)`.
- [ ] Smoke: `bin/cake routes | grep cms` shows entries.
- [ ] Commit: `feat(cms): wire routes, cms cache config, uploads dir`

### Task 11: CmsPagesController + templates (read-only paths)

**Files:**
- Create `src/Controller/Admin/CmsPagesController.php` with `index()` and `view($slug)` actions only (read-only first cut).
- Create `templates/Admin/CmsPages/index.php` — page list as `.admin-table` with columns: Page, Sections, Updated, Action(View).
- Create `templates/Admin/CmsPages/view.php` — sections table with type icon, current value preview (truncate 80 chars; image previewed as 40px thumb), lock badge, Edit/History action links.
- Modify `templates/layout/admin.php` — add `Site Content` sidebar nav entry under existing nav with `bi-pencil-square` icon, route to `/admin/cms`.
- Create `webroot/css/cms-admin.css` skeleton + wire into admin layout.

- [ ] Manual smoke: log in as admin, visit `/admin/cms` and `/admin/cms/pages/home`, verify lists render and styles match other admin pages.
- [ ] Commit: `feat(cms): admin landing + per-page sections list`

### Task 12: Section editor (type-aware partials, lock acquire on GET)

**Files:**
- Modify `src/Controller/Admin/CmsPagesController.php`: add `editSection($id)` GET handling lock acquisition + render.
- Create `templates/Admin/CmsPages/edit_section.php` — outer form, lock banner if held by other.
- Create `templates/element/cms/section_input/{text,textarea,html,url,email,image,number}.php`.
- Create `webroot/js/cms-admin.js` skeleton (heartbeat + html toolbar).

- [ ] Each partial renders the correct input matching spec §7.
- [ ] HTML partial: textarea + 5 toolbar buttons (Bold/Italic/Link/UL/OL) wrapping selection.
- [ ] Image partial: thumbnail preview of currently bound media + dropdown to pick existing media + inline file input.
- [ ] Lock banner: shows holder display name + age + actions (Force take / Cancel).
- [ ] Manual smoke: open edit page, banner not shown for self; open in second admin session, banner appears.
- [ ] Commit: `feat(cms): type-aware section editor with lock awareness`

### Task 13: Section save (validate + revision + cache invalidate)

**Files:**
- Modify `src/Controller/Admin/CmsPagesController.php`: add `editSection($id)` POST branch.

- [ ] On POST: ensure lock held by current user (else flash + redirect to view); inside DB transaction call RevisionRecorder::snapshot, patchEntity from request data (image type accepts existing `media_id` OR uploaded file via MediaUploader), validate, save; on success invalidate ContentResolver and release lock; flash + redirect to view page.
- [ ] Errors: validation re-renders form keeping lock; transaction failure flashes error and keeps lock.
- [ ] Manual smoke: change `home` hero title via UI, refresh `/`, see new title.
- [ ] Commit: `feat(cms): persist section edits with revision + cache invalidate`

### Task 14: History + restore + lock force/heartbeat actions

**Files:**
- Modify `src/Controller/Admin/CmsPagesController.php`: add `history($id)`, `restore($id, $revId)`, `heartbeat($id)`, `forceLock($id)` actions.
- Create `templates/Admin/CmsPages/history.php` — chronological revision list with Restore button per row.
- Modify `webroot/js/cms-admin.js` — heartbeat ping every 60s while edit page focused; release on submit/`beforeunload` via `navigator.sendBeacon`.

- [ ] Manual smoke: edit a section, view history, restore an earlier revision, see public site reflect it.
- [ ] Manual smoke: open edit in 2 sessions, click Force take in second, refresh first → its lock banner now shows new holder.
- [ ] Commit: `feat(cms): revision history, restore, lock heartbeat + force-take`

### Task 15: CmsMediaController + library template

**Files:**
- Create `src/Controller/Admin/CmsMediaController.php` (`index`, `upload`, `delete`).
- Create `templates/Admin/CmsMedia/index.php` — grid of media thumbnails + inline upload form (multipart) + delete buttons that confirm and POST.

- [ ] Upload: accepts file, calls MediaUploader, flash + redirect.
- [ ] Delete: refuses (409 + flash) when `page_sections.media_id` references row; otherwise removes file from disk + DB row.
- [ ] Manual smoke: upload a logo, see it in library, set as `branding.logo_image`, verify on public pages.
- [ ] Commit: `feat(cms): media library controller + template`

---

## Phase 5 — Public Integration

### Task 16: Replace hardcoded copy in home.php

**Files:** Modify `templates/Pages/home.php`

Replace per spec §9 — title, hero eyebrow/title, hero background URL, both
CTAs, footer copyright, favicon. Use `h()` for text echoes. Keep original
strings as fallback.

- [ ] Manual smoke: home page visually identical to before.
- [ ] Edit `home.hero.title` in admin → home page reflects change after refresh.
- [ ] Commit: `feat(cms): wire home page through CMS helper`

### Task 17: Replace hardcoded copy in contact.php

**Files:** Modify `templates/Pages/contact.php`

Replace title, intro paragraph, favicon. Hero "Enquiry Form" → CMS.

- [ ] Manual smoke: contact page renders unchanged; CMS edits propagate.
- [ ] Commit: `feat(cms): wire contact page through CMS helper`

### Task 18: Replace hardcoded copy in Courses/index.php

**Files:** Modify `templates/Courses/index.php`

Replace eyebrow, page title heading, Pottery + Knitting card titles + descriptions.

- [ ] Manual smoke: courses page renders unchanged; CMS edits propagate.
- [ ] Commit: `feat(cms): wire courses page through CMS helper`

### Task 19: Replace brand strings in public_nav element

**Files:** Modify `templates/element/public_nav.php`

Replace brand title, brand subtitle, "Enquire" label, "Log In" label.

- [ ] Manual smoke: public nav across all pages shows CMS-driven brand.
- [ ] Commit: `feat(cms): wire public navigation brand through CMS helper`

---

## Phase 6 — Polish, Docs, Verify, Push

### Task 20: Acceptance smoke test (manual via browser)

- [ ] Log in as admin in browser. Walk every checklist item from spec §17.
- [ ] Test mobile viewport: confirm new admin pages still respect mobile.css rules (tabs, headers, tables).
- [ ] Capture before/after of one CMS edit on the home page in conversation.
- [ ] Fix any defects discovered (each defect → its own commit).

### Task 21: Author docs/CMS.md

**Files:** Create `docs/CMS.md`

Cover: what the CMS does, sidebar entry, page list, editing a section,
managing media library, restoring a revision, lock UX, what fallback is
used if a section is missing.

- [ ] Commit: `docs(cms): admin user guide`

### Task 22: Verification & push

Use `superpowers:verification-before-completion`. Then push to gitlab origin
on both `feature/cms-site-content` and `main`.

- [ ] Run full test suite: `vendor/bin/phpunit` — all green.
- [ ] Run `bin/cake migrations status` — all up.
- [ ] Manual smoke (one last walk through home/contact/courses).
- [ ] `git log --oneline feature/cms-site-content` reads clean.
- [ ] `git push -u origin feature/cms-site-content`.
- [ ] `git checkout main && git merge --no-ff feature/cms-site-content && git push origin main`.
- [ ] Confirm both branches present on remote.
