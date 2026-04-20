<?php
/**
 * @var \App\View\AppView $this
 * @var array $daysMap
 * @var iterable $existingSlots
 */
$this->assign('title', 'Edit Schedule');
?>

<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-back-link">
    <i class="bi bi-arrow-left"></i> Back to Schedule
</a>

<div class="admin-form-card">
    <div class="admin-form-header">
        <h2 class="admin-form-title">Edit Weekly Availability</h2>
    </div>
    
    <p style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary); margin-bottom: 24px;">
        Set your available time slots for each day of the week. Students will see this when browsing classes.
    </p>

    <?= $this->Form->create(null, ['url' => ['action' => 'edit']]) ?>
        <div id="slots-container">
            <?php
            $slotIndex = 0;
            foreach ($existingSlots as $slot):
                $dayId = "slot-{$slotIndex}-day";
                $startId = "slot-{$slotIndex}-start";
                $endId = "slot-{$slotIndex}-end";
            ?>
                <div class="slot-row" style="background-color: var(--admin-search-bg); border-radius: 12px; padding: 20px; margin-bottom: 16px; display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap;" role="group" aria-label="Availability slot <?= $slotIndex + 1 ?>">
                    <div class="flex-grow-1" style="min-width: 120px;">
                        <label class="admin-form-label" for="<?= h($dayId) ?>">Day</label>
                        <?= $this->Form->select("slots.{$slotIndex}.day_of_week", $daysMap, [
                            'id' => $dayId,
                            'value' => $slot->day_of_week,
                            'class' => 'admin-form-select',
                        ]) ?>
                    </div>
                    <div class="flex-grow-1" style="min-width: 120px;">
                        <label class="admin-form-label" for="<?= h($startId) ?>">From</label>
                        <?= $this->Form->time("slots.{$slotIndex}.start_time", [
                            'id' => $startId,
                            'value' => $slot->start_time ? $slot->start_time->format('H:i') : '09:00',
                            'class' => 'admin-form-input',
                        ]) ?>
                    </div>
                    <div class="flex-grow-1" style="min-width: 120px;">
                        <label class="admin-form-label" for="<?= h($endId) ?>">To</label>
                        <?= $this->Form->time("slots.{$slotIndex}.end_time", [
                            'id' => $endId,
                            'value' => $slot->end_time ? $slot->end_time->format('H:i') : '17:00',
                            'class' => 'admin-form-input',
                        ]) ?>
                    </div>
                    <div style="padding-bottom: 2px;">
                        <button type="button" onclick="this.closest('.slot-row').remove()" class="admin-action-link delete" style="padding: 10px 16px; height: auto; width: auto;" title="Remove Slot" aria-label="Remove availability slot <?= $slotIndex + 1 ?>">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <?php $slotIndex++; ?>
            <?php endforeach; ?>
        </div>

        <button type="button" onclick="addSlot()" class="admin-btn-secondary mt-3 mb-5">
            <i class="bi bi-plus-lg me-1"></i> Add Time Slot
        </button>

        <div class="admin-form-actions">
            <?= $this->Form->button('Save Schedule', ['class' => 'admin-btn-primary']) ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="admin-btn-secondary">Cancel</a>
        </div>
    <?= $this->Form->end() ?>
</div>

<script>
let slotIndex = <?= $slotIndex ?>;
const daysMap = <?= json_encode($daysMap) ?>;

function addSlot() {
    const container = document.getElementById('slots-container');
    const row = document.createElement('div');
    row.className = 'slot-row';
    row.style.cssText = 'background-color: var(--admin-search-bg); border-radius: 12px; padding: 20px; margin-bottom: 16px; display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap; animation: fadeIn 0.3s ease-in-out;';
    row.setAttribute('role', 'group');
    row.setAttribute('aria-label', `Availability slot ${slotIndex + 1}`);

    let options = '';
    for (const [value, label] of Object.entries(daysMap)) {
        options += `<option value="${value}">${label}</option>`;
    }

    row.innerHTML = `
        <div class="flex-grow-1" style="min-width: 120px;">
            <label class="admin-form-label" for="slot-${slotIndex}-day">Day</label>
            <select id="slot-${slotIndex}-day" name="slots[${slotIndex}][day_of_week]" class="admin-form-select">
                ${options}
            </select>
        </div>
        <div class="flex-grow-1" style="min-width: 120px;">
            <label class="admin-form-label" for="slot-${slotIndex}-start">From</label>
            <input id="slot-${slotIndex}-start" type="time" name="slots[${slotIndex}][start_time]" value="09:00" class="admin-form-input">
        </div>
        <div class="flex-grow-1" style="min-width: 120px;">
            <label class="admin-form-label" for="slot-${slotIndex}-end">To</label>
            <input id="slot-${slotIndex}-end" type="time" name="slots[${slotIndex}][end_time]" value="17:00" class="admin-form-input">
        </div>
        <div style="padding-bottom: 2px;">
            <button type="button" onclick="this.closest('.slot-row').remove()" class="admin-action-link delete" style="padding: 10px 16px; height: auto; width: auto;" title="Remove Slot" aria-label="Remove availability slot ${slotIndex + 1}">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
    slotIndex++;
}

// Add simple fade-in animation
const style = document.createElement('style');
style.innerHTML = `
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
`;
document.head.appendChild(style);
</script>
