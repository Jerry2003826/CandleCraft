<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 * @var \App\Model\Entity\Teacher $teacher
 */
$this->assign('title', $class->course?->course_name ?? $class->class_code);

$statusBadge = match ($class->class_status ?? '') {
    'scheduled'  => 'admin-badge-info',
    'completed'  => 'admin-badge-success',
    'cancelled'  => 'admin-badge-danger',
    default      => 'admin-badge-neutral',
};

$attendanceBadge = static function (?string $status): string {
    return match ($status) {
        'present'  => 'admin-badge-success',
        'absent'   => 'admin-badge-danger',
        'late'     => 'admin-badge-warning',
        'excused'  => 'admin-badge-neutral',
        default    => 'admin-badge-neutral',
    };
};

$bookingBadge = static function (string $status): string {
    return match ($status) {
        'confirmed'   => 'admin-badge-success',
        'pending'     => 'admin-badge-warning',
        'cancelled'   => 'admin-badge-danger',
        'waitlisted'  => 'admin-badge-neutral',
        default       => 'admin-badge-neutral',
    };
};

$attendanceOptions = [
    'present' => 'Present',
    'absent' => 'Absent',
    'late' => 'Late',
    'excused' => 'Excused',
];
$returnTo = $this->request->getRequestTarget();

$bookings = $class->bookings ?? [];
$present = 0;
$absent  = 0;
$unmarked = 0;
foreach ($bookings as $b) {
    $s = $b->attendance_record?->attendance_status ?? null;
    if ($s === 'present' || $s === 'late') $present++;
    elseif ($s === 'absent' || $s === 'excused') $absent++;
    else $unmarked++;
}
?>

<?= $this->element('admin_back_link', ['url' => $this->Url->build(['action' => 'index']), 'label' => 'Back to Schedule']) ?>

<!-- Class header -->
<div class="admin-form-card mb-4" style="padding: 28px 32px; max-width: 100%;">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 12px; color: var(--admin-brand-icon); letter-spacing: 0.06em;">
                    <?= h($class->class_code) ?>
                </span>
                <?php if ($class->course?->course_type): ?>
                    <span class="admin-badge admin-badge-neutral" style="font-size: 11px;">
                        <?= h(ucfirst($class->course->course_type)) ?>
                    </span>
                <?php endif; ?>
                <span class="admin-badge <?= $statusBadge ?>"><?= ucfirst(h($class->class_status ?? '')) ?></span>
            </div>
            <h2 style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 26px; color: var(--admin-text-primary); margin: 0 0 16px 0;">
                <?= h($class->course?->course_name ?? 'Class') ?>
            </h2>
            <div class="d-flex flex-wrap gap-4" style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar3"></i>
                    <?= $class->start_datetime ? h($class->start_datetime->format('l, j F Y')) : '-' ?>
                </span>
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock"></i>
                    <?= $class->start_datetime ? h($class->start_datetime->format('g:ia')) : '-' ?>
                    <?php if ($class->end_datetime): ?>
                        &ndash; <?= h($class->end_datetime->format('g:ia')) ?>
                    <?php endif; ?>
                </span>
                <?php if ($class->location): ?>
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt"></i><?= h($class->location) ?>
                    </span>
                <?php endif; ?>
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-people"></i>
                    <?= count($bookings) ?> / <?= h((string)$class->capacity) ?> booked
                </span>
            </div>
        </div>
    </div>

    <?php if ($class->notes): ?>
        <div style="background-color: var(--admin-search-bg); border-radius: 8px; padding: 14px 16px; font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">
            <i class="bi bi-sticky me-2"></i><?= h($class->notes) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Attendance summary -->
<?php if (!empty($bookings)): ?>
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="admin-form-card text-center" style="padding: 16px; max-width: 100%;">
            <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 28px; color: #10B981;"><?= $present ?></div>
            <div style="font-family: 'Inter', sans-serif; font-size: 12px; color: var(--admin-text-secondary); margin-top: 2px;">Present / Late</div>
        </div>
    </div>
    <div class="col-4">
        <div class="admin-form-card text-center" style="padding: 16px; max-width: 100%;">
            <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 28px; color: #EF4444;"><?= $absent ?></div>
            <div style="font-family: 'Inter', sans-serif; font-size: 12px; color: var(--admin-text-secondary); margin-top: 2px;">Absent / Excused</div>
        </div>
    </div>
    <div class="col-4">
        <div class="admin-form-card text-center" style="padding: 16px; max-width: 100%;">
            <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 28px; color: var(--admin-text-secondary);"><?= $unmarked ?></div>
            <div style="font-family: 'Inter', sans-serif; font-size: 12px; color: var(--admin-text-secondary); margin-top: 2px;">Not Marked</div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Student list -->
<div class="admin-form-card" style="max-width: 100%; overflow: hidden;">
    <div class="admin-form-header" style="padding: 20px 24px 0;">
        <h3 class="admin-form-title" style="font-size: 17px; margin-bottom: 0;">
            Enrolled Students
            <span style="font-weight: 400; font-size: 14px; color: var(--admin-text-secondary); margin-left: 6px;">(<?= count($bookings) ?>)</span>
        </h3>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="text-center py-5">
            <i class="bi bi-person-x" style="font-size: 40px; color: var(--admin-text-secondary);"></i>
            <p class="mt-3" style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">No students booked into this class yet.</p>
        </div>
    <?php else: ?>
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Booking Status</th>
                        <th>Mark Attendance</th>
                        <th>Notes</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $attendance = $booking->attendance_record;
                        $attStatus = $attendance?->attendance_status ?? 'present';
                        $bBadge = $bookingBadge($booking->booking_status ?? '');
                        $formId = 'schedule-attendance-form-' . $booking->booking_id;
                        $studentName = $booking->student?->student_name ?? 'Unknown';
                        ?>
                        <tr>
                            <td>
                                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 14px; color: var(--admin-text-primary);">
                                    <?= h($studentName) ?>
                                </div>
                                <?php if ($booking->student?->declared_age): ?>
                                    <div style="font-size: 12px; color: var(--admin-text-secondary);">
                                        Age <?= h((string)$booking->student->declared_age) ?>
                                    </div>
                                <?php endif; ?>
                                <?= $this->Form->create(null, [
                                    'id' => $formId,
                                    'url' => ['controller' => 'Attendance', 'action' => 'mark'],
                                    'class' => 'attendance-row-form',
                                    'templates' => [
                                        'inputContainer' => '{{content}}',
                                        'inputContainerError' => '{{content}}{{error}}',
                                    ],
                                ]) ?>
                                    <?= $this->Form->hidden('booking_id', ['value' => $booking->booking_id]) ?>
                                    <?= $this->Form->hidden('class_id', ['value' => $class->class_id]) ?>
                                    <?= $this->Form->hidden('return_to', ['value' => $returnTo]) ?>
                                <?= $this->Form->end() ?>
                            </td>
                            <td>
                                <span class="admin-badge <?= $bBadge ?>">
                                    <?= ucfirst(h($booking->booking_status ?? '')) ?>
                                </span>
                            </td>
                            <td>
                                <div class="attendance-status-options" aria-label="Attendance status for <?= h($studentName) ?>">
                                    <?php foreach ($attendanceOptions as $value => $label): ?>
                                        <label class="attendance-status-option">
                                            <input
                                                type="radio"
                                                name="attendance_status"
                                                value="<?= h($value) ?>"
                                                form="<?= h($formId) ?>"
                                                <?= $attStatus === $value ? 'checked' : '' ?>
                                            >
                                            <span><?= h($label) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">
                                <input
                                    type="text"
                                    name="attendance_notes"
                                    form="<?= h($formId) ?>"
                                    value="<?= h((string)($attendance?->attendance_notes ?? '')) ?>"
                                    placeholder="Notes..."
                                    class="admin-form-input"
                                    style="width: 100%; padding: 6px 12px; min-height: 36px;"
                                >
                            </td>
                            <td class="text-end">
                                <button
                                    type="submit"
                                    form="<?= h($formId) ?>"
                                    class="admin-btn-primary py-1 px-3"
                                    style="min-height: 36px; padding: 6px 16px !important;"
                                >
                                    Save
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
