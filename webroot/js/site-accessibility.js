(function () {
    function addToken(value, token) {
        var tokens = (value || '').split(/\s+/).filter(Boolean);
        if (!tokens.includes(token)) {
            tokens.push(token);
        }

        return tokens.join(' ');
    }

    function ensureId(node, prefix) {
        if (!node.id) {
            ensureId.counter += 1;
            node.id = prefix + '-' + ensureId.counter;
        }

        return node.id;
    }

    ensureId.counter = 0;

    function hideDecorativeIcons(root) {
        root.querySelectorAll('i.bi').forEach(function (icon) {
            icon.setAttribute('aria-hidden', 'true');
            icon.setAttribute('focusable', 'false');
        });

        root.querySelectorAll('.admin-avatar, .admin-brand-icon').forEach(function (node) {
            node.setAttribute('aria-hidden', 'true');
        });
    }

    function labelIconOnlyControls(root) {
        root.querySelectorAll('a, button').forEach(function (control) {
            if (control.hasAttribute('aria-label')) {
                return;
            }

            var text = control.textContent ? control.textContent.trim() : '';
            if (text !== '') {
                return;
            }

            var explicitLabel = control.getAttribute('title') || control.dataset.a11yLabel;
            if (explicitLabel) {
                control.setAttribute('aria-label', explicitLabel);
            }
        });
    }

    function linkFieldDescriptions(root) {
        root.querySelectorAll('.form-group, .admin-form-group, .login-field, .mb-3').forEach(function (group) {
            var control = group.querySelector('input:not([type="hidden"]), select, textarea');
            if (!control) {
                return;
            }

            var describedBy = control.getAttribute('aria-describedby') || '';
            group.querySelectorAll('.invalid-feedback, .error-message, .field-help, .form-text, small').forEach(function (node) {
                if (node.closest('label')) {
                    return;
                }

                var id = ensureId(node, 'field-description');
                describedBy = addToken(describedBy, id);
            });

            if (describedBy !== '') {
                control.setAttribute('aria-describedby', describedBy);
            }
        });
    }

    function init(root) {
        hideDecorativeIcons(root);
        labelIconOnlyControls(root);
        linkFieldDescriptions(root);
    }

    window.CandleCraftA11y = {
        init: init,
        linkFieldDescriptions: linkFieldDescriptions,
        labelIconOnlyControls: labelIconOnlyControls,
    };

    document.addEventListener('DOMContentLoaded', function () {
        init(document);
    });
})();
