<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Teacher> $teachers
 * @var string|null $status
 */
$this->assign('title', 'Teachers');
?>

<div class="admin-page-header">
    <div class="admin-tabs">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-tab <?= !$status ? 'active' : '' ?>">All</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'active']]) ?>" class="admin-tab <?= $status === 'active' ? 'active' : '' ?>">Active</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'inactive']]) ?>" class="admin-tab <?= $status === 'inactive' ? 'active' : '' ?>">Inactive</a>
    </div>
    
    <form method="get" action="<?= $this->Url->build(['action' => 'index']) ?>" class="flex-grow-1" style="max-width: 400px;">
        <?php if ($status): ?><input type="hidden" name="status" value="<?= h($status) ?>"><?php endif; ?>
        <div class="admin-search" style="background-color: var(--admin-card-bg); border: 1px solid var(--admin-card-border);">
            <i class="bi bi-search"></i>
            <input type="text" name="search" placeholder="Search teachers..." value="<?= h($search ?? '') ?>" style="width: 100%;">
        </div>
    </form>

    <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary">
        <i class="bi bi-plus-lg"></i> Add Teacher
    </a>
</div>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Specialization</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Hire Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teachers as $teacher): ?>
                <tr>
                    <td>
                        <p class="admin-table-primary-text"><?= h($teacher->teacher_name) ?></p>
                    </td>
                    <td>
                        <p class="admin-table-secondary-text"><?= h($teacher->specialization ?: '-') ?></p>
                    </td>
                    <td>
                        <p class="admin-table-secondary-text"><?= h($teacher->phone_number ?: '-') ?></p>
                    </td>
                    <td>
                        <?php 
                            $statusClass = 'admin-badge-neutral';
                            if ($teacher->teacher_status === 'active') $statusClass = 'admin-badge-success';
                            if ($teacher->teacher_status === 'inactive') $statusClass = 'admin-badge-danger';
                        ?>
                        <span class="admin-badge <?= $statusClass ?>"><?= h(ucfirst($teacher->teacher_status)) ?></span>
                    </td>
                    <td>
                        <p class="admin-table-secondary-text"><?= $teacher->hire_date ? $teacher->hire_date->format('j M Y') : '-' ?></p>
                    </td>
	                    <td>
	                        <div class="admin-action-links justify-content-end">
	                            <a href="<?= $this->Url->build(['action' => 'edit', $teacher->teacher_id]) ?>" class="admin-action-link edit" title="Edit">
	                                <i class="bi bi-pencil"></i>
	                            </a>
                            <?= $this->Form->postLink('<i class="bi bi-trash"></i>', ['action' => 'delete', $teacher->teacher_id], [
                                'confirm' => __('Are you sure you want to delete {0}?', $teacher->teacher_name),
                                'class' => 'admin-action-link delete',
                                'title' => 'Delete',
                                'escape' => false
                            ]) ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="admin-pagination">
        <?= $this->Paginator->prev('<i class="bi bi-chevron-left"></i>', ['escape' => false]) ?>
        <?= $this->Paginator->numbers(['escape' => false]) ?>
        <?= $this->Paginator->next('<i class="bi bi-chevron-right"></i>', ['escape' => false]) ?>
    </div>
</div>
