"""Generate the English version of the CMS Solution 3 design document.

Run from the repository root:
    python3 scripts/build_cms_solution3_docx_en.py

Output:
    ~/Desktop/CMS-Solution3-Design.docx
"""

from __future__ import annotations

import os
from pathlib import Path
from typing import Iterable

from docx import Document
from docx.enum.table import WD_ALIGN_VERTICAL, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor

OUTPUT_PATH = Path(os.path.expanduser("~/Desktop")) / "CMS-Solution3-Design.docx"

EN_BODY_FONT = "Calibri"
EN_HEAD_FONT = "Calibri"
MONO_FONT = "Consolas"


def _set_run_font(run, *, name: str = EN_BODY_FONT) -> None:
    run.font.name = name
    rpr = run._element.get_or_add_rPr()
    rfonts = rpr.find(qn("w:rFonts"))
    if rfonts is None:
        rfonts = rpr.makeelement(qn("w:rFonts"), {})
        rpr.append(rfonts)
    rfonts.set(qn("w:ascii"), name)
    rfonts.set(qn("w:hAnsi"), name)
    rfonts.set(qn("w:eastAsia"), name)


def _add_paragraph(
    doc: Document,
    text: str,
    *,
    style: str | None = None,
    bold: bool = False,
    size_pt: float | None = None,
    color: tuple[int, int, int] | None = None,
    align: int | None = None,
    space_after_pt: float | None = None,
    font_name: str = EN_BODY_FONT,
) -> None:
    para = doc.add_paragraph(style=style) if style else doc.add_paragraph()
    if align is not None:
        para.alignment = align
    if space_after_pt is not None:
        para.paragraph_format.space_after = Pt(space_after_pt)
    run = para.add_run(text)
    run.bold = bold
    if size_pt is not None:
        run.font.size = Pt(size_pt)
    if color is not None:
        run.font.color.rgb = RGBColor(*color)
    _set_run_font(run, name=font_name)


def _add_bullets(doc: Document, items: Iterable[str]) -> None:
    for item in items:
        para = doc.add_paragraph(style="List Bullet")
        run = para.add_run(item)
        _set_run_font(run)


def _add_numbered(doc: Document, items: Iterable[str]) -> None:
    for item in items:
        para = doc.add_paragraph(style="List Number")
        run = para.add_run(item)
        _set_run_font(run)


def _add_heading(doc: Document, text: str, level: int) -> None:
    heading = doc.add_heading(level=level)
    run = heading.add_run(text)
    run.bold = True
    _set_run_font(run, name=EN_HEAD_FONT)


def _set_table_cell_borders(cell) -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    tc_borders = tc_pr.find(qn("w:tcBorders"))
    if tc_borders is None:
        tc_borders = tc_pr.makeelement(qn("w:tcBorders"), {})
        tc_pr.append(tc_borders)
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        border = tc_borders.find(qn(f"w:{edge}"))
        if border is None:
            border = tc_borders.makeelement(qn(f"w:{edge}"), {})
            tc_borders.append(border)
        border.set(qn("w:val"), "single")
        border.set(qn("w:sz"), "6")
        border.set(qn("w:color"), "999999")


def _add_table(
    doc: Document,
    header: list[str],
    rows: list[list[str]],
    *,
    col_widths_cm: list[float] | None = None,
) -> None:
    table = doc.add_table(rows=1 + len(rows), cols=len(header))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False

    if col_widths_cm:
        for col_idx, width in enumerate(col_widths_cm):
            for row in table.rows:
                row.cells[col_idx].width = Cm(width)

    for col_idx, label in enumerate(header):
        cell = table.rows[0].cells[col_idx]
        cell.vertical_alignment = WD_ALIGN_VERTICAL.CENTER
        cell.paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
        run = cell.paragraphs[0].add_run(label)
        run.bold = True
        run.font.size = Pt(10.5)
        run.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
        _set_run_font(run)
        tc_pr = cell._tc.get_or_add_tcPr()
        shd = tc_pr.find(qn("w:shd"))
        if shd is None:
            shd = tc_pr.makeelement(qn("w:shd"), {})
            tc_pr.append(shd)
        shd.set(qn("w:val"), "clear")
        shd.set(qn("w:color"), "auto")
        shd.set(qn("w:fill"), "2F5597")
        _set_table_cell_borders(cell)

    for row_idx, row in enumerate(rows, start=1):
        for col_idx, value in enumerate(row):
            cell = table.rows[row_idx].cells[col_idx]
            cell.vertical_alignment = WD_ALIGN_VERTICAL.CENTER
            run = cell.paragraphs[0].add_run(value)
            run.font.size = Pt(10)
            _set_run_font(run)
            _set_table_cell_borders(cell)


def build() -> None:
    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    doc = Document()

    section = doc.sections[0]
    section.top_margin = Cm(2.2)
    section.bottom_margin = Cm(2.2)
    section.left_margin = Cm(2.4)
    section.right_margin = Cm(2.4)

    style = doc.styles["Normal"]
    style.font.name = EN_BODY_FONT
    style.font.size = Pt(11)

    # ----------------- Cover -----------------
    _add_paragraph(
        doc,
        "CakePHP Self-Hosted CMS",
        bold=True,
        size_pt=26,
        color=(0x1F, 0x3A, 0x5F),
        align=WD_ALIGN_PARAGRAPH.CENTER,
        space_after_pt=4,
    )
    _add_paragraph(
        doc,
        "Solution 3: Pages + Typed Sections + Media Library + Revisions + Collaborative Locks",
        bold=True,
        size_pt=13,
        color=(0x2F, 0x55, 0x97),
        align=WD_ALIGN_PARAGRAPH.CENTER,
        space_after_pt=24,
    )
    _add_paragraph(
        doc,
        "Designed for v2 iteration — administrators maintain copy, logos, and images "
        "with zero code changes.",
        size_pt=11,
        color=(0x55, 0x55, 0x55),
        align=WD_ALIGN_PARAGRAPH.CENTER,
        space_after_pt=2,
    )
    _add_paragraph(
        doc,
        "Repository: cakephp-app  ·  Document v1.0  ·  Issued 2026-05-11",
        size_pt=10,
        color=(0x88, 0x88, 0x88),
        align=WD_ALIGN_PARAGRAPH.CENTER,
        space_after_pt=18,
    )

    # ----------------- 1. One-line positioning -----------------
    _add_heading(doc, "1. One-Line Positioning", level=1)
    _add_paragraph(
        doc,
        "Solution 3 lifts every editable piece of website content (titles, paragraphs, "
        "images, logos) out of the template code, stores it in a small set of structured "
        "database tables, and re-attaches it to pages through a thin service layer and a "
        "view helper. The result: administrators can change copy from the admin console "
        "with zero code edits, and developers only ever write one line in templates: "
        "$this->Cms->text(...).",
    )

    # ----------------- 2. Why Solution 3 -----------------
    _add_heading(doc, "2. Why Solution 3 (vs. Solution 1 / Solution 2)", level=1)
    _add_paragraph(
        doc,
        "Three approaches were considered during design. Solution 3 won for the reasons "
        "shown below.",
    )

    _add_table(
        doc,
        header=["Solution", "Approach", "Strengths", "Why we did NOT pick it"],
        rows=[
            [
                "Solution 1: Key-Value",
                "A single site_settings(key, value) table holds everything",
                "Fastest to build, lowest migration cost",
                "No types, no versioning, no media handling; HTML and plain text get "
                "mixed; key collisions appear as the site grows; no audit/rollback path",
            ],
            [
                "Solution 2: Per-page JSON",
                "One JSON blob per page, deserialized in templates",
                "Flexible structure, single-page edits feel natural",
                "Fields lack constraints; concurrent editors collide; images still need a "
                "separate pipeline; rollback is page-level only",
            ],
            [
                "Solution 3: Layered + Typed",
                "site_pages -> page_sections (typed) -> site_media, plus "
                "page_section_revisions and page_section_locks",
                "Typed, validatable, cacheable, rollback-able, collaboration-aware; "
                "maps 1:1 to CakePHP ORM",
                "— (chosen)",
            ],
        ],
        col_widths_cm=[3.2, 4.2, 4.4, 4.6],
    )

    _add_paragraph(
        doc,
        "Conclusion: this project already has 6+ public templates (home, contact, "
        "courses, etc.) and will keep adding pages (course detail, events). Solutions 1 "
        "and 2 hit a wall on multi-user editing, rich text safety, image assets, and "
        "history rollback. Solution 3 introduces 5 tables, but each table has a sharp, "
        "single responsibility — the long-term cost is the lowest of the three.",
    )

    # ----------------- 3. Five-table data model -----------------
    _add_heading(doc, "3. Five-Table Data Model", level=1)
    _add_paragraph(doc, "The model has two main lines plus three supporting tables:")
    _add_bullets(
        doc,
        [
            "Content line: site_pages -> page_sections -> site_media (a section can point "
            "at one image)",
            "Governance line: page_sections -> page_section_revisions (a snapshot per "
            "save, append-only)",
            "Supporting table: page_section_locks (who is editing what right now)",
        ],
    )

    _add_heading(doc, "3.1 site_pages — page registry", level=2)
    _add_paragraph(
        doc,
        "Treat 'page' as a first-class entity so the admin UI can group/permission/cache "
        "around it cleanly.",
    )
    _add_table(
        doc,
        header=["Column", "Type", "Purpose"],
        rows=[
            ["id", "BIGINT UNSIGNED PK", "Primary key"],
            ["page_key", "VARCHAR(64) UNIQUE", "Stable identifier, e.g. home / contact / global"],
            ["title", "VARCHAR(255)", "Display name in the admin UI"],
            ["description", "TEXT", "Internal help text for editors; never rendered publicly"],
            ["is_active", "TINYINT(1)", "Reserved for future grey-launch / takedown"],
            ["created / modified", "DATETIME", "Audit timestamps"],
        ],
        col_widths_cm=[3.2, 4.4, 8.4],
    )

    _add_heading(doc, "3.2 page_sections — typed content blocks", level=2)
    _add_paragraph(
        doc,
        "The heart of the CMS. Every editable string corresponds to one row, with "
        "content_type telling the front-end whether it is plain text, sanitized HTML, "
        "or an image reference.",
    )
    _add_table(
        doc,
        header=["Column", "Type", "Purpose"],
        rows=[
            ["id", "BIGINT UNSIGNED PK", "Primary key"],
            ["site_page_id", "BIGINT UNSIGNED FK", "Which page this section belongs to"],
            ["section_key", "VARCHAR(64)", "Stable key inside the page, e.g. hero_title"],
            ["label", "VARCHAR(255)", "Admin-facing display name (e.g. 'Hero headline')"],
            [
                "content_type",
                "ENUM('text','html','image')",
                "Drives the input control AND the sanitization policy",
            ],
            [
                "content_value",
                "LONGTEXT",
                "Holds text/html content; empty for image rows",
            ],
            ["media_id", "BIGINT UNSIGNED FK NULL", "References site_media when type = image"],
            ["display_order", "INT", "Ordering inside the admin page detail view"],
            ["is_active", "TINYINT(1)", "Soft-disable a section; the public side falls back"],
            [
                "UNIQUE(site_page_id, section_key)",
                "—",
                "Guarantees uniqueness of section_key per page",
            ],
        ],
        col_widths_cm=[4.4, 3.6, 8.0],
    )

    _add_heading(doc, "3.3 site_media — media library", level=2)
    _add_paragraph(
        doc,
        "Putting images in their own table buys: upload once, reference many; full audit "
        "trail; one-shot replacement. Swap the logo and every page picks it up "
        "immediately.",
    )
    _add_table(
        doc,
        header=["Column", "Purpose"],
        rows=[
            ["id / file_name / file_size / mime_type / width / height", "Basic metadata"],
            [
                "storage_path",
                "Relative on-disk path under webroot/uploads/site/<hash>.<ext>",
            ],
            [
                "alt_text",
                "Accessibility — used directly as the <img alt=\"\"> on the public site",
            ],
            ["uploaded_by_id", "Audit: who uploaded this asset"],
        ],
        col_widths_cm=[6.0, 10.0],
    )

    _add_heading(doc, "3.4 page_section_revisions — revision history", level=2)
    _add_paragraph(
        doc,
        "Append-only. Every save writes a row containing content_value_snapshot and "
        "media_id_snapshot. This is what enables: viewing change history, comparing "
        "versions, one-click rollback, and accountability.",
    )

    _add_heading(doc, "3.5 page_section_locks — collaborative locking", level=2)
    _add_paragraph(
        doc,
        "Soft locking — no DB row locks. Opening the editor INSERTs a row with "
        "expires_at; the front-end sends a heartbeat every 60 seconds to extend the TTL; "
        "leaving the page DELETEs it. If another editor arrives while the lock is alive, "
        "they see 'XXX is editing this section' and may choose to force-take, which "
        "writes an audit entry.",
    )

    # ----------------- 4. Service layer -----------------
    _add_heading(doc, "4. Service Layer (src/Service/Cms/)", level=1)
    _add_paragraph(
        doc,
        "All business rules are pulled out of controllers and templates into five "
        "single-responsibility PHP services. Controllers handle protocol (HTTP / forms / "
        "redirects), templates handle rendering, and the rules live here.",
    )
    _add_table(
        doc,
        header=["Service", "Responsibility"],
        rows=[
            [
                "HtmlSanitizer",
                "Whitelist HTML cleaning. Sanitization runs at RENDER time, not at save "
                "time, so re-editing never lossy-converts content. preg_replace strips "
                "script/style/iframe/object/embed first, then strip_tags + DOMDocument "
                "applies the whitelist",
            ],
            [
                "ContentResolver",
                "Loads sections + media for a given page_key in one go and caches the "
                "bundle in the FileEngine cache config named 'cms'. Any section write "
                "invalidates that page's bundle precisely",
            ],
            [
                "SectionLockService",
                "acquire / heartbeat / release / forceTake; TTL is config-driven; returns "
                "a LockOutcome value object that tells callers whether they got the lock, "
                "were blocked, or successfully force-took it",
            ],
            [
                "RevisionRecorder",
                "Snapshots the current value before every save and writes a revision row. "
                "restore(revisionId) re-applies an old value to page_sections AND writes "
                "a fresh revision so the audit chain stays unbroken",
            ],
            [
                "MediaUploader",
                "Three-layer upload validation: extension + MIME + magic bytes; hashed "
                "file names land under webroot/uploads/site/; failures throw "
                "MediaUploadException",
            ],
        ],
        col_widths_cm=[3.6, 12.4],
    )

    # ----------------- 5. CmsHelper -----------------
    _add_heading(doc, "5. CmsHelper — One-Line Adoption in Templates", level=1)
    _add_paragraph(
        doc,
        "The entire developer-facing surface is just four methods, so the cognitive cost "
        "is essentially zero:",
    )
    _add_bullets(
        doc,
        [
            "$this->Cms->text('home', 'hero_title', 'Welcome')           — plain text",
            "$this->Cms->html('home', 'hero_body',  '<p>Default</p>')    — sanitized HTML",
            "$this->Cms->image('global', 'logo', '/img/logo.png')        — returns image URL",
            "$this->Cms->imageAlt('global', 'logo', 'Logo')              — matching alt text",
        ],
    )
    _add_paragraph(doc, "Three governing design rules:")
    _add_numbered(
        doc,
        [
            "A fallback ALWAYS exists. If the CMS has no row, the cache misses, or the DB "
            "throws, rendering falls back to the template-supplied default — the page "
            "never goes blank. This makes 'first time wiring up the CMS' equivalent to "
            "swapping a hardcoded string for a method call: there is no failure mode that "
            "blanks the page.",
            "Dynamic placeholders such as {year}: writing '© {year} CandleCraft' in a "
            "template produces the current year at render time, so admins never need to "
            "edit the copyright line each January.",
            "Per-request memoization. A given page_key is fetched once per HTTP request; "
            "every section on the page shares the same bundle, costing zero extra SQL.",
        ],
    )

    # ----------------- 6. Admin UX -----------------
    _add_heading(doc, "6. Admin Interface and Collaboration UX", level=1)
    _add_paragraph(
        doc,
        "Entry point: /admin/cms — gated by both Authentication and Authorization "
        "middleware; only the admin role can reach it.",
    )

    _add_heading(doc, "6.1 Three-level navigation", level=2)
    _add_bullets(
        doc,
        [
            "Page list: /admin/cms — lists every site_pages row",
            "Page detail: /admin/cms/pages/{page_key} — sections sorted by display_order",
            "Section editor: /admin/cms/pages/{page_key}/sections/{section_key}/edit",
        ],
    )

    _add_heading(doc, "6.2 Type-aware input controls", level=2)
    _add_bullets(
        doc,
        [
            "text  -> single-line input or multi-line textarea (chosen by length hint)",
            "html  -> textarea + whitelist help; saved verbatim, sanitized at render",
            "image -> current preview + 'pick from media library' + 'upload new image'",
        ],
    )

    _add_heading(doc, "6.3 Locking UX, end to end", level=2)
    _add_numbered(
        doc,
        [
            "Open the editor: try acquire. On success, edit normally. On failure, show "
            "'XXX is editing (X minutes remaining)' with a 'force-take' button.",
            "While editing: the front-end sends a heartbeat every 60 seconds to extend "
            "the TTL to 5 minutes.",
            "On save or navigate-away: call release immediately to free the lock.",
            "Force-take: forceTake reassigns the lock; the previous holder is told 'your "
            "lock has been taken' on their next heartbeat / save attempt, preventing "
            "overwrite-loss.",
            "Every acquire and forceTake is recorded in page_section_locks plus the audit "
            "log, so any incident is traceable.",
        ],
    )

    _add_heading(doc, "6.4 Revision history and rollback", level=2)
    _add_bullets(
        doc,
        [
            "Each section has a 'History' link that lists all revisions (timestamp + "
            "actor + content type)",
            "A revision can be previewed before restoring",
            "Restore does not delete the newer rows — it writes the old value as a NEW "
            "revision, so the audit chain stays intact",
        ],
    )

    _add_heading(doc, "6.5 Media library", level=2)
    _add_bullets(
        doc,
        [
            "/admin/cms/media — grid view of every site_media row, searchable by name",
            "Uploads land on disk with a hashed file name to avoid collision",
            "Replacement is a first-class action: an image-typed section can swap the "
            "underlying media in place and the public site picks it up immediately",
        ],
    )

    # ----------------- 7. Template migration -----------------
    _add_heading(doc, "7. Public Template Migration Strategy", level=1)
    _add_paragraph(
        doc,
        "Migrate progressively, stay backward-compatible. Every hardcoded string is "
        "replaced with a CmsHelper call that keeps the original literal as a fallback, "
        "so 'wiring a template to the CMS' never breaks the page even if the CMS row is "
        "missing.",
    )
    _add_paragraph(doc, "Migration example (templates/Pages/home.php):")
    _add_paragraph(
        doc,
        "Before:",
        bold=True,
        size_pt=10,
        color=(0x55, 0x55, 0x55),
    )
    code = doc.add_paragraph()
    code.paragraph_format.left_indent = Cm(0.6)
    run = code.add_run("<h1>Welcome to CandleCraft</h1>")
    run.font.name = MONO_FONT
    run.font.size = Pt(10)
    run.font.color.rgb = RGBColor(0x33, 0x33, 0x33)

    _add_paragraph(
        doc,
        "After:",
        bold=True,
        size_pt=10,
        color=(0x55, 0x55, 0x55),
    )
    code = doc.add_paragraph()
    code.paragraph_format.left_indent = Cm(0.6)
    run = code.add_run(
        "<h1><?= h($this->Cms->text('home', 'hero_title', 'Welcome to CandleCraft')) ?></h1>"
    )
    run.font.name = MONO_FONT
    run.font.size = Pt(10)
    run.font.color.rgb = RGBColor(0x33, 0x33, 0x33)

    _add_paragraph(
        doc,
        "Templates already migrated: home.php, contact.php, courses/index.php, and "
        "element/public_nav.php (logo + brand name + navigation menu copy) — covering "
        "every high-frequency surface that admins actually edit.",
    )

    # ----------------- 8. Security / cache / perf -----------------
    _add_heading(doc, "8. Security, Caching, and Performance", level=1)
    _add_table(
        doc,
        header=["Concern", "Mechanism"],
        rows=[
            [
                "Authorization",
                "/admin/cms is gated by the admin middleware AND an Authorization "
                "policy; non-admins see HTTP 403",
            ],
            [
                "CSRF / form tokens",
                "Reuses CakePHP's default middleware; every write requires a valid token",
            ],
            [
                "HTML injection",
                "Store raw + sanitize on render. preg_replace strips "
                "script/style/iframe first, then DOMDocument enforces the whitelist",
            ],
            [
                "Upload safety",
                "MediaUploader does extension + MIME + magic-bytes validation; oversize "
                "files are rejected; hashed names prevent overwrite",
            ],
            [
                "Caching",
                "FileEngine cache config 'cms' holds full per-page bundles; any section "
                "write surgically invalidates that page's bundle",
            ],
            [
                "Performance",
                "Per-request memoization plus per-page caching means the steady state is "
                "zero database queries during render; many sections share one I/O",
            ],
            [
                "Observability",
                "All lock acquire / forceTake events are audited; revisions record every "
                "content change; uploads record uploaded_by_id",
            ],
        ],
        col_widths_cm=[3.4, 12.6],
    )

    # ----------------- 9. TDD -----------------
    _add_heading(doc, "9. TDD and Test Coverage", level=1)
    _add_paragraph(
        doc,
        "The CMS was built strictly TDD — 22 tasks across 6 phases, each starting from a "
        "failing test. Coverage breakdown:",
    )
    _add_bullets(
        doc,
        [
            "Unit: success / failure / boundary cases for each of the 5 services",
            "View Helper: fallback paths, sanitization, {year} interpolation, cache hit / "
            "miss / stale",
            "Controller integration: login, CSRF, authorization, form submit, upload, "
            "rollback, force-take",
            "Fixtures: all 5 tables seeded; cache state is explicitly dropped between "
            "test cases (Cache::drop('cms'))",
        ],
    )
    _add_paragraph(
        doc,
        "Full regression: 327 tests / 0 failures / 0 errors (excluding 6 pre-existing "
        "incomplete tests in unrelated modules). The test suite is both a quality "
        "guardrail and living documentation of how every service is supposed to behave.",
    )

    # ----------------- 10. Future extensions -----------------
    _add_heading(doc, "10. Future Extension Points", level=1)
    _add_bullets(
        doc,
        [
            "Draft / publish workflow: add a status column to page_sections plus a "
            "publish action — the data model already leaves room for it",
            "Localization: add a locale column and switch the unique index to "
            "(site_page_id, section_key, locale)",
            "More content types: extend the ENUM with link / video / json and add a "
            "matching CmsHelper method",
            "WYSIWYG inline editing: replace the admin form with inline controls; the "
            "back-end APIs stay unchanged",
            "Import / export: page_sections + revisions + media are all structured, so "
            "moving a whole page between environments is a packaging exercise",
        ],
    )

    # ----------------- 11. Why this fits -----------------
    _add_heading(doc, "11. Why This Solution Fits This Project Best", level=1)
    _add_numbered(
        doc,
        [
            "Native to CakePHP: 5 tables map 1:1 to 5 Table/Entity classes; nothing new "
            "for developers to learn",
            "Progressive adoption: template migration is fallback-driven, so it can be "
            "rolled out section by section without ever breaking the public site",
            "Friction-free for admins: the back-office groups by page, by section, with "
            "type-aware controls — the mental model matches WordPress / Lark Docs that "
            "they already use daily",
            "One-line adoption for developers: $this->Cms->text(...) is the entire "
            "surface; the marginal cost of adding a new editable section is essentially "
            "zero",
            "Operationally clean: cache can be cleared, versions can be rolled back, "
            "uploads can be audited, locks can be force-taken — every 'what if it goes "
            "wrong?' has a built-in answer",
            "Test-complete: every service, helper, and critical path has tests, so future "
            "refactors can move with confidence",
        ],
    )

    doc.save(OUTPUT_PATH)
    print(f"Wrote {OUTPUT_PATH}")


if __name__ == "__main__":
    build()
