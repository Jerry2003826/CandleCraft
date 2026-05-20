<?php
declare(strict_types=1);

namespace App\Service\Cms;

use Cake\I18n\DateTime;

/**
 * Result of {@see SectionLockService::acquire()}.
 *
 * `status` is one of the class constants below.
 *   - ACQUIRED        : caller now holds a fresh lock
 *   - ALREADY_SELF    : caller already held an active lock
 *   - HELD_BY_OTHER   : another user holds the lock; metadata in `holderId`,
 *                      `holderName`, `lockedAt`, `expiresAt`
 */
final class LockOutcome
{
    public const ACQUIRED = 'acquired';
    public const ALREADY_SELF = 'already_self';
    public const HELD_BY_OTHER = 'held_by_other';

    /**
     * Construct.
     *
     * @param mixed $status Status.
     * @param mixed $holderId Holderid.
     * @param mixed $holderName Holdername.
     * @param mixed $lockedAt Lockedat.
     * @param mixed $expiresAt Expiresat.
     * @return mixed
     */
    public function __construct(
        public readonly string $status,
        public readonly ?int $holderId = null,
        public readonly ?string $holderName = null,
        public readonly ?DateTime $lockedAt = null,
        public readonly ?DateTime $expiresAt = null,
    ) {
    }
}
