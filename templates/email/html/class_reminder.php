<?php
/**
 * @var string $recipient_name
 * @var string $class_name
 * @var string $schedule
 * @var string $location
 * @var string $teacher_name
 */
?>
<p>Hello <?= h($recipient_name ?: 'there') ?>,</p>

<p>This is a friendly reminder that your CandleCraft Academy class starts tomorrow.</p>

<div style="padding: 18px; border-radius: 14px; background: #f7f3ec; border: 1px solid #e4d7c2; margin: 20px 0;">
    <p style="margin: 0 0 10px 0;"><strong>Class:</strong> <?= h($class_name) ?></p>
    <p style="margin: 0 0 10px 0;"><strong>When:</strong> <?= h($schedule) ?></p>
    <p style="margin: 0 0 10px 0;"><strong>Location:</strong> <?= h($location) ?></p>
    <p style="margin: 0;"><strong>Teacher:</strong> <?= h($teacher_name) ?></p>
</div>

<p>We look forward to seeing you soon.</p>

<p>Kind regards,<br>CandleCraft Academy</p>
