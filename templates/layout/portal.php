<?php
/**
 * @var \App\View\AppView $this
 */
$controller = $this->request->getParam('controller');
$action = $this->request->getParam('action');
$identity = $this->request->getAttribute('identity');
$portalContext = $portalContext ?? [
    'title' => 'Portal',
    'icon' => 'bi bi-palette',
    'welcome' => 'Portal',
    'nav' => [],
];
$displayName = 'User';
if ($identity) {
    $displayName = h((string)($identity->get('username') ?: $identity->get('email')));
}

$this->Form->setTemplates([
    'inputContainer' => '<div class="mb-3">{{content}}</div>',
    'inputContainerError' => '<div class="mb-3">{{content}}{{error}}</div>',
    'label' => '<label class="form-label"{{attrs}}>{{text}}</label>',
    'input' => '<input type="{{type}}" name="{{name}}" class="form-control" {{attrs}}/>',
    'select' => '<select class="form-select" {{attrs}}>{{content}}</select>',
    'textarea' => '<textarea class="form-control" {{attrs}}>{{value}}</textarea>',
    'error' => '<div class="invalid-feedback d-block">{{content}}</div>',
]);

$this->Paginator->setTemplates([
    'first' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
    'prevActive' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
    'prevDisabled' => '<li class="page-item disabled"><span class="page-link">{{text}}</span></li>',
    'number' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
    'current' => '<li class="page-item active"><span class="page-link">{{text}}</span></li>',
    'nextActive' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
    'nextDisabled' => '<li class="page-item disabled"><span class="page-link">{{text}}</span></li>',
    'last' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
]);
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
    <?= $this->Html->css(['admin-bootstrap', 'bootstrap-icons', 'admin']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body>
    <a href="#main-content" class="visually-hidden-focusable">Skip to main content</a>

    <!-- Sidebar -->
    <aside class="offcanvas-lg offcanvas-start" id="portalSidebar" tabindex="-1" aria-label="Portal navigation">
        <div class="offcanvas-header d-lg-none">
            <div class="d-flex align-items-center gap-3">
                <div class="sidebar-brand-icon"><i class="<?= $portalContext['icon'] ?>"></i></div>
                <h5 class="sidebar-brand-text mb-0"><?= h($portalContext['title']) ?></h5>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-0">
            <div class="d-none d-lg-block text-center py-4 border-bottom border-white border-opacity-10">
                <div class="sidebar-brand-icon mx-auto mb-2"><i class="<?= $portalContext['icon'] ?>"></i></div>
                <h2 class="sidebar-brand-text"><?= h($portalContext['title']) ?></h2>
            </div>

            <nav class="nav flex-column py-3 flex-grow-1">
                <?php foreach ($portalContext['nav'] as $item): ?>
                    <?php
                    $isActive = $controller === $item['controller'];
                    if ($isActive && isset($item['action'])) {
                        $isActive = $action === $item['action'];
                    }
                    ?>
                    <a href="<?= $this->Url->build($item['url']) ?>"
                       class="nav-link <?= $isActive ? 'active' : '' ?>">
                        <i class="<?= $item['icon'] ?>"></i><span><?= h($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-footer">
                <?= $this->Form->postLink(
                    '<i class="bi bi-box-arrow-left"></i><span>Logout</span>',
                    ['prefix' => false, 'controller' => 'Users', 'action' => 'logout'],
                    ['escape' => false, 'class' => 'nav-link']
                ) ?>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <div class="sidebar-main d-flex flex-column">
        <nav class="sidebar-navbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle-btn btn btn-sm btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#portalSidebar">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title"><?= $this->fetch('title') ?></h1>
            </div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-person-circle text-muted"></i>
                <span class="text-muted"><?= h($portalContext['welcome']) ?>, <?= $displayName ?></span>
            </div>
        </nav>

        <main class="container-fluid p-4" id="main-content" role="main" tabindex="-1">
            <div aria-live="polite"><?= $this->Flash->render() ?></div>
            <?= $this->fetch('content') ?>
        </main>
    </div>

    <script src="/js/admin-bootstrap.js"></script>
    <script src="/js/admin-app.js"></script>
    <?= $this->fetch('script') ?>
</body>
</html>
