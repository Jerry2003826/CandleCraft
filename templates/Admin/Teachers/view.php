<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Teacher $teacher
 */
$this->assign('title', 'Teacher Details');
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <a href="#" onclick="history.back(); return false;" class="admin-back-link mb-0">
        <i class="bi bi-arrow-left"></i> Back
    </a>
    <div class="d-flex gap-2">
        <a href="<?= $this->Url->build(['action' => 'edit', $teacher->teacher_id]) ?>" class="admin-btn-secondary" style="color: #D97706; padding: 6px 16px; font-size: 13px;">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <?= $this->Form->postLink(
            '<i class="bi bi-trash me-1"></i> Delete',
            ['action' => 'delete', $teacher->teacher_id],
            [
                'confirm' => __('Are you sure you want to delete {0}?', $teacher->teacher_name),
                'class' => 'admin-btn-secondary',
                'style' => 'color: #EF4444; padding: 6px 16px; font-size: 13px;',
                'escape' => false,
            ]
        ) ?>
    </div>
</div>

<div class="admin-form-card mb-4" style="max-width: 100%; padding: 32px;">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 1px solid var(--admin-card-border);">
        <h2 class="admin-form-title" style="font-size: 20px; margin: 0;"><?= h($teacher->teacher_name) ?></h2>
        <?php
            $statusClass = 'admin-badge-neutral';
            if ($teacher->teacher_status === 'active') $statusClass = 'admin-badge-success';
            if ($teacher->teacher_status === 'inactive') $statusClass = 'admin-badge-danger';
        ?>
        <span class="admin-badge <?= $statusClass ?>" style="padding: 6px 12px; font-size: 13px;"><?= h(ucfirst((string)$teacher->teacher_status)) ?></span>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Name</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= h($teacher->teacher_name) ?></div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Email</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= $teacher->user ? h($teacher->user->email) : '-' ?></div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Phone</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= h($teacher->phone_number ?: 'N/A') ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Specialization</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= h($teacher->specialization ?: 'N/A') ?></div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Hire Date</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= $teacher->hire_date ? $teacher->hire_date->format('j M Y') : 'N/A' ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($teacher->classes)): ?>
<div class="admin-table-card">
    <div style="padding: 20px 24px; border-bottom: 1.5px solid var(--admin-card-border); background: rgba(210, 154, 88, 0.03);">
        <h3 class="admin-form-title" style="font-size: 18px; margin: 0;">Assigned Classes</h3>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Class Code</th>
                    <th>Course</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Location</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teacher->classes as $class): ?>
                <tr>
                    <td><p class="admin-table-primary-text"><?= h($class->class_code) ?></p></td>
                    <td><p class="admin-table-secondary-text"><?= $class->course ? h($class->course->course_name) : '-' ?></p></td>
                    <td><p class="admin-table-secondary-text"><?= $class->start_datetime ? $class->start_datetime->format('j M Y, g:ia') : '-' ?></p></td>
                    <td><p class="admin-table-secondary-text"><?= $class->end_datetime ? $class->end_datetime->format('j M Y, g:ia') : '-' ?></p></td>
                    <td><p class="admin-table-secondary-text"><?= h($class->location) ?></p></td>
                    <td>
                        <?php
                            $cStatusClass = 'admin-badge-neutral';
                            if ($class->class_status === 'scheduled') $cStatusClass = 'admin-badge-info';
                            if ($class->class_status === 'ongoing') $cStatusClass = 'admin-badge-success';
                            if ($class->class_status === 'cancelled') $cStatusClass = 'admin-badge-danger';
                            if ($class->class_status === 'full') $cStatusClass = 'admin-badge-warning';
                        ?>
                        <span class="admin-badge <?= $cStatusClass ?>"><?= h(ucfirst((string)$class->class_status)) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
