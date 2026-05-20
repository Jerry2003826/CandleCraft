<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\PageSection $section
 * @var \App\Model\Entity\SitePage|null $page
 * @var \App\Service\Cms\LockOutcome $lockOutcome
 * @var iterable<\App\Model\Entity\SiteMedia> $mediaOptions
 */

use App\Service\Cms\LockOutcome;

$this->assign('title', sprintf('Edit · %s', $section->section_label));

$contentType = (string)$section->content_type;
$readonly = $lockOutcome->status === LockOutcome::HELD_BY_OTHER;

$pageSlug = $page?->page_slug ?? ($section->site_page->page_slug ?? '');
$mediaOptions = is_array($mediaOptions) ? $mediaOptions : iterator_to_array($mediaOptions);
?>

<div class="admin-page-header d-flex justify-content-between align-items-start flex-wrap" style="gap:12px;">
    <div>
        <p class="admin-page-subtitle" style="color:var(--admin-text-secondary); margin:0 0 4px;">
            <a href="<?= $this->Url->build(['action' => 'index']) ?>">Site Content</a>
            &rsaquo; <a href="<?= $this->Url->build(['action' => 'view', $pageSlug]) ?>"><?= h($page?->page_title ?? $pageSlug) ?></a>
            &rsaquo; <code><?= h($section->section_key) ?></code>
        </p>
        <?php if (!empty($section->section_hint)): ?>
            <p class="admin-page-hint" style="color:var(--admin-text-secondary); margin:0;"><?= h($section->section_hint) ?></p>
        <?php endif; ?>
    </div>
    <div style="display:flex; gap:8px;">
        <a class="admin-btn-secondary" href="<?= $this->Url->build(['action' => 'history', $section->id]) ?>">
            <i class="bi bi-clock-history"></i> History
        </a>
        <a class="admin-btn-secondary" href="<?= $this->Url->build(['action' => 'view', $pageSlug]) ?>">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php if ($lockOutcome->status === LockOutcome::HELD_BY_OTHER): ?>
    <div class="cms-lock-banner cms-lock-banner--blocked">
        <div>
            <strong>Locked by <?= h($lockOutcome->holderName ?? ('user #' . (int)$lockOutcome->holderId)) ?></strong>
            <span style="color:var(--admin-text-secondary);">since <?= h($lockOutcome->lockedAt?->nice() ?? 'unknown') ?> · expires <?= h($lockOutcome->expiresAt?->nice() ?? 'unknown') ?></span>
        </div>
        <?= $this->Form->postLink(
            __('Force-take editing'),
            '/admin/cms/sections/' . (int)$section->id . '/lock-force',
            [
                'class' => 'admin-btn-danger',
                'confirm' => __('Take editing rights from {0}? Their unsaved work may be lost.', $lockOutcome->holderName ?? ('user #' . (int)$lockOutcome->holderId)),
                'escape' => false,
            ]
        ) ?>
    </div>
<?php elseif ($lockOutcome->status === LockOutcome::ACQUIRED): ?>
    <div class="cms-lock-banner cms-lock-banner--ok">
        <i class="bi bi-pencil-square"></i>
        Editing lock acquired. The lock auto-renews while this tab stays open and releases on save.
    </div>
<?php else: ?>
    <div class="cms-lock-banner cms-lock-banner--ok">
        <i class="bi bi-info-circle"></i>
        You already hold the editing lock for this section.
    </div>
<?php endif; ?>

<div class="admin-form-card">
    <?= $this->Form->create($section, [
        'type' => $contentType === 'image' ? 'file' : 'post',
        'class' => 'cms-edit-form',
    ]) ?>

    <div class="cms-form-row">
        <label class="cms-form-label">Section</label>
        <div class="cms-form-static">
            <strong><?= h($section->section_label) ?></strong>
            <code class="ms-2"><?= h($section->section_key) ?></code>
            <span class="badge bg-light text-dark ms-2"><?= h($contentType) ?></span>
        </div>
    </div>

    <div class="cms-form-row">
        <?php if ($contentType === 'text' || $contentType === 'url' || $contentType === 'email' || $contentType === 'number'): ?>
            <label class="cms-form-label" for="content_value">Value</label>
            <?= $this->Form->control('content_value', [
                'type' => match ($contentType) {
                    'url' => 'url',
                    'email' => 'email',
                    'number' => 'number',
                    default => 'text',
                },
                'label' => false,
                'class' => 'admin-input cms-input',
                'disabled' => $readonly,
                'required' => $contentType !== 'number',
                'value' => $section->content_value,
                'maxlength' => 1024,
            ]) ?>

        <?php elseif ($contentType === 'textarea'): ?>
            <label class="cms-form-label" for="content_value">Value</label>
            <?= $this->Form->control('content_value', [
                'type' => 'textarea',
                'label' => false,
                'class' => 'admin-input cms-textarea',
                'rows' => 6,
                'disabled' => $readonly,
                'value' => $section->content_value,
            ]) ?>

        <?php elseif ($contentType === 'html'): ?>
            <label class="cms-form-label" for="content_value">HTML content</label>
            <div class="cms-html-toolbar">
                <button type="button" data-cms-tag="strong"><b>B</b></button>
                <button type="button" data-cms-tag="em"><i>I</i></button>
                <button type="button" data-cms-tag="u"><u>U</u></button>
                <button type="button" data-cms-tag="ul">• List</button>
                <button type="button" data-cms-tag="a">Link</button>
                <span class="cms-html-help">Allowed tags: &lt;p&gt; &lt;strong&gt; &lt;em&gt; &lt;u&gt; &lt;a&gt; &lt;ul&gt; &lt;ol&gt; &lt;li&gt; &lt;br&gt; &lt;span&gt;. Anything else is stripped on render.</span>
            </div>
            <?= $this->Form->control('content_value', [
                'type' => 'textarea',
                'label' => false,
                'class' => 'admin-input cms-html-textarea',
                'rows' => 10,
                'disabled' => $readonly,
                'value' => $section->content_value,
                'id' => 'cms-html-editor',
            ]) ?>

        <?php elseif ($contentType === 'image'): ?>
            <label class="cms-form-label">Current image</label>
            <div class="cms-image-current">
                <?php if ($section->site_media): ?>
                    <img src="<?= h($section->site_media->file_url) ?>" alt="<?= h($section->site_media->alt_text ?? '') ?>" />
                    <p class="cms-image-meta">
                        <?= h($section->site_media->file_name) ?>
                        · <?= h(number_format($section->site_media->file_size / 1024, 1)) ?> KB
                    </p>
                <?php else: ?>
                    <span style="color:var(--admin-text-secondary);">No image set yet.</span>
                <?php endif; ?>
            </div>

            <label class="cms-form-label" for="uploaded_image">Upload new image</label>
            <?= $this->Form->control('uploaded_image', [
                'type' => 'file',
                'label' => false,
                'class' => 'admin-input',
                'disabled' => $readonly,
                'accept' => 'image/png,image/jpeg,image/webp,image/gif,image/svg+xml',
            ]) ?>
            <p class="cms-form-hint">PNG / JPEG / WEBP / GIF / SVG. Max 5 MB. Upload replaces the current selection.</p>

            <label class="cms-form-label" for="alt_text">Alt text (used by screen readers)</label>
            <?= $this->Form->control('alt_text', [
                'type' => 'text',
                'label' => false,
                'class' => 'admin-input',
                'disabled' => $readonly,
                'value' => $section->site_media->alt_text ?? '',
                'maxlength' => 255,
            ]) ?>

            <?php if (!empty($mediaOptions)): ?>
                <label class="cms-form-label" style="margin-top:16px;">Or pick from library</label>
                <div class="cms-media-grid">
                    <?php foreach ($mediaOptions as $media): ?>
                        <label class="cms-media-tile">
                            <input type="radio" name="media_id" value="<?= (int)$media->id ?>" <?= $section->media_id === $media->id ? 'checked' : '' ?> <?= $readonly ? 'disabled' : '' ?> />
                            <img src="<?= h($media->file_url) ?>" alt="<?= h($media->alt_text ?? $media->file_name) ?>" />
                            <span class="cms-media-name"><?= h($media->file_name) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="cms-form-actions">
        <?= $this->Form->button(__('Save changes'), [
            'type' => 'submit',
            'class' => 'admin-btn-primary',
            'disabled' => $readonly,
        ]) ?>
        <a class="admin-btn-secondary" href="<?= $this->Url->build(['action' => 'view', $pageSlug]) ?>">Cancel</a>
    </div>

    <?= $this->Form->end() ?>
</div>

<?php $this->append('script'); ?>
<script>
(function () {
    const sectionId = <?= (int)$section->id ?>;
    const heartbeatUrl = <?= json_encode('/admin/cms/sections/' . (int)$section->id . '/heartbeat') ?>;
    const csrfToken = <?= json_encode($this->request->getAttribute('csrfToken')) ?>;

    if (csrfToken && heartbeatUrl) {
        setInterval(() => {
            fetch(heartbeatUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': csrfToken,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            }).catch(() => {});
        }, 60_000);
    }

    const editor = document.getElementById('cms-html-editor');
    if (editor) {
        document.querySelectorAll('.cms-html-toolbar button').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const tag = btn.dataset.cmsTag;
                const start = editor.selectionStart;
                const end = editor.selectionEnd;
                const before = editor.value.substring(0, start);
                const selected = editor.value.substring(start, end);
                const after = editor.value.substring(end);
                let snippet;
                if (tag === 'a') {
                    const url = window.prompt('Link URL', 'https://');
                    if (!url) return;
                    snippet = `<a href="${url}">${selected || url}</a>`;
                } else if (tag === 'ul') {
                    snippet = `<ul>\n  <li>${selected || 'Item'}</li>\n</ul>`;
                } else {
                    snippet = `<${tag}>${selected || tag.toUpperCase()}</${tag}>`;
                }
                editor.value = before + snippet + after;
                editor.focus();
                editor.selectionStart = editor.selectionEnd = (before + snippet).length;
            });
        });
    }
})();
</script>
<?php $this->end(); ?>
