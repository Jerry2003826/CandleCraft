<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Student> $students
 * @var string|null $status
 */
$this->assign('title', 'Students');
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
            <input type="text" name="search" placeholder="Search students..." value="<?= h($search ?? '') ?>" style="width: 100%;">
        </div>
    </form>

    <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary">
        <i class="bi bi-plus-lg"></i> Add Student
    </a>
</div>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Date of Birth</th>
                    <th>Status</th>
                    <th>Age Verified</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td>
                        <p class="admin-table-primary-text"><?= h($student->student_name) ?></p>
                    </td>
                    <td>
                        <p class="admin-table-secondary-text"><?= $student->date_of_birth ? $student->date_of_birth->format('j M Y') : '-' ?></p>
                    </td>
                    <td>
                        <?php 
                            $statusClass = 'admin-badge-neutral';
                            if ($student->student_status === 'active') $statusClass = 'admin-badge-success';
                            if ($student->student_status === 'inactive') $statusClass = 'admin-badge-danger';
                        ?>
                        <span class="admin-badge <?= $statusClass ?>"><?= h(ucfirst($student->student_status)) ?></span>
                    </td>
                    <td>
                        <?php if ($student->user && $student->user->age_verified_by_admin): ?>
                            <span class="admin-badge admin-badge-success"><i class="bi bi-shield-check me-1"></i> Yes</span>
                        <?php elseif ($student->user && $student->user->self_declared_adult): ?>
                            <span class="admin-badge admin-badge-warning"><i class="bi bi-exclamation-triangle me-1"></i> Pending</span>
                        <?php else: ?>
                            <span class="admin-badge admin-badge-neutral">No</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <p class="admin-table-secondary-text"><?= $student->created_at ? $student->created_at->format('j M Y') : '-' ?></p>
                    </td>
                    <td>
                        <div class="admin-action-links justify-content-end">
                            <a href="<?= $this->Url->build(['action' => 'view', $student->student_id]) ?>" class="admin-action-link view" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= $this->Url->build(['action' => 'edit', $student->student_id]) ?>" class="admin-action-link edit" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?= $this->Form->postLink('<i class="bi bi-trash"></i>', ['action' => 'delete', $student->student_id], [
                                'confirm' => __('Are you sure you want to delete {0}?', $student->student_name),
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
