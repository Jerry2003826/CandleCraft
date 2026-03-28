<?php
/**
 * @var \App\View\AppView $this
 * @var array $children
 */
$this->assign('title', 'My Children');
?>

<div class="card">
    <div class="card-header">
        <h3>My Children</h3>
    </div>
    <div class="card-body">
        <?php if (empty($children)): ?>
            <div class="empty-state">
                <div class="icon">&#x1F476;</div>
                <p>No children linked yet. Please contact admin.</p>
            </div>
        <?php else: ?>
            <div class="portal-class-list">
                <?php foreach ($children as $child): ?>
                    <section class="portal-class-card">
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
                            <span>DOB: <?= $child->date_of_birth ? $child->date_of_birth->format('j M Y') : '-' ?></span>
                            <span><?= h($child->medical_notes ?: 'No medical notes') ?></span>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
