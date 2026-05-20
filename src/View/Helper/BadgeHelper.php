<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;

class BadgeHelper extends Helper
{
    private array $statusMap = [
        'active' => 'bg-success',
        'inactive' => 'bg-danger',
        'pending' => 'bg-warning text-dark',
        'confirmed' => 'bg-success',
        'cancelled' => 'bg-danger',
        'scheduled' => 'bg-info',
        'ongoing' => 'bg-success',
        'completed' => 'bg-secondary',
        'full' => 'bg-danger',
        'unread' => 'bg-primary',
        'read' => 'bg-info',
        'replied' => 'bg-warning text-dark',
        'archived' => 'bg-secondary',
        'present' => 'bg-success',
        'absent' => 'bg-danger',
        'late' => 'bg-warning text-dark',
        'excused' => 'bg-info',
        'new' => 'bg-success',
        'paid' => 'bg-success',
    ];

    /**
     * Status.
     *
     * @param mixed $status Status.
     */
    public function status(string $status): string
    {
        $class = $this->statusMap[$status] ?? 'bg-secondary';

        return sprintf('<span class="badge %s">%s</span>', $class, ucfirst(h($status)));
    }
}
