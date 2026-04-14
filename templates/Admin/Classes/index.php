<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ClassEntity> $classes
 * @var string|null $status
 * @var string|null $search
 */
$this->assign('title', 'Classes');
?>

<!-- Tab Navigation -->
<div class="admin-page-header mb-4">
    <div class="admin-tabs">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-tab active">Class List</a>
        <a href="<?= $this->Url->build(['action' => 'availability']) ?>" class="admin-tab">Availability</a>
        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Attendance', 'action' => 'index']) ?>" class="admin-tab">Attendance</a>
    </div>
</div>

<!-- Toolbar: Search + Add -->
<div class="admin-page-header justify-content-end">
    <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary">
        <i class="bi bi-plus-lg"></i> Add Class
    </a>
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
                ?>
                <tr>
                    <td>
                        <a href="<?= $this->Url->build(['action' => 'view', $class->class_id]) ?>" class="admin-table-primary-text text-decoration-none" style="color: var(--admin-brand-icon);">
                            <?= h($class->class_code) ?>
                        </a>
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
                            <a href="<?= $this->Url->build(['action' => 'view', $class->class_id]) ?>" class="admin-action-link view" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= $this->Url->build(['action' => 'edit', $class->class_id]) ?>" class="admin-action-link edit" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
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
