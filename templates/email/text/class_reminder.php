<?php
/**
 * @var string $recipient_name
 * @var string $class_name
 * @var string $schedule
 * @var string $location
 * @var string $teacher_name
 * @var string|null $login_url
 */
?>
Hello <?= $recipient_name ?: 'there' ?>,

This is a friendly reminder that your CandleCraft Academy class is coming up soon.

Class: <?= $class_name ?>
Date and time: <?= $schedule ?>
Location: <?= $location ?>
Teacher: <?= $teacher_name ?>

Please arrive a few minutes early so you have time to settle in.
<?php if (!empty($login_url)): ?>
You can log in to review your booking: <?= $login_url ?>
<?php endif; ?>

Warm regards,
CandleCraft Academy
