<?php
/**
 * @var \App\View\AppView $this
 */
$controller = $this->request->getParam('controller');
$identity = $this->request->getAttribute('identity');
$adminName = 'Admin';
if ($identity) {
    $adminName = h($identity->get('username'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        CandleCraft Academy - Admin
        <?php if ($this->fetch('title')): ?> | <?= $this->fetch('title') ?><?php endif; ?>
    </title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css(['admin']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">&#x1F3A8;</div>
                <h2>CandleCraft Academy</h2>
            </div>

            <nav class="sidebar-nav">
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']) ?>"
                   class="<?= $controller === 'Dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon">&#x1F4CA;</span>
                    <span>Dashboard</span>
                </a>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Students', 'action' => 'index']) ?>"
                   class="<?= $controller === 'Students' ? 'active' : '' ?>">
                    <span class="nav-icon">&#x1F393;</span>
                    <span>Students</span>
                </a>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teachers', 'action' => 'index']) ?>"
                   class="<?= $controller === 'Teachers' ? 'active' : '' ?>">
                    <span class="nav-icon">&#x1F468;&#x200D;&#x1F3EB;</span>
                    <span>Teachers</span>
                </a>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Classes', 'action' => 'index']) ?>"
                   class="<?= $controller === 'Classes' ? 'active' : '' ?>">
                    <span class="nav-icon">&#x1F4D6;</span>
                    <span>Classes</span>
                </a>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Messages', 'action' => 'index']) ?>"
                   class="<?= $controller === 'Messages' ? 'active' : '' ?>">
                    <span class="nav-icon">&#x2709;</span>
                    <span>Messages</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="<?= $this->Url->build(['prefix' => false, 'controller' => 'Users', 'action' => 'logout']) ?>">
                    <span class="nav-icon">&#x1F6AA;</span>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <div class="main-content">
            <div class="top-bar">
                <h1><?= $this->fetch('title') ?></h1>
                <div class="user-info">
                    <span>Welcome, <?= $adminName ?></span>
                </div>
            </div>

            <div class="content-area">
                <?= $this->Flash->render() ?>
                <?= $this->fetch('content') ?>
            </div>
        </div>
    </div>
</body>
</html>
