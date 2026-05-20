<?php
declare(strict_types=1);

namespace App\Service\Cms;

use Cake\Datasource\EntityInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;

/**
 * Soft per-section editing lock with TTL + heartbeat.
 *
 * - Lock has a TTL of 5 minutes (configurable).
 * - Expired locks are treated as released and physically removed on next acquire.
 * - heartbeat() extends `expires_at` for the current holder only.
 * - forceTake() overwrites whoever held the lock, returning ACQUIRED.
 */
final class SectionLockService
{
    use LocatorAwareTrait;

    private int $ttlMinutes;

    /**
     * Construct.
     *
     * @param mixed $ttlMinutes Ttlminutes.
     * @return mixed
     */
    public function __construct(int $ttlMinutes = 5)
    {
        $this->ttlMinutes = $ttlMinutes;
    }

    /**
     * Acquire.
     *
     * @param mixed $sectionId Sectionid.
     * @param mixed $userId Userid.
     */
    public function acquire(int $sectionId, int $userId): LockOutcome
    {
        $existing = $this->findActiveLock($sectionId);

        if ($existing === null) {
            return $this->writeFreshLock($sectionId, $userId);
        }

        if ((int)$existing->get('locked_by_id') === $userId) {
            return new LockOutcome(
                LockOutcome::ALREADY_SELF,
                holderId: $userId,
                lockedAt: $existing->get('locked_at'),
                expiresAt: $existing->get('expires_at'),
            );
        }

        return new LockOutcome(
            LockOutcome::HELD_BY_OTHER,
            holderId: (int)$existing->get('locked_by_id'),
            holderName: $this->lookupUserName((int)$existing->get('locked_by_id')),
            lockedAt: $existing->get('locked_at'),
            expiresAt: $existing->get('expires_at'),
        );
    }

    /**
     * Heartbeat.
     *
     * @param mixed $sectionId Sectionid.
     * @param mixed $userId Userid.
     */
    public function heartbeat(int $sectionId, int $userId): bool
    {
        $existing = $this->findActiveLock($sectionId);
        if ($existing === null || (int)$existing->get('locked_by_id') !== $userId) {
            return false;
        }
        $existing->set('expires_at', DateTime::now()->addMinutes($this->ttlMinutes));
        $this->locks()->save($existing);

        return true;
    }

    /**
     * Release.
     *
     * @param mixed $sectionId Sectionid.
     * @param mixed $userId Userid.
     */
    public function release(int $sectionId, int $userId): void
    {
        $existing = $this->locks()->find()->where(['section_id' => $sectionId])->first();
        if ($existing === null || (int)$existing->get('locked_by_id') !== $userId) {
            return;
        }
        $this->locks()->delete($existing);
    }

    /**
     * Force take.
     *
     * @param mixed $sectionId Sectionid.
     * @param mixed $userId Userid.
     */
    public function forceTake(int $sectionId, int $userId): LockOutcome
    {
        $existing = $this->locks()->find()->where(['section_id' => $sectionId])->first();
        if ($existing !== null) {
            $this->locks()->delete($existing);
        }

        return $this->writeFreshLock($sectionId, $userId);
    }

    /**
     * Current holder.
     *
     * @param mixed $sectionId Sectionid.
     */
    public function currentHolder(int $sectionId): ?LockOutcome
    {
        $existing = $this->findActiveLock($sectionId);
        if ($existing === null) {
            return null;
        }

        return new LockOutcome(
            LockOutcome::HELD_BY_OTHER,
            holderId: (int)$existing->get('locked_by_id'),
            holderName: $this->lookupUserName((int)$existing->get('locked_by_id')),
            lockedAt: $existing->get('locked_at'),
            expiresAt: $existing->get('expires_at'),
        );
    }

    /**
     * Find active lock.
     *
     * @param mixed $sectionId Sectionid.
     */
    private function findActiveLock(int $sectionId): ?EntityInterface
    {
        $existing = $this->locks()->find()->where(['section_id' => $sectionId])->first();
        if ($existing === null) {
            return null;
        }

        $expiresAt = $existing->get('expires_at');
        if ($expiresAt instanceof DateTime && $expiresAt->lessThan(DateTime::now())) {
            $this->locks()->delete($existing);

            return null;
        }

        return $existing;
    }

    /**
     * Write fresh lock.
     *
     * @param mixed $sectionId Sectionid.
     * @param mixed $userId Userid.
     */
    private function writeFreshLock(int $sectionId, int $userId): LockOutcome
    {
        $now = DateTime::now();
        $expires = (clone $now)->addMinutes($this->ttlMinutes);
        $entity = $this->locks()->newEntity([
            'section_id' => $sectionId,
            'locked_by_id' => $userId,
            'locked_at' => $now,
            'expires_at' => $expires,
        ]);
        $this->locks()->saveOrFail($entity);

        return new LockOutcome(
            LockOutcome::ACQUIRED,
            holderId: $userId,
            holderName: $this->lookupUserName($userId),
            lockedAt: $now,
            expiresAt: $expires,
        );
    }

    /**
     * Locks.
     */
    private function locks(): Table
    {
        return $this->fetchTable('PageSectionLocks');
    }

    /**
     * Lookup user name.
     *
     * @param mixed $userId Userid.
     */
    private function lookupUserName(int $userId): ?string
    {
        $row = $this->fetchTable('Users')
            ->find()
            ->select(['username'])
            ->where(['user_id' => $userId])
            ->first();

        return $row?->get('username');
    }
}
