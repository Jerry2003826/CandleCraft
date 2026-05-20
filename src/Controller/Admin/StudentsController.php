<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Chronos\ChronosDate;
use RuntimeException;
use Throwable;

class StudentsController extends AppController
{
    /**
     * Index.
     */
    public function index(): void
    {
        $studentsTable = $this->fetchTable('Students');
        $query = $studentsTable->find()
            ->contain(['Users'])
            ->orderBy(['Students.student_name' => 'ASC']);

        $status = $this->request->getQuery('status');
        if ($status && in_array($status, ['active', 'inactive'])) {
            $query->where(['Students.student_status' => $status]);
        }

        $search = $this->request->getQuery('search');
        if ($search) {
            $query->where(['Students.student_name LIKE' => '%' . $search . '%']);
        }

        $students = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('students', 'status', 'search'));
    }

    /**
     * View.
     *
     * @param mixed $id Id.
     */
    public function view(?string $id = null): void
    {
        $studentsTable = $this->fetchTable('Students');
        $student = $studentsTable->get($id, contain: ['Users']);
        $this->set(compact('student'));
    }

    /**
     * Add.
     *
     * @return mixed
     */
    public function add()
    {
        $studentsTable = $this->fetchTable('Students');
        $usersTable = $this->fetchTable('Users');
        $student = $studentsTable->newEmptyEntity();
        $createPortalAccount = true;
        $portalAccount = [
            'username' => '',
            'email' => '',
            'password' => '',
            'account_status' => 'active',
        ];
        $portalAccountErrors = [];

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $calculatedAge = $this->calculateAgeFromDateString((string)($data['date_of_birth'] ?? ''));
            if ($calculatedAge !== null) {
                $data['declared_age'] = $calculatedAge;
            }
            $createPortalAccount = (bool)$this->request->getData('create_portal_account');
            $portalAccount = [
                'username' => trim((string)($data['username'] ?? '')),
                'email' => trim((string)($data['email'] ?? '')),
                'password' => (string)($data['password'] ?? ''),
                'account_status' => 'active',
            ];

            $student = $studentsTable->patchEntity($student, [
                'student_name' => $data['student_name'] ?? null,
                'declared_age' => $data['declared_age'] ?? null,
                'student_status' => $data['student_status'] ?? 'active',
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'medical_notes' => $data['medical_notes'] ?? null,
            ]);

            $isAdult = $calculatedAge !== null && $calculatedAge >= 18;

            if ($createPortalAccount) {
                $user = $usersTable->newEntity(
                    [
                        'username' => $portalAccount['username'],
                        'email' => $portalAccount['email'],
                        'password_hash' => $portalAccount['password'],
                        'user_role' => 'student',
                        'account_status' => $portalAccount['account_status'],
                        'age_verified_by_admin' => $isAdult,
                        'self_declared_adult' => false,
                    ],
                    [
                        'accessibleFields' => [
                            'user_role' => true,
                            'account_status' => true,
                            'age_verified_by_admin' => true,
                            'self_declared_adult' => true,
                        ],
                    ],
                );

                $portalAccountErrors = $user->getErrors();
                if ($user->hasErrors() || $student->hasErrors()) {
                    $this->set(compact('student', 'createPortalAccount', 'portalAccount', 'portalAccountErrors'));

                    return;
                }

                $connection = $studentsTable->getConnection();
                $connection->begin();
                try {
                    $savedUser = $usersTable->save($user);
                    if (!$savedUser) {
                        throw new RuntimeException($this->extractFirstValidationError(
                            $user->getErrors(),
                            'Could not create the student login.',
                        ));
                    }

                    $student->user_id = $savedUser->user_id;
                    $savedStudent = $studentsTable->save($student);
                    if (!$savedStudent) {
                        throw new RuntimeException($this->extractFirstValidationError(
                            $student->getErrors(),
                            'Could not save the student profile.',
                        ));
                    }

                    $connection->commit();
                    if ($isAdult) {
                        $this->Flash->success(__(
                            'The student and portal account have been created. Adult verification was granted' .
                            'automatically.',
                        ));
                    } else {
                        $this->Flash->success(__(
                            'The student and portal account have been created. Adult verification is required before' .
                            'booking and payment features unlock.',
                        ));
                    }

                    return $this->redirect(['action' => 'view', $savedStudent->student_id]);
                } catch (Throwable $exception) {
                    $connection->rollback();
                    $this->Flash->error($exception->getMessage());
                }
            } elseif ($studentsTable->save($student)) {
                $this->Flash->success(__(
                    'The student profile has been saved without a portal login.',
                ));

                return $this->redirect(['action' => 'view', $student->student_id]);
            }

            $this->Flash->error(__(
                'The student could not be saved. Please try again.',
            ));
        }

        $this->set(compact('student', 'createPortalAccount', 'portalAccount', 'portalAccountErrors'));
    }

    /**
     * Edit.
     *
     * @param mixed $id Id.
     * @return mixed
     */
    public function edit(?string $id = null)
    {
        $studentsTable = $this->fetchTable('Students');
        $student = $studentsTable->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $dobInput = trim((string)($data['date_of_birth'] ?? ''));
            $calculatedAge = $this->calculateAgeFromDateString($dobInput);
            if ($calculatedAge !== null) {
                // DOB takes precedence: derive declared_age from it.
                $data['declared_age'] = $calculatedAge;
            } elseif ($dobInput === '' && (!isset($data['declared_age']) || $data['declared_age'] === '')) {
                // No DOB and no declared_age provided: keep existing record value
                // so the form does not blank out an age previously set by admin.
                unset($data['declared_age']);
            }

            $student = $studentsTable->patchEntity($student, $data, [
                'fields' => [
                    'student_name',
                    'declared_age',
                    'student_status',
                    'date_of_birth',
                    'medical_notes',
                ],
            ]);
            if ($studentsTable->save($student)) {
                if ($student->user_id !== null) {
                    $usersTable = $this->fetchTable('Users');
                    $user = $usersTable->get($student->user_id);
                    $ageCheck = $this->evaluateAdultEligibility($student);

                    if ($user->age_verified_by_admin && !$ageCheck['eligible']) {
                        $user->age_verified_by_admin = false;
                        $usersTable->save($user);
                        $this->Flash->warning(__(
                            'Adult verification was removed automatically because the updated age details show this' .
                            'customer is under 18.',
                        ));
                    }
                }

                $this->Flash->success(__(
                    'The customer has been saved.',
                ));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__(
                'The student could not be saved. Please try again.',
            ));
        }

        $this->set(compact('student'));
    }

    /**
     * Verify age.
     *
     * @param mixed $id Id.
     * @return mixed
     */
    public function verifyAge(?string $id = null)
    {
        $this->request->allowMethod(['post']);

        $studentsTable = $this->fetchTable('Students');
        $usersTable = $this->fetchTable('Users');

        $student = $studentsTable->get($id, contain: ['Users']);

        if (!$student->user) {
            $this->Flash->error(__(
                'This student has no linked user account.',
            ));

            return $this->redirect($this->referer(['action' => 'view', $id], true));
        }

        $ageCheck = $this->evaluateAdultEligibility($student);
        if (!$ageCheck['eligible']) {
            $this->Flash->error($ageCheck['message']);

            return $this->redirect($this->referer(['action' => 'view', $id], true));
        }

        $user = $usersTable->get($student->user->user_id);
        $user->age_verified_by_admin = true;

        if ($usersTable->save($user)) {
            $this->Flash->success(__(
                'Adult verification recorded for {0}. Booking and payment features are now enabled.',
                $student->student_name,
            ));
        } else {
            $this->Flash->error(__(
                'Could not verify age. Please try again.',
            ));
        }

        return $this->redirect($this->referer(['action' => 'view', $id], true));
    }

    /**
     * Unverify age.
     *
     * @param mixed $id Id.
     * @return mixed
     */
    public function unverifyAge(?string $id = null)
    {
        $this->request->allowMethod(['post']);

        $studentsTable = $this->fetchTable('Students');
        $usersTable = $this->fetchTable('Users');

        $student = $studentsTable->get($id, contain: ['Users']);

        if (!$student->user) {
            $this->Flash->error(__(
                'This student has no linked user account.',
            ));

            return $this->redirect($this->referer(['action' => 'view', $id], true));
        }

        $user = $usersTable->get($student->user->user_id);

        if (!$user->age_verified_by_admin) {
            $this->Flash->warning(__(
                'Adult verification is already cleared for {0}.',
                $student->student_name,
            ));

            return $this->redirect($this->referer(['action' => 'view', $id], true));
        }

        $user->age_verified_by_admin = false;

        if ($usersTable->save($user)) {
            $this->Flash->success(__(
                'Adult verification has been removed for {0}. Booking and payment access are locked again.',
                $student->student_name,
            ));
        } else {
            $this->Flash->error(__(
                'Could not remove adult verification. Please try again.',
            ));
        }

        return $this->redirect($this->referer(['action' => 'view', $id], true));
    }

    /**
     * Delete.
     *
     * @param mixed $id Id.
     * @return mixed
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $studentsTable = $this->fetchTable('Students');
        $student = $studentsTable->get($id);
        if ($studentsTable->delete($student)) {
            $this->Flash->success(__(
                'The student has been deleted.',
            ));
        } else {
            $this->Flash->error(__(
                'The student could not be deleted. Please try again.',
            ));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Extract first validation error.
     *
     * @param mixed $errors Errors.
     * @param mixed $fallback Fallback.
     */
    private function extractFirstValidationError(array $errors, string $fallback): string
    {
        foreach ($errors as $fieldErrors) {
            if (!is_array($fieldErrors)) {
                continue;
            }

            foreach ($fieldErrors as $message) {
                if (is_string($message) && $message !== '') {
                    return $message;
                }
            }
        }

        return $fallback;
    }

    /**
     * @param object $student
     * @return array{eligible: bool, age: ?int, source: ?string, message: string}
     */
    private function evaluateAdultEligibility(object $student): array
    {
        if (!empty($student->date_of_birth)) {
            $age = (int)$student->date_of_birth->diff(new ChronosDate())->y;

            return [
                'eligible' => $age >= 18,
                'age' => $age,
                'source' => 'date_of_birth',
                'message' => $age >= 18
                    ? ''
                    : __('This customer cannot be verified as an adult because their date of birth shows they are only {0}.', $age),
            ];
        }

        if ($student->declared_age !== null) {
            $age = (int)$student->declared_age;

            return [
                'eligible' => $age >= 18,
                'age' => $age,
                'source' => 'declared_age',
                'message' => $age >= 18
                    ? ''
                    : __('This customer cannot be verified as an adult because their declared age is {0}.', $age),
            ];
        }

        return [
            'eligible' => false,
            'age' => null,
            'source' => null,
            'message' => __('This customer cannot be verified as an adult until a date of birth or declared age is recorded.'),
        ];
    }

    /**
     * Calculate age from date string.
     *
     * @param mixed $dateOfBirth Dateofbirth.
     */
    private function calculateAgeFromDateString(string $dateOfBirth): ?int
    {
        $dateOfBirth = trim($dateOfBirth);
        if ($dateOfBirth === '') {
            return null;
        }

        try {
            $dob = ChronosDate::parse($dateOfBirth);
        } catch (Throwable) {
            return null;
        }

        $today = new ChronosDate();
        if ($dob > $today) {
            return null;
        }

        return (int)$dob->diff($today)->y;
    }
}
