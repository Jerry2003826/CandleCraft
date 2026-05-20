<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\SiteMedia> $media
 */
$this->assign('title', 'Media Library');
$media = is_array($media) ? $media : iterator_to_array($media);
?>

<div class="admin-page-header d-flex justify-content-between align-items-start flex-wrap" style="gap:12px;">
    <div>
        <p class="admin-page-subtitle" style="color:var(--admin-text-secondary); margin:0;">
            Reusable images, logos, and icons. Files live under <code>/uploads/site/</code>.
        </p>
    </div>
    <a class="admin-btn-secondary" href="<?= $this->Url->build(['controller' => 'CmsPages', 'action' => 'index']) ?>">
        <i class="bi bi-arrow-left"></i> Back to Site Content
    </a>
</div>

<div class="admin-form-card" style="margin-bottom:18px;">
    <?= $this->Form->create(null, [
        'type' => 'file',
        'url' => '/admin/cms/media',
    ]) ?>

    <div class="cms-form-row">
        <label class="cms-form-label" for="file">Upload new media</label>
        <?= $this->Form->control('file', [
            'type' => 'file',
            'label' => false,
            'class' => 'admin-input',
            'accept' => 'image/png,image/jpeg,image/webp,image/gif,image/svg+xml',
            'required' => true,
        ]) ?>
        <p class="cms-form-hint">PNG / JPEG / WEBP / GIF / SVG. Max 5 MB. Files are renamed with a hash for safety.</p>
    </div>

    <div class="cms-form-row">
        <label class="cms-form-label" for="alt_text">Alt text (optional, helps accessibility)</label>
        <?= $this->Form->control('alt_text', [
            'type' => 'text',
            'label' => false,
            'class' => 'admin-input',
            'maxlength' => 255,
        ]) ?>
    </div>

    <div class="cms-form-actions">
        <?= $this->Form->button(__('Upload'), [
            'type' => 'submit',
            'class' => 'admin-btn-primary',
        ]) ?>
    </div>

    <?= $this->Form->end() ?>
</div>

<?php if (empty($media)): ?>
    <div class="admin-empty-state">
        <i class="bi bi-image"></i>
        <p>No media yet. Upload your first image above to get started.</p>
    </div>
<?php else: ?>
    <div class="cms-media-grid">
        <?php foreach ($media as $item): ?>
            <div class="cms-media-card">
                <div class="cms-media-card__thumb">
                    <img src="<?= h($item->file_url) ?>" alt="<?= h($item->alt_text ?? $item->file_name) ?>" />
                </div>
                <div class="cms-media-card__body">
                    <strong style="color:var(--admin-text-primary);"><?= h($item->file_name) ?></strong>
                    <span><?= h(number_format($item->file_size / 1024, 1)) ?> KB · <?= h($item->mime_type) ?></span>
                    <?php if (!empty($item->alt_text)): ?>
                        <span style="font-style:italic;">Alt: <?= h($item->alt_text) ?></span>
                    <?php endif; ?>
                    <?= $this->Form->postLink(
                        '<i class="bi bi-trash"></i> Delete',
                        '/admin/cms/media/' . (int)$item->id . '/delete',
                        [
                            'class' => 'admin-btn-secondary',
                            'confirm' => __('Delete this media file? Sections that reference it will block the delete.'),
                            'escape' => false,
                            'style' => 'margin-top:6px; align-self:flex-start;',
                        ]
                    ) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
