<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Message $message
 * @var \Cake\ORM\ResultSet $replies
 * @var array<string, mixed> $requestMeta
 * @var \App\Model\Entity\User|null $existingUser
 * @var \App\Model\Entity\Student|null $linkedStudent
 */
$this->assign('title', 'View Enquiry');

$isAccountRequest = (bool)($requestMeta['is_account_request'] ?? false);
$declaredAge = $requestMeta['declared_age'] ?? null;
$isDeclared18 = is_int($declaredAge) && $declaredAge >= 18;
$hasAgeDeclaration = $declaredAge !== null;
$displayText = $isAccountRequest
    ? (string)($requestMeta['clean_message_text'] ?? '')
    : str_replace(['[AGE DECLARATION: 18+]', '[AGE DECLARATION: Under 18]'], '', (string)$message->message_text);
$displayText = trim($displayText);
?>

<div class="admin-page-header d-flex justify-content-between align-items-center mb-4">
    <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link mb-0">
        <i class="bi bi-arrow-left"></i> Back to Enquiries
    </a>
    <div class="d-flex align-items-center gap-2">
        <?php if ($isAccountRequest && !$existingUser): ?>
            <a href="<?= $this->Url->build(['action' => 'createAccount', $message->message_id]) ?>" class="admin-btn-primary" style="padding: 6px 16px; font-size: 13px;">
                <i class="bi bi-person-plus me-1"></i> Register Customer
            </a>
        <?php elseif ($linkedStudent): ?>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Students', 'action' => 'view', $linkedStudent->student_id, '?' => ['message' => $message->message_id]]) ?>" class="admin-btn-secondary" style="padding: 6px 16px; font-size: 13px;">
                View Customer Record
            </a>
        <?php endif; ?>
        <?php if ($message->message_status !== 'archived'): ?>
            <a href="<?= $this->Url->build(['action' => 'reply', $message->message_id]) ?>" class="admin-btn-secondary" style="color: #10B981; padding: 6px 16px; font-size: 13px;">
                <i class="bi bi-reply me-1"></i> Respond
            </a>
            <?= $this->Form->postLink(
                '<i class="bi bi-archive me-1"></i> Archive',
                ['action' => 'archive', $message->message_id],
                [
                    'confirm' => __('Archive this enquiry?'),
                    'class' => 'admin-btn-secondary',
                    'style' => 'color: #B45309; padding: 6px 16px; font-size: 13px;',
                    'escape' => false,
                ]
            ) ?>
        <?php else: ?>
            <?= $this->Form->postLink(
                '<i class="bi bi-arrow-counterclockwise me-1"></i> Restore',
                ['action' => 'restore', $message->message_id],
                [
                    'class' => 'admin-btn-secondary',
                    'style' => 'color: #2563EB; padding: 6px 16px; font-size: 13px;',
                    'escape' => false,
                ]
            ) ?>
            <?= $this->Form->postLink(
                '<i class="bi bi-trash me-1"></i> Delete',
                ['action' => 'delete', $message->message_id],
                [
                    'confirm' => __('Delete this archived enquiry permanently?'),
                    'class' => 'admin-btn-secondary',
                    'style' => 'color: #EF4444; padding: 6px 16px; font-size: 13px;',
                    'escape' => false,
                ]
            ) ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($isAccountRequest): ?>
    <div class="admin-form-card d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-4" style="background-color: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.3); padding: 20px; max-width: 100%;">
        <div class="d-flex align-items-center gap-3">
            <div style="width: 40px; height: 40px; border-radius: 8px; background-color: rgba(59, 130, 246, 0.1); display: flex; justify-content: center; align-items: center;">
                <i class="bi bi-person-plus" style="font-size: 20px; color: #3B82F6;"></i>
            </div>
            <div>
                <strong style="color: var(--admin-text-primary); font-family: 'Inter', sans-serif; font-size: 15px;">Portal Access Request:</strong>
                <span style="color: var(--admin-text-secondary); font-family: 'Inter', sans-serif; font-size: 14px;">Requested access as <strong style="color: var(--admin-text-primary);"><?= h((string)($requestMeta['requested_role_label'] ?? 'Customer')) ?></strong>.</span>
                <span style="color: var(--admin-text-secondary); font-family: 'Inter', sans-serif; font-size: 14px; margin-left: 8px;">Legacy profile: <strong style="color: var(--admin-text-primary);"><?= h((string)($requestMeta['legacy_profile_label'] ?? 'Student')) ?></strong>.</span>
                <?php if ($hasAgeDeclaration): ?>
                    <span style="color: var(--admin-text-secondary); font-family: 'Inter', sans-serif; font-size: 14px; margin-left: 8px;">Declared age: <strong style="color: var(--admin-text-primary);"><?= h((string)$declaredAge) ?></strong>.</span>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <?php if ($existingUser): ?>
                <span class="admin-badge admin-badge-success" style="padding: 6px 12px; font-size: 13px;">Existing account: <?= h($existingUser->username) ?></span>
            <?php else: ?>
                <a href="<?= $this->Url->build(['action' => 'createAccount', $message->message_id]) ?>" class="admin-btn-primary" style="padding: 6px 16px; font-size: 13px; background-color: #3B82F6; color: #FFFFFF; box-shadow: none;">
                    <i class="bi bi-person-plus me-1"></i> Register Customer
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($hasAgeDeclaration): ?>
    <?php 
        $alertBg = $isDeclared18 ? 'rgba(16, 185, 129, 0.05)' : 'var(--admin-search-bg)';
        $alertBorder = $isDeclared18 ? 'rgba(16, 185, 129, 0.3)' : 'var(--admin-card-border)';
        $iconColor = $isDeclared18 ? '#10B981' : 'var(--admin-text-secondary)';
        $iconClass = $isDeclared18 ? 'bi-shield-check' : 'bi-info-circle';
    ?>
    <div class="admin-form-card d-flex align-items-center gap-3 mb-4" style="background-color: <?= $alertBg ?>; border: 1px solid <?= $alertBorder ?>; padding: 20px; max-width: 100%;">
        <div style="width: 40px; height: 40px; border-radius: 8px; background-color: <?= $isDeclared18 ? 'rgba(16, 185, 129, 0.1)' : 'var(--admin-card-bg)' ?>; display: flex; justify-content: center; align-items: center; border: 1px solid <?= $alertBorder ?>;">
            <i class="bi <?= $iconClass ?>" style="font-size: 20px; color: <?= $iconColor ?>;"></i>
        </div>
        <div>
            <strong style="color: var(--admin-text-primary); font-family: 'Inter', sans-serif; font-size: 15px;">Age Declaration:</strong>
            <span style="color: var(--admin-text-secondary); font-family: 'Inter', sans-serif; font-size: 14px;">
                <?php if ($isDeclared18): ?>
                    Customer self-declared they are <strong style="color: var(--admin-text-primary);">18 years or older</strong>.
                <?php else: ?>
                    Customer indicated they are <strong style="color: var(--admin-text-primary);">under 18</strong>.
                <?php endif; ?>
            </span>
        </div>
    </div>
<?php endif; ?>

<div class="admin-form-card mb-4" style="max-width: 100%; padding: 32px;">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 1px solid var(--admin-card-border);">
        <h2 class="admin-form-title" style="font-size: 20px; margin: 0;"><?= h($message->subject) ?></h2>
        <?php 
            $statusClass = 'admin-badge-neutral';
            if ($message->message_status === 'unread') $statusClass = 'admin-badge-info';
            if ($message->message_status === 'replied') $statusClass = 'admin-badge-success';
        ?>
        <span class="admin-badge <?= $statusClass ?>" style="padding: 6px 12px; font-size: 13px;"><?= h(ucfirst($message->message_status)) ?></span>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">From</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                    <?php if ($message->sender_user): ?>
                        <?= h($message->sender_user->username) ?> 
                        <span style="font-weight: 400; color: var(--admin-text-secondary); font-size: 14px;">(<?= h($message->sender_user->email) ?>)</span>
                    <?php else: ?>
                        <?= h($message->sender_name ?: 'Unknown') ?>
                        <?php if ($message->sender_email): ?>
                            <span style="font-weight: 400; color: var(--admin-text-secondary); font-size: 14px;">(<?= h($message->sender_email) ?>)</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Phone</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                    <?= h($message->sender_phone ?: '-') ?>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Type</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 14px; color: var(--admin-text-primary);">
                    <?= $isAccountRequest ? 'Customer Access Request' : 'Enquiry' ?>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Received</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 14px; color: var(--admin-text-primary);">
                    <?php if ($message->sent_at): ?>
                        <?= h($message->sent_at->timeAgoInWords()) ?>
                        <span style="display: block; font-weight: 400; color: var(--admin-text-secondary); margin-top: 4px;"><?= h($message->sent_at->format('j M Y, g:ia')) ?></span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Source Page</div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 14px; color: var(--admin-text-primary);">
                    <?= h($message->source_page ?: '-') ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <?php if ($isAccountRequest): ?>
                <div style="margin-bottom: 16px;">
                    <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Requested Account</div>
                    <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                        <?= h((string)($requestMeta['requested_role_label'] ?? 'Customer')) ?>
                    </div>
                </div>
                <div style="margin-bottom: 16px;">
                    <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Legacy Profile</div>
                    <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                        <?= h((string)($requestMeta['legacy_profile_label'] ?? 'Student')) ?>
                    </div>
                </div>
                <?php if ($existingUser): ?>
                    <div style="margin-bottom: 16px;">
                        <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Existing Account</div>
                        <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                            <?= h($existingUser->username) ?> 
                            <span style="font-weight: 400; color: var(--admin-text-secondary); font-size: 14px;">(<?= h($existingUser->email) ?>)</span>
                        </div>
                    </div>
                    <?php if ($linkedStudent): ?>
                        <div style="margin-bottom: 16px;">
                            <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Student Record</div>
                            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Students', 'action' => 'view', $linkedStudent->student_id, '?' => ['message' => $message->message_id]]) ?>" class="admin-action-link view" style="padding: 4px 12px; height: auto; width: auto; font-size: 13px; text-decoration: none; border-radius: 6px; display: inline-block;">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Open Customer Profile
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($hasAgeDeclaration): ?>
                <div style="margin-bottom: 16px;">
                    <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Declared Age</div>
                    <div style="font-family: 'Inter', sans-serif; font-weight: 600; font-size: 15px; color: var(--admin-text-primary);">
                        <?= h((string)$declaredAge) ?>
                    </div>
                </div>
                <div style="margin-bottom: 16px;">
                    <div style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Adult Status</div>
                    <span class="admin-badge <?= $isDeclared18 ? 'admin-badge-success' : 'admin-badge-neutral' ?>" style="padding: 4px 10px; font-size: 12px;">
                        <?= $isDeclared18 ? '18 or older' : 'Under 18' ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<h3 class="admin-form-title mb-3" style="font-size: 18px;">Enquiry Thread</h3>
<div class="admin-form-card" style="max-width: 100%; padding: 0; background-color: transparent; border: none; box-shadow: none;">
    <div class="d-flex flex-column gap-3">
        <!-- Original Enquiry -->
        <div style="background-color: var(--admin-card-bg); border: 1px solid var(--admin-card-border); border-radius: 12px; padding: 20px;">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px solid var(--admin-card-border);">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background-color: var(--admin-search-bg); display: flex; justify-content: center; align-items: center; color: var(--admin-text-secondary);">
                        <i class="bi bi-person"></i>
                    </div>
                    <div>
                        <strong style="display: block; font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-primary);">
                            <?php if ($message->sender_user): ?><?= h($message->sender_user->username) ?>
                            <?php else: ?><?= h($message->sender_name ?: 'Unknown') ?><?php endif; ?>
                        </strong>
                        <span style="font-family: 'Inter', sans-serif; font-size: 12px; color: var(--admin-text-secondary);">
                            <?= $message->sent_at ? $message->sent_at->format('j M Y, g:ia') : '' ?>
                        </span>
                    </div>
                </div>
                <span class="admin-badge admin-badge-neutral">Original enquiry</span>
            </div>
            <div style="font-family: 'Inter', sans-serif; font-size: 15px; color: var(--admin-text-primary); line-height: 1.6; white-space: pre-wrap;"><?= h($displayText !== '' ? $displayText : 'No additional notes provided.') ?></div>
        </div>

        <!-- Replies -->
        <?php foreach ($replies as $reply): ?>
            <?php
                $deliveryClass = 'admin-badge-neutral';
                $deliveryLabel = ucfirst((string)($reply->delivery_status ?: 'pending'));
                if ($reply->delivery_status === 'sent') {
                    $deliveryClass = 'admin-badge-success';
                } elseif ($reply->delivery_status === 'failed') {
                    $deliveryClass = 'admin-badge-danger';
                } elseif ($reply->delivery_status === 'pending') {
                    $deliveryClass = 'admin-badge-warning';
                }
            ?>
            <div style="background-color: var(--admin-search-bg); border: 1px solid var(--admin-card-border); border-radius: 12px; padding: 20px; margin-left: 32px; position: relative;">
                <div style="position: absolute; left: -20px; top: 32px; width: 20px; height: 1px; background-color: var(--admin-card-border);"></div>
                <div style="position: absolute; left: -20px; top: -16px; width: 1px; height: 48px; background-color: var(--admin-card-border);"></div>
                
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px solid var(--admin-card-border);">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background-color: var(--admin-card-bg); display: flex; justify-content: center; align-items: center; color: #10B981; border: 1px solid rgba(16, 185, 129, 0.2);">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <div>
                            <strong style="display: block; font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-primary);">
                                <?php if ($reply->sender_user): ?><?= h($reply->sender_user->username) ?>
                                <?php else: ?>Admin<?php endif; ?>
                            </strong>
                            <span style="font-family: 'Inter', sans-serif; font-size: 12px; color: var(--admin-text-secondary);">
                                <?= $reply->sent_at ? $reply->sent_at->format('j M Y, g:ia') : '' ?>
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="admin-badge <?= $deliveryClass ?>"><?= h($deliveryLabel) ?></span>
                    </div>
                </div>
                <div style="font-family: 'Inter', sans-serif; font-size: 13px; color: var(--admin-text-secondary); margin-bottom: 12px;">
                    Reply sent to <?= h($reply->recipient_name ?: $reply->recipient_email ?: 'external contact') ?>
                    <?php if ($reply->recipient_email): ?>
                        <span style="opacity: 0.65;">(<?= h($reply->recipient_email) ?>)</span>
                    <?php endif; ?>
                </div>
                <div style="font-family: 'Inter', sans-serif; font-size: 15px; color: var(--admin-text-primary); line-height: 1.6; white-space: pre-wrap;"><?= h($reply->message_text) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
