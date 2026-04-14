<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Student $student
 */
$this->assign('title', 'Student Details');
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Students</a>
    <div class="btn-group btn-group-sm">
        <a href="<?= $this->Url->build(['action' => 'edit', $student->student_id]) ?>" class="btn btn-outline-warning">Edit</a>
        <?= $this->Form->postLink('Delete', ['action' => 'delete', $student->student_id], [
            'confirm' => __('Are you sure you want to delete {0}?', $student->student_name),
            'class' => 'btn btn-outline-danger',
        ]) ?>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= h($student->student_name) ?></h5>
        <?= $this->Badge->status($student->student_status) ?>
    </div>
    <div class="card-body">
        <table class="detail-table table table-borderless">
            <tr><th class="text-end text-muted" style="width:180px">Name:</th><td><?= h($student->student_name) ?></td></tr>
            <tr><th class="text-end text-muted">Date of Birth:</th><td><?= $student->date_of_birth ? $student->date_of_birth->format('j M Y') : '-' ?></td></tr>
            <tr><th class="text-end text-muted">Status:</th><td><?= $this->Badge->status($student->student_status) ?></td></tr>
            <tr><th class="text-end text-muted">Medical Notes:</th><td><?= $student->medical_notes ? nl2br(h($student->medical_notes)) : 'N/A' ?></td></tr>
            <tr><th class="text-end text-muted">Created:</th><td><?= $student->created_at ? $student->created_at->format('j M Y, g:ia') : '-' ?></td></tr>
            <tr><th class="text-end text-muted">Updated:</th><td><?= $student->updated_at ? $student->updated_at->format('j M Y, g:ia') : '-' ?></td></tr>
            <?php if ($student->user): ?>
                <tr><th class="text-end text-muted">Account Status:</th><td><?= $this->Badge->status($student->user->account_status) ?></td></tr>
                <tr>
                    <th class="text-end text-muted">Self-Declared 18+:</th>
                    <td>
                        <?php if ($student->user->self_declared_adult): ?>
                            <span class="badge bg-info text-dark"><i class="bi bi-check-circle me-1"></i>Yes</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">No</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th class="text-end text-muted">Age Verified (Admin):</th>
                    <td>
                        <?php if ($student->user->age_verified_by_admin): ?>
                            <span class="badge bg-success"><i class="bi bi-shield-check me-1"></i>Verified</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Not Verified</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php if ($student->user && !$student->user->age_verified_by_admin): ?>
    <div class="card mt-3 border-warning">
        <div class="card-header bg-warning bg-opacity-10">
            <h5 class="mb-0"><i class="bi bi-shield-exclamation me-2"></i>Age Verification Required</h5>
        </div>
        <div class="card-body">
            <p class="mb-2">
                This student
                <?php if ($student->user->self_declared_adult): ?>
                    has <strong>self-declared</strong> they are 18 years or older during registration.
                <?php else: ?>
                    has <strong>not</strong> declared they are 18+.
                <?php endif; ?>
            </p>
            <?php if ($student->date_of_birth): ?>
                <?php
                $dob = $student->date_of_birth;
                $age = (int)$dob->diff(new \Cake\Chronos\ChronosDate())->y;
                ?>
                <p class="mb-3">
                    Date of birth: <strong><?= $dob->format('j M Y') ?></strong>
                    &mdash; Calculated age: <strong><?= $age ?> years old</strong>
                </p>
            <?php endif; ?>
            <p class="text-muted mb-3">
                By clicking the button below, you confirm that you have verified this student is 18 years or older.
                This will <strong>enable payment features</strong> for this user.
            </p>
            <?= $this->Form->postLink(
                '<i class="bi bi-shield-check me-1"></i> Verify Age & Enable Payments',
                ['action' => 'verifyAge', $student->student_id],
                [
                    'escape' => false,
                    'class' => 'btn btn-success',
                    'confirm' => 'Are you sure you want to verify this student\'s age and enable their payment features?',
                ]
            ) ?>
        </div>
    </div>
<?php endif; ?>
