<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\SitePage> $pages
 * @var array<int,int> $sectionCounts
 */
$this->assign('title', 'Site Content');
?>

<div class="admin-page-header d-flex justify-content-between align-items-start flex-wrap">
    <div>
        <p class="admin-page-subtitle" style="color: var(--admin-text-secondary); margin:0 0 4px;">
            Edit titles, brand strings, hero copy and category descriptions without touching code.
        </p>
    </div>
    <a href="<?= $this->Url->build(['controller' => 'CmsMedia', 'action' => 'index']) ?>" class="admin-btn-secondary">
        <i class="bi bi-images"></i> Media library
    </a>
</div>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Page URL</th>
                    <th>Sections</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $page): ?>
                    <tr>
                        <td>
                            <p class="admin-table-primary-text"><?= h($page->page_title) ?></p>
                        </td>
                        <td>
                            <code style="background:var(--admin-card-bg); padding:2px 8px; border-radius:6px;">
                                <?= h($page->page_slug) ?>
                            </code>
                        </td>
                        <td>
                            <p class="admin-table-primary-text"><?= (int)($sectionCounts[$page->id] ?? 0) ?></p>
                        </td>
                        <td>
                            <?php if ($page->is_active): ?>
                                <span class="badge bg-success-subtle text-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a class="admin-btn-secondary" href="<?= $this->Url->build(['action' => 'view', $page->page_slug]) ?>">
                                <i class="bi bi-pencil-square"></i> Edit sections
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
