<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Teacher $teacher
 * @var iterable<\App\Model\Entity\ClassEntity> $classes
 * @var int $classCount
 * @var int $upcomingClasses
 * @var int $studentCount
 * @var int $attendanceMarked
 */
$this->assign('title', 'Teacher Dashboard');

// Determine first name
$firstName = 'Teacher';
if ($teacher && $teacher->teacher_name) {
    $parts = explode(' ', $teacher->teacher_name);
    $firstName = $parts[0];
}
?>

<!-- Welcome Banner -->
<div class="admin-form-card mb-4 welcome-banner" style="border: none; padding: 32px; max-width: 100%;">
    <h2 class="welcome-title" style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 28px; margin: 0 0 8px 0;">
        Welcome back, <?= h($firstName) ?>!
    </h2>
    <p class="welcome-subtitle" style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 16px; margin: 0;">
        You have <?= $upcomingClasses ?> upcoming class<?= $upcomingClasses !== 1 ? 'es' : '' ?> this week. Have a great time teaching!
    </p>
</div>

<!-- Stats Grid -->
<div class="row g-4 mb-5">
    <div class="col-sm-6 col-xl-3 d-flex">
        <div class="admin-form-card flex-grow-1" style="padding: 24px; max-width: 100%; border-radius: 12px; border: 1px solid var(--admin-card-border); background-color: var(--admin-card-bg); display: flex; flex-direction: column; justify-content: space-between;">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h3 style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: var(--admin-text-secondary); margin: 0;">My Classes</h3>
                <div style="width: 32px; height: 32px; border-radius: 8px; background-color: rgba(59, 130, 246, 0.1); display: flex; justify-content: center; align-items: center;">
                    <i class="bi bi-book" style="font-size: 16px; color: #3B82F6;"></i>
                </div>
            </div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 36px; color: var(--admin-text-primary); line-height: 1;">
                <?= h((string)$classCount) ?>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 d-flex">
        <div class="admin-form-card flex-grow-1" style="padding: 24px; max-width: 100%; border-radius: 12px; border: 1px solid var(--admin-card-border); background-color: var(--admin-card-bg); display: flex; flex-direction: column; justify-content: space-between;">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h3 style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: var(--admin-text-secondary); margin: 0;">Upcoming</h3>
                <div style="width: 32px; height: 32px; border-radius: 8px; background-color: rgba(245, 158, 11, 0.1); display: flex; justify-content: center; align-items: center;">
                    <i class="bi bi-calendar-event" style="font-size: 16px; color: #F59E0B;"></i>
                </div>
            </div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 36px; color: var(--admin-text-primary); line-height: 1;">
                <?= h((string)$upcomingClasses) ?>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 d-flex">
        <div class="admin-form-card flex-grow-1" style="padding: 24px; max-width: 100%; border-radius: 12px; border: 1px solid var(--admin-card-border); background-color: var(--admin-card-bg); display: flex; flex-direction: column; justify-content: space-between;">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h3 style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: var(--admin-text-secondary); margin: 0;">Students</h3>
                <div style="width: 32px; height: 32px; border-radius: 8px; background-color: rgba(16, 185, 129, 0.1); display: flex; justify-content: center; align-items: center;">
                    <i class="bi bi-people" style="font-size: 16px; color: #10B981;"></i>
                </div>
            </div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 36px; color: var(--admin-text-primary); line-height: 1;">
                <?= h((string)$studentCount) ?>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 d-flex">
        <div class="admin-form-card flex-grow-1" style="padding: 24px; max-width: 100%; border-radius: 12px; border: 1px solid var(--admin-card-border); background-color: var(--admin-card-bg); display: flex; flex-direction: column; justify-content: space-between;">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h3 style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: var(--admin-text-secondary); margin: 0;">Attendance Marked</h3>
                <div style="width: 32px; height: 32px; border-radius: 8px; background-color: rgba(139, 92, 246, 0.1); display: flex; justify-content: center; align-items: center;">
                    <i class="bi bi-check2-all" style="font-size: 16px; color: #8B5CF6;"></i>
                </div>
            </div>
            <div style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 36px; color: var(--admin-text-primary); line-height: 1;">
                <?= h((string)$attendanceMarked) ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Upcoming Classes & Attendance -->
    <div class="col-lg-8 d-flex flex-column">
        <h3 class="admin-form-title mb-4"><?= h($teacher->teacher_name) ?>'s Schedule</h3>
        
        <?php if ($classes->isEmpty()): ?>
            <div class="admin-form-card text-center py-5 flex-grow-1 d-flex flex-column justify-content-center" style="max-width: 100%;">
                <i class="bi bi-calendar-x" style="font-size: 48px; color: var(--admin-text-secondary);"></i>
                <p class="mt-3" style="color: var(--admin-text-secondary);">No classes assigned yet.</p>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-4 flex-grow-1">
                <?php foreach ($classes as $class): ?>
                    <?php 
                    $courseType = strtolower($class->course?->course_type ?? 'default');
                    $typeColor = $courseType === 'pottery' ? '#1D4ED8' : ($courseType === 'knitting' ? '#B45309' : '#374151');
                    $typeBg = $courseType === 'pottery' ? '#DBEAFE' : ($courseType === 'knitting' ? '#FEF3C7' : '#F3F4F6');
                    
                    $statusClass = 'admin-badge-neutral';
                    if ($class->class_status === 'scheduled') $statusClass = 'admin-badge-info';
                    if ($class->class_status === 'completed') $statusClass = 'admin-badge-success';
                    if ($class->class_status === 'cancelled') $statusClass = 'admin-badge-danger';
                    ?>
                    <div class="admin-form-card" style="padding: 24px; max-width: 100%;">
                        <div class="d-flex justify-content-between align-items-start mb-4 pb-4" style="border-bottom: 1px solid var(--admin-card-border);">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 12px; color: var(--admin-brand-icon); letter-spacing: 0.05em;">
                                        <?= h($class->class_code) ?>
                                    </span>
                                    <?php if ($class->course?->course_type): ?>
                                        <span class="admin-badge admin-badge-neutral" style="padding: 2px 8px; font-size: 11px;">
                                            <?= h(ucfirst($class->course->course_type)) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 20px; color: var(--admin-text-primary); margin: 0 0 12px 0;">
                                    <?= h($class->course?->course_name ?? 'Class') ?>
                                </h3>
                                
                                <div class="d-flex flex-wrap gap-4" style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="bi bi-clock"></i>
                                        <?= $class->start_datetime ? $class->start_datetime->format('D j M Y, g:ia') : '-' ?>
                                    </span>
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="bi bi-geo-alt"></i><?= h($class->location) ?>
                                    </span>
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="bi bi-people"></i><?= count($class->bookings) ?> booked
                                    </span>
                                </div>
                            </div>
                            <span class="admin-badge <?= $statusClass ?>"><?= ucfirst(h($class->class_status)) ?></span>
                        </div>
                        
                        <?php if (empty($class->bookings)): ?>
                            <p style="color: var(--admin-text-secondary); font-size: 14px; margin: 0; text-align: center; padding: 16px 0;">
                                No students booked into this class yet.
                            </p>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-3">
                                <h4 style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 14px; color: var(--admin-text-primary); margin: 0 0 8px 0;">Mark Attendance</h4>
                                <?php foreach ($class->bookings as $booking): ?>
                                    <div style="background-color: var(--admin-search-bg); border-radius: 8px; padding: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                                        <div>
                                            <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary); margin-bottom: 4px;">
                                                <?= h($booking->student?->student_name ?? 'Student') ?>
                                            </div>
                                            <div style="font-family: 'Inter', sans-serif; font-size: 12px; color: var(--admin-text-secondary);">
                                                Current status: 
                                                <?php if ($booking->attendance_record): ?>
                                                    <?php 
                                                        $astat = $booking->attendance_record->attendance_status;
                                                        $aclass = $astat === 'present' ? 'text-success' : ($astat === 'absent' ? 'text-danger' : 'text-warning');
                                                    ?>
                                                    <span class="<?= $aclass ?> fw-bold"><?= ucfirst(h($astat)) ?></span>
                                                <?php else: ?>
                                                    Not marked
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?= $this->Form->create(null, [
                                            'url' => ['prefix' => 'Teacher', 'controller' => 'Dashboard', 'action' => 'markAttendance', $booking->booking_id],
                                            'style' => 'display: flex; gap: 8px; align-items: center; flex-wrap: wrap;',
                                            'templates' => [
                                                'inputContainer' => '{{content}}',
                                                'inputContainerError' => '{{content}}{{error}}'
                                            ]
                                        ]) ?>
                                            <?= $this->Form->select('attendance_status', [
                                                'present' => 'Present',
                                                'late' => 'Late',
                                                'absent' => 'Absent',
                                                'excused' => 'Excused',
                                            ], [
                                                'value' => $booking->attendance_record?->attendance_status ?? 'present',
                                                'class' => 'admin-form-select',
                                                'style' => 'width: auto; padding: 8px 32px 8px 12px;'
                                            ]) ?>
                                            <?= $this->Form->text('attendance_notes', [
                                                'value' => $booking->attendance_record?->attendance_notes,
                                                'placeholder' => 'Attendance note...',
                                                'class' => 'admin-form-input',
                                                'style' => 'width: 180px; padding: 8px 12px;'
                                            ]) ?>
                                            <?= $this->Form->button('Save', ['class' => 'admin-btn-primary', 'style' => 'padding: 8px 16px;']) ?>
                                        <?= $this->Form->end() ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Quick Actions -->
    <div class="col-lg-4 d-flex flex-column">
        <h3 class="admin-form-title mb-4">Quick Actions</h3>
        
        <div class="d-flex flex-column gap-3 mb-5">
            <a href="<?= $this->Url->build(['prefix' => 'Teacher', 'controller' => 'Attendance', 'action' => 'index']) ?>" class="admin-form-card d-flex align-items-center text-decoration-none" style="padding: 20px; max-width: 100%; transition: transform 0.2s, box-shadow 0.2s; border-radius: 12px; border: 1px solid var(--admin-card-border); background-color: var(--admin-card-bg);">
                <div style="width: 48px; height: 48px; background-color: rgba(59, 130, 246, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                    <i class="bi bi-clipboard-check" style="font-size: 20px; color: #3B82F6;"></i>
                </div>
                <div>
                    <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary); margin-bottom: 2px;">Manage Attendance</div>
                    <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">View all class attendance</div>
                </div>
            </a>
            
            <a href="<?= $this->Url->build(['prefix' => 'Teacher', 'controller' => 'Availability', 'action' => 'index']) ?>" class="admin-form-card d-flex align-items-center text-decoration-none" style="padding: 20px; max-width: 100%; transition: transform 0.2s, box-shadow 0.2s; border-radius: 12px; border: 1px solid var(--admin-card-border); background-color: var(--admin-card-bg);">
                <div style="width: 48px; height: 48px; background-color: rgba(245, 158, 11, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                    <i class="bi bi-calendar-range" style="font-size: 20px; color: #F59E0B;"></i>
                </div>
                <div>
                    <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary); margin-bottom: 2px;">My Schedule</div>
                    <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">View weekly timetable</div>
                </div>
            </a>

            <a href="<?= $this->Url->build(['prefix' => 'Teacher', 'controller' => 'Resources', 'action' => 'index']) ?>" class="admin-form-card d-flex align-items-center text-decoration-none" style="padding: 20px; max-width: 100%; transition: transform 0.2s, box-shadow 0.2s; border-radius: 12px; border: 1px solid var(--admin-card-border); background-color: var(--admin-card-bg);">
                <div style="width: 48px; height: 48px; background-color: rgba(16, 185, 129, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                    <i class="bi bi-folder" style="font-size: 20px; color: #10B981;"></i>
                </div>
                <div>
                    <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary); margin-bottom: 2px;">Class Resources</div>
                    <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary);">Upload course materials</div>
                </div>
            </a>
        </div>
    </div>
</div>
