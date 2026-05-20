<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 */
$this->assign('title', 'Class Details');
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link mb-0" onclick="history.back(); return false;">
        <i class="bi bi-arrow-left"></i> Back
    </a>
    <div class="d-flex gap-2">
        <a href="<?= $this->Url->build(['action' => 'edit', $class->class_id]) ?>" class="admin-btn-secondary" style="color: #D97706; padding: 6px 16px; font-size: 13px;">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <?= $this->Form->postLink(
            '<i class="bi bi-trash me-1"></i> Delete',
            ['action' => 'delete', $class->class_id],
            [
                'confirm' => __('Are you sure you want to delete class {0}?', $class->class_code),
                'class' => 'admin-btn-secondary',
                'style' => 'color: #EF4444; padding: 6px 16px; font-size: 13px;',
                'escape' => false,
            ]
        ) ?>
    </div>
</div>

<div class="admin-form-card mb-4" style="max-width: 100%; padding: 32px;">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 1px solid var(--admin-card-border);">
        <h2 class="admin-form-title" style="font-size: 20px; margin: 0;"><?= h($class->class_code) ?></h2>
        <?php
            $statusClass = 'admin-badge-neutral';
            if ($class->class_status === 'scheduled') $statusClass = 'admin-badge-info';
            if ($class->class_status === 'ongoing') $statusClass = 'admin-badge-success';
            if ($class->class_status === 'cancelled') $statusClass = 'admin-badge-danger';
            if ($class->class_status === 'full') $statusClass = 'admin-badge-warning';
        ?>
        <span class="admin-badge <?= $statusClass ?>" style="padding: 6px 12px; font-size: 13px;"><?= h(ucfirst((string)$class->class_status)) ?></span>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Class Code</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= h($class->class_code) ?></div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Course</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= $class->course ? h($class->course->course_name) : '-' ?></div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Teacher</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= $class->teacher ? h($class->teacher->teacher_name) : '-' ?></div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Start</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= $class->start_datetime ? $class->start_datetime->format('j M Y, g:ia') : '-' ?></div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">End</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= $class->end_datetime ? $class->end_datetime->format('j M Y, g:ia') : '-' ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Location</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= h($class->location) ?></div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Capacity</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);"><?= h($class->capacity) ?></div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Notes</div>
                <div style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-primary); line-height: 1.5;">
                    <?= $class->notes ? nl2br(h($class->notes)) : '<span style="color: var(--admin-text-secondary);">N/A</span>' ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($class->bookings)): ?>
<div class="admin-table-card">
    <div style="padding: 20px 24px; border-bottom: 1.5px solid var(--admin-card-border); background: rgba(210, 154, 88, 0.03);">
        <h3 class="admin-form-title" style="font-size: 18px; margin: 0;">Enrolled Students</h3>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Booking Date</th>
                    <th>Status</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($class->bookings as $booking): ?>
                <tr>
                    <td><p class="admin-table-primary-text"><?= $booking->student ? h($booking->student->student_name) : '-' ?></p></td>
                    <td><p class="admin-table-secondary-text"><?= $booking->booking_date ? $booking->booking_date->format('j M Y') : '-' ?></p></td>
                    <td>
                        <?php
                            $bStatusClass = 'admin-badge-neutral';
                            if ($booking->booking_status === 'confirmed') $bStatusClass = 'admin-badge-success';
                            if ($booking->booking_status === 'pending') $bStatusClass = 'admin-badge-warning';
                            if ($booking->booking_status === 'cancelled') $bStatusClass = 'admin-badge-danger';
                        ?>
                        <span class="admin-badge <?= $bStatusClass ?>"><?= h(ucfirst((string)$booking->booking_status)) ?></span>
                    </td>
                    <td><p class="admin-table-primary-text">$<?= number_format((float)$booking->price_at_booking, 2) ?></p></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
