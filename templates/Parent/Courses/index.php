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
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <div>
            <h3><?= h($course->course_name) ?></h3>
            <span style="font-size: 0.85rem; color: #666;">
                <?= h(ucfirst($course->course_type)) ?>
                &middot;
                <?= h(ucfirst(str_replace('_', ' ', $course->course_level ?? 'All Levels'))) ?>
                &middot;
                $<?= number_format((float)$course->course_price, 2) ?> per class
            </span>
        </div>
    </div>

    <?php if ($course->course_description): ?>
        <div style="padding: 12px 20px 0;">
            <p style="margin: 0; color: #555; font-size: 0.95rem;"><?= h($course->course_description) ?></p>
        </div>
    <?php endif; ?>

    <?php if (empty($classes)): ?>
        <div class="empty-state" style="padding: 24px;">
            <p>No upcoming classes available for this course.</p>
        </div>
    <?php else: ?>
        <div class="portal-class-list" style="padding: 16px 20px;">
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
                        <span><?= $class->start_datetime ? $class->start_datetime->format('g:ia') : '' ?><?= $class->end_datetime ? ' – ' . $class->end_datetime->format('g:ia') : '' ?></span>
                        <?php if ($class->location): ?><span><?= h($class->location) ?></span><?php endif; ?>
                        <span>Teacher: <?= h($class->teacher?->teacher_name ?? 'TBA') ?></span>
                    </div>
                    <div class="portal-status-row">
                        <div>
                            <span class="portal-status-row__label">Price</span>
                            <strong>$<?= number_format((float)$course->course_price, 2) ?></strong>
                        </div>
                        <div>
                            <?php if ($class->available_slots > 0): ?>
                                <a href="<?= $this->Url->build(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'add', $class->class_id]) ?>" class="btn btn-sm btn-primary">
                                    Book Now
                                </a>
                            <?php else: ?>
                                <button class="btn btn-sm" disabled>Full</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if (empty($courseData)): ?>
    <div class="empty-state">
        <div class="icon">&#x1F4DA;</div>
        <p>No courses available at the moment. Please check back soon.</p>
    </div>
<?php endif; ?>
