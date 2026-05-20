<?php
/**
 * @var \App\View\AppView $this
 * @var string $message
 * @var string $url
 */
use Cake\Core\Configure;

$this->setLayout('error');
$statusCode = $this->response->getStatusCode();

if (Configure::read('debug')) :
    $this->setLayout('dev_error');

    $this->assign('title', $message);
    $this->assign('templateName', 'error400.php');

    $this->start('file');
    echo $this->element('auto_table_warning');
    $this->end();
endif;
?>
<?php if ($statusCode === 404): ?>
    <section class="site-error">
        <p class="site-error__eyebrow">404</p>
        <h1>Page Not Found</h1>
        <p>The page you are looking for does not exist.</p>
        <div class="site-error__actions">
            <?= $this->Html->link('Return Home', '/', ['class' => 'site-error__button']) ?>
            <?= $this->Html->link('Contact Support', '/contact', ['class' => 'site-error__link']) ?>
        </div>
    </section>
<?php else: ?>
    <section class="site-error">
        <p class="site-error__eyebrow"><?= h((string)$statusCode) ?></p>
        <h1><?= h($message) ?></h1>
        <p><?= __d('cake', 'The requested address {0} was not found on this server.', "<strong>'{$url}'</strong>") ?></p>
        <div class="site-error__actions">
            <?= $this->Html->link('Return Home', '/', ['class' => 'site-error__button']) ?>
        </div>
    </section>
<?php endif; ?>
