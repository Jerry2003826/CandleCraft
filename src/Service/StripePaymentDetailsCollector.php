<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Log\Log;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Throwable;

class StripePaymentDetailsCollector
{
    /**
     * Collect from checkout session.
     *
     * @param mixed $session Session.
     */
    public function collectFromCheckoutSession(object $session): array
    {
        $details = [
            'stripe_session_id' => $this->stringValue($session->id ?? null),
            'stripe_payment_intent_id' => $this->objectId($session->payment_intent ?? null),
            'stripe_charge_id' => '',
            'stripe_customer_id' => $this->objectId($session->customer ?? null),
            'stripe_invoice_id' => $this->objectId($session->invoice ?? null),
            'stripe_invoice_pdf_url' => $this->stringValue($session->invoice_pdf ?? null),
            'stripe_receipt_url' => $this->stringValue($session->receipt_url ?? null),
            'stripe_payment_method_type' => '',
        ];

        $paymentIntent = $session->payment_intent ?? null;
        if (is_object($paymentIntent)) {
            $this->mergePaymentIntentDetails($details, $paymentIntent);
        } elseif ($details['stripe_payment_intent_id'] !== '' && $this->canRetrieveFromStripe()) {
            try {
                StripeApiBootstrap::configure();
                $paymentIntent = PaymentIntent::retrieve([
                    'id' => $details['stripe_payment_intent_id'],
                    'expand' => ['latest_charge'],
                ]);
                $this->mergePaymentIntentDetails($details, $paymentIntent);
            } catch (Throwable $exception) {
                Log::warning('Unable to retrieve Stripe payment intent details: ' . json_encode([
                    'payment_intent' => $details['stripe_payment_intent_id'],
                    'session_id' => $details['stripe_session_id'],
                    'error' => $exception->getMessage(),
                ]));
            }
        }

        $invoice = $session->invoice ?? null;
        if (is_object($invoice)) {
            $this->mergeInvoiceDetails($details, $invoice);
        } elseif (
            $details['stripe_invoice_id'] !== ''
            && $details['stripe_invoice_pdf_url'] === ''
            && $this->canRetrieveFromStripe()
        ) {
            try {
                StripeApiBootstrap::configure();
                $invoice = Invoice::retrieve($details['stripe_invoice_id']);
                $this->mergeInvoiceDetails($details, $invoice);
            } catch (Throwable $exception) {
                Log::warning('Unable to retrieve Stripe invoice details: ' . json_encode([
                    'invoice' => $details['stripe_invoice_id'],
                    'session_id' => $details['stripe_session_id'],
                    'error' => $exception->getMessage(),
                ]));
            }
        }

        return array_filter($details, static fn(string $value): bool => $value !== '');
    }

    /**
     * Merge payment intent details.
     *
     * @param mixed $details Details.
     * @param mixed $paymentIntent Paymentintent.
     */
    private function mergePaymentIntentDetails(array &$details, object $paymentIntent): void
    {
        $details['stripe_payment_intent_id'] = $this->objectId($paymentIntent);
        $latestCharge = $paymentIntent->latest_charge ?? null;

        if (is_object($latestCharge)) {
            $details['stripe_charge_id'] = $this->objectId($latestCharge);
            $details['stripe_receipt_url'] = $this->stringValue($latestCharge->receipt_url ?? null);
            $details['stripe_payment_method_type'] = $this->paymentMethodTypeFromCharge($latestCharge);
        } elseif (is_string($latestCharge)) {
            $details['stripe_charge_id'] = $latestCharge;
        }
    }

    /**
     * Merge invoice details.
     *
     * @param mixed $details Details.
     * @param mixed $invoice Invoice.
     */
    private function mergeInvoiceDetails(array &$details, object $invoice): void
    {
        $details['stripe_invoice_id'] = $this->objectId($invoice);
        $details['stripe_invoice_pdf_url'] = $this->stringValue($invoice->invoice_pdf ?? null);
    }

    /**
     * Payment method type from charge.
     *
     * @param mixed $charge Charge.
     */
    private function paymentMethodTypeFromCharge(object $charge): string
    {
        $details = $charge->payment_method_details ?? null;
        if (is_object($details) && isset($details->type)) {
            return (string)$details->type;
        }

        return '';
    }

    /**
     * Can retrieve from stripe.
     */
    private function canRetrieveFromStripe(): bool
    {
        return StripeConfiguration::hasUsableSecretKey()
            && (PHP_SAPI !== 'cli' || Configure::read('Payments.retrieve_stripe_details_in_cli') === true);
    }

    /**
     * Object id.
     *
     * @param mixed $value Value.
     */
    private function objectId(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_object($value) && isset($value->id)) {
            return (string)$value->id;
        }

        return '';
    }

    /**
     * String value.
     *
     * @param mixed $value Value.
     */
    private function stringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
