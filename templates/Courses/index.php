<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Course> $courses
 * @var string|null $type
 */
$this->assign('title', $type ? ucfirst($type) . ' Classes' : 'Our Courses');
$potteryUrl = $this->Url->build(['controller' => 'Courses', 'action' => 'index', '?' => ['type' => 'pottery']]);
$knittingUrl = $this->Url->build(['controller' => 'Courses', 'action' => 'index', '?' => ['type' => 'knitting']]);
?>

<div class="section-heading" style="text-align: center; padding: 60px 20px 20px;">
    <p class="overline" style="font-family: var(--font-grown); color: var(--home-accent); letter-spacing: 0.3em; margin-bottom: 12px; font-size: 0.8rem;">
        CandleCraft Academy
    </p>
    <h1 style="font-family: var(--font-grown); font-size: clamp(2.5rem, 5vw, 4rem); color: #f5ecdf; text-transform: uppercase; letter-spacing: 0.15em; margin: 0; line-height: 1;">
        <?= $type ? h(ucfirst($type)) . ' Classes' : 'Our Courses' ?>
    </h1>
    <div style="width: 60px; height: 2px; background: var(--home-accent); margin: 24px auto 0; opacity: 0.6;"></div>
</div>

<?php if (!$type): ?>

    <!-- Category landing: two big cards -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; max-width: 900px; margin: 60px auto 100px; padding: 0 20px;">

        <a href="<?= h($potteryUrl) ?>" class="course-category-link">
            <div class="course-category-card">
                <div style="font-size: 3.5rem; margin-bottom: 20px;">&#x1F3FA;</div>
                <h2 style="font-family: var(--font-grown); font-size: 2rem; color: #f5ecdf; text-transform: uppercase; letter-spacing: 0.15em; margin: 0 0 12px;">Pottery</h2>
                <p style="font-family: var(--font-grown); color: var(--home-text-muted); font-size: 0.95rem; margin: 0;">Wheel throwing, hand building &amp; more</p>
                <p style="font-family: var(--font-grown); color: var(--home-accent); font-size: 0.8rem; letter-spacing: 0.2em; text-transform: uppercase; margin: 24px 0 0;">View Classes &rarr;</p>
            </div>
        </a>

        <a href="<?= h($knittingUrl) ?>" class="course-category-link">
            <div class="course-category-card">
                <div style="font-size: 3.5rem; margin-bottom: 20px;">&#x1F9F6;</div> 
                <h2 style="font-family: var(--font-grown); font-size: 2rem; color: #f5ecdf; text-transform: uppercase; letter-spacing: 0.15em; margin: 0 0 12px;">Knitting</h2>
                <p style="font-family: var(--font-grown); color: var(--home-text-muted); font-size: 0.95rem; margin: 0;">Beginner to advanced stitches &amp; patterns</p>
                <p style="font-family: var(--font-grown); color: var(--home-accent); font-size: 0.8rem; letter-spacing: 0.2em; text-transform: uppercase; margin: 24px 0 0;">View Classes &rarr;</p>
            </div>
        </a>

    </div>

<?php else: ?>

    <!-- Back link -->
    <div style="text-align: center; margin-top: 20px;">
        <a href="<?= $this->Url->build(['controller' => 'Courses', 'action' => 'index']) ?>"
           style="font-family: var(--font-grown); color: var(--home-accent); font-size: 0.85rem; letter-spacing: 0.1em; text-decoration: none;">
            &larr; All Courses
        </a>
    </div>

    <?php if (empty($courses) || $courses->isEmpty()): ?>
        <div class="empty-state" style="text-align: center; padding: 100px 20px;">
            <div class="icon" style="font-size: 3rem; margin-bottom: 20px;">&#x1F4D6;</div>
            <p style="font-family: var(--font-grown); color: var(--home-text-muted);">No <?= h(ucfirst($type)) ?> courses available at the moment.</p>
        </div>

    <?php else: ?>
        <div class="flip-card-grid">

        <?php foreach ($courses as $course): ?>

            <div class="flip-card">
                <article class="flip-card__content" aria-label="<?= h($course->course_name) ?>">
                    <p class="overline" style="font-family: var(--font-grown); margin-bottom: 8px; font-size: 0.75rem;">
                        <?= h(ucfirst($course->course_level ?? 'All Levels')) ?>
                    </p>

                    <h2 style="font-family: var(--font-grown); font-size: 1.8rem; line-height: 1.2; margin-bottom: 12px; color: #ffffff;">
                        <?= h($course->course_name) ?>
                    </h2>

                    <p class="flip-card__description">
                        <?= h($course->course_description ?: 'Explore guided, small-group sessions designed around practical making skills.') ?>
                    </p>

                    <div class="flip-card__meta">
                        <span class="flip-card__meta-label">
                            <?= count($course->classes) ?> Classes
                        </span>
                        <span class="flip-card__meta-value">
                            $<?= number_format((float)$course->course_price, 0) ?>
                        </span>
                    </div>

                    <div class="flip-card__actions">
                        <a href="<?= $this->Url->build(['controller' => 'Courses', 'action' => 'view', $course->course_id]) ?>" class="btn btn-primary">
                            View Details
                        </a>
                    </div>
                </article>
            </div>

        <?php endforeach; ?>

        </div>
    <?php endif; ?>

<?php endif; ?>
