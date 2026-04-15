<?php
/**
 * @var \App\View\AppView $this
 */
$homeUrl = $this->Url->build('/');
$this->Html->css('redesign.css?v=' . time(), ['block' => true]);
?>

<style>
    /* Hide default layout header and footer for full-screen login */
    .hero-home, .home-footer { display: none !important; }
    body.site-home { background: #fff; }
    main { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .home-shell { display: block; }
</style>

<script>
    // Apply theme immediately to prevent FOUC on login page
    const savedTheme = localStorage.getItem('admin-theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
</script>

<div class="login-screen">
    <div class="login-image-container">
        <img src="<?= $this->Url->build('/image/Pottery.jpeg') ?>" alt="Pottery" class="login-image" />
    </div>
    
    <div class="login-form-container">
        <div class="login-form-wrapper">
            <div class="login-header">
                <p class="login-overline">SECURE ACCESS</p>
                <h1 class="login-title">User Login Page</h1>
                <p class="login-subtitle">Enter the email and password issued by CandleCraft Academy to open your portal.</p>
            </div>

            <div class="login-flash-container">
                <?= $this->Flash->render() ?>
            </div>

            <div class="login-fields">
                <?= $this->Form->create(null, [
                    'url' => ['controller' => 'Users', 'action' => 'login'],
                ]) ?>

                <div class="login-field">
                    <label class="login-label">Academy Email</label>
                    <?= $this->Form->email('email', [
                        'id' => 'email',
                        'placeholder' => 'email@candlecraft.com',
                        'required' => true,
                        'class' => 'login-input'
                    ]) ?>
                </div>

                <div class="login-field">
                    <label class="login-label">Password</label>
                    <?= $this->Form->password('password', [
                        'id' => 'password',
                        'placeholder' => '••••••••',
                        'required' => true,
                        'class' => 'login-input'
                    ]) ?>
                </div>

                <div class="login-actions">
                    <a href="<?= h($homeUrl) ?>" class="login-return-link">
                        Return to homepage
                    </a>
                    <?= $this->Form->button(__('Sign In'), ['class' => 'login-submit-btn']) ?>
                </div>

                <?= $this->Form->end() ?>
            </div>

            <div class="login-footer">
                <p class="login-footer-text">
                    Need access? Submit the enquiry form and our admin team can create your portal account.
                </p>
            </div>
        </div>
    </div>
</div>
