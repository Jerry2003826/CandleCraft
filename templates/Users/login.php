<?php
/**
 * @var \App\View\AppView $this
 */
$homeUrl = $this->Url->build('/');
?>

<div class="login-container" style="padding: 40px 0 100px;">
    <div class="enquiry-card" style="max-width: 500px; margin: 0 auto; background: rgba(47, 34, 25, 0.85); backdrop-filter: blur(12px); border-radius: 24px; border: 1px solid rgba(210, 154, 88, 0.3); padding: 50px 40px; box-shadow: var(--home-shadow);">
        
     
     <div class="card-header" style="text-align: center; margin-bottom: 40px; border: none; background: transparent;">
            <p class="overline" style="font-family: var(--font-grown); color: var(--home-accent); letter-spacing: 0.3em; font-size: 0.7rem; margin-bottom: 12px;">
                Secure Access
            </p>
            
            <h1 style="font-family: var(--font-grown); text-transform: uppercase; font-weight: 400; letter-spacing: 0.15em; color: #f5ecdf; margin: 0; font-size: 2rem;">
                Portal Login
            </h1>
            
            <div style="width: 40px; height: 1px; background: var(--home-accent); margin: 20px auto 0; opacity: 0.6;"></div>
        </div>
            
        <div class="card-body">
            <p style="font-family: var(--font-grown); color: var(--home-text-muted); text-align: center; margin-bottom: 35px; font-size: 0.95rem; letter-spacing: 0.05em;">
                Enter your academy credentials to access your dashboard.
            </p>

            <?= $this->Flash->render() ?>

            <?= $this->Form->create(null, [
                'class' => 'login-form',
                'url' => ['controller' => 'Users', 'action' => 'login'],
            ]) ?>

            <div class="form-group" style="margin-bottom: 25px;">
                <label style="color: var(--home-accent-soft); font-family: var(--font-grown); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.15em; display: block; margin-bottom: 10px;">Academy Email</label>
                <?= $this->Form->email('email', [
                    'id' => 'email',
                    'placeholder' => 'email@candlecraft.com',
                    'required' => true,
                    'style' => 'width: 100%;'
                ]) ?>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="color: var(--home-accent-soft); font-family: var(--font-grown); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.15em; display: block; margin-bottom: 10px;">Password</label>
                <?= $this->Form->password('password', [
                    'id' => 'password',
                    'placeholder' => '••••••••',
                    'required' => true,
                    'style' => 'width: 100%;'
                ]) ?>
            </div>

            <div class="form-actions" style="display: flex; justify-content: flex-end; margin-bottom: 35px;">
                <a href="<?= h($homeUrl) ?>" style="font-size: 0.75rem; color: var(--home-accent); font-family: var(--font-grown); text-decoration: none; letter-spacing: 0.1em; border-bottom: 1px solid rgba(210, 154, 88, 0.3);">
                    Return to homepage
                </a>
            </div>

            <?= $this->Form->button(__('Sign In'), ['class' => 'btn-primary', 'style' => 'width: 100%; padding: 18px; font-size: 0.9rem; letter-spacing: 0.2em;']) ?>
            <?= $this->Form->end() ?>

            <div style="margin-top: 40px; text-align: center; font-size: 0.8rem; color: var(--home-text-muted); font-family: var(--font-grown); border-top: 1px solid rgba(255,255,255,0.1); padding-top: 25px; line-height: 1.6;">
                Need access? <br>
                <span style="color: var(--home-accent-soft);">Contact CandleCraft Academy</span> to set up your account.
            </div>
        </div>
    </div>
</div>