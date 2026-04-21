<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

use Cake\Chronos\ChronosDate;
use Cake\Http\Response;
use RuntimeException;

class AccountController extends AppController
{
    public function index(): void
    {
        $student = $this->getStudentWithUser();
        $effectiveAge = $this->determineRecordedAge($student);

        $this->set(compact('student', 'effectiveAge'));
        $this->set('title', 'My Account');
    }

    public function edit(): ?Response
    {
        $studentsTable = $this->fetchTable('Students');
        $usersTable = $this->fetchTable('Users');
        $student = $this->getStudentWithUser();
        $user = $student->user ?: $usersTable->get($student->user_id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $student = $studentsTable->patchEntity($student, $this->request->getData(), [
                'fields' => [
                    'student_name',
                    'declared_age',
                    'date_of_birth',
                    'medical_notes',
                ],
            ]);
            $user = $usersTable->patchEntity($user, $this->request->getData(), [
                'fields' => ['email'],
            ]);
            $student->set('user', $user);

            if ($student->hasErrors() || $user->hasErrors()) {
                $this->Flash->error(
                    $this->extractFirstValidationError($student->getErrors(), null)
                    ?? $this->extractFirstValidationError($user->getErrors(), 'Your account details could not be updated.')
                );
            } else {
                $verificationCleared = false;
                $ageCheck = $this->evaluateAdultEligibility($student);

                if ($user->age_verified_by_admin && !$ageCheck['eligible']) {
                    $user->age_verified_by_admin = false;
                    $verificationCleared = true;
                }

                $connection = $studentsTable->getConnection();
                $connection->begin();

                try {
                    if (!$usersTable->save($user)) {
                        throw new RuntimeException(
                            $this->extractFirstValidationError($user->getErrors(), 'Your account email could not be updated.')
                        );
                    }

                    if (!$studentsTable->save($student)) {
                        throw new RuntimeException(
                            $this->extractFirstValidationError($student->getErrors(), 'Your personal details could not be updated.')
                        );
                    }

                    $connection->commit();
                    $this->Flash->success(__('Your account details have been updated.'));
                    if ($verificationCleared) {
                        $this->Flash->warning(__('Adult verification was removed automatically because the updated age details show you are under 18.'));
                    }

                    return $this->redirect(['action' => 'index']);
                } catch (\Throwable $exception) {
                    $connection->rollback();
                    $this->Flash->error($exception->getMessage());
                }
            }
        }

        $this->set(compact('student', 'user'));
        $this->set('title', 'Edit My Account');

        return null;
    }

    private function getStudentWithUser(): object
    {
        $identity = $this->Authentication->getIdentity();

        return $this->fetchTable('Students')->find()
            ->contain(['Users'])
            ->where(['Students.user_id' => $identity->get('user_id')])
            ->firstOrFail();
    }

    private function determineRecordedAge(object $student): ?int
    {
        if (!empty($student->date_of_birth)) {
            return (int)$student->date_of_birth->diff(new ChronosDate())->y;
        }

        if ($student->declared_age !== null) {
            return (int)$student->declared_age;
        }

        return null;
    }

    /**
     * @param object $student
     * @return array{eligible: bool, age: ?int, source: ?string}
     */
    private function evaluateAdultEligibility(object $student): array
    {
        if (!empty($student->date_of_birth)) {
            $age = (int)$student->date_of_birth->diff(new ChronosDate())->y;

            return [
                'eligible' => $age >= 18,
                'age' => $age,
                'source' => 'date_of_birth',
            ];
        }

        if ($student->declared_age !== null) {
            $age = (int)$student->declared_age;

            return [
                'eligible' => $age >= 18,
                'age' => $age,
                'source' => 'declared_age',
            ];
        }

        return [
            'eligible' => false,
            'age' => null,
            'source' => null,
        ];
    }

    private function extractFirstValidationError(array $errors, ?string $fallback = null): ?string
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
}
