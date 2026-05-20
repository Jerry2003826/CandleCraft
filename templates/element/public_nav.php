<?php
/**
 * @var \App\View\AppView $this
 * @var string $homeUrl
 * @var string $coursesUrl
 * @var string $contactUrl
 * @var string $loginUrl
 * @var string|null $portalUrl
 * @var bool $showHomeLink
 * @var bool $showAboutLink
 * @var string|null $aboutUrl
 * @var string $contactLabel
 * @var string $menuId
 */
$portalUrl = $portalUrl ?? null;
$showHomeLink = $showHomeLink ?? false;
$showAboutLink = $showAboutLink ?? false;
$aboutUrl = $aboutUrl ?? ($homeUrl . '#main-content');
$menuId = $menuId ?? 'public-courses-menu';

$brandTitle = $this->Cms->text('global', 'branding.site_name', 'CandleCraft Academy');
$brandSubtitle = $this->Cms->text('global', 'branding.site_subtitle', 'Pottery & Knitting Tutoring');
$brandLogoUrl = $this->Cms->image('global', 'branding.logo_image');
$brandLogoAlt = $this->Cms->imageAlt('global', 'branding.logo_image', $brandTitle);
$contactLabel = $contactLabel ?? $this->Cms->text('global', 'nav.cta_label', 'Enquire');
$loginLabel = $this->Cms->text('global', 'nav.login_label', 'Log In');
?>
<nav class="hero-nav hero-nav--sticky" aria-label="Primary">
    <a href="<?= h($homeUrl) ?>" class="brand-mark">
        <?php if ($brandLogoUrl): ?>
            <img src="<?= h($brandLogoUrl) ?>" alt="<?= h($brandLogoAlt) ?>" class="brand-mark__logo" style="max-height:36px; vertical-align:middle; margin-right:8px;" />
        <?php endif; ?>
        <span class="brand-mark__title"><?= h($brandTitle) ?></span>
        <span class="brand-mark__subtitle"><?= h($brandSubtitle) ?></span>
    </a>
    <div class="hero-nav__links">
        <?php if ($showHomeLink): ?>
            <a href="<?= h($homeUrl) ?>">Home</a>
        <?php endif; ?>

        <?php if ($showAboutLink && $aboutUrl): ?>
            <a href="<?= h($aboutUrl) ?>">About</a>
        <?php endif; ?>

        <a href="<?= h($coursesUrl) ?>" class="hero-nav__courses">Courses</a>

        <a href="<?= h($contactUrl) ?>"><?= h($contactLabel) ?></a>

        <a href="<?= h($loginUrl) ?>" class="hero-nav__login"><?= h($loginLabel) ?></a>
    </div>
</nav>
