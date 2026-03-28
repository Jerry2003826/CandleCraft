<?php
/**
 * @var \App\View\AppView $this
 */
$controller = $this->request->getParam('controller');
$identity = $this->request->getAttribute('identity');
$portalContext = $portalContext ?? [
    'title' => 'Portal',
    'icon' => '&#x1F3A8;',
    'welcome' => 'Portal',
    'nav' => [],
];
$displayName = 'User';
if ($identity) {
    $displayName = h((string)($identity->get('username') ?: $identity->get('email')));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        CandleCraft Academy - <?= h($portalContext['title']) ?>
        <?php if ($this->fetch('title')): ?> | <?= $this->fetch('title') ?><?php endif; ?>
    </title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css(['admin']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <div class="admin-wrapper portal-wrapper">
        <aside class="sidebar portal-sidebar" role="navigation" aria-label="Portal navigation">
            <div class="sidebar-brand">
                <div class="brand-icon"><?= $portalContext['icon'] ?></div>
                <h2><?= h($portalContext['title']) ?></h2>
            </div>

            <nav class="sidebar-nav">
                <?php foreach ($portalContext['nav'] as $item): ?>
                    <?php
                    $isActive = $controller === $item['controller'];
                    if ($isActive && isset($item['action'])) {
                        $isActive = $this->request->getParam('action') === $item['action'];
                    }
                    ?>
                    <a href="<?= $this->Url->build($item['url']) ?>"
                       class="<?= $isActive ? 'active' : '' ?>">
                        <span class="nav-icon"><?= $item['icon'] ?></span>
                        <span><?= h($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-footer">
                <?= $this->Form->postLink(
                    '<span class="nav-icon">&#x1F6AA;</span><span>Logout</span>',
                    ['prefix' => false, 'controller' => 'Users', 'action' => 'logout'],
                    ['escape' => false]
                ) ?>
            </div>
        </aside>

        <div class="main-content">
            <div class="top-bar">
                <h1><?= $this->fetch('title') ?></h1>
                <div class="user-info">
                    <span><?= h($portalContext['welcome']) ?>, <?= $displayName ?></span>
                </div>
            </div>

            <div class="content-area" id="main-content" role="main" tabindex="-1">
                <div aria-live="polite"><?= $this->Flash->render() ?></div>
                <?= $this->fetch('content') ?>
            </div>
        </div>
    </div>
</body>
</html>
