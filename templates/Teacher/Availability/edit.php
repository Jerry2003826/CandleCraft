<?php
/**
 * @var \App\View\AppView $this
 * @var array $daysMap
 * @var iterable $existingSlots
 */
$this->assign('title', 'Edit Schedule');
?>

<div class="card">
    <div class="card-header">
        <h3>Edit Weekly Availability</h3>
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-sm">&larr; Back</a>
    </div>
    <div class="card-body">
        <p style="color: #666; margin-bottom: 20px;">
            Set your available time slots for each day of the week. Students will see this when browsing classes.
        </p>

        <?= $this->Form->create(null, ['url' => ['action' => 'edit']]) ?>
        <div id="slots-container">
            <?php
            $slotIndex = 0;
            foreach ($existingSlots as $slot):
            ?>
                <div class="slot-row" style="display: flex; gap: 10px; align-items: center; margin-bottom: 12px; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                    <?= $this->Form->select("slots.{$slotIndex}.day_of_week", $daysMap, [
                        'value' => $slot->day_of_week,
                        'style' => 'padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd;',
                    ]) ?>
                    <label style="font-size: 0.9em;">From:</label>
                    <?= $this->Form->time("slots.{$slotIndex}.start_time", [
                        'value' => $slot->start_time ? $slot->start_time->format('H:i') : '09:00',
                        'style' => 'padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd;',
                    ]) ?>
                    <label style="font-size: 0.9em;">To:</label>
                    <?= $this->Form->time("slots.{$slotIndex}.end_time", [
                        'value' => $slot->end_time ? $slot->end_time->format('H:i') : '17:00',
                        'style' => 'padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd;',
                    ]) ?>
                    <button type="button" onclick="this.closest('.slot-row').remove()" class="btn btn-sm" style="color: #e74c3c;">Remove</button>
                </div>
                <?php $slotIndex++; ?>
            <?php endforeach; ?>
        </div>

        <button type="button" onclick="addSlot()" class="btn btn-sm" style="margin: 10px 0;">+ Add Time Slot</button>

        <div style="margin-top: 20px;">
            <?= $this->Form->button('Save Schedule', ['class' => 'btn btn-primary']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>

<script>
let slotIndex = <?= $slotIndex ?>;
const daysMap = <?= json_encode($daysMap) ?>;

function addSlot() {
    const container = document.getElementById('slots-container');
    const row = document.createElement('div');
    row.className = 'slot-row';
    row.style.cssText = 'display: flex; gap: 10px; align-items: center; margin-bottom: 12px; padding: 10px; background: #f8f9fa; border-radius: 8px;';

    let options = '';
    for (const [value, label] of Object.entries(daysMap)) {
        options += `<option value="${value}">${label}</option>`;
    }

    row.innerHTML = `
        <select name="slots[${slotIndex}][day_of_week]" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd;">
            ${options}
        </select>
        <label style="font-size: 0.9em;">From:</label>
        <input type="time" name="slots[${slotIndex}][start_time]" value="09:00" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd;">
        <label style="font-size: 0.9em;">To:</label>
        <input type="time" name="slots[${slotIndex}][end_time]" value="17:00" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd;">
        <button type="button" onclick="this.closest('.slot-row').remove()" class="btn btn-sm" style="color: #e74c3c;">Remove</button>
    `;
    container.appendChild(row);
    slotIndex++;
}
</script>
