<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ClassEntity> $classes
 * @var string|null $status
 * @var string|null $search
 */
$this->assign('title', 'Classes');
?>

<div class="admin-page-header admin-list-toolbar">
    <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary">
        <i class="bi bi-plus-lg"></i> Add Class
    </a>
    <form method="get" action="<?= $this->Url->build(['action' => 'index']) ?>" role="search" class="admin-list-toolbar__search">
        <div class="admin-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <label for="classSearch" class="visually-hidden">Search classes</label>
            <input type="text" name="search" placeholder="Search classes..." id="classSearch"
                   aria-label="Search classes" value="<?= h($search ?? '') ?>">
        </div>
    </form>
</div>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Class Code</th>
                    <th>Course</th>
                    <th>Teacher</th>
                    <th>Schedule</th>
                    <th>Students</th>
                    <th>Location</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $class):
                    $booked = count($class->bookings ?? []);
                    $cap = (int)$class->capacity;
                    $pct = $cap > 0 ? round($booked / $cap * 100) : 0;
                    $barColor = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
                    $viewUrl = $this->Url->build(['action' => 'view', $class->class_id]);
                ?>
	                <tr class="admin-clickable-row" data-href="<?= h($viewUrl) ?>" tabindex="0" role="link" aria-label="View class <?= h($class->class_code) ?>">
	                    <td>
	                        <p class="admin-table-primary-text" style="color: var(--admin-brand-icon);"><?= h($class->class_code) ?></p>
	                    </td>
                    <td>
                        <p class="admin-table-primary-text"><?= $class->course ? h($class->course->course_name) : '-' ?></p>
                    </td>
                    <td>
                        <p class="admin-table-secondary-text"><?= $class->teacher ? h($class->teacher->teacher_name) : '-' ?></p>
                    </td>
                    <td>
                        <p class="admin-table-secondary-text">
                            <?php if ($class->start_datetime): ?>
                                <?= $class->start_datetime->format('D, g:ia') ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </p>
                    </td>
                    <td style="min-width:120px">
                        <div class="d-flex align-items-center gap-2">
                            <span class="admin-table-primary-text mb-0"><?= $booked ?>/<?= $cap ?></span>
                            <div class="progress flex-grow-1" style="height:6px;max-width:60px;background-color:var(--admin-search-bg);">
                                <div class="progress-bar <?= $barColor ?>" style="width:<?= $pct ?>%"></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <p class="admin-table-secondary-text"><?= h($class->location) ?></p>
                    </td>
	                    <td>
	                        <div class="admin-action-links justify-content-end">
	                            <a href="<?= $this->Url->build(['action' => 'edit', $class->class_id]) ?>" class="admin-action-link edit" title="Edit" aria-label="Edit class <?= h($class->class_code) ?>">
	                                <i class="bi bi-pencil"></i>
	                            </a>
	                            <?= $this->Form->create(null, [
	                                'url' => ['action' => 'delete', $class->class_id],
	                                'class' => 'd-inline m-0',
	                            ]) ?>
	                                <?= $this->Form->button('<i class="bi bi-trash"></i>', [
	                                    'class' => 'admin-action-link delete',
	                                    'type' => 'submit',
	                                    'title' => 'Delete',
	                                    'aria-label' => 'Delete class ' . $class->class_code,
	                                    'onclick' => "return confirm('Delete this class?');",
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
document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form[role="search"]');
    var searchInput = document.getElementById('classSearch');
    if (!form || !searchInput) return;
    var timer;
    searchInput.addEventListener('input', function() {
        clearTimeout(timer);
        timer = setTimeout(function() { form.submit(); }, 400);
    });

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
