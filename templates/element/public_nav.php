<?php
/**
 * @var \App\View\AppView $this
 * @var string $homeUrl
 * @var string $coursesUrl
 * @var string $contactUrl
 * @var string $loginUrl
 * @var string|null $portalUrl
 * @var bool $showHomeLink
 * @var string $contactLabel
 * @var string $menuId
 */
$portalUrl = $portalUrl ?? null;
$showHomeLink = $showHomeLink ?? true;
$contactLabel = $contactLabel ?? 'Enquire';
$menuId = $menuId ?? 'public-courses-menu';
?>
<nav class="hero-nav" aria-label="Primary">
    <a href="<?= h($homeUrl) ?>" class="brand-mark">
        <span class="brand-mark__title">CandleCraft Academy</span>
        <span class="brand-mark__subtitle">Pottery &amp; Knitting Tutoring</span>
    </a>
    <div class="hero-nav__links">
        <?php if ($showHomeLink): ?>
            <a href="<?= h($homeUrl) ?>">Home</a>
        <?php endif; ?>

        <div class="nav-dropdown" data-nav-disclosure>
            <div class="nav-dropdown__trigger">
                <a href="<?= h($coursesUrl) ?>" class="nav-dropdown__primary-link">Courses</a>
            </div>
            <div class="nav-dropdown__menu" id="<?= h($menuId) ?>" data-nav-panel>
                <div class="nav-dropdown__menu-inner">
                    <a href="<?= h($coursesUrl) ?>?type=pottery">Pottery</a>
                    <a href="<?= h($coursesUrl) ?>?type=knitting">Knitting</a>
                </div>
            </div>
        </div>

        <a href="<?= h($contactUrl) ?>"><?= h($contactLabel) ?></a>

        <?php if ($portalUrl): ?>
            <a href="<?= h($portalUrl) ?>" class="hero-nav__login">My Portal</a>
        <?php else: ?>
            <a href="<?= h($loginUrl) ?>" class="hero-nav__login">Log In</a>
        <?php endif; ?>
    </div>
</nav>
