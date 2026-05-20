<h2><?= h($title) ?></h2>
<p><?= h($message) ?></p>

<?php if (!empty($context)): ?>
    <h3>Context</h3>
    <dl>
        <?php foreach ($context as $key => $value): ?>
            <dt><?= h((string)$key) ?></dt>
            <dd><?= h(is_scalar($value) || $value === null ? (string)$value : json_encode($value)) ?></dd>
        <?php endforeach; ?>
    </dl>
<?php endif; ?>
