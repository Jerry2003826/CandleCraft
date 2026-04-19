<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorInterface;
use RuntimeException;
use Throwable;

class PendingPaymentDispositionService
{
    private object $paymentsTable;
    private StripeCheckoutGatewayInterface $gateway;

    public function __construct(
        ?LocatorInterface $tableLocator = null,
        ?StripeCheckoutGatewayInterface $gateway = null,
    ) {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->paymentsTable = $locator->get('Payments');
        $this->gateway = $gateway ?? $this->buildGateway();
    }

    public function voidPendingPayment(object $payment, string $reasonCode, array $context = []): void
    {
        if ((string)($payment->payment_status ?? '') !== 'pending') {
            return;
        }

        $transactionReference = (string)($payment->transaction_reference ?? '');
        $expirationResult = [
            'expired' => false,
            'skipped' => true,
        ];

        if ($this->looksLikeStripeCheckoutSession($transactionReference) && $this->isStripeConfigured()) {
            $expirationResult = $this->expireStripeCheckoutSession($payment, $context);
        }

        $payment->payment_status = 'voided';
        $payment->notes = PaymentNotes::merge($payment->notes, array_merge([
            'payment_resolution' => $reasonCode,
            'portal_source' => (string)($context['portal_source'] ?? 'unknown'),
            'checkout_session_expired' => $expirationResult['expired'],
            'checkout_session_expiration_skipped' => $expirationResult['skipped'],
        ], $context));
        $this->paymentsTable->saveOrFail($payment);
    }

    private function expireStripeCheckoutSession(object $payment, array $context): array
    {
        $transactionReference = (string)($payment->transaction_reference ?? '');

        try {
            $session = $this->gateway->retrieveCheckoutSession($transactionReference);
            $paymentStatus = strtolower((string)($session->payment_status ?? ''));
            $sessionStatus = strtolower((string)($session->status ?? ''));

            if ($paymentStatus === 'paid' || $sessionStatus === 'complete') {
                throw new RuntimeException('This booking already has a processed payment and requires manual review.');
            }

            if ($sessionStatus === 'expired') {
                return ['expired' => false, 'skipped' => false];
            }

            if ($sessionStatus === 'open' && $paymentStatus === 'unpaid') {
                $this->gateway->expireCheckoutSession($transactionReference);

                return ['expired' => true, 'skipped' => false];
            }

            return ['expired' => false, 'skipped' => false];
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Failed to expire Stripe checkout session during pending payment disposition: ' . json_encode([
                'payment_id' => (int)($payment->payment_id ?? 0),
                'booking_id' => (int)($payment->booking_id ?? 0),
                'portal_source' => (string)($context['portal_source'] ?? 'unknown'),
                'transaction_reference' => $transactionReference,
                'error' => $exception->getMessage(),
            ]));

            throw new RuntimeException('The checkout session could not be cancelled right now. Please try again.');
        }
    }

    private function looksLikeStripeCheckoutSession(string $transactionReference): bool
    {
        return str_starts_with($transactionReference, 'cs_');
    }

    private function isStripeConfigured(): bool
    {
        $key = (string)Configure::read('Stripe.secret_key');

        return $key !== '' && $key !== 'sk_test_placeholder';
    }

    private function buildGateway(): StripeCheckoutGatewayInterface
    {
        $gatewayClass = (string)Configure::read('Payments.gateway_class', StripeCheckoutGateway::class);
        $gateway = new $gatewayClass();

        if (!$gateway instanceof StripeCheckoutGatewayInterface) {
            throw new RuntimeException(sprintf(
                'Configured payment gateway "%s" must implement %s.',
                $gatewayClass,
                StripeCheckoutGatewayInterface::class
            ));
        }

        return $gateway;
    }
}
