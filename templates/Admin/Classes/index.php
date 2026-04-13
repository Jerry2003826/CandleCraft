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
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link active" href="<?= $this->Url->build(['action' => 'index']) ?>">Class List</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->Url->build(['action' => 'availability']) ?>">Availability</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Attendance', 'action' => 'index']) ?>">Attendance</a>
    </li>
</ul>

<!-- Toolbar: Search + Add -->
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <form method="get" action="<?= $this->Url->build(['action' => 'index']) ?>" class="flex-grow-1" style="max-width:600px">
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search classes..." value="<?= h($search ?? '') ?>">
        </div>
    </form>
    <div class="ms-auto">
        <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Class</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Class Code</th>
                    <th>Course</th>
                    <th>Teacher</th>
                    <th>Schedule</th>
                    <th>Students</th>
                    <th>Location</th>
                    <th>Actions</th>
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
                        <a href="<?= $this->Url->build(['action' => 'view', $class->class_id]) ?>" class="fw-semibold text-decoration-none" style="color:var(--cc-primary)">
                            <?= h($class->class_code) ?>
                        </a>
                    </td>
                    <td><strong><?= $class->course ? h($class->course->course_name) : '-' ?></strong></td>
                    <td><?= $class->teacher ? h($class->teacher->teacher_name) : '-' ?></td>
                    <td>
                        <?php if ($class->start_datetime): ?>
                            <?= $class->start_datetime->format('D, g:ia') ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td style="min-width:100px">
                        <div class="d-flex align-items-center gap-2">
                            <strong><?= $booked ?>/<?= $cap ?></strong>
                            <div class="progress flex-grow-1" style="height:6px;max-width:60px">
                                <div class="progress-bar <?= $barColor ?>" style="width:<?= $pct ?>%"></div>
                            </div>
                        </div>
                    </td>
                    <td><?= h($class->location) ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= $this->Url->build(['action' => 'view', $class->class_id]) ?>" class="btn btn-outline-primary">View</a>
                            <a href="<?= $this->Url->build(['action' => 'edit', $class->class_id]) ?>" class="btn btn-outline-warning">Edit</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body d-flex justify-content-center">
        <ul class="pagination mb-0">
            <?= $this->Paginator->prev('‹ Previous') ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next('Next ›') ?>
        </ul>
    </div>
</div>
