<?= $title ?>

<?= $message ?>

<?php if (!empty($context)): ?>
Context:
<?php foreach ($context as $key => $value): ?>
- <?= $key ?>: <?= is_scalar($value) || $value === null ? (string)$value : json_encode($value) ?>
<?php endforeach; ?>
<?php endif; ?>
