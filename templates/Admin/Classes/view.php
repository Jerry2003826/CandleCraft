<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 */
$this->assign('title', 'Class Details');
?>

<div class="toolbar">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">&larr; Back to Classes</a>
    <div>
        <a href="<?= $this->Url->build(['action' => 'edit', $class->class_id]) ?>"
           class="btn btn-sm btn-warning">Edit</a>
        <?= $this->Form->postLink(
            'Delete',
            ['action' => 'delete', $class->class_id],
            [
                'confirm' => __('Are you sure you want to delete class {0}?', $class->class_code),
                'class' => 'btn btn-sm btn-danger',
            ]
        ) ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><?= h($class->class_code) ?></h3>
        <span class="badge badge-<?= h($class->class_status) ?>">
            <?= ucfirst(h($class->class_status)) ?>
        </span>
    </div>
    <div class="card-body">
        <table class="detail-table">
            <tr>
                <th>Class Code:</th>
                <td><?= h($class->class_code) ?></td>
            </tr>
            <tr>
                <th>Course:</th>
                <td><?= $class->course ? h($class->course->course_name) : '-' ?></td>
            </tr>
            <tr>
                <th>Teacher:</th>
                <td><?= $class->teacher ? h($class->teacher->teacher_name) : '-' ?></td>
            </tr>
            <tr>
                <th>Start:</th>
                <td><?= $class->start_datetime ? $class->start_datetime->format('j M Y, g:ia') : '-' ?></td>
            </tr>
            <tr>
                <th>End:</th>
                <td><?= $class->end_datetime ? $class->end_datetime->format('j M Y, g:ia') : '-' ?></td>
            </tr>
            <tr>
                <th>Location:</th>
                <td><?= h($class->location) ?></td>
            </tr>
            <tr>
                <th>Capacity:</th>
                <td><?= h($class->capacity) ?></td>
            </tr>
            <tr>
                <th>Notes:</th>
                <td><?= $class->notes ? nl2br(h($class->notes)) : 'N/A' ?></td>
            </tr>
        </table>
    </div>
</div>

<?php if (!empty($class->bookings)): ?>
<div class="card">
    <div class="card-header">
        <h3>Enrolled Students</h3>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Booking Date</th>
                <th>Status</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($class->bookings as $booking): ?>
                <tr>
                    <td><?= $booking->student ? h($booking->student->student_name) : '-' ?></td>
                    <td><?= $booking->booking_date ? $booking->booking_date->format('j M Y') : '-' ?></td>
                    <td>
                        <span class="badge badge-<?= h($booking->booking_status) ?>">
                            <?= ucfirst(h($booking->booking_status)) ?>
                        </span>
                    </td>
                    <td>$<?= number_format((float)$booking->price_at_booking, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
