<?php
/**
 * @var \App\View\AppView $this
 * @var string|null $url
 * @var string|null $label
 * @var bool|null $useHistory
 */
$url = $url ?? '#';
$label = $label ?? 'Back';
$useHistory = $useHistory ?? false;
?>
<div class="admin-back-row">
    <a
        href="<?= h($url) ?>"
        class="admin-back-link"
        <?= $useHistory ? 'onclick="history.back(); return false;"' : '' ?>
    >
        <i class="bi bi-arrow-left"></i> <?= h($label) ?>
    </a>
</div>
