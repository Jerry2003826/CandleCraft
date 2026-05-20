<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $classes
 * @var iterable $students
 * @var \App\Model\Entity\ClassEntity|null $selectedClass
 * @var string|null $selectedClassId
 */
$this->assign('title', 'Attendance');
$attendanceOptions = [
    'present' => 'Present',
    'absent' => 'Absent',
    'late' => 'Late',
    'excused' => 'Excused',
];
?>

<div class="mb-3"></div>

<form method="get" class="mb-3">
    <label for="select-class" class="admin-form-label mb-2" style="font-size: 14px; font-weight: 600;">Select Class</label>
    <select name="class_id" id="select-class" onchange="this.form.submit()" class="admin-form-select" style="width: 100%; max-width: 560px; font-size: 15px; padding: 10px 16px; height: auto;">
        <option value="">-- Choose a class --</option>
        <?php foreach ($classes as $class): ?>
            <option value="<?= h($class->class_id) ?>" <?= $selectedClassId == $class->class_id ? 'selected' : '' ?>>
                <?= h($class->class_code) ?> - <?= h($class->course ? $class->course->course_name : '') ?>
                (<?= $class->start_datetime ? $class->start_datetime->format('j M Y') : '' ?>)
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($selectedClass && !$students->isEmpty()): ?>
    <div class="d-flex gap-2 mb-4">
        <button onclick="markAllPresent()" class="admin-btn-secondary py-1 px-3" style="min-height: 36px; white-space: nowrap;">
            <i class="bi bi-person-check"></i> Mark All Present
        </button>
        <button onclick="clearAll()" class="admin-btn-secondary py-1 px-3" style="min-height: 36px; white-space: nowrap;">
            <i class="bi bi-x-circle"></i> Clear All
        </button>
        <button id="save-all-btn" onclick="saveAllAttendance()" class="admin-btn-primary py-1 px-3" style="min-height: 36px; white-space: nowrap;">
            <i class="bi bi-check-all"></i> Save All
        </button>
    </div>
<?php endif; ?>

<?php if ($selectedClass && !$students->isEmpty()): ?>
    <div class="admin-table-card">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Mark Attendance</th>
                        <th>Notes</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $booking): ?>
                        <?php
                        $currentStatus = $booking->attendance_record
                            ? $booking->attendance_record->attendance_status
                            : 'present';
                        $formId = 'attendance-form-' . $booking->booking_id;
                        $studentName = $booking->student ? $booking->student->student_name : 'Unknown';
                        ?>
                        <tr>
                            <td>
                                <p class="admin-table-primary-text mb-0"><?= h($studentName) ?></p>
                                <?= $this->Form->create(null, [
                                    'id' => $formId,
                                    'url' => ['controller' => 'Attendance', 'action' => 'mark'],
                                    'class' => 'attendance-row-form',
                                    'templates' => [
                                        'inputContainer' => '{{content}}',
                                        'inputContainerError' => '{{content}}{{error}}'
                                    ]
                                ]) ?>
                                    <?= $this->Form->hidden('booking_id', ['value' => $booking->booking_id]) ?>
                                    <?= $this->Form->hidden('class_id', ['value' => $selectedClassId]) ?>
                                <?= $this->Form->end() ?>
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
                                                <?= $currentStatus === $value ? 'checked' : '' ?>
                                            >
                                            <span><?= h($label) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <input
                                    type="text"
                                    name="attendance_notes"
                                    form="<?= h($formId) ?>"
                                    value="<?= h((string)($booking->attendance_record?->attendance_notes ?? '')) ?>"
                                    placeholder="Notes..."
                                    class="admin-form-input"
                                    style="width: 100%; padding: 6px 12px; min-height: 36px;"
                                >
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-2 align-items-center">
                                    <button
                                        type="submit"
                                        form="<?= h($formId) ?>"
                                        class="admin-btn-primary py-1 px-3"
                                        style="min-height: 36px; padding: 6px 16px !important;"
                                    >
                                        Save
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($selectedClass): ?>
    <div class="admin-form-card text-center py-5" style="max-width: 100%;">
        <i class="bi bi-clipboard-x text-muted" style="font-size: 48px;"></i>
        <p class="mt-3 text-muted" style="font-family: 'Inter', sans-serif; font-size: 15px;">No students enrolled in this class yet.</p>
    </div>
<?php else: ?>
    <div class="admin-form-card text-center py-5" style="max-width: 100%;">
        <i class="bi bi-calendar-check text-muted" style="font-size: 48px;"></i>
        <p class="mt-3 text-muted" style="font-family: 'Inter', sans-serif; font-size: 15px;">Select a class from the dropdown above to manage attendance.</p>
    </div>
<?php endif; ?>

<div class="mt-4">
    <a href="<?= $this->Url->build(['action' => 'history']) ?>" class="admin-btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
        <i class="bi bi-clock-history"></i> View Attendance History
    </a>
</div>

<script>
function markAllPresent() {
    document.querySelectorAll('input[name="attendance_status"][value="present"]').forEach(input => {
        input.checked = true;
    });
}

function clearAll() {
    document.querySelectorAll('input[name="attendance_status"][value="present"]').forEach(input => {
        input.checked = true;
    });
    document.querySelectorAll('input[name="attendance_notes"][form^="attendance-form-"]').forEach(input => {
        input.value = '';
    });
}

async function saveAllAttendance() {
    const btn = document.getElementById('save-all-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';

    const forms = document.querySelectorAll('.attendance-row-form');
    const markUrl = '<?= $this->Url->build(['controller' => 'Attendance', 'action' => 'mark']) ?>';

    try {
        await Promise.all(Array.from(forms).map(form =>
            fetch(markUrl, { method: 'POST', body: new FormData(form) })
        ));
        window.location.reload();
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-all"></i> Save All';
        alert('An error occurred while saving. Please try again.');
    }
}
</script>
