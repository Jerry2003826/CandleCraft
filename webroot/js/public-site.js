(function () {
    function closeDisclosure(disclosure) {
        var button = disclosure.querySelector('.nav-dropdown__toggle');
        var panel = disclosure.querySelector('[data-nav-panel]');
        if (!button || !panel) {
            return;
        }

        disclosure.removeAttribute('data-open');
        button.setAttribute('aria-expanded', 'false');
        panel.hidden = true;
    }

    function openDisclosure(disclosure, focusFirstLink) {
        var button = disclosure.querySelector('.nav-dropdown__toggle');
        var panel = disclosure.querySelector('[data-nav-panel]');
        if (!button || !panel) {
            return;
        }

        disclosure.setAttribute('data-open', 'true');
        button.setAttribute('aria-expanded', 'true');
        panel.hidden = false;

        if (focusFirstLink) {
            var firstLink = panel.querySelector('a');
            if (firstLink) {
                firstLink.focus();
            }
        }
    }

    function initPublicNav(root) {
        var disclosures = root.querySelectorAll('[data-nav-disclosure]');
        disclosures.forEach(function (disclosure) {
            var button = disclosure.querySelector('.nav-dropdown__toggle');
            var panel = disclosure.querySelector('[data-nav-panel]');
            if (!button || !panel) {
                return;
            }

            closeDisclosure(disclosure);

            button.addEventListener('click', function () {
                var isOpen = button.getAttribute('aria-expanded') === 'true';
                disclosures.forEach(closeDisclosure);
                if (!isOpen) {
                    openDisclosure(disclosure, false);
                }
            });

            button.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    disclosures.forEach(closeDisclosure);
                    openDisclosure(disclosure, true);
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeDisclosure(disclosure);
                }
            });

            panel.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeDisclosure(disclosure);
                    button.focus();
                }
            });

            disclosure.addEventListener('focusout', function () {
                window.requestAnimationFrame(function () {
                    if (!disclosure.contains(document.activeElement)) {
                        closeDisclosure(disclosure);
                    }
                });
            });

            panel.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    closeDisclosure(disclosure);
                });
            });
        });

        document.addEventListener('click', function (event) {
            disclosures.forEach(function (disclosure) {
                if (!disclosure.contains(event.target)) {
                    closeDisclosure(disclosure);
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initPublicNav(document);
    });
})();
