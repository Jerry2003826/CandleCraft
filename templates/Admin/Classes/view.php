<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 */
$this->assign('title', 'Class Details');
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Classes</a>
    <div class="btn-group btn-group-sm">
        <a href="<?= $this->Url->build(['action' => 'edit', $class->class_id]) ?>" class="btn btn-outline-warning">Edit</a>
        <?= $this->Form->postLink('Delete', ['action' => 'delete', $class->class_id], [
            'confirm' => __('Are you sure you want to delete class {0}?', $class->class_code),
            'class' => 'btn btn-outline-danger',
        ]) ?>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= h($class->class_code) ?></h5>
        <?= $this->Badge->status($class->class_status) ?>
    </div>
    <div class="card-body">
        <table class="table table-borderless">
            <tr><th class="text-end text-muted" style="width:180px">Class Code:</th><td><?= h($class->class_code) ?></td></tr>
            <tr><th class="text-end text-muted">Course:</th><td><?= $class->course ? h($class->course->course_name) : '-' ?></td></tr>
            <tr><th class="text-end text-muted">Teacher:</th><td><?= $class->teacher ? h($class->teacher->teacher_name) : '-' ?></td></tr>
            <tr><th class="text-end text-muted">Start:</th><td><?= $class->start_datetime ? $class->start_datetime->format('j M Y, g:ia') : '-' ?></td></tr>
            <tr><th class="text-end text-muted">End:</th><td><?= $class->end_datetime ? $class->end_datetime->format('j M Y, g:ia') : '-' ?></td></tr>
            <tr><th class="text-end text-muted">Location:</th><td><?= h($class->location) ?></td></tr>
            <tr><th class="text-end text-muted">Capacity:</th><td><?= h($class->capacity) ?></td></tr>
            <tr><th class="text-end text-muted">Notes:</th><td><?= $class->notes ? nl2br(h($class->notes)) : 'N/A' ?></td></tr>
        </table>
    </div>
</div>

<?php if (!empty($class->bookings)): ?>
<div class="card">
    <div class="card-header"><h5 class="mb-0">Enrolled Students</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Student</th><th>Booking Date</th><th>Status</th><th>Price</th></tr></thead>
            <tbody>
                <?php foreach ($class->bookings as $booking): ?>
                <tr>
                    <td><?= $booking->student ? h($booking->student->student_name) : '-' ?></td>
                    <td><?= $booking->booking_date ? $booking->booking_date->format('j M Y') : '-' ?></td>
                    <td><?= $this->Badge->status($booking->booking_status) ?></td>
                    <td>$<?= number_format((float)$booking->price_at_booking, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
