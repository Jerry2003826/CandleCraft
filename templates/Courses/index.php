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

        <a href="<?= h($potteryUrl) ?>" style="text-decoration: none;">
            <div style="background: rgba(47, 34, 25, 0.85); backdrop-filter: blur(10px); border-radius: 24px; border: 1px solid rgba(210, 154, 88, 0.3); padding: 56px 40px; text-align: center; box-shadow: var(--home-shadow); transition: border-color 0.2s ease, transform 0.2s ease;"
                 onmouseover="this.style.borderColor='rgba(210,154,88,0.7)'; this.style.transform='translateY(-4px)';"
                 onmouseout="this.style.borderColor='rgba(210,154,88,0.3)'; this.style.transform='none';">
                <div style="font-size: 3.5rem; margin-bottom: 20px;">&#x1F3FA;</div>
                <h2 style="font-family: var(--font-grown); font-size: 2rem; color: #f5ecdf; text-transform: uppercase; letter-spacing: 0.15em; margin: 0 0 12px;">Pottery</h2>
                <p style="font-family: var(--font-grown); color: var(--home-text-muted); font-size: 0.95rem; margin: 0;">Wheel throwing, hand building &amp; more</p>
                <p style="font-family: var(--font-grown); color: var(--home-accent); font-size: 0.8rem; letter-spacing: 0.2em; text-transform: uppercase; margin: 24px 0 0;">View Classes &rarr;</p>
            </div>
        </a>

        <a href="<?= h($knittingUrl) ?>" style="text-decoration: none;">
            <div style="background: rgba(47, 34, 25, 0.85); backdrop-filter: blur(10px); border-radius: 24px; border: 1px solid rgba(210, 154, 88, 0.3); padding: 56px 40px; text-align: center; box-shadow: var(--home-shadow); transition: border-color 0.2s ease, transform 0.2s ease;"
                 onmouseover="this.style.borderColor='rgba(210,154,88,0.7)'; this.style.transform='translateY(-4px)';"
                 onmouseout="this.style.borderColor='rgba(210,154,88,0.3)'; this.style.transform='none';">
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
                <div class="flip-card-inner">

                    <div class="flip-card-front" style="font-family: var(--font-grown);">
                        <p class="overline" style="font-family: var(--font-grown); margin-bottom: 8px; font-size: 0.75rem; color: var(--home-accent-soft); letter-spacing: 0.15em; font-weight: 700;">
                            <?= h(ucfirst($course->course_level ?? 'All Levels')) ?>
                        </p>

                        <h3 style="font-family: var(--font-grown); font-size: 1.8rem; line-height: 1.2; margin-bottom: 24px; color: #ffffff;">
                            <?= h($course->course_name) ?>
                        </h3>

                        <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(245, 236, 223, 0.2); padding-top: 20px;">
                            <span style="font-family: var(--font-grown); font-size: 0.9rem; color: #f5ecdf; font-weight: 400;">
                                <?= count($course->classes) ?> Classes
                            </span>

                            <span style="font-family: var(--font-grown); font-weight: 700; color: var(--home-accent-soft); font-size: 1.1rem;">
                                $<?= number_format((float)$course->course_price, 0) ?>
                            </span>
                        </div>
                    </div>

                    <div class="flip-card-back">
                        <p style="font-family: var(--font-grown); font-size: 1rem; color: #f5ecdf; line-height: 1.7; margin-bottom: 20px;">
                            <?= h($course->course_description) ?>
                        </p>
                        <a href="<?= $this->Url->build(['controller' => 'Courses', 'action' => 'view', $course->course_id]) ?>" class="btn btn-primary">
                            View Details
                        </a>
                    </div>

                </div>
            </div>

        <?php endforeach; ?>

        </div>
    <?php endif; ?>

<?php endif; ?>
