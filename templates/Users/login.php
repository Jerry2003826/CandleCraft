<?php
/**
 * @var \App\View\AppView $this
 */
?>
<?= $this->Form->create(null, [
    'class' => 'login-form',
    'url' => ['controller' => 'Users', 'action' => 'login'],
]) ?>
    <div class="form-group">
        <label for="email">Email</label>
        <?= $this->Form->email('email', [
            'id' => 'email',
            'placeholder' => 'Enter your email',
            'required' => true,
        ]) ?>
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <?= $this->Form->password('password', [
            'id' => 'password',
            'placeholder' => 'Enter your password',
            'required' => true,
        ]) ?>
    </div>
    <div class="form-actions">
        <a href="#" class="forgot">Forgot Password</a>
    </div>
    <?= $this->Form->button(__('Log In'), ['class' => 'btn btn-primary btn-block']) ?>
<?= $this->Form->end() ?>

<div class="register-link">
    Don't have an account? <a href="#">Register</a>
</div>
