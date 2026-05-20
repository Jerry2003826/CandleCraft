<?php
declare(strict_types=1);

namespace App\Service;

final class CustomerAccessPolicy
{
    /**
     * Legacy-compatible customer roles.
     *
     * `customer` is reserved for future unification, while existing
     * `student` and `parent` roles continue to work unchanged.
     *
     * @var list<string>
     */
    private const CUSTOMER_ROLES = ['student', 'parent', 'customer'];

    /**
     * Is customer role.
     *
     * @param mixed $role Role.
     */
    public function isCustomerRole(string $role): bool
    {
        return in_array($role, self::CUSTOMER_ROLES, true);
    }

    /**
     * Is customer identity.
     *
     * @param mixed $identity Identity.
     */
    public function isCustomerIdentity(mixed $identity): bool
    {
        return $this->isCustomerRole($this->readString($identity, 'user_role'));
    }

    /**
     * Is adult confirmed.
     *
     * @param mixed $identity Identity.
     * @param mixed $currentUser Currentuser.
     */
    public function isAdultConfirmed(mixed $identity, mixed $currentUser = null): bool
    {
        $verificationSource = $this->hasField($currentUser, 'age_verified_by_admin')
            ? $currentUser
            : $identity;

        return $this->readBool($verificationSource, 'age_verified_by_admin');
    }

    /**
     * Can book.
     *
     * @param mixed $identity Identity.
     * @param mixed $currentUser Currentuser.
     */
    public function canBook(mixed $identity, mixed $currentUser = null): bool
    {
        $roleSource = $this->hasField($currentUser, 'user_role')
            ? $currentUser
            : $identity;

        return $this->isCustomerIdentity($roleSource)
            && $this->isAdultConfirmed($identity, $currentUser);
    }

    /**
     * Can pay.
     *
     * @param mixed $identity Identity.
     * @param mixed $currentUser Currentuser.
     */
    public function canPay(mixed $identity, mixed $currentUser = null): bool
    {
        return $this->canBook($identity, $currentUser);
    }

    /**
     * Can view resources.
     *
     * @param mixed $identity Identity.
     */
    public function canViewResources(mixed $identity): bool
    {
        return $this->isCustomerIdentity($identity);
    }

    /**
     * Can view schedule.
     *
     * @param mixed $identity Identity.
     */
    public function canViewSchedule(mixed $identity): bool
    {
        return $this->isCustomerIdentity($identity);
    }

    /**
     * Can view attendance.
     *
     * @param mixed $identity Identity.
     */
    public function canViewAttendance(mixed $identity): bool
    {
        return $this->isCustomerIdentity($identity);
    }

    /**
     * Has field.
     *
     * @param mixed $record Record.
     * @param mixed $field Field.
     */
    private function hasField(mixed $record, string $field): bool
    {
        if ($record === null) {
            return false;
        }

        if (is_array($record)) {
            return array_key_exists($field, $record);
        }

        if (is_object($record) && method_exists($record, 'has')) {
            return $record->has($field);
        }

        if (is_object($record) && method_exists($record, 'get')) {
            return $record->get($field) !== null;
        }

        return false;
    }

    /**
     * Read string.
     *
     * @param mixed $identity Identity.
     * @param mixed $field Field.
     */
    private function readString(mixed $identity, string $field): string
    {
        if (is_object($identity) && method_exists($identity, 'get')) {
            return (string)$identity->get($field);
        }

        if (is_array($identity)) {
            return (string)($identity[$field] ?? '');
        }

        return '';
    }

    /**
     * Read bool.
     *
     * @param mixed $identity Identity.
     * @param mixed $field Field.
     */
    private function readBool(mixed $identity, string $field): bool
    {
        if (is_object($identity) && method_exists($identity, 'get')) {
            return (bool)$identity->get($field);
        }

        if (is_array($identity)) {
            return (bool)($identity[$field] ?? false);
        }

        return false;
    }
}
