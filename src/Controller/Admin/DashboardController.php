<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class DashboardController extends AppController
{
    public function index(): void
    {
        $messagesTable = $this->fetchTable('Messages');
        $enquiryConditions = ['Messages.message_type' => 'contact_form'];

        $totalEnquiries = $messagesTable->find()->where($enquiryConditions)->count();
        $newMessages = $messagesTable->find()->where($enquiryConditions + ['Messages.message_status' => 'unread'])->count();
        $repliedMessages = $messagesTable->find()->where($enquiryConditions + ['Messages.message_status' => 'replied'])->count();

        $totalStudents = $this->fetchTable('Students')->find()->where(['Students.student_status' => 'active'])->count();
        $totalTeachers = $this->fetchTable('Teachers')->find()->where(['Teachers.teacher_status' => 'active'])->count();
        $totalClasses = $this->fetchTable('Classes')->find()->where(['Classes.class_status IN' => ['scheduled', 'ongoing']])->count();
        $totalBookings = $this->fetchTable('Bookings')->find()->where(['Bookings.booking_status IN' => ['pending', 'confirmed']])->count();

        $recentMessages = $messagesTable->find()
            ->contain(['SenderUsers'])
            ->where($enquiryConditions)
            ->order(['Messages.sent_at' => 'DESC'])
            ->limit(10)
            ->all();

        $this->set(compact(
            'totalEnquiries', 'newMessages', 'repliedMessages',
            'totalStudents', 'totalTeachers', 'totalClasses', 'totalBookings',
            'recentMessages'
        ));
    }
}
