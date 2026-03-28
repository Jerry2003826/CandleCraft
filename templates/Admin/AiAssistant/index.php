<?php
/**
 * @var \App\View\AppView $this
 * @var string|null $question
 * @var string|null $response
 */
$this->assign('title', 'AI Assistant');
?>

<div class="card">
    <div class="card-header">
        <h3>AI Administrative Assistant</h3>
    </div>
    <div class="card-body">
        <p style="color: #666; margin-bottom: 20px;">
            Ask questions about course management, scheduling, bookings, or general administrative tasks.
        </p>

        <?= $this->Form->create(null, ['url' => ['action' => 'ask']]) ?>
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <?= $this->Form->text('question', [
                'placeholder' => 'e.g., How do I manage class capacity? What courses are most popular?',
                'style' => 'flex: 1; padding: 12px; border-radius: 8px; border: 1px solid #ddd; font-size: 1em;',
                'required' => true,
                'value' => $question ?? '',
            ]) ?>
            <?= $this->Form->button('Ask', ['class' => 'btn btn-primary', 'style' => 'padding: 12px 24px;']) ?>
        </div>
        <?= $this->Form->end() ?>

        <?php if (isset($response)): ?>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #3498db;">
                <div style="margin-bottom: 12px;">
                    <strong>Your Question:</strong>
                    <p style="color: #555;"><?= h($question) ?></p>
                </div>
                <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 12px 0;">
                <div>
                    <strong>AI Response:</strong>
                    <div style="color: #333; margin-top: 8px; white-space: pre-wrap;"><?= h($response) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div style="margin-top: 24px; padding: 16px; background: #f0f7ff; border-radius: 8px;">
            <h4 style="margin-bottom: 8px; color: #2c3e50;">Suggested Questions</h4>
            <ul style="color: #555; padding-left: 20px;">
                <li>How should I handle waitlisted students when a spot opens up?</li>
                <li>What's the best way to schedule pottery classes for beginners?</li>
                <li>How do I manage teacher availability during holiday periods?</li>
                <li>What information should I include in course descriptions?</li>
                <li>How to handle cancellations and refund policies?</li>
            </ul>
        </div>
    </div>
</div>
