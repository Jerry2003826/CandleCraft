<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Course> $courses
 * @var string|null $type
 */
$coursesBundle = $this->Cms->all('courses');
$pageTitle = $this->Cms->text('courses', 'intro.title', 'Our Courses');
$pageEyebrow = $this->Cms->text('courses', 'intro.eyebrow', 'CandleCraft Academy');

$this->assign('title', $type ? ucfirst($type) . ' Classes' : $pageTitle);
$potteryUrl = $this->Url->build(['controller' => 'Courses', 'action' => 'index', '?' => ['type' => 'pottery']]);
$knittingUrl = $this->Url->build(['controller' => 'Courses', 'action' => 'index', '?' => ['type' => 'knitting']]);
?>

<div class="section-heading courses-page-heading">
    <p class="overline courses-page-eyebrow">
        <?= h($pageEyebrow) ?>
    </p>
    <h1 class="courses-page-title">
        <?= $type ? h(ucfirst($type)) . ' Classes' : h($pageTitle) ?>
    </h1>
    <div class="courses-page-divider"></div>
</div>

<?php if (!$type): ?>

    <!-- Category landing: two big cards -->
    <div class="course-category-grid">

        <a href="<?= h($potteryUrl) ?>" class="course-category-link">
            <div class="course-category-card">
                <div class="course-category-card__icon">&#x1F3FA;</div>
                <h2 class="course-category-card__title"><?= h($this->Cms->text('courses', 'category.pottery_title', 'Pottery')) ?></h2>
                <div class="course-category-card__description"><?= $this->Cms->html('courses', 'category.pottery_description', 'Learn pottery through guided, hands-on lessons that build your skills from basic techniques to creating your own finished pieces.') ?></div>
                <p class="course-category-card__cta">View Classes &rarr;</p>
            </div>
        </a>

        <a href="<?= h($knittingUrl) ?>" class="course-category-link">
            <div class="course-category-card">
                <div class="course-category-card__icon">&#x1F9F6;</div>
                <h2 class="course-category-card__title"><?= h($this->Cms->text('courses', 'category.knitting_title', 'Knitting')) ?></h2>
                <div class="course-category-card__description"><?= $this->Cms->html('courses', 'category.knitting_description', 'Learn knitting step by step with practical lessons that help you master stitches and create your own handmade projects.') ?></div>
                <p class="course-category-card__cta">View Classes &rarr;</p>
            </div>
        </a>

    </div>

<?php else: ?>

    <div style="max-width: 1100px; margin: 0 auto 32px; padding: 0 20px;">
        <a href="<?= $this->Url->build(['controller' => 'Courses', 'action' => 'index']) ?>"
           style="display: inline-flex; align-items: center; gap: 8px; font-family: var(--font-grown); color: var(--home-accent); font-size: 0.78rem; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 700; text-decoration: none; padding: 10px 20px; border: 1px solid rgba(210, 154, 88, 0.45); border-radius: 999px; background: rgba(210, 154, 88, 0.08); transition: background 0.2s, border-color 0.2s;"
           onmouseover="this.style.background='rgba(210,154,88,0.18)'; this.style.borderColor='rgba(210,154,88,0.7)'"
           onmouseout="this.style.background='rgba(210,154,88,0.08)'; this.style.borderColor='rgba(210,154,88,0.45)'">
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

                    <div class="flip-card__meta">
                        <span class="flip-card__meta-value">
                            <span style="font-family: sans-serif;">$</span><?= number_format((float)$course->course_price, 0) ?>
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
