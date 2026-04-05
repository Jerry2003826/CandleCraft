<?php
/**
 * @var \App\View\AppView $this
 * @var array $children
 */
$this->assign('title', 'My Children');
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">My Children</h5>
    </div>
    <div class="card-body">
        <?php if (empty($children)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-person-hearts" style="font-size: 48px;"></i>
                <p class="mt-3">No children linked yet. Please contact admin.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($children as $child): ?>
                    <div class="col-md-6">
                        <div class="portal-class-card">
                            <div class="portal-class-card__header">
                                <div>
                                    <p class="portal-class-card__eyebrow">Student</p>
                                    <h3><?= h($child->student_name) ?></h3>
                                </div>
                                <span class="badge badge-<?= h($child->student_status ?? 'active') ?>">
                                    <?= ucfirst(h($child->student_status ?? 'active')) ?>
                                </span>
                            </div>
                            <div class="portal-class-card__meta">
                                <span><i class="bi bi-calendar3 me-1"></i>DOB: <?= $child->date_of_birth ? $child->date_of_birth->format('j M Y') : '-' ?></span>
                                <span><i class="bi bi-heart-pulse me-1"></i><?= h($child->medical_notes ?: 'No medical notes') ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
