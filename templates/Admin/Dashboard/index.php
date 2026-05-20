<?php
/**
 * @var \App\View\AppView $this
 * @var int $pendingAccountRequestCount
 * @var iterable $pendingAccountRequests
 * @var int $newMessages
 * @var int $totalClasses
 * @var int $pendingBookings
 * @var int $totalStudents
 * @var iterable $recentBookings
 * @var iterable $recentMessages
 */
$this->assign('title', 'Dashboard');

$stats = [
    [
        'title' => 'New Enquiries',
        'value' => $newMessages,
        'icon'  => 'bi-envelope-fill',
        'trend' => $newMessages > 0 ? 'Needs attention' : 'All clear',
        'accent'  => '#D97706',
        'bg'      => '#FFFBEB',
        'iconBg'  => '#FDE68A',
        'border'  => '#FCD34D',
        'urgent'  => $newMessages > 0,
    ],
    [
        'title' => 'Upcoming Classes',
        'value' => $totalClasses,
        'icon'  => 'bi-calendar3-fill',
        'trend' => 'Next 7 days',
        'accent'  => '#2563EB',
        'bg'      => '#EFF6FF',
        'iconBg'  => '#BFDBFE',
        'border'  => '#93C5FD',
        'urgent'  => false,
    ],
    [
        'title' => 'Pending Bookings',
        'value' => $pendingBookings,
        'icon'  => 'bi-hourglass-split',
        'trend' => $pendingBookings > 0 ? 'Pending action' : 'All confirmed',
        'accent'  => $pendingBookings > 0 ? '#DC2626' : '#059669',
        'bg'      => $pendingBookings > 0 ? '#FEF2F2' : '#ECFDF5',
        'iconBg'  => $pendingBookings > 0 ? '#FECACA' : '#A7F3D0',
        'border'  => $pendingBookings > 0 ? '#FCA5A5' : '#6EE7B7',
        'urgent'  => $pendingBookings > 0,
    ],
    [
        'title' => 'Active Customers',
        'value' => $totalStudents,
        'icon'  => 'bi-people-fill',
        'trend' => 'Total enrolled',
        'accent'  => '#059669',
        'bg'      => '#ECFDF5',
        'iconBg'  => '#A7F3D0',
        'border'  => '#6EE7B7',
        'urgent'  => false,
    ],
];
?>

<div class="admin-dashboard-wrapper" style="padding-bottom: 50px;">

    <!-- Stat cards -->
    <div class="row g-3 mb-4">
        <?php foreach ($stats as $stat): ?>
        <div class="col-sm-6 col-xl-3">
            <div style="background: <?= $stat['bg'] ?>; border: 1.5px solid <?= $stat['border'] ?>; border-radius: 16px; padding: 22px 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: <?= $stat['accent'] ?>; border-radius: 16px 0 0 16px;"></div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px;">
                    <h3 style="font-size: 11px; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; color: <?= $stat['accent'] ?>; margin: 0; opacity: 0.8;"><?= $stat['title'] ?></h3>
                    <div style="width: 38px; height: 38px; background: <?= $stat['iconBg'] ?>; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="bi <?= $stat['icon'] ?>" style="color: <?= $stat['accent'] ?>; font-size: 17px;"></i>
                    </div>
                </div>
                <div style="font-size: 2.4rem; font-weight: 800; color: <?= $stat['accent'] ?>; line-height: 1; margin-bottom: 8px;"><?= $stat['value'] ?></div>
                <div style="font-size: 12px; font-weight: 600; color: <?= $stat['accent'] ?>; opacity: 0.75; display: flex; align-items: center; gap: 5px;">
                    <?php if ($stat['urgent']): ?>
                        <i class="bi bi-exclamation-circle-fill" style="font-size: 11px;"></i>
                    <?php endif; ?>
                    <?= $stat['trend'] ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Actions -->
    <div style="background: #fff; border: 1.5px solid #E5E7EB; border-radius: 16px; padding: 18px 24px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <p style="font-size: 10px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: #9CA3AF; margin: 0 0 4px 0;">Common Tasks</p>
        <p style="font-size: 12px; color: #6B7280; margin: 0 0 14px 0;">Start with bookings or enquiries; use the sidebar for less frequent admin tools.</p>
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            <a href="<?= $this->Url->build(['controller' => 'Bookings',  'action' => 'index']) ?>"
               style="display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; background: #EFF6FF; color: #2563EB; border: 1.5px solid #BFDBFE; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; transition: background 0.15s;"
               onmouseover="this.style.background='#DBEAFE'" onmouseout="this.style.background='#EFF6FF'">
                <i class="bi bi-calendar-check-fill"></i> Manage Bookings
            </a>
            <a href="<?= $this->Url->build(['controller' => 'Messages',  'action' => 'index']) ?>"
               style="display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; background: #FFFBEB; color: #D97706; border: 1.5px solid #FCD34D; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; transition: background 0.15s;"
               onmouseover="this.style.background='#FEF3C7'" onmouseout="this.style.background='#FFFBEB'">
                <i class="bi bi-envelope-fill"></i> View Enquiries
            </a>
            <a href="<?= $this->Url->build(['controller' => 'Students',  'action' => 'index']) ?>"
               style="display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; background: #ECFDF5; color: #059669; border: 1.5px solid #6EE7B7; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; transition: background 0.15s;"
               onmouseover="this.style.background='#D1FAE5'" onmouseout="this.style.background='#ECFDF5'">
                <i class="bi bi-people-fill"></i> All Customers
            </a>
            <a href="<?= $this->Url->build(['controller' => 'Classes',   'action' => 'add']) ?>"
               style="display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; background: #F5F3FF; color: #7C3AED; border: 1.5px solid #C4B5FD; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; transition: background 0.15s;"
               onmouseover="this.style.background='#EDE9FE'" onmouseout="this.style.background='#F5F3FF'">
                <i class="bi bi-plus-circle-fill"></i> Add Class
            </a>
            <a href="<?= $this->Url->build(['controller' => 'Courses',   'action' => 'index']) ?>"
               style="display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; background: #FFF1F2; color: #E11D48; border: 1.5px solid #FECDD3; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; transition: background 0.15s;"
               onmouseover="this.style.background='#FFE4E6'" onmouseout="this.style.background='#FFF1F2'">
                <i class="bi bi-mortarboard-fill"></i> Courses
            </a>
        </div>
    </div>

    <!-- Main panels -->
    <div class="row g-4">

        <!-- Recent Bookings -->
        <div class="col-lg-7">
            <div style="background: #fff; border: 1.5px solid #BFDBFE; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 20px rgba(37,99,235,0.07);">
                <div style="padding: 20px 24px; border-bottom: 1.5px solid #BFDBFE; background: linear-gradient(135deg, #EFF6FF 0%, #F0F9FF 100%); display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; background: #2563EB; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-calendar-check-fill" style="color: #fff; font-size: 14px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 16px; font-weight: 700; color: #1E3A5F; margin: 0;">Recent Booking Activity</h3>
                            <p style="font-size: 12px; color: #64748B; margin: 4px 0 0;">Shows the latest booking records only; payment receipts are inside booking details.</p>
                        </div>
                    </div>
                    <a href="<?= $this->Url->build(['controller' => 'Bookings', 'action' => 'index']) ?>"
                       style="font-size: 12px; font-weight: 700; color: #2563EB; text-transform: uppercase; letter-spacing: 0.1em; text-decoration: none;">
                        View All &rarr;
                    </a>
                </div>

                <div style="padding: 20px; display: flex; flex-direction: column; gap: 10px;">
                    <?php if ($recentBookings->isEmpty()): ?>
                        <div style="text-align: center; padding: 40px; color: #9CA3AF;">
                            <i class="bi bi-calendar-x" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                            No recent activity.
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentBookings as $booking):
                            $status = strtolower((string)$booking->booking_status);
                            $statusStyles = [
                                'confirmed' => ['bg' => '#ECFDF5', 'color' => '#065F46', 'border' => '#6EE7B7'],
                                'pending'   => ['bg' => '#FFFBEB', 'color' => '#92400E', 'border' => '#FCD34D'],
                                'cancelled' => ['bg' => '#FEF2F2', 'color' => '#991B1B', 'border' => '#FCA5A5'],
                            ];
                            $s = $statusStyles[$status] ?? ['bg' => '#F3F4F6', 'color' => '#374151', 'border' => '#D1D5DB'];
                        ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border: 1.5px solid #E5E7EB; border-radius: 12px; transition: border-color 0.2s, box-shadow 0.2s;"
                             onmouseover="this.style.borderColor='#93C5FD';this.style.boxShadow='0 2px 8px rgba(37,99,235,0.1)'"
                             onmouseout="this.style.borderColor='#E5E7EB';this.style.boxShadow='none'">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 38px; height: 38px; background: #EFF6FF; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="bi bi-person-fill" style="color: #2563EB; font-size: 16px;"></i>
                                </div>
                                <div>
                                    <p style="font-weight: 700; font-size: 14px; color: #111827; margin: 0;"><?= h($booking->student?->student_name ?? 'Guest') ?></p>
                                    <p style="font-size: 12px; color: #6B7280; margin: 0;"><?= h($booking->class_entity?->course?->course_name ?? 'Unspecified Course') ?></p>
                                </div>
                            </div>
                            <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: <?= $s['color'] ?>; background: <?= $s['bg'] ?>; border: 1px solid <?= $s['border'] ?>; padding: 4px 10px; border-radius: 20px;">
                                <?= h(ucfirst($status)) ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Enquiries -->
        <div class="col-lg-5">
            <div style="background: #fff; border: 1.5px solid #FCD34D; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 20px rgba(217,119,6,0.08);">
                <div style="padding: 20px 24px; border-bottom: 1.5px solid #FCD34D; background: linear-gradient(135deg, #FFFBEB 0%, #FEF9EC 100%); display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; background: #D97706; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-envelope-fill" style="color: #fff; font-size: 14px;"></i>
                        </div>
                        <h3 style="font-size: 16px; font-weight: 700; color: #78350F; margin: 0;">Enquiries</h3>
                    </div>
                    <a href="<?= $this->Url->build(['controller' => 'Messages', 'action' => 'index']) ?>"
                       style="font-size: 12px; font-weight: 700; color: #D97706; text-transform: uppercase; letter-spacing: 0.1em; text-decoration: none;">
                        View All &rarr;
                    </a>
                </div>

                <div style="display: flex; flex-direction: column;">
                    <?php if ($recentMessages->isEmpty()): ?>
                        <div style="text-align: center; padding: 40px; color: #9CA3AF;">
                            <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                            No pending messages.
                        </div>
                    <?php else: ?>
                        <?php $i = 0; foreach ($recentMessages as $message): $i++;
                            $unread = $message->message_status === 'unread';
                        ?>
                        <a href="<?= $this->Url->build(['controller' => 'Messages', 'action' => 'view', $message->message_id]) ?>"
                           style="display: flex; align-items: center; gap: 12px; padding: 16px 22px; text-decoration: none; background: <?= $unread ? '#FFFBEB' : ($i % 2 == 0 ? '#FAFAFA' : '#fff') ?>; border-bottom: 1px solid #FEF3C7; transition: background 0.15s;"
                           onmouseover="this.style.background='#FEF3C7'"
                           onmouseout="this.style.background='<?= $unread ? '#FFFBEB' : ($i % 2 == 0 ? '#FAFAFA' : '#fff') ?>'">

                            <div style="flex-shrink: 0; width: 34px; height: 34px; background: <?= $unread ? '#FDE68A' : '#F3F4F6' ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-person-fill" style="color: <?= $unread ? '#D97706' : '#9CA3AF' ?>; font-size: 14px;"></i>
                            </div>

                            <div style="flex: 1; min-width: 0;">
                                <p style="font-weight: <?= $unread ? '700' : '600' ?>; font-size: 14px; color: #111827; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= h($message->sender_name ?: 'Web Inquiry') ?></p>
                                <p style="font-size: 12px; color: #6B7280; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= h(\Cake\Utility\Text::truncate($message->subject, 32)) ?></p>
                            </div>

                            <div style="flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                <span style="font-size: 11px; font-weight: 600; color: #9CA3AF;"><?= $message->sent_at ? $message->sent_at->format('j M') : '-' ?></span>
                                <?php if ($unread): ?>
                                    <span style="display: inline-block; width: 8px; height: 8px; background: #D97706; border-radius: 50%;"></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>
