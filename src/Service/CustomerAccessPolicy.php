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

    public function isCustomerRole(string $role): bool
    {
        return in_array($role, self::CUSTOMER_ROLES, true);
    }

    public function isCustomerIdentity(mixed $identity): bool
    {
        return $this->isCustomerRole($this->readString($identity, 'user_role'));
    }

    public function isAdultConfirmed(mixed $identity): bool
    {
        return $this->readBool($identity, 'age_verified_by_admin');
    }

    public function canBook(mixed $identity): bool
    {
        return $this->isCustomerIdentity($identity) && $this->isAdultConfirmed($identity);
    }

    public function canPay(mixed $identity): bool
    {
        return $this->canBook($identity);
    }

    public function canViewResources(mixed $identity): bool
    {
        return $this->isCustomerIdentity($identity);
    }

    public function canViewSchedule(mixed $identity): bool
    {
        return $this->isCustomerIdentity($identity);
    }

    public function canViewAttendance(mixed $identity): bool
    {
        return $this->isCustomerIdentity($identity);
    }

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
