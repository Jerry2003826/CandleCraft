<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SitePage $page
 * @var iterable<\App\Model\Entity\PageSection> $sections
 * @var \Cake\I18n\DateTime $now
 */
$this->assign('title', $page->page_title);

$sectionList = is_array($sections) ? $sections : iterator_to_array($sections);

$typeIcon = static function (string $type): string {
    return match ($type) {
        'text', 'textarea' => 'bi-type',
        'html'            => 'bi-code-slash',
        'image'           => 'bi-image',
        'url'             => 'bi-link-45deg',
        'email'           => 'bi-envelope',
        'number'          => 'bi-123',
        default           => 'bi-card-text',
    };
};

$truncate = static function (?string $value, int $max = 80): string {
    $value = (string)$value;
    if ($value === '') {
        return '—';
    }
    $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
    if (function_exists('mb_strlen') && mb_strlen($value) > $max) {
        return mb_substr($value, 0, $max) . '…';
    }
    if (strlen($value) > $max) {
        return substr($value, 0, $max) . '…';
    }
    return $value;
};
?>

<div class="admin-page-header d-flex justify-content-between align-items-start flex-wrap" style="gap:12px;">
    <div>
        <p class="admin-page-subtitle" style="color: var(--admin-text-secondary); margin:0 0 4px;">
            Page URL: <code><?= h('/' . ltrim($page->page_slug, '/')) ?></code> · <?= count($sectionList) ?> sections
        </p>
    </div>
    <?= $this->element('admin_back_link', ['url' => $this->Url->build(['action' => 'index']), 'label' => 'Back to Pages']) ?>
</div>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Type</th>
                    <th>Current value</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sectionList as $section): ?>
                    <?php $lock = $section->page_section_lock ?? null; ?>
                    <?php $lockActive = $lock !== null && $lock->expires_at !== null && $lock->expires_at->greaterThan($now); ?>
                    <tr>
                        <td>
                            <p class="admin-table-primary-text"><?= h($section->section_label) ?></p>
                            <p class="admin-table-secondary-text"><code><?= h($section->section_key) ?></code></p>
                            <?php if (!empty($section->section_hint)): ?>
                                <p class="admin-table-secondary-text" style="font-style:italic;"><?= h($section->section_hint) ?></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark"><i class="bi <?= h($typeIcon($section->content_type)) ?>"></i> <?= h($section->content_type) ?></span>
                        </td>
                        <td>
                            <?php if ($section->content_type === 'image'): ?>
                                <?php if ($section->site_media): ?>
                                    <img src="<?= h($section->site_media->file_url) ?>" alt="<?= h($section->site_media->alt_text ?? '') ?>" style="max-height:40px; max-width:120px; border:1px solid var(--admin-card-border); border-radius:6px;">
                                <?php else: ?>
                                    <span style="color:var(--admin-text-secondary);">— not set —</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="admin-table-primary-text"><?= h($truncate($section->content_value)) ?></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$section->is_active): ?>
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                            <?php elseif ($lockActive): ?>
                                <span class="badge bg-warning-subtle text-warning" title="Locked by user #<?= (int)$lock->locked_by_id ?>">
                                    <i class="bi bi-lock-fill"></i> Editing
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success">Editable</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end" style="white-space:nowrap;">
                            <a class="admin-btn-secondary" href="<?= $this->Url->build(['action' => 'editSection', $section->id]) ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a class="admin-btn-secondary" href="<?= $this->Url->build(['action' => 'history', $section->id]) ?>">
                                <i class="bi bi-clock-history"></i> History
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
