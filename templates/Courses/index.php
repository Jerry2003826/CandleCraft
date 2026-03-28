<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Course> $courses
 */
$this->assign('title', 'Our Courses');
?>

<div class="card">
    <div class="card-header">
        <h3 style="font-family: var(--font-grown); text-transform: uppercase; font-weight: 700; letter-spacing: 0.1em;">Our Courses</h3>
    </div>
    <div class="card-body">
        <?php if (empty($courses) || $courses->isEmpty()): ?>
            <div class="empty-state">
                <div class="icon">&#x1F4D6;</div>
                <p>No courses available at the moment.</p>
            </div>
        <?php else: ?>
            <div class="portal-class-list">
                <?php foreach ($courses as $course): ?>
                    <section class="portal-class-card">
                        <div class="portal-class-card__header">
                            <div>
                                <p class="portal-class-card__eyebrow">
                                    <?= h(ucfirst($course->course_level ?? 'All Levels')) ?>
                                </p>
                                <h3><?= h($course->course_name) ?></h3>
                                 <span style="font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(240, 207, 156, 0.7); font-family: var(--font-grown);"><?= count($course->classes) ?> upcoming classes</span>
                            </div>
                            <span class="badge badge-confirmed">
                                $<?= number_format((float)$course->course_price, 2) ?>
                            </span>
                        </div>

                        <?php if ($course->course_description): ?>
                            <p style="color: #555; margin: 8px 0;"><?= h($course->course_description) ?></p>
                        <?php endif; ?>

                        <div style="margin-top: 12px;">
                            <a href="<?= $this->Url->build(['controller' => 'Courses', 'action' => 'view', $course->course_id]) ?>"
                               class="btn btn-sm btn-primary">View Classes</a>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
