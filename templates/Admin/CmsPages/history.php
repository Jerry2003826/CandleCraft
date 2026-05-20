<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\PageSection $section
 * @var iterable<\App\Model\Entity\PageSectionRevision> $revisions
 */
$this->assign('title', sprintf('History · %s', $section->section_label));
$revisions = is_array($revisions) ? $revisions : iterator_to_array($revisions);
$pageSlug = $section->site_page->page_slug ?? '';

$truncate = static function (?string $value, int $max = 160): string {
    $value = (string)$value;
    if ($value === '') {
        return '—';
    }
    $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
    return mb_strlen($value) > $max ? mb_substr($value, 0, $max) . '…' : $value;
};
?>

<div class="admin-page-header d-flex justify-content-between align-items-start flex-wrap" style="gap:12px;">
    <div>
        <p class="admin-page-subtitle" style="color:var(--admin-text-secondary); margin:0 0 4px;">
            <a href="<?= $this->Url->build(['action' => 'index']) ?>">Site Content</a>
            &rsaquo; <a href="<?= $this->Url->build(['action' => 'view', $pageSlug]) ?>"><?= h($section->site_page->page_title ?? $pageSlug) ?></a>
            &rsaquo; <code><?= h($section->section_key) ?></code>
            &rsaquo; History
        </p>
    </div>
    <div style="display:flex; gap:8px;">
        <a class="admin-btn-secondary" href="<?= $this->Url->build(['action' => 'editSection', $section->id]) ?>">
            <i class="bi bi-pencil"></i> Edit current
        </a>
        <a class="admin-btn-secondary" href="<?= $this->Url->build(['action' => 'view', $pageSlug]) ?>">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php if (empty($revisions)): ?>
    <div class="admin-empty-state">
        <i class="bi bi-clock-history"></i>
        <p>No revisions yet. The next save will create the first snapshot.</p>
    </div>
<?php else: ?>
    <div class="cms-history-list">
        <?php foreach ($revisions as $rev): ?>
            <article class="cms-history-item">
                <header class="cms-history-head">
                    <div>
                        <strong>#<?= (int)$rev->id ?></strong>
                        <span class="cms-history-when"><?= h($rev->changed_at?->nice() ?? '') ?></span>
                        <span class="cms-history-by">
                            by <?= h($rev->changed_by->username ?? ('user #' . (int)$rev->changed_by_id)) ?>
                        </span>
                    </div>
                    <div>
                        <?= $this->Form->postLink(
                            '<i class="bi bi-arrow-counterclockwise"></i> Restore this version',
                            '/admin/cms/sections/' . (int)$section->id . '/restore/' . (int)$rev->id,
                            [
                                'class' => 'admin-btn-secondary',
                                'confirm' => __('Replace the current value with this revision?'),
                                'escape' => false,
                            ]
                        ) ?>
                    </div>
                </header>
                <?php if (!empty($rev->change_summary)): ?>
                    <p class="cms-history-summary"><?= h($rev->change_summary) ?></p>
                <?php endif; ?>
                <pre class="cms-history-snippet"><?= h($truncate($rev->content_value_snapshot)) ?></pre>
                <?php if ($rev->media_id_snapshot): ?>
                    <p class="cms-history-meta">Image media id: <?= (int)$rev->media_id_snapshot ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
