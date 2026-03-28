<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Course $course
 * @var iterable $classes
 */
$this->assign('title', h($course->course_name));
?>

<div class="card">
    <div class="card-header">
        <h3><?= h($course->course_name) ?></h3>
        <a href="<?= $this->Url->build(['controller' => 'Courses', 'action' => 'index']) ?>" class="btn btn-sm">&larr; All Courses</a>
    </div>
    <div class="card-body">
        <div class="portal-status-row">
            <div>
                <span class="portal-status-row__label">Type</span>
                <strong><?= h($course->course_type) ?></strong>
            </div>
            <div>
                <span class="portal-status-row__label">Level</span>
                <strong><?= h(ucfirst($course->course_level ?? 'All Levels')) ?></strong>
            </div>
            <div>
                <span class="portal-status-row__label">Price</span>
                <strong>$<?= number_format((float)$course->course_price, 2) ?></strong>
            </div>
        </div>

        <?php if ($course->course_description): ?>
            <p style="color: #555; margin: 16px 0;"><?= h($course->course_description) ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3>Available Classes</h3>
    </div>
    <?php if (empty($classes) || $classes->isEmpty()): ?>
        <div class="empty-state">
            <div class="icon">&#x1F4C5;</div>
            <p>No upcoming classes for this course.</p>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Class Code</th>
                    <th>Teacher</th>
                    <th>Schedule</th>
                    <th>Location</th>
                    <th>Availability</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><?= h($class->class_code) ?></td>
                        <td><?= h($class->teacher?->teacher_name ?? 'TBA') ?></td>
                        <td><?= $class->start_datetime ? $class->start_datetime->format('j M Y, g:ia') : '-' ?></td>
                        <td><?= h($class->location) ?></td>
                        <td>
                            <?php if ($class->available_slots > 0): ?>
                                <span class="badge badge-confirmed"><?= $class->available_slots ?> / <?= $class->capacity ?> spots</span>
                            <?php else: ?>
                                <span class="badge badge-cancelled">Full</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                                $identity = $this->request->getAttribute('identity');
                                if ($identity && $identity->get('user_role') === 'student' && ($class->available_slots ?? 0) > 0):
                            ?>
                                <a href="<?= $this->Url->build(['prefix' => 'Student', 'controller' => 'Bookings', 'action' => 'add', $class->class_id]) ?>"
                                   class="btn btn-sm btn-primary">Book Now</a>
                            <?php elseif (!$identity && ($class->available_slots ?? 0) > 0): ?>
                                <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>"
                                   class="btn btn-sm btn-primary">Login to Book</a>
                            <?php elseif (($class->available_slots ?? 0) <= 0): ?>
                                <span class="badge badge-cancelled">Full</span>
                            <?php else: ?>
                                <span class="badge badge-pending">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
