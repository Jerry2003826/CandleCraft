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

// Bootstrap FormHelper templates
$this->Form->setTemplates([
    'inputContainer' => '<div class="mb-3">{{content}}</div>',
    'inputContainerError' => '<div class="mb-3">{{content}}{{error}}</div>',
    'label' => '<label class="form-label"{{attrs}}>{{text}}</label>',
    'input' => '<input type="{{type}}" class="form-control" {{attrs}}/>',
    'select' => '<select class="form-select" {{attrs}}>{{content}}</select>',
    'textarea' => '<textarea class="form-control" {{attrs}}>{{value}}</textarea>',
    'error' => '<div class="invalid-feedback d-block">{{content}}</div>',
]);

// Bootstrap Paginator templates
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
        CandleCraft Academy - Admin
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
    <aside class="offcanvas-lg offcanvas-start" id="adminSidebar" tabindex="-1" aria-label="Admin navigation">
        <div class="offcanvas-header d-lg-none">
            <div class="d-flex align-items-center gap-3">
                <div class="sidebar-brand-icon">&#x1F3A8;</div>
                <h5 class="sidebar-brand-text mb-0">CandleCraft Academy</h5>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-0">
            <!-- Brand (desktop only) -->
            <div class="d-none d-lg-block text-center py-4 border-bottom border-white border-opacity-10">
                <div class="sidebar-brand-icon mx-auto mb-2">&#x1F3A8;</div>
                <h2 class="sidebar-brand-text">CandleCraft Academy</h2>
            </div>

            <!-- Navigation -->
            <nav class="nav flex-column py-3 flex-grow-1">
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']) ?>"
                   class="nav-link <?= $controller === 'Dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-bar-chart-line"></i><span>Dashboard</span>
                </a>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Students', 'action' => 'index']) ?>"
                   class="nav-link <?= $controller === 'Students' ? 'active' : '' ?>">
                    <i class="bi bi-mortarboard"></i><span>Students</span>
                </a>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teachers', 'action' => 'index']) ?>"
                   class="nav-link <?= $controller === 'Teachers' ? 'active' : '' ?>">
                    <i class="bi bi-person-workspace"></i><span>Teachers</span>
                </a>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Classes', 'action' => 'index']) ?>"
                   class="nav-link <?= $controller === 'Classes' ? 'active' : '' ?>">
                    <i class="bi bi-book"></i><span>Classes</span>
                </a>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Attendance', 'action' => 'index']) ?>"
                   class="nav-link <?= $controller === 'Attendance' ? 'active' : '' ?>">
                    <i class="bi bi-clipboard-check"></i><span>Attendance</span>
                </a>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Bookings', 'action' => 'index']) ?>"
                   class="nav-link <?= $controller === 'Bookings' ? 'active' : '' ?>">
                    <i class="bi bi-calendar-event"></i><span>Bookings</span>
                </a>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Courses', 'action' => 'index']) ?>"
                   class="nav-link <?= $controller === 'Courses' ? 'active' : '' ?>">
                    <i class="bi bi-palette"></i><span>Courses</span>
                </a>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Resources', 'action' => 'index']) ?>"
                   class="nav-link <?= $controller === 'Resources' ? 'active' : '' ?>">
                    <i class="bi bi-folder"></i><span>Resources</span>
                </a>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Messages', 'action' => 'index']) ?>"
                   class="nav-link <?= $controller === 'Messages' ? 'active' : '' ?>">
                    <i class="bi bi-envelope"></i><span>Messages</span>
                </a>
            </nav>

            <!-- Footer -->
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
        <!-- Navbar -->
        <nav class="sidebar-navbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle-btn btn btn-sm btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title"><?= $this->fetch('title') ?></h1>
            </div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-person-circle text-muted"></i>
                <span class="text-muted"><?= $adminName ?></span>
            </div>
        </nav>

        <!-- Content -->
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
