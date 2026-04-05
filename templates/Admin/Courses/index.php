<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Course> $courses
 */
$this->assign('title', 'Courses');
?>

<div class="d-flex justify-content-end mb-3">
    <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Course</a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">All Courses</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>Type</th><th>Level</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (empty($courses) || (is_object($courses) && $courses->isEmpty())): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No courses found.</td></tr>
                <?php else: ?>
                    <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><strong><?= h($course->course_name) ?></strong></td>
                        <td><?= h(ucfirst($course->course_type ?? '-')) ?></td>
                        <td><span class="badge bg-info"><?= h(ucfirst(str_replace('_', ' ', $course->course_level ?? 'all levels'))) ?></span></td>
                        <td>$<?= number_format((float)$course->course_price, 2) ?></td>
                        <td>
                            <?php if ($course->is_active ?? true): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= $this->Url->build(['action' => 'edit', $course->course_id]) ?>" class="btn btn-outline-primary">Edit</a>
                                <?= $this->Form->postLink('Delete', ['action' => 'delete', $course->course_id], [
                                    'class' => 'btn btn-outline-danger',
                                    'confirm' => 'Are you sure you want to delete this course?'
                                ]) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
