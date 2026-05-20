<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class DashboardController extends AppController
{
    /**
     * Index.
     */
    public function index(): void
    {
        $messagesTable = $this->fetchTable('Messages');
        $enquiryConditions = ['Messages.message_type' => 'contact_form'];
        $accountRequestConditions = [
            'Messages.message_type' => 'contact_form',
            'OR' => [
                ['Messages.source_page' => 'account-request'],
                ['Messages.message_text LIKE' => '%[REQUEST TYPE: account_access]%'],
                ['Messages.message_text LIKE' => '%[REQUEST TYPE: customer_access]%'],
            ],
        ];
        $pendingAccountRequestConditions = $accountRequestConditions + [
            'Messages.message_status IN' => ['unread', 'read'],
        ];

        $totalEnquiries = $messagesTable->find()->where($enquiryConditions)->count();
        $newMessages = $messagesTable->find()->where($enquiryConditions + ['Messages.message_status' => 'unread'])->count();
        $repliedMessages = $messagesTable->find()->where($enquiryConditions + ['Messages.message_status' => 'replied'])->count();
        $pendingAccountRequestCount = $messagesTable->find()->where($pendingAccountRequestConditions)->count();

        $totalStudents = $this->fetchTable('Students')->find()->where(['Students.student_status' => 'active'])->count();
        $totalTeachers = $this->fetchTable('Teachers')->find()->where(['Teachers.teacher_status' => 'active'])->count();
        $totalClasses = $this->fetchTable('Classes')->find()->where(['Classes.class_status IN' => ['scheduled', 'ongoing']])->count();
        $totalBookings = $this->fetchTable('Bookings')->find()->where(['Bookings.booking_status IN' => ['pending', 'confirmed']])->count();

        $recentMessages = $messagesTable->find()
            ->contain(['SenderUsers'])
            ->where($enquiryConditions)
            ->orderBy(['Messages.sent_at' => 'DESC'])
            ->limit(10)
            ->all();

        $pendingAccountRequests = $messagesTable->find()
            ->where($pendingAccountRequestConditions)
            ->orderBy(['Messages.sent_at' => 'DESC'])
            ->limit(5)
            ->all();

        $existingUsersByEmail = [];
        $linkedStudentsByUserId = [];
        $requesterEmails = [];
        foreach ($pendingAccountRequests as $request) {
            if (!empty($request->sender_email)) {
                $requesterEmails[] = (string)$request->sender_email;
            }
        }

        $requesterEmails = array_values(array_unique($requesterEmails));
        if ($requesterEmails !== []) {
            $users = $this->fetchTable('Users')->find()
                ->where(['Users.email IN' => $requesterEmails])
                ->all();

            $studentUserIds = [];
            foreach ($users as $user) {
                $existingUsersByEmail[(string)$user->email] = $user;
                if (in_array((string)$user->user_role, ['student', 'customer'], true)) {
                    $studentUserIds[] = (string)$user->user_id;
                }
            }

            if ($studentUserIds !== []) {
                $students = $this->fetchTable('Students')->find()
                    ->where(['Students.user_id IN' => $studentUserIds])
                    ->all();

                foreach ($students as $student) {
                    if ($student->user_id !== null) {
                        $linkedStudentsByUserId[(string)$student->user_id] = $student;
                    }
                }
            }
        }

        $this->set(compact(
            'totalEnquiries',
            'newMessages',
            'repliedMessages',
            'totalStudents',
            'totalTeachers',
            'totalClasses',
            'totalBookings',
            'recentMessages',
            'pendingAccountRequestCount',
            'pendingAccountRequests',
            'existingUsersByEmail',
            'linkedStudentsByUserId',
        ));

        // Recent bookings for admin management
        $pendingBookings = $this->fetchTable('Bookings')->find()->where(['Bookings.booking_status' => 'pending'])->count();

        $recentBookings = $this->fetchTable('Bookings')->find()
            ->contain(['Students', 'Classes' => ['Courses']])
            ->orderBy(['Bookings.booking_date' => 'DESC'])
            ->limit(10)
            ->all();

        $this->set(compact('pendingBookings', 'recentBookings'));
    }
}
