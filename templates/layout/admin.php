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
    'input' => '<input type="{{type}}" name="{{name}}" class="form-control" {{attrs}}/>',
    'select' => '<select class="form-select" {{attrs}}>{{content}}</select>',
    'textarea' => '<textarea class="form-control" {{attrs}}>{{value}}</textarea>',
    'error' => '<div class="invalid-feedback d-block">{{content}}</div>',
]);

// Bootstrap Paginator templates
$this->Paginator->setTemplates([
    'first' => '<div class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></div>',
    'prevActive' => '<div class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></div>',
    'prevDisabled' => '<div class="page-item disabled"><span class="page-link">{{text}}</span></div>',
    'number' => '<div class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></div>',
    'current' => '<div class="page-item active"><span class="page-link">{{text}}</span></div>',
    'nextActive' => '<div class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></div>',
    'nextDisabled' => '<div class="page-item disabled"><span class="page-link">{{text}}</span></div>',
    'last' => '<div class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></div>',
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        CandleCraft Academy - Admin Portal
        <?php if ($this->fetch('title')): ?> | <?= $this->fetch('title') ?><?php endif; ?>
    </title>
    <script>
        // Apply theme immediately to prevent FOUC
        const savedTheme = localStorage.getItem('admin-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css(['admin-bootstrap', 'bootstrap-icons', 'admin']) ?>
    <link rel="stylesheet" href="/css/redesign.css?v=<?= time() ?>">
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body class="redesign">
    <a href="#main-content" class="visually-hidden-focusable">Skip to main content</a>

    <!-- Sidebar -->
    <aside class="offcanvas-lg offcanvas-start" id="adminSidebar" tabindex="-1" aria-label="Admin navigation">
        <div class="offcanvas-header d-lg-none">
            <div class="d-flex align-items-center gap-3">
                <div class="admin-brand-icon"></div>
                <h5 class="admin-brand-text mb-0">CandleCraft</h5>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column" style="height: 100%;">
            <div class="flex-grow-1" data-sidebar-scroll-key="admin-sidebar-scroll" style="overflow-y: auto; padding-right: 8px; margin-right: -8px;">
                <!-- Brand (redesign) -->
                <div class="admin-brand">
                    <div class="admin-brand-icon"></div>
                    <div class="admin-brand-text">CandleCraft</div>
                </div>

                <!-- Navigation -->
                <nav class="admin-nav" aria-label="Admin sections">
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']) ?>"
                       class="nav-link <?= $controller === 'Dashboard' ? 'active' : '' ?>">
                        <i class="bi bi-grid-1x2" aria-hidden="true"></i><span>Dashboard</span>
                    </a>
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Students', 'action' => 'index']) ?>"
                       class="nav-link <?= $controller === 'Students' ? 'active' : '' ?>">
                        <i class="bi bi-people" aria-hidden="true"></i><span>Customers</span>
                    </a>
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teachers', 'action' => 'index']) ?>"
                       class="nav-link <?= $controller === 'Teachers' ? 'active' : '' ?>">
                        <i class="bi bi-person-badge" aria-hidden="true"></i><span>Teachers</span>
                    </a>
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Classes', 'action' => 'index']) ?>"
                       class="nav-link <?= $controller === 'Classes' ? 'active' : '' ?>">
                        <i class="bi bi-calendar3" aria-hidden="true"></i><span>Classes</span>
                    </a>
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Attendance', 'action' => 'index']) ?>"
                       class="nav-link <?= $controller === 'Attendance' ? 'active' : '' ?>">
                        <i class="bi bi-check-circle" aria-hidden="true"></i><span>Attendance</span>
                    </a>
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Bookings', 'action' => 'index']) ?>"
                       class="nav-link <?= $controller === 'Bookings' ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-text" aria-hidden="true"></i><span>Bookings</span>
                    </a>
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Courses', 'action' => 'index']) ?>"
                       class="nav-link <?= $controller === 'Courses' ? 'active' : '' ?>">
                        <i class="bi bi-book" aria-hidden="true"></i><span>Courses</span>
                    </a>
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Resources', 'action' => 'index']) ?>"
                       class="nav-link <?= $controller === 'Resources' ? 'active' : '' ?>">
                        <i class="bi bi-download" aria-hidden="true"></i><span>Resources</span>
                    </a>
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Messages', 'action' => 'index']) ?>"
                       class="nav-link <?= $controller === 'Messages' ? 'active' : '' ?>">
                        <i class="bi bi-chat-square-text" aria-hidden="true"></i><span>Enquiries</span>
                    </a>
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'PaymentWebhookIncidents', 'action' => 'index']) ?>"
                       class="nav-link <?= $controller === 'PaymentWebhookIncidents' ? 'active' : '' ?>">
                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span>Webhook Incidents</span>
                    </a>
                </nav>
            </div>

            <!-- Footer -->
            <div class="admin-nav-bottom mt-auto pt-4">
                <button type="button" class="nav-link nav-link--button" id="themeToggle" aria-pressed="false">
                    <i class="bi bi-moon" aria-hidden="true"></i><span id="themeToggleText">Dark Mode</span>
                </button>
                <?= $this->Form->create(null, [
                    'url' => ['prefix' => false, 'controller' => 'Users', 'action' => 'logout'],
                    'class' => 'm-0',
                ]) ?>
                    <?= $this->Form->button('<i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Logout</span>', [
                        'type' => 'submit',
                        'escape' => false,
                        'class' => 'nav-link nav-link--button logout',
                    ]) ?>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <div class="sidebar-main d-flex flex-column">
        <!-- Navbar -->
        <nav class="sidebar-navbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button
                    class="sidebar-toggle-btn btn btn-sm btn-outline-secondary d-lg-none"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#adminSidebar"
                    aria-controls="adminSidebar"
                    aria-expanded="false"
                    aria-label="Open admin navigation"
                >
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <h1 class="page-title"><?= $this->fetch('title') ?></h1>
            </div>
            <div class="admin-header-actions d-flex align-items-center gap-2">
                <span class="admin-user-label"><?= $adminName ?></span>
                <div class="admin-avatar" title="<?= $adminName ?>"></div>
            </div>
        </nav>

        <!-- Content -->
        <main class="container-fluid" id="main-content" role="main" tabindex="-1">
            <div aria-live="polite"><?= $this->Flash->render() ?></div>
            <?= $this->fetch('content') ?>
        </main>
    </div>

    <script src="/js/site-accessibility.js"></script>
    <script src="/js/admin-bootstrap.js"></script>
    <script src="/js/admin-app.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const themeToggle = document.getElementById('themeToggle');
        const sidebarScrollContainer = document.querySelector('[data-sidebar-scroll-key="admin-sidebar-scroll"]');
        const sidebarToggle = document.querySelector('[data-bs-target="#adminSidebar"]');
        const sidebarElement = document.getElementById('adminSidebar');
        if (themeToggle) {
            const themeToggleText = document.getElementById('themeToggleText');
            const themeToggleIcon = themeToggle.querySelector('i');
            
            function setTheme(isDark) {
                if (isDark) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    themeToggleText.textContent = 'Light Mode';
                    themeToggleIcon.classList.remove('bi-moon');
                    themeToggleIcon.classList.add('bi-sun');
                    localStorage.setItem('admin-theme', 'dark');
                    themeToggle.setAttribute('aria-pressed', 'true');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                    themeToggleText.textContent = 'Dark Mode';
                    themeToggleIcon.classList.remove('bi-sun');
                    themeToggleIcon.classList.add('bi-moon');
                    localStorage.setItem('admin-theme', 'light');
                    themeToggle.setAttribute('aria-pressed', 'false');
                }
            }

            // Initialize toggle state based on current theme
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            setTheme(isDark);

            themeToggle.addEventListener('click', (e) => {
                e.preventDefault();
                const currentlyDark = document.documentElement.getAttribute('data-theme') === 'dark';
                setTheme(!currentlyDark);
            });
        }

        if (sidebarToggle && sidebarElement && window.bootstrap?.Offcanvas) {
            sidebarElement.addEventListener('shown.bs.offcanvas', () => {
                sidebarToggle.setAttribute('aria-expanded', 'true');
            });
            sidebarElement.addEventListener('hidden.bs.offcanvas', () => {
                sidebarToggle.setAttribute('aria-expanded', 'false');
            });
        }

        if (sidebarScrollContainer) {
            const storageKey = sidebarScrollContainer.dataset.sidebarScrollKey;
            const savedScrollTop = sessionStorage.getItem(storageKey);
            if (savedScrollTop !== null) {
                sidebarScrollContainer.scrollTop = Number(savedScrollTop) || 0;
            }

            const persistScrollPosition = () => {
                sessionStorage.setItem(storageKey, String(sidebarScrollContainer.scrollTop));
            };

            sidebarScrollContainer.addEventListener('scroll', persistScrollPosition, { passive: true });
            window.addEventListener('pagehide', persistScrollPosition);
        }
    });
    </script>

    <?= $this->fetch('script') ?>
</body>
</html>
