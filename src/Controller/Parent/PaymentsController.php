<?php
declare(strict_types=1);

namespace App\Controller\Parent;

use App\Service\BookingCancellationService;
use App\Service\PaymentCheckoutService;
use App\Service\StripeConfiguration;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Routing\Router;
use RuntimeException;

class PaymentsController extends AppController
{
    private function getParentEntity()
    {
        $identity = $this->Authentication->getIdentity();

        return $this->fetchTable('Parents')->find()
            ->where(['Parents.user_id' => $identity?->get('user_id')])
            ->firstOrFail();
    }

    private function getAllowedStudentIds(int $parentId): array
    {
        return $this->fetchTable('ParentStudents')->find()
            ->where(['ParentStudents.parent_id' => $parentId])
            ->all()
            ->extract('student_id')
            ->toArray();
    }

    private function isStripeConfigured(): bool
    {
        return StripeConfiguration::isCheckoutReady();
    }

    private function isDemoModeEnabled(): bool
    {
        return (bool)Configure::read('debug') && (bool)Configure::read('Payments.demo_mode');
    }

    private function getRequestBaseUrl(): string
    {
        $uri = $this->request->getUri();

        return $uri->getScheme() . '://' . $uri->getAuthority();
    }

    public function index(): void
    {
        $parent = $this->getParentEntity();
        $allowedStudentIds = $this->getAllowedStudentIds((int)$parent->parent_id);

        $bookings = [];
        if (!empty($allowedStudentIds)) {
            $bookings = $this->fetchTable('Bookings')->find()
                ->where(['Bookings.student_id IN' => $allowedStudentIds])
                ->contain([
                    'Students',
                    'Classes' => ['Courses'],
                    'Payments',
                ])
                ->order(['Bookings.booking_date' => 'DESC'])
                ->all();
        }

        $this->set(compact('bookings'));
        $this->set('title', 'Payments');
    }

    public function process(?int $bookingId = null): ?Response
    {
        $parent = $this->getParentEntity();
        $allowedStudentIds = $this->getAllowedStudentIds((int)$parent->parent_id);
        if (empty($allowedStudentIds)) {
            $this->Flash->error(__('No linked children found.'));

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $bookingsTable = $this->fetchTable('Bookings');
        $paymentsTable = $this->fetchTable('Payments');
        $booking = $bookingsTable->find()
            ->contain(['Students', 'Classes' => ['Courses']])
            ->where([
                'Bookings.booking_id' => $bookingId,
                'Bookings.student_id IN' => $allowedStudentIds,
            ])
            ->firstOrFail();

        $existingPayment = $paymentsTable->find()
            ->where([
                'Payments.booking_id' => $bookingId,
                'Payments.payment_status IN' => ['paid', 'pending'],
            ])
            ->first();

        if (
            $existingPayment &&
            $existingPayment->payment_status === 'paid' &&
            in_array($booking->booking_status, ['confirmed', 'completed'], true)
        ) {
            $this->Flash->info(__('Payment already completed for this booking.'));

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        if ($this->request->is('post')) {
            try {
                $result = (new PaymentCheckoutService())->startCheckout($booking, [
                    'success_url' => $this->getRequestBaseUrl()
                        . Router::url(['prefix' => 'Parent', 'controller' => 'Payments', 'action' => 'success', '?' => ['session_id' => '{CHECKOUT_SESSION_ID}']]),
                    'cancel_url' => $this->getRequestBaseUrl()
                        . Router::url(['prefix' => 'Parent', 'controller' => 'Payments', 'action' => 'cancel', $bookingId]),
                    'portal_source' => 'parent_portal',
                    'payer_id' => $this->Authentication->getIdentity()?->get('user_id'),
                ]);

                return $this->handleCheckoutResult($result);
            } catch (RuntimeException $exception) {
                $this->Flash->error(__($exception->getMessage()));
            }
        }

        $stripeReady = $this->isStripeConfigured();
        $demoModeEnabled = $this->isDemoModeEnabled();
        $this->set(compact('booking', 'stripeReady', 'demoModeEnabled'));
        $this->set('title', 'Payment');

        return null;
    }

    private function handleCheckoutResult(array $result): ?Response
    {
        if (($result['kind'] ?? null) === 'redirect') {
            return $this->redirect((string)$result['redirectUrl']);
        }

        if (($result['kind'] ?? null) === 'completed') {
            $payment = $result['payment'];
            $booking = $result['booking'];

            $this->loadComponent('Notification');
            $className = $booking->class_entity?->course?->course_name ?? 'Class';
            $this->Notification->sendPaymentReceipt(
                $this->Authentication->getIdentity()->get('user_id'),
                $className,
                (float)$payment->amount,
            );

            $message = ($result['completed_reason'] ?? null) === 'zero_amount'
                ? __('Free booking confirmed successfully.')
                : __('Demo payment completed successfully! Booking confirmed.');
            $this->Flash->success($message);

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $this->Flash->info(__('Payment already completed for this booking.'));

        return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
    }

    public function success(): ?Response
    {
        $sessionId = $this->request->getQuery('session_id');
        $paymentsTable = $this->fetchTable('Payments');
        $parent = $this->getParentEntity();
        $allowedStudentIds = $this->getAllowedStudentIds((int)$parent->parent_id);

        if (!is_string($sessionId) || $sessionId === '') {
            $this->Flash->error(__('Payment session could not be found.'));

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $payment = $paymentsTable->find()
            ->contain(['Bookings'])
            ->matching('Bookings', function ($query) use ($allowedStudentIds) {
                return $query->where(['Bookings.student_id IN' => $allowedStudentIds]);
            })
            ->where(['Payments.transaction_reference' => $sessionId])
            ->first();

        if (!$payment) {
            $this->Flash->error(__('Payment session not found for your account.'));

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        if ($payment->payment_status === 'paid') {
            $this->Flash->success(__('Payment completed successfully!'));
        } else {
            $this->Flash->info(__('Payment received. Confirmation will appear shortly once Stripe finishes processing the webhook.'));
        }

        return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
    }

    public function cancel(?int $bookingId = null): ?Response
    {
        $parent = $this->getParentEntity();
        $allowedStudentIds = $this->getAllowedStudentIds((int)$parent->parent_id);
        $booking = $this->fetchTable('Bookings')->find()
            ->where([
                'Bookings.booking_id' => $bookingId,
                'Bookings.student_id IN' => $allowedStudentIds,
            ])
            ->firstOrFail();

        try {
            (new BookingCancellationService())->voidPendingPaymentsForBooking((int)$booking->booking_id, [
                'portal_source' => 'parent_portal',
            ]);
        } catch (RuntimeException $exception) {
            $this->Flash->error(__($exception->getMessage()));

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $this->Flash->warning(__('Payment was cancelled. Booking remains pending.'));

        return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
    }
}
