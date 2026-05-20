<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Student> $students
 * @var string|null $status
 */
$this->assign('title', 'Customers');
?>

<div class="admin-page-header admin-list-toolbar">
    <div class="admin-tabs">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-tab <?= !$status ? 'active' : '' ?>">All</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'active']]) ?>" class="admin-tab <?= $status === 'active' ? 'active' : '' ?>">Active</a>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['status' => 'inactive']]) ?>" class="admin-tab <?= $status === 'inactive' ? 'active' : '' ?>">Inactive</a>
    </div>

    <div class="admin-list-toolbar__actions">
        <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary">
            <i class="bi bi-plus-lg"></i> Register Customer
        </a>
        <form method="get" action="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-list-toolbar__search" role="search">
            <?php if ($status): ?><input type="hidden" name="status" value="<?= h($status) ?>"><?php endif; ?>
            <div class="admin-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label for="student-search" class="visually-hidden">Search customers</label>
                <input type="text" id="student-search" name="search" placeholder="Search customers..." value="<?= h($search ?? '') ?>" aria-label="Search customers">
            </div>
        </form>
    </div>
</div>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Declared Age</th>
                    <th>Status</th>
                    <th>Over 18?</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <?php $viewUrl = $this->Url->build(['action' => 'view', $student->student_id]); ?>
                <tr class="admin-clickable-row" data-href="<?= h($viewUrl) ?>" tabindex="0" role="link" aria-label="View <?= h($student->student_name) ?>">
                    <td>
                        <p class="admin-table-primary-text"><?= h($student->student_name) ?></p>
                    </td>
                    <td>
                        <p class="admin-table-secondary-text"><?= $student->declared_age !== null ? h((string)$student->declared_age) : '-' ?></p>
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
                        <?php if (!$student->user): ?>
                            <span class="admin-badge admin-badge-neutral"><i class="bi bi-person-dash me-1"></i> No account</span>
                        <?php elseif ($student->user->age_verified_by_admin): ?>
                            <span class="admin-badge admin-badge-success"><i class="bi bi-shield-check me-1"></i> Yes</span>
                        <?php else: ?>
                            <span class="admin-badge admin-badge-warning"><i class="bi bi-hourglass-split me-1"></i> Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <p class="admin-table-secondary-text"><?= $student->created_at ? $student->created_at->format('j M Y') : '-' ?></p>
                    </td>
	                    <td>
	                        <div class="admin-action-links justify-content-end">
                            <a href="<?= h($viewUrl) ?>" class="admin-action-link view" title="View" aria-label="View <?= h($student->student_name) ?>">
                                <i class="bi bi-eye"></i>
                            </a>
	                            <a href="<?= $this->Url->build(['action' => 'edit', $student->student_id]) ?>" class="admin-action-link edit" title="Edit" aria-label="Edit <?= h($student->student_name) ?>">
	                                <i class="bi bi-pencil"></i>
	                            </a>
                            <?= $this->Form->create(null, [
                                'url' => ['action' => 'delete', $student->student_id],
                                'class' => 'd-inline m-0',
                            ]) ?>
                                <?= $this->Form->button('<i class="bi bi-trash"></i>', [
                                    'class' => 'admin-action-link delete',
                                    'type' => 'submit',
                                    'title' => 'Delete',
                                    'aria-label' => 'Delete ' . $student->student_name,
                                    'onclick' => "return confirm('Delete this customer?');",
                                    'escapeTitle' => false,
                                ]) ?>
                            <?= $this->Form->end() ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="admin-pagination">
        <?= $this->Paginator->prev('<i class="bi bi-chevron-left"></i>', ['escape' => false, 'aria-label' => 'Previous page']) ?>
        <?= $this->Paginator->numbers(['escape' => false]) ?>
        <?= $this->Paginator->next('<i class="bi bi-chevron-right"></i>', ['escape' => false, 'aria-label' => 'Next page']) ?>
    </div>
</div>

<style>
    .admin-clickable-row {
        cursor: pointer;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.admin-clickable-row[data-href]').forEach(function (row) {
        function openRow(event) {
            if (event.target.closest('a, button, form, input, select, textarea, label')) {
                return;
            }
            window.location.href = row.dataset.href;
        }

        row.addEventListener('click', openRow);
        row.addEventListener('keydown', function (event) {
            if (event.target.closest('a, button, form, input, select, textarea, label')) {
                return;
            }
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            event.preventDefault();
            window.location.href = row.dataset.href;
        });
    });
});
</script>
