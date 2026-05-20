<?php

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var string $token
 */
$this->Html->css('redesign.css?v=' . time(), ['block' => true]);
?>

<script>
    const savedTheme = localStorage.getItem('admin-theme');
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
</script>

<style>
    .login-page-wrap {
        display: flex;
        justify-content: center;
        padding: 3rem 1rem 4rem;
    }
    .login-page-wrap .login-form-container {
        flex: none;
        width: 100%;
        max-width: 750px;
        align-items: flex-start;
        padding: 28px;
    }
    .login-page-wrap .login-form-wrapper {
        max-width: 100%;
        background: rgba(47, 34, 25, 0.85);
        backdrop-filter: blur(10px);
        border-radius: 24px;
        border: 1px solid rgba(210, 154, 88, 0.3);
        padding: 48px 40px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        transition: border-color 0.2s ease;
    }
    .login-page-wrap .login-form-wrapper:hover {
        border-color: rgba(210, 154, 88, 0.6);
    }
    .login-page-wrap .login-overline {
        color: var(--home-accent, #d29a58);
        font-family: var(--font-grown, serif);
        letter-spacing: 0.3em;
    }
    .login-page-wrap .login-title {
        color: #f5ecdf;
        font-family: var(--font-grown, serif);
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }
    .login-page-wrap .login-subtitle {
        color: var(--home-text-muted, rgba(245, 236, 223, 0.65));
        font-family: var(--font-grown, serif);
    }
    .login-page-wrap .login-label {
        display: block;
        color: #f5ecdf;
        font-family: var(--font-grown, serif);
        margin-bottom: 8px;
    }
    .login-page-wrap .login-input {
        display: block;
        width: 100%;
        min-height: 52px;
        box-sizing: border-box;
        margin: 0;
        padding: 14px 16px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.07);
        border: 1px solid rgba(210, 154, 88, 0.35);
        color: #f5ecdf;
        font-family: var(--font-grown, serif);
        font-size: 1rem;
        outline: none;
        appearance: none;
    }
    .login-page-wrap .login-input::placeholder {
        color: rgba(245, 236, 223, 0.4);
    }
    .login-page-wrap .login-input:focus {
        border-color: rgba(210, 154, 88, 0.8);
        box-shadow: 0 0 0 3px rgba(210, 154, 88, 0.12);
        background: rgba(255, 255, 255, 0.1);
    }
    .login-page-wrap .login-submit-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 48px;
        padding: 14px 32px;
        border: 0;
        border-radius: 999px;
        background: var(--home-accent, #d29a58);
        color: #2d190e;
        cursor: pointer;
        font-family: var(--font-grown, serif);
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        line-height: 1;
        text-transform: uppercase;
        box-shadow: 0 14px 30px rgba(0, 0, 0, 0.18);
    }
    .login-page-wrap .login-submit-btn:hover {
        background: rgba(210, 154, 88, 0.85);
    }
    .login-page-wrap .login-return-link {
        color: var(--home-accent, #d29a58);
    }
    .login-page-wrap .login-field {
        margin-bottom: 20px;
    }
    .login-page-wrap .login-actions {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        margin-top: 28px;
    }
    .login-page-wrap .login-flash-container .message,
    .login-page-wrap .login-flash-container div,
    .login-page-wrap .alert-danger {
        background: rgba(138, 48, 44, 0.4) !important;
        border: 1px solid rgba(225, 110, 103, 0.34) !important;
        border-radius: 8px !important;
        color: #ffd8d4 !important;
        padding: 12px 16px !important;
        font-family: var(--font-grown, serif) !important;
        font-size: 0.95rem !important;
        margin-bottom: 16px !important;
    }
    .login-page-wrap .login-flash-container div a,
    .login-page-wrap .btn-close {
        display: none !important;
    }
</style>

<div class="login-page-wrap">
    <div class="login-form-container">
        <div class="login-form-wrapper">
            <div class="login-header">
                <p class="login-overline">SECURE ACCESS</p>
                <h1 class="login-title">Reset Password</h1>
                <p class="login-subtitle" style="font-size: 1.1rem;">Choose a new password for your portal account.</p>
            </div>

            <div class="login-fields">
                <?= $this->Form->create($user, ['url' => ['action' => 'resetPassword', $token]]) ?>

                <div class="login-flash-container">
                    <?= $this->Flash->render() ?>
                </div>

                <div class="login-field">
                    <label class="login-label" for="password">New Password</label>
                    <?= $this->Form->password('password', [
                        'id' => 'password',
                        'placeholder' => 'Use at least 8 characters',
                        'required' => true,
                        'minlength' => 8,
                        'class' => 'login-input',
                        'autocomplete' => 'new-password',
                    ]) ?>
                </div>

                <div class="login-actions">
                    <?= $this->Form->button('Update Password', ['class' => 'login-submit-btn', 'type' => 'submit']) ?>
                </div>

                <?= $this->Form->end() ?>

                <div style="text-align: right; margin-top: 8px;">
                    <a href="<?= $this->Url->build(['action' => 'login']) ?>" class="login-return-link" style="font-size: 0.9rem;">Back to login</a>
                </div>
            </div>
        </div>
    </div>
</div>
