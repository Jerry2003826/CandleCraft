# Site Content Management (CMS)

Administrators can edit titles, taglines, copy, images, and other reusable content on the public website **without changing the codebase**. This module powers that workflow.

## How it works

1. The website's reusable content is stored in five database tables (`site_pages`, `page_sections`, `site_media`, `page_section_revisions`, `page_section_locks`).
2. Public templates pull copy through the `Cms` view helper, e.g. `<?= h($this->Cms->text('home', 'hero.title', 'Default Title')) ?>`.
3. When a section is updated, the matching page's cache entry is invalidated automatically — the next public visitor sees the new value within seconds.

## Accessing the editor

1. Sign in as an admin user.
2. In the left sidebar, click **Site Content**.
3. The page list shows the four CMS pages currently provisioned: **Global / Brand**, **Home Page**, **Contact / Enquiry**, **Courses Listing**.
4. Click **Edit sections** to view all editable fragments for a page.

## Editing a section

1. From a page's section list, click **Edit** next to a row.
2. The editor:
    - Acquires a soft **lock** so two admins cannot overwrite each other.
    - Shows a green banner when the lock is yours, or a red banner if someone else is currently editing.
    - For HTML sections, exposes a small toolbar (bold/italic/link/list) and the safelist of allowed tags.
    - For images, shows the current preview, a file uploader, an alt-text field, and a "Pick from library" grid.
3. Make your changes and click **Save changes**.
4. Each save automatically:
    - Snapshots the previous value into `page_section_revisions` ("Manual edit" entry by default).
    - Updates the section.
    - Invalidates the relevant page cache so visitors see the change immediately.

## Concurrency / lock guidance

- A lock lives 5 minutes and **auto-renews every minute** while the editor tab is open.
- A lock is released automatically when you save.
- When you visit a section that someone else is editing, you can **Force-take editing** from the red banner. The previous holder's unsaved work is **lost**, and an audit row is recorded.

## Revision history

1. From the section list, click **History**.
2. Each save creates a row with: who, when, change summary, and the previous content snapshot.
3. Click **Restore this version** to roll back. The current value is also snapshotted before restore (the timeline is append-only — you never lose data).

## Media library

1. From the page list, click **Media Library** in the breadcrumb (or via `/admin/cms/media`).
2. Upload PNG / JPEG / WEBP / GIF / SVG up to **5 MB**.
3. Files are saved to `webroot/uploads/site/{year}/{month}/{hash}.{ext}` and are served from `/uploads/site/...`.
4. Provide **alt text** for accessibility — it propagates to all sections referencing this image.
5. Delete is blocked when any active section still references the file (prevents orphan images).

## Adding new editable content (developer task)

1. Add a row to `site_pages` if you need a new page (or reuse an existing one).
2. Add `page_sections` rows with a unique `section_key`, a human label, content type (`text`, `textarea`, `html`, `url`, `email`, `number`, `image`), and the default value.
3. In your `templates/...` file, render with the helper:
    - `$this->Cms->text($pageSlug, $sectionKey, $fallback)` — text/url/email/number, with `{year}` interpolation.
    - `$this->Cms->html($pageSlug, $sectionKey, $fallback)` — sanitized HTML.
    - `$this->Cms->image($pageSlug, $sectionKey)` — returns the `file_url` of the bound media or `null`.
    - `$this->Cms->imageAlt($pageSlug, $sectionKey, $fallback)` — alt text for accessibility.
4. The fallback is shown if the section is missing or empty, so the public site never breaks while content is being prepared.

## Security & validation

- HTML values are stripped against a strict whitelist (`p, br, strong, em, b, i, u, a, ul, ol, li, span`) and links are forced to `rel="nofollow noopener"`.
- File uploads are validated by **mime type, file size, and magic byte signature** before being persisted.
- All admin write actions require an authenticated admin and a CSRF token (handled by CakePHP automatically).
- Foreign keys cascade on user delete so deleting a user does not orphan locks/revisions.

## Cache configuration

- Defined in `config/app.php` under `Cache.cms`. Default engine is `FileEngine` with a 1-hour duration. Switch to Redis/APCu in production for multi-server deployments.

## File map (developer reference)

| Concern | Path |
|---------|------|
| Migrations | `config/Migrations/20260511020000_CreateCmsTables.php` and `…020100_SeedDefaultCmsContent.php` |
| ORM models | `src/Model/Table/{SitePages,PageSections,SiteMedia,PageSectionRevisions,PageSectionLocks}Table.php` |
| ORM entities | `src/Model/Entity/{SitePage,PageSection,SiteMedia,PageSectionRevision,PageSectionLock}.php` |
| Services | `src/Service/Cms/{HtmlSanitizer,ContentResolver,SectionLockService,RevisionRecorder,MediaUploader,LockOutcome,MediaUploadException}.php` |
| Helper | `src/View/Helper/CmsHelper.php` |
| Controllers | `src/Controller/Admin/{CmsPagesController,CmsMediaController}.php` |
| Admin templates | `templates/Admin/CmsPages/*.php`, `templates/Admin/CmsMedia/index.php` |
| Admin styles | `webroot/css/cms-admin.css` |
| Tests | `tests/TestCase/Service/Cms/*.php`, `tests/TestCase/View/Helper/CmsHelperTest.php` |
