<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Student $student
 */
$this->assign('title', 'Edit Student');
?>

<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back to Students
</a>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Edit Student: <?= h($student->student_name) ?></h2>
    </div>
    
    <?= $this->Form->create($student) ?>
        <div class="row g-4">
            <div class="col-md-12">
                <div class="admin-form-group mb-0">
                    <label for="student-name" class="admin-form-label">Student Name</label>
                    <?= $this->Form->text('student_name', [
                        'id' => 'student-name', 
                        'required' => true, 
                        'maxlength' => 100,
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="date-of-birth" class="admin-form-label">Date of Birth</label>
                    <?= $this->Form->date('date_of_birth', [
                        'id' => 'date-of-birth', 
                        'required' => true,
                        'class' => 'admin-form-input'
                    ]) ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-form-group mb-0">
                    <label for="student-status" class="admin-form-label">Status</label>
                    <?= $this->Form->select('student_status', [
                        'active' => 'Active', 
                        'inactive' => 'Inactive'
                    ], [
                        'id' => 'student-status',
                        'class' => 'admin-form-select'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="admin-form-group mt-4">
            <label for="medical-notes" class="admin-form-label">Medical Notes</label>
            <?= $this->Form->textarea('medical_notes', [
                'id' => 'medical-notes', 
                'rows' => 4, 
                'maxlength' => 1000,
                'class' => 'admin-form-textarea'
            ]) ?>
        </div>

        <?php if ($student->user_id): ?>
            <?php
            $usersTable = \Cake\ORM\TableRegistry::getTableLocator()->get('Users');
            $linkedUser = $usersTable->find()->where(['user_id' => $student->user_id])->first();
            ?>
            <?php if ($linkedUser): ?>
                <div class="admin-form-group mt-4 p-4" style="background-color: var(--admin-search-bg); border-radius: 12px;">
                    <label class="admin-form-label" style="font-weight: 600;">Account & Age Verification</label>
                    <div style="margin-bottom: 8px;">
                        <span style="color: var(--admin-text-secondary); font-size: 14px;">Account Status:</span> 
                        <strong style="color: var(--admin-text-primary); font-size: 14px;"><?= h($linkedUser->account_status) ?></strong>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <span style="color: var(--admin-text-secondary); font-size: 14px;">Self-declared 18+:</span> 
                        <strong style="color: var(--admin-text-primary); font-size: 14px;"><?= $linkedUser->self_declared_adult ? 'Yes' : 'No' ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--admin-text-secondary); font-size: 14px;">Admin verified 18+:</span> 
                        <strong style="color: var(--admin-text-primary); font-size: 14px;"><?= $linkedUser->age_verified_by_admin ? 'Yes' : 'No' ?></strong>
                    </div>
                    <?php if (!$linkedUser->age_verified_by_admin): ?>
                        <div class="mt-3">
                            <a href="<?= $this->Url->build(['action' => 'view', $student->student_id]) ?>" class="admin-btn-secondary" style="color: #D97706; background-color: #FEF3C7;">
                                <i class="bi bi-shield-exclamation me-2"></i> Go to View page to verify age
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="admin-form-actions">
            <?= $this->Form->button('Update Student', ['class' => 'admin-btn-primary']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary">Cancel</a>
        </div>
    <?= $this->Form->end() ?>
</div>
