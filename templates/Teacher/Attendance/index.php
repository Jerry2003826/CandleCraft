<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $classes
 * @var iterable $students
 * @var \App\Model\Entity\ClassEntity|null $selectedClass
 * @var string|null $selectedClassId
 */
$this->assign('title', 'Attendance Management');
?>

<div class="card">
    <div class="card-header">
        <h3>Attendance Management</h3>
    </div>
    <div style="padding: 12px 16px; border-bottom: 1px solid #eee;">
        <form method="get" style="display: flex; gap: 10px; align-items: center;">
            <label for="select-class" style="font-weight: 500;">Select Class:</label>
            <select name="class_id" id="select-class" onchange="this.form.submit()" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd;">
                <option value="">-- Choose a class --</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= h($class->class_id) ?>" <?= $selectedClassId == $class->class_id ? 'selected' : '' ?>>
                        <?= h($class->class_code) ?> -
                        <?= h($class->course ? $class->course->course_name : '') ?>
                        (<?= $class->start_datetime ? $class->start_datetime->format('j M Y') : '' ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($selectedClass && !$students->isEmpty()): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $booking): ?>
                    <tr>
                        <td><?= h($booking->student ? $booking->student->student_name : 'Unknown') ?></td>
                        <td>
                            <?php
                            $currentStatus = $booking->attendance_record
                                ? $booking->attendance_record->attendance_status
                                : 'pending';
                            ?>
                            <span class="badge badge-<?= h($currentStatus === 'present' ? 'confirmed' : ($currentStatus === 'absent' ? 'cancelled' : 'pending')) ?>">
                                <?= ucfirst(h($currentStatus)) ?>
                            </span>
                        </td>
                        <td>
                            <?= h($booking->attendance_record ? ($booking->attendance_record->attendance_notes ?? '-') : '-') ?>
                        </td>
                        <td>
                            <?= $this->Form->create(null, [
                                'url' => ['controller' => 'Attendance', 'action' => 'mark'],
                                'style' => 'display: flex; gap: 6px; align-items: center;',
                            ]) ?>
                            <?= $this->Form->hidden('booking_id', ['value' => $booking->booking_id]) ?>
                            <?= $this->Form->hidden('class_id', ['value' => $selectedClassId]) ?>
                            <?= $this->Form->select('attendance_status', [
                                'present' => 'Present',
                                'absent' => 'Absent',
                                'late' => 'Late',
                                'excused' => 'Excused',
                            ], [
                                'value' => $currentStatus !== 'pending' ? $currentStatus : null,
                                'style' => 'padding: 4px 8px; border-radius: 4px; border: 1px solid #ddd;',
                            ]) ?>
                            <?= $this->Form->text('attendance_notes', [
                                'placeholder' => 'Notes...',
                                'style' => 'padding: 4px 8px; border-radius: 4px; border: 1px solid #ddd; width: 120px;',
                            ]) ?>
                            <?= $this->Form->button('Save', ['class' => 'btn btn-sm btn-primary']) ?>
                            <?= $this->Form->end() ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($selectedClass): ?>
        <div class="empty-state">
            <div class="icon">&#x1F4CB;</div>
            <p>No students enrolled in this class yet.</p>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="icon">&#x1F4C5;</div>
            <p>Select a class to manage attendance.</p>
        </div>
    <?php endif; ?>
</div>

<div style="margin-top: 16px;">
    <a href="<?= $this->Url->build(['action' => 'history']) ?>" class="btn btn-sm">View Attendance History</a>
</div>
