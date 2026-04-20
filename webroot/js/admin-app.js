/* CandleCraft Academy - Admin & Portal JS */
document.addEventListener('DOMContentLoaded', function () {
    window.CandleCraftA11y?.init(document);

    /* ===== Schedule Page — View Toggle ===== */
    var viewBtns = document.querySelectorAll('.sp-view-btn');
    var calView = document.getElementById('calendarView');
    var listView = document.getElementById('listView');

    if (document.querySelector('[data-view-toggle-managed="custom"]')) {
        // Page-specific scripts handle these toggles.
    } else if (viewBtns.length && calView && listView) {
        var savedView = localStorage.getItem('schedule_view');
        initialiseViewButtons();
        if (savedView === 'list') {
            switchView('list');
        } else {
            switchView('calendar');
        }

        viewBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                switchView(btn.dataset.view);
            });
        });
    }

    function switchView(view) {
        if (!calView || !listView) return;
        viewBtns.forEach(function (b) {
            var isActive = b.dataset.view === view;
            b.classList.toggle('active', isActive);
            b.setAttribute('aria-pressed', String(isActive));
        });
        if (view === 'list') {
            calView.classList.add('d-none');
            listView.classList.remove('d-none');
            calView.hidden = true;
            listView.hidden = false;
        } else {
            listView.classList.add('d-none');
            calView.classList.remove('d-none');
            listView.hidden = true;
            calView.hidden = false;
        }
        localStorage.setItem('schedule_view', view);
    }

    function initialiseViewButtons() {
        viewBtns.forEach(function (btn) {
            var targetId = btn.dataset.view === 'list' ? 'listView' : 'calendarView';
            btn.setAttribute('aria-controls', targetId);
            btn.setAttribute('aria-pressed', String(btn.classList.contains('active')));
            if (btn.tagName === 'BUTTON' && !btn.getAttribute('type')) {
                btn.setAttribute('type', 'button');
            }
        });

        if (calView) {
            calView.hidden = calView.classList.contains('d-none');
        }
        if (listView) {
            listView.hidden = listView.classList.contains('d-none');
        }
    }

    /* ===== Week Calendar — Scroll to current time / first event ===== */
    var wcScroll = document.getElementById('wcScroll');
    if (wcScroll) {
        var nowLine = document.getElementById('wcNowLine');
        if (nowLine) {
            var offset = nowLine.offsetTop - 100;
            wcScroll.scrollTop = Math.max(0, offset);
        } else {
            var firstEvt = wcScroll.querySelector('.wc-evt');
            if (firstEvt) {
                wcScroll.scrollTop = Math.max(0, firstEvt.offsetTop - 60);
            }
        }
    }

    /* ===== Calendar Event Tooltips ===== */
    document.querySelectorAll('.wc-evt[title]').forEach(function (el) {
        new bootstrap.Tooltip(el, {
            placement: 'top',
            trigger: 'hover focus',
        });
    });

    /* ===== Anchor scroll from calendar to list ===== */
    document.querySelectorAll('.wc-evt[href^="#booking-"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            switchView('list');
            var target = document.querySelector(link.getAttribute('href'));
            if (target) {
                setTimeout(function () {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    target.style.background = '#fff8e1';
                    setTimeout(function () { target.style.background = ''; }, 2000);
                }, 100);
            }
        });
    });
});
