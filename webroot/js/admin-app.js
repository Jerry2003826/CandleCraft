/* CandleCraft Academy - Admin & Portal JS */
document.addEventListener('DOMContentLoaded', function () {

    /* Auto-hide flash alerts */
    document.querySelectorAll('.alert[data-autohide]').forEach(function (el) {
        setTimeout(function () {
            var alert = bootstrap.Alert.getOrCreateInstance(el);
            if (alert) alert.close();
        }, 5000);
    });

    /* ===== Schedule Page — View Toggle ===== */
    var viewBtns = document.querySelectorAll('.sp-view-btn');
    var calView = document.getElementById('calendarView');
    var listView = document.getElementById('listView');

    if (viewBtns.length && calView && listView) {
        var savedView = localStorage.getItem('schedule_view');
        if (savedView === 'list') {
            switchView('list');
        }

        viewBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                switchView(btn.dataset.view);
            });
        });
    }

    function switchView(view) {
        if (!calView || !listView) return;
        viewBtns.forEach(function (b) { b.classList.remove('active'); });
        if (view === 'list') {
            calView.classList.add('d-none');
            listView.classList.remove('d-none');
            document.querySelector('[data-view="list"]')?.classList.add('active');
        } else {
            listView.classList.add('d-none');
            calView.classList.remove('d-none');
            document.querySelector('[data-view="calendar"]')?.classList.add('active');
        }
        localStorage.setItem('schedule_view', view);
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
            trigger: 'hover',
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
