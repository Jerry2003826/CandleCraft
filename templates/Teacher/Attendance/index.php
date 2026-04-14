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

<div class="admin-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <h2 class="admin-form-title m-0">Attendance Management</h2>
    <form method="get" class="d-flex align-items-center gap-3">
        <label for="select-class" class="admin-form-label mb-0" style="white-space: nowrap;">Select Class:</label>
        <div class="admin-search" style="background-color: var(--admin-card-bg); border: 1px solid var(--admin-card-border); padding: 4px 12px; border-radius: 8px;">
            <select name="class_id" id="select-class" onchange="this.form.submit()" style="border: none; background: transparent; outline: none; color: var(--admin-text-primary); font-family: 'Inter', sans-serif; font-size: 14px; cursor: pointer; padding-right: 8px;">
                <option value="">-- Choose a class --</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= h($class->class_id) ?>" <?= $selectedClassId == $class->class_id ? 'selected' : '' ?>>
                        <?= h($class->class_code) ?> - <?= h($class->course ? $class->course->course_name : '') ?> 
                        (<?= $class->start_datetime ? $class->start_datetime->format('j M Y') : '' ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($selectedClass && !$students->isEmpty()): ?>
    <div class="admin-table-card">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $booking): ?>
                        <tr>
                            <td>
                                <p class="admin-table-primary-text mb-0"><?= h($booking->student ? $booking->student->student_name : 'Unknown') ?></p>
                            </td>
                            <td>
                                <?php
                                $currentStatus = $booking->attendance_record
                                    ? $booking->attendance_record->attendance_status
                                    : 'pending';
                                
                                $statusClass = 'admin-badge-neutral';
                                if ($currentStatus === 'present') $statusClass = 'admin-badge-success';
                                if ($currentStatus === 'absent') $statusClass = 'admin-badge-danger';
                                if ($currentStatus === 'late') $statusClass = 'admin-badge-warning';
                                ?>
                                <span class="admin-badge <?= $statusClass ?>">
                                    <?= ucfirst(h($currentStatus)) ?>
                                </span>
                            </td>
                            <td>
                                <p class="admin-table-secondary-text mb-0">
                                    <?= h($booking->attendance_record ? ($booking->attendance_record->attendance_notes ?? '-') : '-') ?>
                                </p>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end">
                                    <?= $this->Form->create(null, [
                                        'url' => ['controller' => 'Attendance', 'action' => 'mark'],
                                        'class' => 'd-flex gap-2 align-items-center flex-wrap justify-content-end',
                                        'templates' => [
                                            'inputContainer' => '{{content}}',
                                            'inputContainerError' => '{{content}}{{error}}'
                                        ]
                                    ]) ?>
                                        <?= $this->Form->hidden('booking_id', ['value' => $booking->booking_id]) ?>
                                        <?= $this->Form->hidden('class_id', ['value' => $selectedClassId]) ?>
                                        
                                        <?= $this->Form->select('attendance_status', [
                                            'present' => 'Present',
                                            'absent' => 'Absent',
                                            'late' => 'Late',
                                            'excused' => 'Excused',
                                        ], [
                                            'value' => $currentStatus !== 'pending' ? $currentStatus : 'present',
                                            'class' => 'admin-form-select',
                                            'style' => 'width: auto; padding: 6px 32px 6px 12px; min-height: 36px;'
                                        ]) ?>
                                        
                                        <?= $this->Form->text('attendance_notes', [
                                            'placeholder' => 'Notes...',
                                            'class' => 'admin-form-input',
                                            'style' => 'width: 140px; padding: 6px 12px; min-height: 36px;'
                                        ]) ?>
                                        
                                        <?= $this->Form->button('Save', [
                                            'class' => 'admin-btn-primary py-1 px-3',
                                            'style' => 'min-height: 36px; padding: 6px 16px !important;'
                                        ]) ?>
                                    <?= $this->Form->end() ?>
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
