<?php
/**
 * @var string $recipient_name
 * @var string $class_name
 * @var string $schedule
 * @var string $location
 * @var string $teacher_name
 */
?>
Hello <?= $recipient_name ?: 'there' ?>,

This is a friendly reminder that your CandleCraft Academy class starts tomorrow.

Class: <?= $class_name ?>
When: <?= $schedule ?>
Location: <?= $location ?>
Teacher: <?= $teacher_name ?>

We look forward to seeing you soon.

Kind regards,
CandleCraft Academy
