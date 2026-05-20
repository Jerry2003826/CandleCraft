<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Course> $courses
 */
$this->assign('title', 'Courses');

$courseList = is_object($courses) && method_exists($courses, 'items')
    ? $courses->items()
    : (is_array($courses) ? $courses : iterator_to_array($courses));
?>

<div class="admin-page-header admin-list-toolbar">
    <div class="admin-list-toolbar__leading">
        <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary">
            <i class="bi bi-plus-lg"></i> Add Course
        </a>
        <div class="admin-pill-tabs" role="group" aria-label="Filter by course type">
            <button type="button" class="admin-pill-tab active" data-filter="all">All</button>
            <button type="button" class="admin-pill-tab" data-filter="pottery">Pottery</button>
            <button type="button" class="admin-pill-tab" data-filter="knitting">Knitting</button>
        </div>
    </div>
    <form method="get" action="<?= $this->Url->build(['action' => 'index']) ?>" role="search" class="admin-list-toolbar__search">
        <div class="admin-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <label for="courseSearch" class="visually-hidden">Search courses</label>
            <input type="text" name="search" placeholder="Search courses..." id="courseSearch"
                   aria-label="Search courses" value="<?= h($search ?? '') ?>">
        </div>
    </form>
</div>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Type</th>
                    <th>Level</th>
                    <th>Price</th>
                    <th>Classes</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($courseList === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No courses found.
                            <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary ms-2">
                                <i class="bi bi-plus-lg"></i> Add Your First Course
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($courseList as $course): ?>
                        <?php
                        $type = ucfirst(strtolower($course->course_type ?? ''));
                        $isActive = $course->is_active ?? true;
                        $level = ucfirst(str_replace('_', ' ', $course->course_level ?? 'All Levels'));
                        $classCount = 0;
                        if (isset($course->class_count)) {
                            $classCount = $course->class_count;
                        } elseif (isset($course->classes) && is_countable($course->classes)) {
                            $classCount = count($course->classes);
                        }
                        $classesUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Classes', 'action' => 'index', '?' => ['search' => $course->course_name]]);
                        ?>
                        <tr class="admin-clickable-row" data-href="<?= h($classesUrl) ?>" data-course-name="<?= h(strtolower($course->course_name ?? '')) ?>" data-course-type="<?= h(strtolower($course->course_type ?? '')) ?>" tabindex="0" role="link" aria-label="View classes for <?= h($course->course_name) ?>">
                            <td>
                                <p class="admin-table-primary-text"><?= h($course->course_name) ?></p>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge-neutral"><?= h($type) ?></span>
                            </td>
                            <td>
                                <p class="admin-table-secondary-text"><?= h($level) ?></p>
                            </td>
                            <td>
                                <p class="admin-table-primary-text">$<?= number_format((float)$course->course_price, 0) ?></p>
                            </td>
                            <td>
                                <a href="<?= h($classesUrl) ?>"
                                   class="admin-table-secondary-text" style="text-decoration: none; color: inherit;"
                                   title="View classes for <?= h($course->course_name) ?>">
                                    <?= h($classCount) ?> <i class="bi bi-box-arrow-up-right" style="font-size: 11px; opacity: 0.5;"></i>
                                </a>
                            </td>
                            <td>
                                <?php if ($isActive): ?>
                                    <span class="admin-badge admin-badge-success">Active</span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="admin-action-links justify-content-end">
                                    <a href="<?= h($classesUrl) ?>" class="admin-action-link" title="View classes" aria-label="View classes for <?= h($course->course_name) ?>" style="color: var(--admin-brand-icon);">
                                        <i class="bi bi-calendar3"></i>
                                    </a>
                                    <a href="<?= $this->Url->build(['action' => 'edit', $course->course_id]) ?>" class="admin-action-link edit" title="Edit" aria-label="Edit <?= h($course->course_name) ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?= $this->Form->create(null, [
                                        'url' => ['action' => 'delete', $course->course_id],
                                        'class' => 'd-inline m-0',
                                    ]) ?>
                                        <?= $this->Form->button('<i class="bi bi-trash"></i>', [
                                            'class' => 'admin-action-link delete',
                                            'type' => 'submit',
                                            'title' => 'Delete',
                                            'aria-label' => 'Delete ' . $course->course_name,
                                            'onclick' => "return confirm('Are you sure you want to delete this course?');",
                                            'escapeTitle' => false,
                                        ]) ?>
                                    <?= $this->Form->end() ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="noTypeResults" style="display:none;">
                    <td colspan="7" class="text-center text-muted py-4">No courses match this filter.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
    .admin-clickable-row {
        cursor: pointer;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form[role="search"]');
    var searchInput = document.getElementById('courseSearch');
    if (!form || !searchInput) return;
    var timer;
    searchInput.addEventListener('input', function() {
        clearTimeout(timer);
        timer = setTimeout(function() { form.submit(); }, 400);
    });

    var filterBtns = document.querySelectorAll('.admin-pill-tab[data-filter]');
    var courseRows = document.querySelectorAll('.admin-clickable-row[data-course-type]');
    var noResultsRow = document.getElementById('noTypeResults');
    var activeFilter = 'all';

    filterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeFilter = btn.dataset.filter;
            filterBtns.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var visible = 0;
            courseRows.forEach(function (row) {
                var match = activeFilter === 'all' || row.dataset.courseType === activeFilter;
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (noResultsRow) noResultsRow.style.display = visible === 0 ? '' : 'none';
        });
    });

    document.querySelectorAll('.admin-clickable-row[data-href]').forEach(function (row) {
        function openRow(event) {
            if (event.target.closest('a, button, form, input, select, textarea, label')) {
                return;
            }
            window.location.href = row.dataset.href;
        }

        row.addEventListener('click', openRow);
        row.addEventListener('keydown', function (event) {
            if (event.target.closest('a, button, form, input, select, textarea, label')) {
                return;
            }
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            event.preventDefault();
            window.location.href = row.dataset.href;
        });
    });
});
</script>
