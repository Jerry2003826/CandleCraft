<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\Payments\PaymentDispositionBlockedException;
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
    private StripeCheckoutSessionClassifier $sessionClassifier;

    /**
     * Construct.
     *
     * @param mixed $tableLocator Tablelocator.
     * @param mixed $gateway Gateway.
     * @param mixed $sessionClassifier Sessionclassifier.
     * @return mixed
     */
    public function __construct(
        ?LocatorInterface $tableLocator = null,
        ?StripeCheckoutGatewayInterface $gateway = null,
        ?StripeCheckoutSessionClassifier $sessionClassifier = null,
    ) {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->paymentsTable = $locator->get('Payments');
        $this->gateway = $gateway ?? $this->buildGateway();
        $this->sessionClassifier = $sessionClassifier ?? new StripeCheckoutSessionClassifier();
    }

    /**
     * Void pending payment.
     *
     * @param mixed $payment Payment.
     * @param mixed $reasonCode Reasoncode.
     * @param mixed $context Context.
     */
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
        $payment->notes = PaymentNotes::merge($payment->notes, array_filter([
            'payment_resolution' => $reasonCode,
            'portal_source' => (string)($context['portal_source'] ?? 'unknown'),
            'checkout_session_expired' => $expirationResult['expired'],
            'checkout_session_expiration_skipped' => $expirationResult['skipped'],
            'disposition_context' => $this->buildDispositionContext($context),
        ], static fn($value) => $value !== null));
        $this->paymentsTable->saveOrFail($payment);
    }

    /**
     * Expire stripe checkout session.
     *
     * @param mixed $payment Payment.
     * @param mixed $context Context.
     */
    private function expireStripeCheckoutSession(object $payment, array $context): array
    {
        $transactionReference = (string)($payment->transaction_reference ?? '');

        try {
            $session = $this->gateway->retrieveCheckoutSession($transactionReference);
            $classification = $this->sessionClassifier->classify($session);

            if (($classification['state'] ?? null) === StripeCheckoutSessionClassifier::STATE_PAID) {
                throw new PaymentDispositionBlockedException(
                    'This booking already has a processed payment and requires manual review.',
                );
            }

            if (($classification['state'] ?? null) === StripeCheckoutSessionClassifier::STATE_AWAITING_PAYMENT) {
                throw new PaymentDispositionBlockedException(
                    'Your payment is still processing with Stripe. Please wait a moment and try again shortly.',
                );
            }

            if (($classification['state'] ?? null) === StripeCheckoutSessionClassifier::STATE_EXPIRED) {
                return ['expired' => false, 'skipped' => false];
            }

            if (($classification['state'] ?? null) === StripeCheckoutSessionClassifier::STATE_OPEN_NON_PAID) {
                $this->gateway->expireCheckoutSession($transactionReference);

                return ['expired' => true, 'skipped' => false];
            }

            if (($classification['state'] ?? null) === StripeCheckoutSessionClassifier::STATE_OPEN_UNKNOWN_PAYMENT_STATUS) {
                throw new PaymentDispositionBlockedException(
                    'The current payment session is still open with an unknown payment status. Please try again shortly.',
                );
            }

            return ['expired' => false, 'skipped' => false];
        } catch (PaymentDispositionBlockedException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Failed to expire Stripe checkout session during pending payment disposition: ' . json_encode([
                'payment_id' => (int)($payment->payment_id ?? 0),
                'booking_id' => (int)($payment->booking_id ?? 0),
                'portal_source' => (string)($context['portal_source'] ?? 'unknown'),
                'transaction_reference' => $transactionReference,
                'error' => $exception->getMessage(),
            ]));

            throw new RuntimeException(
                'The checkout session could not be cancelled right now. Please try again.',
            );
        }
    }

    /**
     * Looks like stripe checkout session.
     *
     * @param mixed $transactionReference Transactionreference.
     */
    private function looksLikeStripeCheckoutSession(string $transactionReference): bool
    {
        return str_starts_with($transactionReference, 'cs_');
    }

    /**
     * Is stripe configured.
     */
    private function isStripeConfigured(): bool
    {
        return StripeConfiguration::canManageCheckoutSessions();
    }

    /**
     * Build disposition context.
     *
     * @param mixed $context Context.
     */
    private function buildDispositionContext(array $context): ?array
    {
        return $context === [] ? null : $context;
    }

    /**
     * Build gateway.
     */
    private function buildGateway(): StripeCheckoutGatewayInterface
    {
        $gatewayClass = (string)Configure::read('Payments.gateway_class', StripeCheckoutGateway::class);
        $gateway = new $gatewayClass();

        if (!$gateway instanceof StripeCheckoutGatewayInterface) {
            throw new RuntimeException(sprintf(
                'Configured payment gateway "%s" must implement %s.',
                $gatewayClass,
                StripeCheckoutGatewayInterface::class,
            ));
        }

        return $gateway;
    }
}
