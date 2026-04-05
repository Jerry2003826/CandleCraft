<?php
/**
 * @var \App\View\AppView $this
 * @var array $courseData
 */
$this->assign('title', 'Browse Courses');
?>

<?php foreach ($courseData as $item):
    $course = $item['course'];
    $classes = $item['classes'];
?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1"><?= h($course->course_name) ?></h5>
            <small class="text-muted">
                <?= h(ucfirst($course->course_type)) ?>
                &middot;
                <?= h(ucfirst(str_replace('_', ' ', $course->course_level ?? 'All Levels'))) ?>
                &middot;
                $<?= number_format((float)$course->course_price, 2) ?> per class
            </small>
        </div>
    </div>

    <?php if ($course->course_description): ?>
        <div class="card-body pb-0">
            <p class="text-muted mb-0"><?= h($course->course_description) ?></p>
        </div>
    <?php endif; ?>

    <?php if (empty($classes)): ?>
        <div class="text-center py-4 text-muted">
            <p class="mb-0">No upcoming classes available for this course.</p>
        </div>
    <?php else: ?>
        <div class="card-body">
            <div class="portal-class-list">
                <?php foreach ($classes as $class): ?>
                    <section class="portal-class-card">
                        <div class="portal-class-card__header">
                            <div>
                                <p class="portal-class-card__eyebrow"><?= h($class->class_code) ?></p>
                                <h3><?= $class->start_datetime ? $class->start_datetime->format('D j M Y') : 'Date TBA' ?></h3>
                            </div>
                            <?php if ($class->available_slots <= 0): ?>
                                <span class="badge badge-cancelled">Full</span>
                            <?php else: ?>
                                <span class="badge badge-confirmed"><?= h((string)$class->available_slots) ?> spot<?= $class->available_slots !== 1 ? 's' : '' ?> left</span>
                            <?php endif; ?>
                        </div>
                        <div class="portal-class-card__meta">
                            <span><i class="bi bi-clock me-1"></i><?= $class->start_datetime ? $class->start_datetime->format('g:ia') : '' ?><?= $class->end_datetime ? ' – ' . $class->end_datetime->format('g:ia') : '' ?></span>
                            <?php if ($class->location): ?><span><i class="bi bi-geo-alt me-1"></i><?= h($class->location) ?></span><?php endif; ?>
                            <span><i class="bi bi-person me-1"></i><?= h($class->teacher?->teacher_name ?? 'TBA') ?></span>
                        </div>
                        <div class="portal-status-row">
                            <div>
                                <span class="portal-status-row__label">Price</span>
                                <strong>$<?= number_format((float)$course->course_price, 2) ?></strong>
                            </div>
                            <div>
                                <?php if ($class->available_slots > 0): ?>
                                    <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'add', $class->class_id]) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i> Book Now
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled>Full</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if (empty($courseData)): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-palette" style="font-size: 48px;"></i>
        <p class="mt-3">No courses available at the moment. Please check back soon.</p>
    </div>
<?php endif; ?>
