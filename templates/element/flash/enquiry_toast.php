<?php
/**
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="enquiry-toast" role="status" aria-live="polite" aria-atomic="true">
    <span class="enquiry-toast__icon" aria-hidden="true">&#10003;</span>
    <p class="enquiry-toast__message"><?= $message ?></p>
    <button type="button" class="enquiry-toast__close" aria-label="Dismiss notification">&times;</button>
    <span class="enquiry-toast__bar" aria-hidden="true"></span>
</div>
