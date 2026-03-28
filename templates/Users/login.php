<?php
/**
 * @var \App\View\AppView $this
 */
$homeUrl = $this->Url->build('/');

$portalCards = [
    [
        'label' => 'Student Portal',
        'title' => 'View class times',
        'description' => 'Check upcoming lessons and see attendance updates in one place.',
        'email' => 'alice.wong@candlecraft.com',
    ],
    [
        'label' => 'Teacher Portal',
        'title' => 'Mark attendance',
        'description' => 'Review teaching schedules and record attendance after each class.',
        'email' => 'emma.clay@candlecraft.com',
    ],
    [
        'label' => 'Parent Portal',
        'title' => 'Manage family bookings',
        'description' => 'Book classes for your children and complete payments in one place.',
        'email' => 'parent@candlecraft.com',
    ],
    [
        'label' => 'Admin Portal',
        'title' => 'Manage operations',
        'description' => 'Oversee bookings, classes, enquiries, and academy records.',
        'email' => 'admin@candlecraft.com',
    ],
];
?>

<!-- Portal info cards in a single row -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px;">
    <?php foreach ($portalCards as $card): ?>
    <div class="portal-entry-card">
    <span class="portal-entry-card__label"><?= h($card['label']) ?></span>
    <h3><?= h($card['title']) ?></h3>
    <p><?= h($card['description']) ?></p>
    <span class="portal-entry-card__hint"><?= h($card['email']) ?></span>
</div>
    <?php endforeach; ?>
</div>

<!-- Login form card -->
<div class="card" style="max-width: 560px; margin: 0 auto;">
    <div class="card-header">
        <h3>Portal Login</h3>
    </div>
    <div class="card-body">
        <p class="portal-login-note">Use your academy email and password to sign in.</p>

        <?= $this->Flash->render() ?>

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
            <span class="portal-login-hint">Use your academy email and password.</span>
            <a href="<?= h($homeUrl) ?>" class="forgot">Return to homepage</a>
        </div>

        <?= $this->Form->button(__('Log In'), ['class' => 'btn btn-primary btn-block']) ?>
        <?= $this->Form->end() ?>

        <div class="register-link">
            Need access? Contact CandleCraft Academy to set up your portal account.
        </div>
    </div>
</div>