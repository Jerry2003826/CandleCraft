<?php
/**
 * @var \App\View\AppView $this
 * @var array $schedule
 * @var array $daysMap
 * @var iterable $existingSlots
 * @var iterable $blockedDates
 * @var string $calendarMinDate
 * @var string $calendarMaxDate
 */
$this->assign('title', 'Manage Availability');
$slotIndex = 0;
$activeTab = $this->request->getQuery('tab') === 'blocked' ? 'blocked' : 'general';
?>

<!-- Tabs -->
<div class="admin-tabs mb-4">
    <button type="button" class="admin-tab <?= $activeTab === 'general' ? 'active' : '' ?>" onclick="switchTab('general')">
        <i class="bi bi-calendar-week me-1"></i> General Availability
    </button>
    <button type="button" class="admin-tab <?= $activeTab === 'blocked' ? 'active' : '' ?>" onclick="switchTab('blocked')">
        <i class="bi bi-slash-circle me-1"></i> Block Out Dates
    </button>
</div>

<!-- ===== GENERAL AVAILABILITY TAB ===== -->
<div id="tab-general" <?= $activeTab !== 'general' ? 'style="display:none"' : '' ?>>

    <?php if (!empty($schedule)): ?>
        <!-- Hide the header-level "Edit Availability" button when the
             schedule is empty: the empty-state card below already shows
             a primary "Add Availability" CTA, so a second button up here
             would just orphan itself in the right-hand whitespace. -->
        <div class="d-flex justify-content-end mb-4">
            <button id="edit-btn" onclick="showEdit()" class="admin-btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                <i class="bi bi-pencil"></i> Edit Availability
            </button>
        </div>
    <?php endif; ?>

    <!-- Overview -->
    <div id="overview-section">
        <?php if (empty($schedule)): ?>
            <div class="admin-form-card text-center py-5" style="max-width: 100%;">
                <i class="bi bi-calendar-x text-muted" style="font-size: 48px;"></i>
                <p class="mt-3 text-muted" style="font-family: 'Inter', sans-serif; font-size: 15px;">You have not set your availability yet.</p>
                <button onclick="showEdit()" class="admin-btn-primary mt-3 mx-auto" style="display: inline-flex; align-items: center; gap: 8px;">
                    <i class="bi bi-plus-lg"></i> Add Availability
                </button>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($daysMap as $dayNum => $dayName): ?>
                    <?php if (isset($schedule[$dayName])): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="admin-form-card h-100" style="padding: 24px;">
                                <h3 class="admin-form-title mb-3" style="font-size: 16px; border-bottom: 1px solid var(--admin-card-border); padding-bottom: 12px;">
                                    <?= h($dayName) ?>
                                </h3>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach ($schedule[$dayName] as $slot): ?>
                                        <div class="d-flex align-items-center gap-2" style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-primary);">
                                            <i class="bi bi-clock" style="color: var(--admin-brand-icon);"></i>
                                            <?= h($slot->start_time?->format('g:ia') ?? '') ?>
	                                            <span style="color: var(--admin-text-secondary);">&ndash;</span>
	                                            <?= h($slot->end_time?->format('g:ia') ?? '') ?>
	                                        </div>
                                            <div style="font-family: 'Inter', sans-serif; font-size: 12px; color: var(--admin-text-secondary); padding-left: 24px;">
                                                <?= $slot->valid_from ? h($slot->valid_from->format('j M Y')) : 'No start date' ?>
                                                &ndash;
                                                <?= $slot->valid_until ? h($slot->valid_until->format('j M Y')) : 'No end date' ?>
                                            </div>
	                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit form (hidden by default) -->
    <div id="edit-section" style="display: none;">
        <div class="admin-form-card">
            <p style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary); margin-bottom: 24px;">
                Set your recurring weekly available time slots. Students will see this when browsing classes.
                Set the date range this weekly availability applies to.
            </p>

            <?= $this->Form->create(null, ['url' => ['action' => 'edit']]) ?>
                <div id="slots-container">
                    <?php foreach ($existingSlots as $slot): ?>
                        <?php
                        $dayId   = "slot-{$slotIndex}-day";
                        $startId = "slot-{$slotIndex}-start";
                        $endId   = "slot-{$slotIndex}-end";
                        ?>
                        <div class="slot-row" style="background-color: var(--admin-search-bg); border-radius: 12px; padding: 20px; margin-bottom: 16px; display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap;">
                            <?= $this->Form->hidden("slots.{$slotIndex}.id", ['value' => $slot->id]) ?>
                            <div class="flex-grow-1" style="min-width: 120px;">
                                <label class="admin-form-label" for="<?= h($dayId) ?>">Day</label>
                                <?= $this->Form->select("slots.{$slotIndex}.day_of_week", $daysMap, [
                                    'id' => $dayId, 'value' => $slot->day_of_week, 'class' => 'admin-form-select',
                                ]) ?>
                            </div>
                            <div class="flex-grow-1" style="min-width: 120px;">
                                <label class="admin-form-label" for="<?= h($startId) ?>">From</label>
                                <?= $this->Form->time("slots.{$slotIndex}.start_time", [
                                    'id' => $startId, 'value' => $slot->start_time ? $slot->start_time->format('H:i') : '09:00', 'class' => 'admin-form-input',
                                ]) ?>
                            </div>
	                            <div class="flex-grow-1" style="min-width: 120px;">
	                                <label class="admin-form-label" for="<?= h($endId) ?>">To</label>
	                                <?= $this->Form->time("slots.{$slotIndex}.end_time", [
	                                    'id' => $endId, 'value' => $slot->end_time ? $slot->end_time->format('H:i') : '17:00', 'class' => 'admin-form-input',
	                                ]) ?>
	                            </div>
                                <div class="flex-grow-1" style="min-width: 150px;">
                                    <label class="admin-form-label" for="slot-<?= h((string)$slotIndex) ?>-valid-from">Start date</label>
                                    <?= $this->Form->date("slots.{$slotIndex}.valid_from", [
                                        'id' => "slot-{$slotIndex}-valid-from",
                                        'value' => $slot->valid_from?->format('Y-m-d') ?? '',
                                        'max' => $calendarMaxDate,
                                        'class' => 'admin-form-input',
                                    ]) ?>
                                </div>
                                <div class="flex-grow-1" style="min-width: 150px;">
                                    <label class="admin-form-label" for="slot-<?= h((string)$slotIndex) ?>-valid-until">End date</label>
                                    <?= $this->Form->date("slots.{$slotIndex}.valid_until", [
                                        'id' => "slot-{$slotIndex}-valid-until",
                                        'value' => $slot->valid_until?->format('Y-m-d') ?? '',
                                        'max' => $calendarMaxDate,
                                        'class' => 'admin-form-input',
                                    ]) ?>
                                </div>
	                            <div style="padding-bottom: 2px;">
                                <button type="button" onclick="if(confirm('Remove this time slot?')) this.closest('.slot-row').remove()" class="admin-action-link delete" style="padding: 10px 16px; height: auto; width: auto;" title="Remove">
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
                    <?= $this->Form->button('Save', ['class' => 'admin-btn-primary']) ?>
                    <button type="button" onclick="cancelEdit()" class="admin-btn-secondary">Cancel</button>
                </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<!-- ===== BLOCK OUT DATES TAB ===== -->
<div id="tab-blocked" <?= $activeTab !== 'blocked' ? 'style="display:none"' : '' ?>>

    <!-- Add blocked date form -->
    <div class="admin-form-card mb-4" style="padding: 24px;">
        <h3 class="admin-form-title mb-3" style="font-size: 16px;">Block Out a Date</h3>
        <p style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--admin-text-secondary); margin-bottom: 20px;">
            Mark specific dates you are unavailable - e.g. holidays, personal days, or one-off absences.
        </p>
        <?= $this->Form->create(null, ['url' => ['action' => 'addBlockedDate']]) ?>
            <div class="d-flex align-items-end gap-3 flex-wrap">
                <div style="min-width: 180px;">
                    <label class="admin-form-label mb-1">Date</label>
	                    <?= $this->Form->date('blocked_date', [
	                        'class' => 'admin-form-input',
	                        'min'   => date('Y-m-d'),
                            'max'   => $calendarMaxDate,
	                        'required' => true,
	                    ]) ?>
                </div>
                <div class="flex-grow-1" style="min-width: 200px;">
                    <label class="admin-form-label mb-1">Reason <span style="font-weight: 400; color: var(--admin-text-secondary);">(optional)</span></label>
                    <?= $this->Form->text('reason', [
                        'class'       => 'admin-form-input',
                        'placeholder' => 'e.g. Public holiday, personal leave...',
                    ]) ?>
                </div>
                <?= $this->Form->button('Block Date', ['class' => 'admin-btn-primary', 'style' => 'white-space: nowrap;']) ?>
            </div>
        <?= $this->Form->end() ?>
    </div>

    <!-- Existing blocked dates list -->
    <div class="admin-table-card">
        <?php if ($blockedDates->isEmpty()): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-check text-muted" style="font-size: 48px;"></i>
                <p class="mt-3 text-muted" style="font-family: 'Inter', sans-serif; font-size: 15px;">No dates blocked yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Reason</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blockedDates as $blocked): ?>
                            <tr>
                                <td>
                                    <p class="admin-table-primary-text mb-0">
                                        <?= h($blocked->blocked_date?->format('j M Y') ?? '') ?>
                                    </p>
                                </td>
                                <td>
                                    <p class="admin-table-secondary-text mb-0">
                                        <?= h($blocked->blocked_date?->format('l') ?? '') ?>
                                    </p>
                                </td>
                                <td>
                                    <p class="admin-table-secondary-text mb-0">
                                        <?= $blocked->reason ? h($blocked->reason) : '<span style="color: var(--admin-text-secondary); opacity: 0.5;">-</span>' ?>
                                    </p>
                                </td>
                                <td class="text-end">
                                    <?= $this->Form->create(null, [
                                        'url' => ['action' => 'deleteBlockedDate', $blocked->id],
                                        'class' => 'd-inline',
                                    ]) ?>
                                        <button type="submit" class="admin-action-link delete" title="Remove" onclick="return confirm('Remove this blocked date?');">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?= $this->Form->end() ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
let slotIndex = <?= $slotIndex ?>;
const daysMap = <?= json_encode($daysMap) ?>;
const calendarMinDate = '<?= h($calendarMinDate) ?>';
const calendarMaxDate = '<?= h($calendarMaxDate) ?>';

function switchTab(tab) {
    document.getElementById('tab-general').style.display = tab === 'general' ? 'block' : 'none';
    document.getElementById('tab-blocked').style.display  = tab === 'blocked'  ? 'block' : 'none';
    document.querySelectorAll('.admin-tab').forEach((btn, i) => {
        btn.classList.toggle('active', (i === 0 && tab === 'general') || (i === 1 && tab === 'blocked'));
    });
}

function showEdit() {
    document.getElementById('overview-section').style.display = 'none';
    document.getElementById('edit-section').style.display     = 'block';
    document.getElementById('edit-btn').style.display         = 'none';
}

function cancelEdit() {
    document.getElementById('overview-section').style.display = 'block';
    document.getElementById('edit-section').style.display     = 'none';
    document.getElementById('edit-btn').style.display         = 'inline-flex';
}

function addSlot() {
    const container = document.getElementById('slots-container');
    const row = document.createElement('div');
    row.className = 'slot-row';
    row.style.cssText = 'background-color: var(--admin-search-bg); border-radius: 12px; padding: 20px; margin-bottom: 16px; display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap; animation: fadeIn 0.2s ease-out;';

    let options = '';
    for (const [value, label] of Object.entries(daysMap)) {
        options += `<option value="${value}">${label}</option>`;
    }

    row.innerHTML = `
        <div class="flex-grow-1" style="min-width: 120px;">
            <label class="admin-form-label" for="slot-${slotIndex}-day">Day</label>
            <select id="slot-${slotIndex}-day" name="slots[${slotIndex}][day_of_week]" class="admin-form-select">${options}</select>
        </div>
        <div class="flex-grow-1" style="min-width: 120px;">
            <label class="admin-form-label" for="slot-${slotIndex}-start">From</label>
            <input id="slot-${slotIndex}-start" type="time" name="slots[${slotIndex}][start_time]" value="09:00" class="admin-form-input">
        </div>
        <div class="flex-grow-1" style="min-width: 120px;">
            <label class="admin-form-label" for="slot-${slotIndex}-end">To</label>
            <input id="slot-${slotIndex}-end" type="time" name="slots[${slotIndex}][end_time]" value="17:00" class="admin-form-input">
        </div>
        <div class="flex-grow-1" style="min-width: 150px;">
            <label class="admin-form-label" for="slot-${slotIndex}-valid-from">Start date</label>
            <input id="slot-${slotIndex}-valid-from" type="date" name="slots[${slotIndex}][valid_from]" value="${calendarMinDate}" max="${calendarMaxDate}" class="admin-form-input">
        </div>
        <div class="flex-grow-1" style="min-width: 150px;">
            <label class="admin-form-label" for="slot-${slotIndex}-valid-until">End date</label>
            <input id="slot-${slotIndex}-valid-until" type="date" name="slots[${slotIndex}][valid_until]" max="${calendarMaxDate}" class="admin-form-input">
        </div>
        <div style="padding-bottom: 2px;">
            <button type="button" onclick="if(confirm('Remove this time slot?')) this.closest('.slot-row').remove()" class="admin-action-link delete" style="padding: 10px 16px; height: auto; width: auto;" title="Remove">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
    slotIndex++;
}

const style = document.createElement('style');
style.innerHTML = `@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }`;
document.head.appendChild(style);
</script>
