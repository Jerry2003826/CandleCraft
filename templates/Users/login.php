<?php
/**
 * @var \App\View\AppView $this
 */
$homeUrl = $this->Url->build('/');
?>

<div class="section-heading" style="text-align: center; padding: 60px 20px 20px;">
    <p class="overline" style="color: var(--home-accent); letter-spacing: 0.3em; margin-bottom: 12px; font-size: 0.8rem;">
        Secure Access
    </p>
    <h1 style="font-size: clamp(2.5rem, 5vw, 4rem); color: #f5ecdf; text-transform: uppercase; letter-spacing: 0.15em; margin: 0; line-height: 1;">
        Portal Login
    </h1>
    <div style="width: 60px; height: 2px; background: var(--home-accent); margin: 24px auto 0; opacity: 0.6;"></div>
</div>

<div class="enquiry-card" style="max-width: 500px; margin: 30px auto 80px; background: rgba(47, 34, 25, 0.85); backdrop-filter: blur(10px); border-radius: 24px; border: 1px solid rgba(210, 154, 88, 0.3); padding: 40px; box-shadow: var(--home-shadow);">

    <p style="color: var(--home-text-muted); text-align: center; margin-bottom: 30px; font-size: 0.95rem; letter-spacing: 0.05em;">
        Enter your academy credentials to access your dashboard.
    </p>

    <?= $this->Flash->render() ?>

    <div class="enquiry-form">
        <?= $this->Form->create(null, [
            'url' => ['controller' => 'Users', 'action' => 'login'],
        ]) ?>

        <div class="form-group" style="margin-bottom: 24px;">
            <label style="color: var(--home-accent-soft); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.1em; display: block; margin-bottom: 8px;">Academy Email</label>
            <?= $this->Form->email('email', [
                'id' => 'email',
                'placeholder' => 'email@candlecraft.com',
                'required' => true,
            ]) ?>
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
            <label style="color: var(--home-accent-soft); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.1em; display: block; margin-bottom: 8px;">Password</label>
            <?= $this->Form->password('password', [
                'id' => 'password',
                'placeholder' => '········',
                'required' => true,
            ]) ?>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 34px; flex-wrap: wrap; gap: 20px;">
            <a href="<?= h($homeUrl) ?>" style="font-size: 0.8rem; color: var(--home-accent); letter-spacing: 0.1em; border-bottom: 1px solid rgba(210, 154, 88, 0.3);">
                Return to homepage
            </a>
            <?= $this->Form->button(__('Sign In'), ['class' => 'btn-primary', 'style' => 'min-width: 160px; padding: 18px; font-size: 0.9rem; letter-spacing: 0.2em;']) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 0.8rem; color: var(--home-text-muted); border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 25px; line-height: 1.6;">
        Need access? <br>
        <span style="color: var(--home-accent-soft);">Contact CandleCraft Academy</span> to set up your account.
    </div>

</div>
