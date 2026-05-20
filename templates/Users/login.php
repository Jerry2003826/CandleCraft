<?php
/**
 * @var \App\View\AppView $this
 */
$homeUrl = $this->Url->build('/');
$this->Html->css('redesign.css?v=' . time(), ['block' => true]);
?>

<script>
    // Each new sign-in starts in light mode by default.
    localStorage.setItem('admin-theme', 'light');
    document.documentElement.removeAttribute('data-theme');
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
        font-size: 0.8rem;
        margin-bottom: 6px;
        white-space: nowrap;
    }
    .login-page-wrap .login-title {
        color: #f5ecdf;
        font-family: var(--font-grown, serif);
        text-transform: uppercase;
        letter-spacing: 0.1em;
        /* Without an explicit cap the global home.css `<h1>` sizing made
           "USER LOGIN PAGE" wrap to three lines on phones. Clamp it so
           the headline shrinks gracefully on narrow viewports while still
           reading large on desktop. */
        font-size: clamp(1.7rem, 6vw, 2.8rem);
        line-height: 1.15;
        margin: 0 0 12px;
    }
    .login-page-wrap .login-subtitle,
    .login-page-wrap .login-footer-text {
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
    .login-page-wrap .login-input::placeholder { color: rgba(245, 236, 223, 0.4); }
    .login-page-wrap .login-input:focus {
        border-color: rgba(210, 154, 88, 0.8);
        box-shadow: 0 0 0 3px rgba(210, 154, 88, 0.12);
        background: rgba(255, 255, 255, 0.1);
    }
    .login-page-wrap .login-return-link { color: var(--home-accent, #d29a58); }
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
    .login-page-wrap .login-submit-btn:hover { background: rgba(210, 154, 88, 0.85); }
    .login-page-wrap .login-footer { border-top-color: rgba(210, 154, 88, 0.2); }

    .login-page-wrap .login-field {
        margin-bottom: 20px;
    }

    .login-page-wrap .login-actions {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        margin-top: 28px;
    }

    .login-page-wrap .login-flash-container .message {
    background: rgba(138, 48, 44, 0.4);
    border: 1px solid rgba(225, 110, 103, 0.34);
    border-radius: 8px;
    color: #ffd8d4;
    padding: 12px 16px;
    margin-bottom: 16px;
    font-family: var(--font-grown, serif);
    font-size: 0.95rem;
}
.login-page-wrap .login-flash-container div {
    background: rgba(138, 48, 44, 0.4);
    border: 1px solid rgba(225, 110, 103, 0.34);
    border-radius: 8px;
    color: #ffd8d4;
    padding: 12px 16px;
    font-family: var(--font-grown, serif);
    font-size: 0.95rem;
    margin-bottom: 16px;
}

.login-page-wrap .login-flash-container div a {
    display: none;
}
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

.login-page-wrap .btn-close {
    display: none !important;
}

.login-page-wrap .login-flash-container {
    margin-bottom: 24px;
}

body.site-home.site-login {
    overflow: auto !important;
    height: auto !important;
}

.brand-mark__title {
    color: #ffffff !important;
}

.hero-nav__links a {
    color: #ffffff !important;
}

.hero-nav__links a:hover,
.hero-nav__links a:focus-visible {
    color: #ffffff !important;
}

</style>

<div class="login-page-wrap">
    <div class="login-form-container">
        <div class="login-form-wrapper">
            <div class="login-header">
                <p class="login-overline">SECURE ACCESS</p>
                <h1 class="login-title">User Login Page</h1>
                <p class="login-subtitle" style="font-size: 1.2rem;">Enter the email and password issued by CandleCraft Academy to open your portal.</p>           
            </div>

           

            <div class="login-fields">
                <?= $this->Form->create(null, [
                    'url' => ['controller' => 'Users', 'action' => 'login'],
                ]) ?>

                 <div class="login-flash-container">
                <?= $this->Flash->render() ?>
                </div>

                <div class="login-field">
                    <label class="login-label" for="email">Academy Email</label>
                    <?= $this->Form->email('email', [
                        'id' => 'email',
                        'placeholder' => 'email@candlecraft.com',
                        'required' => true,
                        'class' => 'login-input',
                        'autocomplete' => 'email',
                    ]) ?>
                </div>

                <div class="login-field">
                    <label class="login-label" for="password">Password</label>
                    <?= $this->Form->password('password', [
                        'id' => 'password',
                        'placeholder' => '********',
                        'required' => true,
                        'class' => 'login-input',
                        'autocomplete' => 'current-password',
                    ]) ?>
                </div>

                <div class="login-actions">
                    <?= $this->Form->button(__('Sign In'), ['class' => 'login-submit-btn', 'type' => 'submit']) ?>
                </div>
                <div style="text-align: right; margin-top: 8px;">
                    <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'forgotPassword']) ?>" class="login-return-link" style="font-size: 0.9rem;">
                    Forgot password?
                    </a>
                </div>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelector('.login-page-wrap form')?.addEventListener('submit', function () {
        localStorage.setItem('admin-theme', 'light');
        document.documentElement.removeAttribute('data-theme');
    });
</script>
