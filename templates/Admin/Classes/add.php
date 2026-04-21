<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClassEntity $class
 * @var \Cake\ORM\ResultSet $courses
 * @var \Cake\ORM\ResultSet $teachers
 * @var array<string, string> $locationOptions
 * @var array<int, int> $courseDurations
 */
$this->assign('title', 'Add Class');
$courseDurationsJson = json_encode($courseDurations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '{}';
?>

<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back to Classes
</a>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Add Class</h2>
    </div>
    
    <?= $this->Form->create($class) ?>
        <div class="admin-form-group" style="padding: 16px 18px; border-radius: 12px; background-color: var(--admin-search-bg); border: 1px solid var(--admin-card-border);">
            <div class="admin-form-label" style="margin-bottom: 6px;">Class Code</div>
            <p style="margin: 0; font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary);">
                The class code will be generated automatically from the selected course when you save this class.
            </p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="course-id" class="admin-form-label">Course</label>
                    <?= $this->Form->select('course_id', $courses, [
                        'id' => 'course-id', 
                        'empty' => '-- Select Course --', 
                        'required' => true,
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="teacher-id" class="admin-form-label">Teacher</label>
                    <?= $this->Form->select('teacher_id', $teachers, [
                        'id' => 'teacher-id', 
                        'empty' => '-- Select Teacher --', 
                        'required' => true,
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="start-datetime" class="admin-form-label">Start Date & Time</label>
                    <?= $this->Form->text('start_datetime', [
                        'type' => 'datetime-local', 
                        'id' => 'start-datetime', 
                        'required' => true,
                        'value' => $class->start_datetime?->format('Y-m-d\TH:i') ?? '',
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <div class="admin-form-label">Fixed Duration</div>
                    <div id="duration-summary" style="padding: 14px 16px; border-radius: 12px; background-color: var(--admin-search-bg); border: 1px solid var(--admin-card-border); min-height: 106px;">
                        <div id="duration-text" style="font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 600; color: var(--admin-text-primary); margin-bottom: 8px;">
                            Select a course to preview the saved class duration.
                        </div>
                        <div id="end-preview" style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary); line-height: 1.5;">
                            End time is calculated automatically once you choose a course and start time.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="admin-form-group mt-4">
            <label for="location" class="admin-form-label">Location</label>
            <?= $this->Form->select('location', $locationOptions, [
                'id' => 'location', 
                'empty' => '-- Select Location --',
                'required' => true, 
                'class' => 'admin-form-select'
            ]) ?>
        </div>
        
        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="capacity" class="admin-form-label">Capacity</label>
                    <?= $this->Form->number('capacity', [
                        'id' => 'capacity', 
                        'value' => 20, 
                        'min' => 1, 
                        'max' => 200,
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="class-status" class="admin-form-label">Status</label>
                    <?= $this->Form->select('class_status', [
                        'scheduled' => 'Scheduled', 
                        'ongoing' => 'Ongoing', 
                        'completed' => 'Completed', 
                        'cancelled' => 'Cancelled', 
                        'full' => 'Full'
                    ], [
                        'id' => 'class-status', 
                        'default' => 'scheduled',
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="admin-form-actions">
            <?= $this->Form->button('Save Class', ['class' => 'admin-btn-primary']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary">Cancel</a>
        </div>
    <?= $this->Form->end() ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var courseDurations = <?= $courseDurationsJson ?>;
    var courseSelect = document.getElementById('course-id');
    var startInput = document.getElementById('start-datetime');
    var durationText = document.getElementById('duration-text');
    var endPreview = document.getElementById('end-preview');

    function formatDuration(minutes) {
        var hours = Math.floor(minutes / 60);
        var remainder = minutes % 60;
        if (hours > 0 && remainder > 0) {
            return hours + 'h ' + remainder + 'm';
        }
        if (hours > 0) {
            return hours + 'h';
        }
        return minutes + 'm';
    }

    function formatLocalDate(value) {
        if (!value) {
            return '';
        }

        var date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '';
        }

        return date.toLocaleString([], {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function updateDurationSummary() {
        var courseId = courseSelect ? courseSelect.value : '';
        var durationMinutes = Number(courseDurations[courseId] || 0);
        var startValue = startInput ? startInput.value : '';

        if (!courseId || !durationMinutes) {
            durationText.textContent = 'Select a course to preview the saved class duration.';
            endPreview.textContent = 'End time is calculated automatically once you choose a course and start time.';
            return;
        }

        durationText.textContent = 'This class will run for ' + formatDuration(durationMinutes) + '.';

        if (!startValue) {
            endPreview.textContent = 'Choose a start date and time to preview the saved end time.';
            return;
        }

        var startDate = new Date(startValue);
        if (Number.isNaN(startDate.getTime())) {
            endPreview.textContent = 'Choose a valid start date and time to preview the saved end time.';
            return;
        }

        var endDate = new Date(startDate.getTime() + (durationMinutes * 60 * 1000));
        endPreview.textContent = 'The class will be saved until ' + formatLocalDate(endDate);
    }

    courseSelect && courseSelect.addEventListener('change', updateDurationSummary);
    startInput && startInput.addEventListener('input', updateDurationSummary);
    updateDurationSummary();
});
</script>
