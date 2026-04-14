<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Course> $courses
 */
$this->assign('title', 'Courses');

$categoryColors = [
    'pottery' => 'pottery',
    'knitting' => 'knitting',
    'candle_making' => 'default',
    'candle making' => 'default',
];
?>

<div class="admin-page-header">
    <div class="admin-search" style="background-color: var(--admin-card-bg); border: 1px solid var(--admin-card-border); max-width: 400px; width: 100%;">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="Search courses..." id="courseSearch" style="width: 100%;">
    </div>
    <div class="d-flex gap-3 align-items-center">
        <div class="admin-tabs">
            <a href="#" class="admin-tab active" id="gridViewBtn"><i class="bi bi-grid"></i> Grid</a>
            <a href="#" class="admin-tab" id="listViewBtn"><i class="bi bi-list-ul"></i> List</a>
        </div>
        <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary">
            <i class="bi bi-plus-lg"></i> Add Course
        </a>
    </div>
</div>

<div class="rd-course-grid" id="courseGrid">
    <?php if (empty($courses) || (is_object($courses) && $courses->isEmpty())): ?>
        <div class="w-100 text-center py-5 text-muted">
            <p>No courses found.</p>
            <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="admin-btn-primary mt-2">
                <i class="bi bi-plus-lg"></i> Add Your First Course
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($courses as $course): ?>
            <?php
            $type = strtolower($course->course_type ?? 'default');
            $colorKey = $categoryColors[$type] ?? 'default';
            $isActive = $course->is_active ?? true;
            $level = ucfirst(str_replace('_', ' ', $course->course_level ?? 'All Levels'));
            $classCount = 0;
            if (isset($course->class_count)) {
                $classCount = $course->class_count;
            } elseif (isset($course->classes) && is_countable($course->classes)) {
                $classCount = count($course->classes);
            }
            ?>
            <div class="rd-course-card" data-course-name="<?= h(strtolower($course->course_name ?? '')) ?>">
                <div class="rd-course-card__bar rd-course-card__bar--<?= $colorKey ?>"></div>
                <div class="rd-course-card__body">
                    <div class="rd-course-card__tags">
                        <span class="admin-badge admin-badge-neutral"><?= h(ucfirst($type)) ?></span>
                        <span class="admin-badge admin-badge-neutral"><?= h($level) ?></span>
                        <span class="spacer"></span>
                        <?php if ($isActive): ?>
                            <span class="admin-badge admin-badge-success">Active</span>
                        <?php else: ?>
                            <span class="admin-badge admin-badge-danger">Inactive</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="rd-course-card__name"><?= h($course->course_name) ?></h3>
                    <div class="rd-course-card__stats">
                        <div class="rd-stat">
                            <span class="rd-stat__label">Price</span>
                            <span class="rd-stat__value">$<?= number_format((float)$course->course_price, 0) ?></span>
                        </div>
                        <div class="rd-stat">
                            <span class="rd-stat__label">Classes</span>
                            <span class="rd-stat__value"><?= h($classCount) ?></span>
                        </div>
                    </div>
                    <div class="admin-action-links mt-auto pt-3 border-top">
                        <a href="<?= $this->Url->build(['action' => 'edit', $course->course_id]) ?>" class="admin-action-link edit" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?= $this->Form->postLink('<i class="bi bi-trash"></i>', ['action' => 'delete', $course->course_id], [
                            'class' => 'admin-action-link delete',
                            'confirm' => 'Are you sure you want to delete this course?',
                            'title' => 'Delete',
                            'escape' => false
                        ]) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('courseSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var query = this.value.toLowerCase();
            var cards = document.querySelectorAll('.rd-course-card');
            cards.forEach(function(card) {
                var name = card.getAttribute('data-course-name') || '';
                card.style.display = name.includes(query) ? '' : 'none';
            });
        });
    }
});
</script>
