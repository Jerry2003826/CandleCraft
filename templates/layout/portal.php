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
        CandleCraft Academy - <?= h($portalContext['title']) ?>
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
    <aside class="offcanvas-lg offcanvas-start" id="portalSidebar" tabindex="-1" aria-label="Portal navigation">
        <div class="offcanvas-header d-lg-none">
            <div class="d-flex align-items-center gap-3">
                <div class="admin-brand-icon"></div>
                <h5 class="admin-brand-text mb-0">CandleCraft</h5>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column" style="height: 100%;">
            <div class="flex-grow-1" data-sidebar-scroll-key="portal-sidebar-scroll" style="overflow-y: auto; padding-right: 8px; margin-right: -8px;">
                <!-- Brand (redesign) -->
                <div class="admin-brand">
                    <div class="admin-brand-icon"></div>
                    <div class="admin-brand-text">CandleCraft</div>
                </div>

                <!-- Navigation -->
                <nav class="admin-nav">
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
            </div>

            <!-- Footer -->
            <div class="admin-nav-bottom mt-auto pt-4">
                <a href="#" class="nav-link" id="themeToggle">
                    <i class="bi bi-moon"></i><span id="themeToggleText">Dark Mode</span>
                </a>
                <a href="#" class="nav-link" id="settingsPlaceholder">
                    <i class="bi bi-gear"></i><span>Settings</span>
                </a>
                <?= $this->Form->postLink(
                    '<i class="bi bi-box-arrow-right"></i><span>Logout</span>',
                    ['prefix' => false, 'controller' => 'Users', 'action' => 'logout'],
                    ['escape' => false, 'class' => 'nav-link logout']
                ) ?>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <div class="sidebar-main d-flex flex-column">
        <!-- Navbar -->
        <nav class="sidebar-navbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle-btn btn btn-sm btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#portalSidebar">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title"><?= $this->fetch('title') ?></h1>
            </div>
            <div class="admin-header-actions">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-bell text-muted" style="font-size: 20px;"></i>
                    <div class="admin-avatar" title="<?= $displayName ?>"></div>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <main class="container-fluid" id="main-content" role="main" tabindex="-1">
            <div aria-live="polite"><?= $this->Flash->render() ?></div>
            <?= $this->fetch('content') ?>
        </main>
    </div>

    <script src="/js/admin-bootstrap.js"></script>
    <script src="/js/admin-app.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const themeToggle = document.getElementById('themeToggle');
        const settingsPlaceholder = document.getElementById('settingsPlaceholder');
        const sidebarScrollContainer = document.querySelector('[data-sidebar-scroll-key="portal-sidebar-scroll"]');
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
                } else {
                    document.documentElement.removeAttribute('data-theme');
                    themeToggleText.textContent = 'Dark Mode';
                    themeToggleIcon.classList.remove('bi-sun');
                    themeToggleIcon.classList.add('bi-moon');
                    localStorage.setItem('admin-theme', 'light');
                }
            }

            // Initialize toggle state based on current theme
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (isDark) {
                themeToggleText.textContent = 'Light Mode';
                themeToggleIcon.classList.remove('bi-moon');
                themeToggleIcon.classList.add('bi-sun');
            }

            themeToggle.addEventListener('click', (e) => {
                e.preventDefault();
                const currentlyDark = document.documentElement.getAttribute('data-theme') === 'dark';
                setTheme(!currentlyDark);
            });
        }

        if (settingsPlaceholder) {
            settingsPlaceholder.addEventListener('click', (e) => {
                e.preventDefault();
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
