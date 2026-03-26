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
        'label' => 'Admin Portal',
        'title' => 'Manage operations',
        'description' => 'Oversee bookings, classes, enquiries, and academy records.',
        'email' => 'admin@candlecraft.com',
    ],
];
?>
<div class="portal-entry-grid">
    <?php foreach ($portalCards as $card): ?>
        <article class="portal-entry-card">
            <span class="portal-entry-card__label"><?= h($card['label']) ?></span>
            <h3><?= h($card['title']) ?></h3>
            <p><?= h($card['description']) ?></p>
            <span class="portal-entry-card__hint"><?= h($card['email']) ?></span>
        </article>
    <?php endforeach; ?>
</div>

<p class="portal-login-note">Unified login for students, teachers, and administrators.</p>

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
