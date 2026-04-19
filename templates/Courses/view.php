<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Course $course
 */
$this->assign('title', h($course->course_name));
$enquiryUrl = $this->Url->build(['controller' => 'Pages', 'action' => 'contact', '?' => ['from' => 'course-view']]);
?>

<div class="section-heading" style="text-align: center; padding: 60px 20px 20px;">
    <p class="overline" style="font-family: var(--font-grown); color: var(--home-accent); letter-spacing: 0.3em; margin-bottom: 12px; font-size: 0.8rem;">
        Classes
    </p>
    <h1 style="font-family: var(--font-grown); font-size: clamp(2.5rem, 5vw, 4rem); color: #f5ecdf; text-transform: uppercase; letter-spacing: 0.15em; margin: 0; line-height: 1;">
        <?= h($course->course_name) ?>
    </h1>
    <div style="width: 60px; height: 2px; background: var(--home-accent); margin: 24px auto 0; opacity: 0.6;"></div>
</div>

<div style="max-width: 800px; margin: 40px auto 80px; padding: 0 20px;">
    <div style="background: rgba(47, 34, 25, 0.85); backdrop-filter: blur(10px); border-radius: 24px; border: 1px solid rgba(210, 154, 88, 0.3); padding: 40px; box-shadow: var(--home-shadow);">

        <div style="display: flex; gap: 32px; justify-content: center; margin-bottom: 32px; flex-wrap: wrap;">
            <div style="text-align: center;">
                <p style="font-family: var(--font-grown); font-size: 0.75rem; color: var(--home-accent); letter-spacing: 0.2em; text-transform: uppercase; margin: 0 0 6px;">Type</p>
                <p style="font-family: var(--font-grown); font-size: 1.1rem; color: #f5ecdf; font-weight: 700; margin: 0;"><?= h($course->course_type) ?></p>
            </div>
            <div style="width: 1px; background: rgba(210, 154, 88, 0.3);"></div>
            <div style="text-align: center;">
                <p style="font-family: var(--font-grown); font-size: 0.75rem; color: var(--home-accent); letter-spacing: 0.2em; text-transform: uppercase; margin: 0 0 6px;">Level</p>
                <p style="font-family: var(--font-grown); font-size: 1.1rem; color: #f5ecdf; font-weight: 700; margin: 0;"><?= h(ucfirst($course->course_level ?? 'All Levels')) ?></p>
            </div>
            <div style="width: 1px; background: rgba(210, 154, 88, 0.3);"></div>
            <div style="text-align: center;">
                <p style="font-family: var(--font-grown); font-size: 0.75rem; color: var(--home-accent); letter-spacing: 0.2em; text-transform: uppercase; margin: 0 0 6px;">Price</p>
                <p style="font-family: var(--font-grown); font-size: 1.1rem; color: #f5ecdf; font-weight: 700; margin: 0;">$<?= number_format((float)$course->course_price, 2) ?></p>
            </div>
        </div>

        <?php if ($course->course_description): ?>
            <p style="font-family: var(--font-grown); color: var(--home-text-muted); font-size: 1.05rem; line-height: 1.8; text-align: center; margin: 0 0 40px;">
                <?= h($course->course_description) ?>
            </p>
        <?php endif; ?>

        <div style="text-align: center; border-top: 1px solid rgba(210, 154, 88, 0.2); padding-top: 32px;">
            <p style="font-family: var(--font-grown); color: var(--home-text-muted); margin-bottom: 20px; font-size: 1rem;">
                Interested in this course? Get in touch with our team.
            </p>
            <a href="<?= h($enquiryUrl) ?>" class="button button--solid" style="display: inline-block;">
                Enquire Now
            </a>
        </div>

    </div>

    <div style="text-align: center; margin-top: 24px;">
        <a href="<?= $this->Url->build(['controller' => 'Courses', 'action' => 'index']) ?>"
           style="font-family: var(--font-grown); color: var(--home-accent); font-size: 0.9rem; letter-spacing: 0.1em; text-decoration: none;">
            &larr; Back to All Courses
        </a>
    </div>
</div>
